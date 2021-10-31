<?php
// engine_methods interface in shared_helper.php
include_once dirname(__FILE__) . '/shared_helper.php';

class PHPTimeSeries implements engine_methods
{
    private $dir = "/var/lib/phptimeseries/";
    public $log;
    
    private $writebuffer = array();

    /**
     * Constructor.
     *
     * @api
    */
    public function __construct($settings)
    {
        if (isset($settings['datadir'])) $this->dir = $settings['datadir'];
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
        unlink($this->dir."feed_$id.MYD");
    }
    
    public function trim($id,$start_time) {
        $id = (int) $id; 
        if (!$npoints = $this->get_npoints($id)) return array('success'=>false,'message'=>'Empty data file, nothing to trim.');
        
        if (!$fh = $this->open($id,'rb')) {
            return array('success'=>false,'message'=>'Error opening data file');
        }

        $dp = $this->binarysearch($fh,$start_time,$npoints);
        if ($dp==-1) return array('success'=>false,'message'=>'Invalid start time');
        
        fseek($fh,$dp[0]*9);
        if (!$binary_data = @fread($fh,$npoints-$dp[0])) {
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

    /**
     * Updates a data point in the feed
     *
     * @param integer $id The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function update($id,$time,$value)
    {
      return $this->post($id,$time,$value);
    }

    /**
     * Get array with last time and value from a feed
     *
     * @param integer $id The id of the feed
    */
    public function lastvalue($id)
    {
        $id = (int) $id;

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
                    // returns nearest datapoint that is >= search time
                    $result = $this->binarysearch($fh,$time,$npoints);
                    if ($result!=-1) {
                        // check that datapoint is within interval
                        if ($result[1]<$div_end) {
                            $value = $result[2];
                        }
                    }
                } else {
                    $sum = 0;
                    $n = 0;

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

    public function get_data($id,$start,$end,$interval,$skipmissing,$limitinterval) {
        return $this->get_data_combined($id,$start,$end,$interval,0,"UTC","unix",false,$skipmissing,$limitinterval);
    }
    
    public function get_data_DMY($id,$start,$end,$mode,$timezone) {
        return $this->get_data_combined($id,$start,$end,$mode,0,$timezone,"unix",false,0,0);
    }
    
    public function get_average($id,$start,$end,$interval) {
        return $this->get_data_combined($id,$start,$end,$interval,1,"UTC","unix",false,0,0);  
    }
    
    public function get_average_DMY($id,$start,$end,$mode,$timezone) {
        return $this->get_data_combined($id,$start,$end,$mode,1,$timezone,"unix",false,0,0);
    }
    
    public function csv_export($id,$start,$end,$interval,$timezone) {
        if ($timezone==false) {
            $timeformat = "unix";
            $timezone = "UTC";
        } else {
            $timeformat = "excel";    
        }
        $this->get_data_combined($id,$start*1000,$end*1000,$interval,0,$timezone,$timeformat,true,0,0);
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

    // Insert data in post write buffer, parameters like post()
    public function post_bulk_prepare($id,$timestamp,$value,$arg=null)
    {
        $id = (int) $id;
        $timestamp = (int) $timestamp;
        $value = (float) $value;

        $filename = "feed_$id.MYD";
        $npoints = $this->get_npoints($id);

        if (!isset($this->writebuffer[$id])) {
            $this->writebuffer[$id] = "";
        }

        // If there is data then read last value
        if ($npoints>=1) {
            static $lastvalue_static_cache = array(); // Array to hold the cache
            if (!isset($lastvalue_static_cache[$id])) { // Not set, cache it from file data
                $lastvalue_static_cache[$id] = $this->lastvalue($id);
            }           
            if ($timestamp<=$lastvalue_static_cache[$id]['time']) {
                // if data is in past, its not supported, could call update here to fix on file before continuing
                // but really this should not happen for past data has process_feed_buffer uses update for that.
                $this->log->warn("post_bulk_prepare() data in past, nothing saved.  feedid=$id timestamp=$timestamp last=".$lastvalue_static_cache[$id]['time']." value=$value");
                return $value;
            }
        }

        $this->writebuffer[$id] .= pack("CIf",249,$timestamp,$value);
        $lastvalue_static_cache[$id] = array('time'=>$timestamp,'value'=>$value); // Set static cache last value
        return $value;
    }

    // Saves post buffer to engine in bulk
    // Writing data in larger blocks saves reduces disk write load
    public function post_bulk_save()
    {
        $byteswritten = 0;
        foreach ($this->writebuffer as $id=>$data)
        {
            $filename = $this->dir."feed_$id.MYD";
            // Auto-correction if something happens to the datafile, it gets partitally written to
            // this will correct the file size to always be an integer number of 4 bytes.
            clearstatcache($filename);
            if (@filesize($filename)%9 != 0) {
                $npoints = floor(filesize($filename)/9.0);
                $fh = fopen($filename,"c");
                fseek($fh,$npoints*9.0);
                fwrite($fh,$data);
                fclose($fh);
                print "PHPTIMESERIES: FIXED DATAFILE WITH INCORRECT LENGHT\n";
                $this->log->warn("post_bulk_save() FIXED DATAFILE WITH INCORRECT LENGHT '$filename'");
            }
            else
            {
                $fh = fopen($filename,"ab");
                fwrite($fh,$data);
                fclose($fh);
            }
            
            $byteswritten += strlen($data);
        }
        $this->writebuffer = array(); // Clear writebuffer

        return $byteswritten;
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
}
