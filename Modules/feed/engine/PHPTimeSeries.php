<?php
// engine_methods interface in shared_helper.php
include_once dirname(__FILE__) . '/shared_helper.php';

class PHPTimeSeries implements engine_methods
{
    private $dir = "/var/lib/phptimeseries/";
    public $log;
    private $fh = array();
    private $npoints = array();
    private $pos = array();
    
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

// #### \/ Below are required methods
    /**
     * Create feed
     *
     * @param integer $id The id of the feed to be created
     * @param array $options for the engine
    */
    public function create($id,$options)
    {
        if (!$this->open($id,"a")) return false;
        $this->close($id);
        return true;
    }

    /**
     * Delete feed
     *
     * @param integer $id The id of the feed to be created
    */
    public function delete($id)
    {
        unlink($this->dir."feed_$id.MYD");
    }

    /**
     * Gets engine metadata
     *
     * @param integer $id The id of the feed to be created
    */
    public function get_meta($id)
    {
        $meta = new stdClass();
        $meta->id = $id;
        
        $meta->start_time = 0;
        $meta->end_time = 0;
        $meta->interval = 1;
        
        $this->open($id,'rb');
        if ($this->npoints[$id]) {
            // Start time
            $array = $this->read($id,0);
            $meta->start_time = $array['time'];
            // End time
            $array = $this->read($id,$this->npoints[$id]-1);
            $meta->end_time = $array['time'];
            $meta->end_value = $array['value'];
            
            if ($meta->end_time>$meta->start_time) {
                $meta->interval = ($meta->end_time - $meta->start_time) / ($this->npoints[$id]-1);
            }
        }
        $this->close($id);
        return $meta;
    }

    /**
     * Returns engine occupied size in bytes
     *
     * @param integer $id The id of the feed to be created
    */
    public function get_feed_size($id)
    {
        return filesize($this->dir."feed_$id.MYD");
    }

    // POST OR UPDATE
    //
    // - fix if filesize is incorrect (not a multiple of 9)
    // - append if file is empty
    // - append if datapoint is in the future
    // - update if datapoint is older than last datapoint value
    /**
     * Adds a data point to the feed
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
        
        if (!$this->open($id,'c+')) return false;
        $this->post_open($id,$time,$value);
        $this->close($id);
        
        return $value;
    }
    
    // Pre opened
    public function post_open($id,$time,$value)
    {
        // Check if datapoint is in the past 
        if ($this->npoints[$id]) {
            $dp = $this->read($id,$this->npoints[$id]-1);
            if ($time<=$dp['time']) {
                // if not: search for existing datapoint at time
                $pos = $this->binarysearch($id,$time,true);
                if ($pos!=-1) {
                    // update existing datapoint
                    $this->write($id,$pos,$time,$value);
                }
                $this->close($id);
                return $value;
            }
        }

        // Otherwise append a new value
        $this->write($id,$this->npoints[$id],$time,$value);
        $this->npoints[$id]++;
    }    

    /**
     * Get array with last time and value from a feed
     *
     * @param integer $id The id of the feed
    */
    public function lastvalue($id)
    {
        $id = (int) $id;
        $dp = false;
        
        if (!$this->open($id,'rb')) return false;
        if ($this->npoints[$id]) {
            $dp = $this->read($id,$this->npoints[$id]-1);
        }
        $this->close($id);
        return $dp;
    }

    public function get_value($id,$time)
    {
        $id = (int) $id;
        $time = (int) $time;
        $value = false;
         
        if (!$this->open($id,'rb')) return false;
        if ($this->npoints[$id]) {
            $pos = $this->binarysearch($id,$time);
            if ($pos==-1) return false;
            $dp = $this->read($id,$pos);
            $value = $dp['value'];
        }
        $this->close($id);
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
        
        if (!$this->open($id,'rb')) return false;
        
        // Get starting position
        $pos_start = $this->binarysearch($id,$time);
        
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
                
                // find end position (which is also the next start position)
                // pos_end - pos_start = number of datapoints to average
                $pos_end = $this->binarysearch($id,$div_end);
                $dp_to_read = ($pos_end-$pos_start);
                
                $value = null;
                
                if ($average && $pos_start!=-1 && $dp_to_read>1) {
                    $value = $this->sum_range($id,$pos_start,$dp_to_read);
                } else {
                    $dp = $this->read($id,$pos_start);
                    if (abs($dp['time']-$div_start)<$interval_check) {
                        $value = $dp['value'];
                    }
                    if (is_nan($value)) $value = null;
                }
                
                if ($value!==null || $skipmissing===0) {
                    if ($csv) { 
                        $helperclass->csv_write($div_start,$value);
                    } else {
                        $data[] = array($div_start*1000,$value);
                    }
                }
                
                $time = $div_end;
                $pos_start = $pos_end;
            }
        } else {
            // Export original data
            $n = 0;
            fseek($this->fh[$id],0);
            while($time<=$end && $n<$this->npoints[$id]) {
                $dp = unpack("x/Itime/fvalue",fread($this->fh[$id],9));
                $time = $dp['time']; $value = $dp['value'];
                
                if ($csv) { 
                    $helperclass->csv_write($time,$value);
                } else {
                    $data[] = array($time*1000,$value);
                }
                $n++;
            }
        }
        
        $this->close($id);
        
        if ($csv) {
            $helperclass->csv_close();
            exit;
        } else {
            return $data;
        }
    }
    
    // The following were all previously implemented seperatly
    // and are now replaced by the combined implementation above 
    // to ensure consistent results and avoid code duplication
    // mapping of original function calls are left in here for
    // compatibility with rest of emoncms application
    public function csv_export($id,$start,$end,$interval,$average,$timezone,$timeformat) {
        $this->get_data_combined($id,$start*1000,$end*1000,$interval,$average,$timezone,$timeformat,true);
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

        $primaryfeedname = $this->dir.$feedname;
        $primary = fopen($primaryfeedname, 'rb');
        $primarysize = filesize($primaryfeedname);

        //$localsize = intval((($start - $meta['start']) / $meta['interval']) * 4);

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

// #### /\ Above are required methods
    
// #### \/ Below engine public specific methods

// #### \/ Bellow are engine private methods
    
    private function binarysearch($id,$time,$exact=false)
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
        if ($this->npoints[$id]==0) return -1;
        $start = 0; $end = $this->npoints[$id]-1;
        $last = -1;
        
        for ($i=0; $i<30; $i++)
        {
            // Get the value in the middle of our range
            $mid = $start + round(($end-$start)*0.5);
            if ($mid==$last) return -1;

            fseek($this->fh[$id],$mid*9);
            $d = fread($this->fh[$id],9);
            $array = @unpack("x/Itime/fvalue",$d);
            $this->pos[$id] = $mid+1;

            // If it is the value we want then exit
            if ($time==$array['time']) return $mid;

            // If the query range is as small as it can be 1 datapoint wide: exit
            if (($end-$start)==1) {
                if (!$exact) {
                    return ($mid-1);
                } else {
                    return -1;
                }
            }
            
            // If the time of the last middle of the range is
            // more than our query time then next itteration is lower half
            // less than our query time then nest ittereation is higher half
            if ($time>$array['time']) $start = $mid; else $end = $mid;
            
            $last = $mid;
        }
        return -1;
    }

    public function trim($id,$start_time){
        return array('success'=>false,'message'=>'"Trim" not available for this storage engine');
    }
    
    public function clear($id){
    
        if (!$this->open($id,"r+")) return false;
        ftruncate($this->fh[$id], 0);
        $this->close($id);
        
        $this->npoints[$id] = 0;
        $this->pos[$id] = 0;
        
        return true;
    }

    /**
     * Abstracted open, read and close methods
     *
     */
    public function open($id,$mode) {

        $this->pos[$id] = 0;

        $filename = $this->dir."feed_$id.MYD";
        if (!$this->fh[$id] = @fopen($filename,$mode)) {
            $error = error_get_last();
            $this->log->warn("PHPTimeSeries: could not open $filename ".$error['message']);
            return false;
        }

        if (!flock($this->fh[$id], LOCK_EX)) {
            $this->log->warn("PHPTimeSeries: $filename locked by another process");
            fclose($this->fh[$id]);
            return false;
        }
        
        clearstatcache($this->dir."feed_$id.MYD");
        $this->npoints[$id] = floor(filesize($this->dir."feed_$id.MYD")/9.0);
        
        return true;
    }
    
    public function write($id,$pos,$time,$value) {
        if ($pos!=$this->pos[$id]) fseek($this->fh[$id], $pos*9);
        fwrite($this->fh[$id], pack("CIf",249,$time,$value));
        $this->pos[$id] = $pos+1;
    }
    
    public function read($id,$pos) {
        if ($pos!=$this->pos[$id]) fseek($this->fh[$id],$pos*9);
        $timevalue = unpack("x/Itime/fvalue",fread($this->fh[$id],9));
        $this->pos[$id] = $pos+1;
        return $timevalue;
    }
    
    public function read_range($id,$pos,$len=1) {
        fseek($this->fh[$id],$pos);
        // read division in one block, much faster!
        $s = fread($this->fh[$id],$len*9.0);
        $s2 = "";
        for ($x=0; $x<$len; $x++) {
            $s2 .= substr($s,($x*9)+5,4);
        }
        $tmp = unpack("f*",$s2);
        $values = array();
        for ($x=0; $x<count($tmp); $x++) {
            if (!is_nan($tmp[$x+1])) {
                $values[] = $tmp[$x+1];
                $n++;
            }
        }
        return $values;
    }

    public function sum_range($id,$pos,$len=1) {
        $sum = 0;
        $n = 0;
        fseek($this->fh[$id],$pos*9);
        // read division in one block, much faster!
        $s = fread($this->fh[$id],$len*9);
        $s2 = "";
        for ($x=0; $x<$len; $x++) {
            $s2 .= substr($s,($x*9)+5,4);
        }
        $tmp = unpack("f*",$s2);
        for ($x=0; $x<count($tmp); $x++) {
            if (!is_nan($tmp[$x+1])) {
                $sum += $tmp[$x+1];
                $n++;
            }
        }
        if ($n>0) $value = 1.0*$sum/$n; else $value = null;
        
        return $value;
    }
    
    public function close($id) {
        fclose($this->fh[$id]);
    }
    
    /**
     * Used for testing
     *
     */
    public function print_all($id) {  
        $this->open($id,"rb");
        
        for ($pos=0; $pos<$this->npoints[$id]; $pos++) {
            $dp = unpack("x/Itime/fvalue",fread($this->fh[$id],9));
            print $dp['time']." ".$dp['value']."\n";
        
        }
        $this->close($id);
    }
}
