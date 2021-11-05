<?php
// engine_methods interface in shared_helper.php
include_once dirname(__FILE__) . '/shared_helper.php';

class PHPTimeSeries implements engine_methods
{
    private $dir = "/var/lib/phptimeseries/";
    public $log;
    private $redis = false;
    private $buffer_enabled = false;
    private $buffer_period = 300;  // 5 minutes
    
    /**
     * Constructor.
     *
     * @api
    */
    public function __construct($settings,$redis)
    {
        $this->redis = $redis;
        if ($this->redis) $this->buffer_enabled = true;
        
        if (isset($settings['datadir'])) $this->dir = $settings['datadir'];
        if (isset($settings['buffer'])) $this->buffer_period = (int) $settings['buffer'];
        if ($this->buffer_period<=0) {
            $this->buffer_period = 0;
            $this->buffer_enabled = false;
        }
        
        $this->log = new EmonLogger(__FILE__);
    }
    
    /**
     * Create feed
     *
     * @param integer $id The id of the feed to be created
     * @param array $options for the engine
    */
    public function create($id,$options)
    {
        $id = (int) $id;
        if (!$fh = $this->open($id,"a")) return false;
        fclose($fh);
        return true;
    }

    /**
     * Delete feed
     *
     * @param integer $id The id of the feed to be created
    */
    public function delete($id)
    {
        $id = (int) $id;
        if ($this->buffer_enabled) $this->buffer_clear($id);
        
        unlink($this->dir."feed_$id.MYD");
    }
    
    public function trim($id,$start_time) {
        $id = (int) $id; 
        // Save local buffer before trim
        if ($this->buffer_enabled) $this->buffer_save($id);
        if (!$npoints = $this->get_npoints($id)) return array('success'=>false,'message'=>'Empty data file, nothing to trim.');
        
        if (!$fh = $this->open($id,'rb')) {
            return array('success'=>false,'message'=>'Error opening data file');
        }

        $dp = $this->binarysearch($fh,$start_time,$npoints);
        if ($dp==-1) return array('success'=>false,'message'=>'Invalid start time');
        
        fseek($fh,$dp[0]*9);
        if (!$binary_data = @fread($fh,($npoints-$dp[0])*9)) {
            $this->log->error("Error reading $datFileName");
            return array('success'=>false,'message'=>'Error reading data file');
        }
        fclose($fh);

        if (!$fh = $this->open($id,'wb')) {
            return array('success'=>false,'message'=>'Error opening data file');
        }
        $writtenBytes = fwrite($fh,$binary_data);
        fclose($fh);

        $this->log->info(".data file trimmed to $writtenBytes bytes");
        return array('success'=>true,'message'=>"$writtenBytes bytes written");
    }
    
    public function clear($id){
    
        $id = (int) $id;
        if ($this->buffer_enabled) $this->buffer_clear($id);
        
        if (!$fh = $this->open($id,"r+")) return false;
        ftruncate($fh, 0);
        fclose($fh);

        $this->log->info("Feed $id datapoints deleted");
        return array('success'=>true,'message'=>"Feed cleared successfully");
    }

    /**
     * Gets engine metadata
     *
     * @param integer $id The id of the feed to be created
    */
    public function get_meta($id)
    {
        $id = (int) $id;
        
        $meta = new stdClass();
        $meta->id = $id;
        
        $meta->start_time = 0;
        $meta->end_time = 0;
        $meta->interval = 1;
        
        if (!$fh = $this->open($id,'rb')) return false;

        if ($npoints = $this->get_npoints($id)) {
            // Start time
            $dp = unpack("x/Itime/fvalue",fread($fh,9));
            $meta->start_time = $dp['time'];
            // End time
            fseek($fh,($npoints-1)*9);
            $dp = unpack("x/Itime/fvalue",fread($fh,9));            
            $meta->end_time = $dp['time'];
            $meta->end_value = $dp['value'];
            
            if ($meta->end_time>$meta->start_time) {
                $meta->interval = ($meta->end_time - $meta->start_time) / ($npoints-1);
            }
        }
        fclose($fh);
        return $meta;
    }

    /**
     * Returns engine occupied size in bytes
     *
     * @param integer $id The id of the feed to be created
    */
    public function get_feed_size($id)
    {
        $id = (int) $id;
        clearstatcache($this->dir."feed_$id.MYD");
        return filesize($this->dir."feed_$id.MYD");
    }

    private function get_npoints($id) {
        clearstatcache($this->dir."feed_$id.MYD");
        return floor(@filesize($this->dir."feed_$id.MYD")/9.0);
    }
    
    /**
     * Adds or updates a datapoint
     *
     * @param integer $id The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @param array $arg optional padding mode argument
    */
    public function post($id,$time,$value,$arg=null)
    {
        $id = (int) $id;
        $time = (int) $time;
        $value = (float) $value;
        
        if ($this->buffer_enabled) {
            return $this->post_buffer($id,$time,$value);
        } else {
            return $this->post_direct($id,$time,$value);  
        }
    }

    private function post_direct($id,$time,$value)
    {
        if (!$fh = $this->open($id,'c+')) return false;
        
        // Check if datapoint is in the past 
        if ($npoints = $this->get_npoints($id)) {
            fseek($fh,($npoints-1)*9);
            $dp = @unpack("x/Itime/fvalue",fread($fh,9));

            if ($time==$dp['time']) {
                // if last dp we know position
                fseek($fh,($npoints-1)*9);
                fwrite($fh,pack("CIf",249,$time,$value));
                fclose($fh);
                return $value;
                                
            } else if ($time<$dp['time']) {
                // if older: search for datapoint at specified time
                $dp = $this->binarysearch($fh,$time,$npoints,true);
                if ($dp!=-1) {
                    // update existing datapoint
                    fseek($fh, $dp[0]*9);
                    fwrite($fh,pack("CIf",249,$time,$value));                
                }
                fclose($fh);
                return $value;
            }
        }
        // Otherwise append a new value
        fseek($fh, $npoints*9);
        fwrite($fh,pack("CIf",249,$time,$value));
        fclose($fh);
        return $value;
    }
    
    private function post_buffer($id,$time,$value)
    { 

        $npoints = $this->get_npoints($id);
        $buffer_start_time = $this->buffer_get_start_time($id);
        $buffer_end_time = false;
        // Update datapoint if in the buffer 
        if ($buffer_start_time && $time>=$buffer_start_time) {
            $buffer_end_time = $this->buffer_get_end_time($id);
            if ($time<=$buffer_end_time) {
                // Replace with updated value
                $this->buffer_update($id,$time,$value);
                return $value;
            }
        } else if ($npoints) {
            // Update datapoint if in the persisted file
            if (!$fh = $this->open($id,'c+')) return false;
            $dp = $this->binarysearch($fh,$time,$npoints,true);
            if ($dp!=-1) {
                if ($dp[2]!=$value) {
                    // update existing datapoint
                    fseek($fh, $dp[0]*9);
                    fwrite($fh,pack("CIf",249,$time,$value));
                }
                fclose($fh);
                return $value;
            }
        }
        $this->buffer_add($id,$time,$value);
        
        // Auto save after set period
        if (($buffer_end_time-$buffer_start_time)>=$this->buffer_period) {
             $this->buffer_save($id);
        }
        
        return $value;
    }

    /**
     * Get array with last time and value from a feed
     *
     * @param integer $id The id of the feed
    */
    public function lastvalue($id)
    {
        $id = (int) $id;
        
        if ($this->buffer_enabled) {
            if ($dp = $this->buffer_last_value($id)) return $dp;
        }

        if (!$npoints = $this->get_npoints($id)) return false;
        if (!$fh = $this->open($id,'rb')) return false;
        
        fseek($fh,($npoints-1)*9);
        $dp = unpack("x/Itime/fvalue",fread($fh,9));
        fclose($fh);
        return array('time'=>$dp['time'], 'value'=>$dp['value']);
    }

    /**
     * Get feed value at specified time
     *
     * @param integer $id The id of the feed
     * @param integer $time The unix timestamp of the data point, in seconds
    */
    public function get_value($id,$time)
    {
        $id = (int) $id;
        $time = (int) $time;

        if ($this->buffer_enabled) {
            $buffer_start_time = $this->buffer_get_start_time($id);
            if ($buffer_start_time!==false && $time>=$buffer_start_time) {
                if ($value = $this->buffer_get_value($id,$time,"+inf")) {
                    return $value;
                } else {
                    return null;
                }
            }
        }

        // Fetch value from file
        if (!$fh = $this->open($id,'rb')) return false;   
        $value = null;         
        if ($npoints = $this->get_npoints($id)) {
            $dp = $this->binarysearch($fh,$time,$npoints,false);
            if ($dp!=-1) $value = $dp[2];
        }
        fclose($fh);
        return $value;
    }
    
    /**
     * @param integer $id The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $interval output data point interval
     * @param integer $average enabled/disable averaging
     * @param string $timezone a name for a php timezone eg. "Europe/London"
     * @param string $timeformat csv datetime format e.g: unix timestamp, excel, iso8601
     * @param integer $csv pipe output as csv
     * @param integer $skipmissing skip null datapoints
     * @param integer $limitinterval limit interval to feed interval
     * @return void or array
     */
    public function get_data_combined($id,$start,$end,$interval,$average=0,$timezone="UTC",$timeformat="unix",$csv=false,$skipmissing=0,$limitinterval=1)
    {
        $id = (int) $id;
        $skipmissing = (int) $skipmissing;
        $limitinterval = (int) $limitinterval;
        $start = intval($start/1000);
        $end = intval($end/1000);
        
        global $settings;
        if ($timezone===0) $timezone = "UTC";
        
        if ($csv) {
            require_once "Modules/feed/engine/shared_helper.php";
            $helperclass = new SharedHelper($settings['feed']);
            $helperclass->set_time_format($timezone,$timeformat);
        }

        if ($end<=$start) return array('success'=>false, 'message'=>"request end time before start time");
        
        // The first section here deals with the timezone aligned interval codes
        // the start time is modified to align to the nearest day, week, month or year
        // later the while loop is advanced by the value in the $modify string
        // all using php DateTime aligned to user/feed timezone
        if (in_array($interval,array("weekly","daily","monthly","annual"))) {
            $fixed_interval = false;
            // align to day, month, year
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone($timezone));
            $date->setTimestamp($start);
            $date->modify("midnight");
            $modify = "+1 day";
            $interval_check = 3600*24;
            if ($interval=="weekly") {
                $date->modify("this monday");
                $modify = "+1 week";
                $interval_check = 3600*24*7;
            } else if ($interval=="monthly") {
                $date->modify("first day of this month");
                $modify = "+1 month";
                $interval_check = 3600*24*30;
            } else if ($interval=="annual") {
                $date->modify("first day of this year");
                $modify = "+1 year";
                $interval_check = 3600*24*365;
            }
            $time = $date->getTimestamp();
        } elseif ($interval=="original") {
            $time = $start;
        } else {
            // If interval codes are not specified then we advanced by a fixed numeric interval 
            $fixed_interval = true;
            $interval = (int) $interval;
            if ($interval<1) $interval = 1;
            $time = $start;
            $interval_check = $interval;
        }
        
        if ($csv) {
            $helperclass->csv_header($id);
        } else {
            $data = array();
        }

        $npoints = $this->get_npoints($id);
        
        if (!$fh = $this->open($id,'rb')) return false;
        
        // Get starting position
        if ($average) {
            $start_dp = $this->binarysearch($fh,$time,$npoints);
            if ($start_dp==-1) {
                $start_dp = array($npoints);
            }
        }
        
        $buffer_start_time = $this->buffer_get_start_time($id);
        
        if ($interval!="original") {
            while($time<=$end)
            {               
                $div_start = $time;
                
                // Advance position
                if ($fixed_interval) {
                    $div_end = $time + $interval;
                } else {
                    $date->modify($modify);
                    $div_end = $date->getTimestamp();
                }
                
                $value = null;
                
                if (!$average) {
                    if ($buffer_start_time!==false && $time>=$buffer_start_time) {
                        // Find closest value to $time within this interval
                        $dp = $this->redis->zRangeByScore("phptimeseries:buffer:$id",$time,($div_end-1), array('limit' => array(0,1)));
                        if (count($dp)>0) {
                            $f = explode("|",$dp[0]);    
                            $value = (float) $f[1];
                        }
                    } else {
                        // returns nearest datapoint that is >= search time
                        $result = $this->binarysearch($fh,$time,$npoints);
                        if ($result!=-1) {
                            // check that datapoint is within interval
                            if ($result[1]<$div_end) {
                                $value = $result[2];
                            }
                        }
                    }
                } else {
                    $sum = 0;
                    $n = 0;

                    if ($buffer_start_time===false || $div_start<$buffer_start_time) {
                    
                        $next_start_dp = $this->binarysearch($fh,$div_end,$npoints);
                        
                    
                        // if end_dp is -1 it means we have a search time that
                        // is greater than the last datapoint in the series
                        // if this is the case we read up to the last datapoint
                        if ($next_start_dp==-1) {
                            $next_start_dp = array($npoints);
                        } else if ($next_start_dp[1]>$div_end) {
                            // withing valid data range end_dp should always be
                            // greater or equall to end. If it is greater then 
                            // we need to limit the range to the previous datapoint
                            $next_start_dp[0] -= 1;
                            // if end_dp is now less than 0 it means the end search
                            // time is before the start of our timeseries
                            if ($next_start_dp[0]<0) {
                                // return null;
                                $next_start_dp[0] = 0;
                            }       
                        }      
                        
                        $len = $next_start_dp[0]-$start_dp[0];
                        if ($len) {
                            fseek($fh,$start_dp[0]*9);
                            $s = fread($fh,$len*9);
                            $s2 = "";
                            for ($x=0; $x<$len; $x++) {
                                $s2 .= substr($s,($x*9)+5,4);
                            }
                            $tmp = unpack("f*",$s2);
                            for ($x=0; $x<count($tmp); $x++) {
                                if (!is_nan($tmp[$x+1])) {
                                    // print $tmp[$x+1]."\n";
                                    $sum += $tmp[$x+1];
                                    $n++;
                                }
                            }
                        }
                        
                        $start_dp[0] = $next_start_dp[0];    
                    }
                    
                    if ($buffer_start_time!==false && $div_end>=$buffer_start_time) {

                        $dps = $this->redis->zRangeByScore("phptimeseries:buffer:$id",$time,$div_end-1);
                        foreach ($dps as $i=>$v) {
                            // print $v." B\n";
                            $f = explode("|",$v);    
                            $value = (float) $f[1];
                            $sum += $value;
                            $n ++;
                        }
                    } 
                    
                    if ($n>0) $value = $sum / $n;
                
                }
                
                if ($value!==null || $skipmissing===0) {
                    if ($csv) { 
                        $helperclass->csv_write($div_start,$value);
                    } else {
                        $data[] = array($div_start*1000,$value);
                    }
                }
                
                $time = $div_end;
            }
        } else {
            // Export original data
            /**
            $n = 0;
            fseek($fh,0);
            while($time<=$end && $n<$npoints) {
                $dp = unpack("x/Itime/fvalue",fread($fh,9));
                $time = $dp['time']; $value = $dp['value'];
                
                if ($csv) { 
                    $helperclass->csv_write($time,$value);
                } else {
                    $data[] = array($time*1000,$value);
                }
                $n++;
            }*/
        }
        
        fclose($fh);
        
        if ($csv) {
            $helperclass->csv_close();
            exit;
        } else {
            return $data;
        }
    }

    public function export($id,$start)
    {
        $id = (int) $id;
        $start = (int) $start;

        $feedname = "feed_$id.MYD";

        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename={$feedname}");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $fh = @fopen( 'php://output', 'w' );

        if (!$primary = $this->open($id,'rb')) return false;
        $primarysize = $this->get_npoints($id)*9;
        
        $localsize = $start;
        $localsize = intval($localsize / 9) * 9;
        if ($localsize<0) $localsize = 0;

        fseek($primary,$localsize);
        $left_to_read = $primarysize - $localsize;
        if ($left_to_read>0){
            do
            {
                if ($left_to_read>8192) $readsize = 8192; else $readsize = $left_to_read;
                $left_to_read -= $readsize;

                $data = fread($primary,$readsize);
                fwrite($fh,$data);
            }
            while ($left_to_read>0);
        }
        fclose($primary);
        fclose($fh);
        exit;
    }

    // returns nearest datapoint that is >= search time
    private function binarysearch($fh,$time,$npoints,$exact=false)
    {
        // Binary search works by finding the file midpoint and then asking if
        // the datapoint we want is in the first half or the second half
        // it then finds the mid point of the half it was in and asks which half
        // of this new range its in, until it narrows down on the value.
        // This approach usuall finds the datapoint you want in around 20
        // itterations compared to the brute force method which may need to
        // go through the whole file that may be millions of lines to find a
        // datapoint.

        // 30 here is our max number of itterations
        // the position should usually be found within
        // 20 itterations.
        
        if ($npoints==0) return -1;
        $start = -1; $end = $npoints-1;
        
        for ($i=0; $i<30; $i++)
        {
            $mid = $start + ceil(($end-$start)*0.5);
            if ($mid<0) {
                print "ERROR: mid<0 should not happen\n";
            }

            fseek($fh,$mid*9);
            $dp = @unpack("x/Itime/fvalue",fread($fh,9));

            if ($dp['time']==$time) {
                return array($mid,$dp['time'],$dp['value']);
            }
            
            if (($end-$start)==1) {
                if (!$exact && $dp['time']>$time) {
                    return array($mid,$dp['time'],$dp['value']);
                }
                return -1;
            }
            
            if ($time>$dp['time']) $start = $mid; else $end = $mid;
        }
        return -1;
    }

    /**
     * Abstracted open method
     *
     */
    private function open($id,$mode) {
    
        $filename = $this->dir."feed_$id.MYD";
        if (!$fh = @fopen($filename,$mode)) {
            $error = error_get_last();
            $this->log->warn("PHPTimeSeries: could not open $filename ".$error['message']);
            return false;
        }

        if (!flock($fh, LOCK_EX)) {
            $this->log->warn("PHPTimeSeries: $filename locked by another process");
            fclose($fh);
            return false;
        }
        
        return $fh;
    }
    
    /**
     * Used for testing
     *
     */
    public function print_all($id) {  

        $sum = 0;
        $n = 0;
                
        if ($fh = $this->open($id,"rb")) {        
            for ($n=0; $n<$this->get_npoints($id); $n++) {
                $dp = @unpack("x/Itime/fvalue",fread($fh,9));
                print $n." ".$dp['time']." ".$dp['value']."\n";
                $sum += $dp['value'];
            }
            fclose($fh);
        }
        
        if ($this->buffer_enabled) {
            $data = $this->redis->zRange("phptimeseries:buffer:$id",0,-1,true);
            foreach ($data as $value=>$time) {
                $f = explode("|",$value);    
                $value = (float) $f[1];
                print $n." ".$time." ".$value." B\n";
                $sum += $value;
                $n++;
            }
        }
        
        if ($n>0) print "average: ".($sum/$n)."\n";
    }
    
    /**
     * Buffer methods
     *
     */
    
    private function buffer_add($id,$time,$value) {
        $this->redis->zAdd("phptimeseries:buffer:$id",(int)$time,dechex((int)$time)."|".$value);
    }
    
    private function buffer_clear($id) {
        $this->redis->del("phptimeseries:buffer:$id");
    }
    
    private function buffer_get_start_time($id) {
        $dp = $this->redis->zRange("phptimeseries:buffer:$id",0,0,true);
        if (count($dp)) {
            return $dp[key($dp)];
        }
        return false;
    }

    private function buffer_get_end_time($id) {
        $dp = $this->redis->zRange("phptimeseries:buffer:$id",-1,-1,true);
        if (count($dp)) {
            return $dp[key($dp)];
        }
        return false;
    }
    
    private function buffer_last_value($id) {
        $dp = $this->redis->zRange("phptimeseries:buffer:$id",-1,-1, array('withscores' => true));
        if (!count($dp)) return false;
        
        $key = key($dp);
        $time = $dp[$key];
        $f = explode("|",$key);    
        $value = (float) $f[1];
        return array('time'=>$time, 'value'=>$value);
    }
    
    private function buffer_get_value($id,$start,$end) {
        // Fetch value from buffer
        $dp = $this->redis->zRangeByScore("phptimeseries:buffer:$id",$start,$end, array('limit' => array(0,1)));
        if (count($dp)>0) {
            $f = explode("|",$dp[0]);    
            return (float) $f[1];
        }
        return false;
    }
    
    private function buffer_update($id,$time,$value) {
        if ($this->redis->zRemRangeByScore("phptimeseries:buffer:$id",$time,$time)) {
            $this->redis->zAdd("phptimeseries:buffer:$id",(int)$time,dechex((int)$time)."|".$value);
        }
    }
    
    public function buffer_save($id) {
    
        $data = $this->redis->zRange("phptimeseries:buffer:$id",0,-1,true);
        if (count($data)) {
            $npoints = $this->get_npoints($id);

            $buffer = "";
            foreach ($data as $value=>$time) {
                $f = explode("|",$value);    
                $value = (float) $f[1];
                $buffer .= pack("CIf",249,$time,$value);    
            }
            
            if (!$fh = $this->open($id,'c+')) return false;
            fseek($fh, $npoints*9);
            fwrite($fh,$buffer);
            fclose($fh);  
        }
        $this->redis->zremrangebyrank("phptimeseries:buffer:$id",0,-1);
    }
}
