<?php
// engine_methods interface in shared_helper.php
include_once dirname(__FILE__) . '/shared_helper.php';

class PHPTimeSeries implements engine_methods
{
    private $dir = "/var/lib/phptimeseries/";
    private $log;
    
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

// #### \/ Below are required methods
    /**
     * Create feed
     *
     * @param integer $feedid The id of the feed to be created
     * @param array $options for the engine
    */
    public function create($feedid,$options)
    {
        $fh = @fopen($this->dir."feed_$feedid.MYD", 'a');
        if (!$fh) {
            $error = error_get_last();
            $msg = "could not write data file ".$error['message'];
            $this->log->error("create() ".$msg);
            return $msg;
        }

        if (!flock($fh, LOCK_EX)) {
            $msg = "data file '".$this->dir."feed_$feedid.MYD"."' is locked by another process";
            $this->log->error("create() ".$msg);
            fclose($fh);
            return $msg;
        }

        fclose($fh);
        if (file_exists($this->dir."feed_$feedid.MYD")) return true;
        return false;
    }

    /**
     * Delete feed
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function delete($feedid)
    {
        unlink($this->dir."feed_$feedid.MYD");
    }

    /**
     * Gets engine metadata
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_meta($feedid)
    {
    
        $fh = fopen($this->dir."feed_$feedid.MYD", 'rb');
        $npoints = floor(filesize($this->dir."feed_$feedid.MYD") / 9.0);
        
        $start_time = 0;
        if ($npoints) {
            $array = unpack("x/Itime/fvalue",fread($fh,9));
            $start_time = $array["time"];
        }
        fclose($fh);
    
        $meta = new stdClass();
        $meta->id = $feedid;
        $meta->start_time = $start_time;
        // $meta->nlayers = 1;
        $meta->npoints = $npoints;
        $meta->interval = 1;
        
        return $meta;
    }

    /**
     * Returns engine occupied size in bytes
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_feed_size($feedid)
    {
        return filesize($this->dir."feed_$feedid.MYD");
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
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @param array $arg optional padding mode argument
    */
    public function post($feedid,$time,$value,$arg=null)
    {
        $this->log->info("PHPTimeSeries:post feedid=$feedid time=$time value=$value");

        $feedid = (int) $feedid;
        $time = (int) $time;
        $value = (float) $value;
                
        clearstatcache($this->dir."feed_$feedid.MYD");
        $filesize = filesize($this->dir."feed_$feedid.MYD");
        $csize = round($filesize / 9.0, 0, PHP_ROUND_HALF_DOWN) *9.0;
        if ($csize!=$filesize) {
            $this->log->warn("post() filesize not integer multiple of 9 bytes, correcting feedid=$feedid");
            
            // extend file by required number of bytes
            if (!$fh = $this->fopendata($this->dir."feed_$feedid.MYD", 'wb')) return false;
            fseek($fh,$csize);
            fwrite($fh, pack("CIf",249,$time,$value));
            fclose($fh);
            return $value;
        }
        
        // If there is data then read last value
        if ($filesize>=9) {
        
            // pen the data file to read and write
            $fh = $this->fopendata($this->dir."feed_$feedid.MYD", 'c+');
            if (!$fh) {
                $this->log->warn("post() could not open data file feedid=$feedid");
                return false;
            }
            
            // Read last value
            fseek($fh,$filesize-9);
            $d = fread($fh,9);
            if (strlen($d)!=9) {
                fclose($fh);
                return false;
            }
            $array = unpack("x/Itime/fvalue",$d);

            // Check if new datapoint is in the future
            if ($time>$array['time']) {
                // if yes: APPEND
                fwrite($fh, pack("CIf",249,$time,$value));
            } else {
                // if no: UPDATE
                // search for existing datapoint at time
                $pos = $this->binarysearch_exact($fh,$time,$filesize);
                if ($pos!=-1) {
                    // update existing datapoint
                    fseek($fh,$pos);
                    fwrite($fh, pack("CIf",249,$time,$value));
                }
            }
            fclose($fh);
        }
        else
        {
            // If theres no data in the file then we just append a first datapoint
            if (!$fh = $this->fopendata($this->dir."feed_$feedid.MYD", 'a')) return false;
            fwrite($fh, pack("CIf",249,$time,$value));
            fclose($fh);
        }
        
        return $value;
    }

    /**
     * Updates a data point in the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function update($feedid,$time,$value)
    {
      return $this->post($feedid,$time,$value);
    }

    /**
     * Get array with last time and value from a feed
     *
     * @param integer $feedid The id of the feed
    */
    public function lastvalue($feedid)
    {
        $feedid = (int)$feedid;
        $this->log->info("lastvalue() $feedid");
        
        if (!file_exists($this->dir."feed_$feedid.MYD"))  return false;
        
        $array = false;
        $fh = fopen($this->dir."feed_$feedid.MYD", 'rb');
        $filesize = filesize($this->dir."feed_$feedid.MYD");
        if ($filesize>=9)
        {
            fseek($fh,$filesize-9);
            $array = unpack("x/Itime/fvalue",fread($fh,9));
        }
        fclose($fh);
        return $array;
    }
    
    /**
     * Return the data for the given timerange - cf shared_helper.php
     *
     * @param integer $limitinterval When set to 1 , return the calculated timestamp if difference between calculated and hardcoded timestamps (based on metadata) is less than $interval - When set to 0, return the harcoded timestamp
    */
    public function get_data($feedid,$start,$end,$interval,$skipmissing,$limitinterval)
    {
        global $settings;

        $start = intval($start/1000);
        $end = intval($end/1000);
        $interval= (int) $interval;

        // Minimum interval
        if ($interval<1) $interval = 1;
        // End must be larger than start
        if ($end<=$start) return array("success"=>false, "message"=>"request end time before start time");
        // Maximum request size
        $req_dp = round(($end-$start) / $interval);
        if ($req_dp > $settings['feed']['max_datapoints']) return array("success"=>false, "message"=>"request datapoint limit reached (" . $settings['feed']['max_datapoints'] . "), increase request interval or time range, requested datapoints = $req_dp");
        
        $fh = fopen($this->dir."feed_$feedid.MYD", 'rb');
        $filesize = filesize($this->dir."feed_$feedid.MYD");

        if ($filesize==0) return array();
        
        $data = array();
        $time = $start;
        $atime = 0;

        while ($time<=$end)
        {
            $pos = $this->binarysearch($fh,$time,0,$filesize-9);
            fseek($fh,$pos);
            $d = fread($fh,9);
            $array = @unpack("x/Itime/fvalue",$d);
            $dptime = $array['time'];

            $value = null;

            $lasttime = $atime;
            $atime = $time;

            if ($limitinterval)
            {
                $diff = abs($dptime-$time);
                if ($diff<$interval) {
                    $value = $array['value'];
                } 
            } else {
                $value = $array['value'];
                $atime = $array['time'];
            }
            if ($value !== null) $value = (float) $value ;

            if ($atime!=$lasttime) {
                if ($value!==null || $skipmissing===0) $data[] = array($atime*1000,$value);
            }

            $time += $interval;
        }

        return $data;
    }
    
    /**
     * @param integer $feedid The id of the feed to fetch from
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
    public function get_data_combined($feedid,$start,$end,$interval,$average=0,$timezone="UTC",$timeformat="unix",$csv=false,$skipmissing=0,$limitinterval=1)
    {
        $feedid = (int) $feedid;
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
            $helperclass->csv_header($feedid);
        } else {
            $data = array();
        }
               
        $filesize = filesize($this->dir."feed_$feedid.MYD");
        $npoints = floor($filesize / 9);
        $fh = fopen($this->dir."feed_$feedid.MYD", 'rb');
        // Get starting position
        $pos_start = $this->binarysearch($fh,$time,0,$filesize-9);
        
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
                $pos_end = $this->binarysearch($fh,$div_end,0,$filesize-9);
                $bytes_to_read = ($pos_end-$pos_start);
                
                $value = null;
                
                if ($average && $bytes_to_read>9) {
                    // Calculate average in period
                    $sum = 0;
                    $n = 0;
                    $dp_to_read = $bytes_to_read / 9;
                    
                    if ($bytes_to_read) {
                        fseek($fh,$pos_start);
                        // read division in one block, much faster!
                        $s = fread($fh,$bytes_to_read);
                        $s2 = "";
                        for ($x=0; $x<$dp_to_read; $x++) {
                            $s2 .= substr($s,($x*9)+5,4);
                        }
                        $tmp = unpack("f*",$s2);
                        for ($x=0; $x<$dp_to_read; $x++) {
                            if (!is_nan($tmp[$x+1])) {
                                $sum += $tmp[$x+1];
                                $n++;
                            }
                        }
                    }
                    if ($n>0) $value = 1.0*$sum/$n;
                    
                } else {
                    fseek($fh,$pos_start);
                    $dp = unpack("x/Itime/fvalue",fread($fh,9));
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
            }
        }
        
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
    public function get_data_v2($feedid,$start,$end,$interval,$skipmissing,$limitinterval) {
        return $this->get_data_combined($feedid,$start,$end,$interval,0,"UTC","unix",false,$skipmissing,$limitinterval);
    }
    public function get_data_DMY($feedid,$start,$end,$interval,$timezone) {
        return $this->get_data_combined($feedid,$start,$end,$interval,0,$timezone);
    }
    public function get_average($feedid,$start,$end,$interval) {
        return $this->get_data_combined($feedid,$start,$end,$interval,1);
    }
    public function get_average_DMY($feedid,$start,$end,$interval,$timezone) {
        return $this->get_data_combined($feedid,$start,$end,$interval,1,$timezone);
    }
    public function csv_export($feedid,$start,$end,$interval,$average,$timezone,$timeformat) {
        $this->get_data_combined($feedid,$start*1000,$end*1000,$interval,$average,$timezone,$timeformat,true);
    }

    public function export($feedid,$start)
    {
        $feedid = (int) $feedid;
        $start = (int) $start;

        $feedname = "feed_$feedid.MYD";

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


// #### \/ Below are buffer write methods

    // Insert data in post write buffer, parameters like post()
    public function post_bulk_prepare($feedid,$timestamp,$value,$arg=null)
    {
        $feedid = (int) $feedid;
        $timestamp = (int) $timestamp;
        $value = (float) $value;

        $filename = "feed_$feedid.MYD";
        $npoints = $this->get_npoints($feedid);

        if (!isset($this->writebuffer[$feedid])) {
            $this->writebuffer[$feedid] = "";
        }

        // If there is data then read last value
        if ($npoints>=1) {
            static $lastvalue_static_cache = array(); // Array to hold the cache
            if (!isset($lastvalue_static_cache[$feedid])) { // Not set, cache it from file data
                $lastvalue_static_cache[$feedid] = $this->lastvalue($feedid);
            }           
            if ($timestamp<=$lastvalue_static_cache[$feedid]['time']) {
                // if data is in past, its not supported, could call update here to fix on file before continuing
                // but really this should not happen for past data has process_feed_buffer uses update for that.
                $this->log->warn("post_bulk_prepare() data in past, nothing saved.  feedid=$feedid timestamp=$timestamp last=".$lastvalue_static_cache[$feedid]['time']." value=$value");
                return $value;
            }
        }

        $this->writebuffer[$feedid] .= pack("CIf",249,$timestamp,$value);
        $lastvalue_static_cache[$feedid] = array('time'=>$timestamp,'value'=>$value); // Set static cache last value
        return $value;
    }

    // Saves post buffer to engine in bulk
    // Writing data in larger blocks saves reduces disk write load
    public function post_bulk_save()
    {
        $byteswritten = 0;
        foreach ($this->writebuffer as $feedid=>$data)
        {
            $filename = $this->dir."feed_$feedid.MYD";
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
    
    
// #### \/ Below engine public specific methods


// #### \/ Bellow are engine private methods    

    private function get_npoints($feedid)
    {
        $bytesize = 0;
        $filename = "feed_$feedid.MYD";

        if (file_exists($this->dir.$filename)) {
            clearstatcache($this->dir.$filename);
            $bytesize += filesize($this->dir.$filename);
        }
            
        if (isset($this->writebuffer[$feedid]))
            $bytesize += strlen($this->writebuffer[$feedid]);
        return floor($bytesize / 9.0);
    } 


    private function fopendata($filename,$mode)
    {
        $fh = @fopen($filename,$mode);

        if (!$fh) {
            $this->log->warn("PHPTimeSeries:fopendata could not open $filename");
            return false;
        }
        
        if (!flock($fh, LOCK_EX)) {
            $this->log->warn("PHPTimeSeries:fopendata $filename locked by another process");
            fclose($fh);
            return false;
        }
        
        return $fh;
    }



    private function binarysearch($fh,$time,$start,$end)
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
        for ($i=0; $i<30; $i++)
        {
            // Get the value in the middle of our range
            $mid = $start + round(($end-$start)/18)*9;
            fseek($fh,$mid);
            $d = fread($fh,9);
            $array = @unpack("x/Itime/fvalue",$d);

            // echo "S:$start E:$end M:$mid $time ".$array['time']." ".($time-$array['time'])."\n";

            // If it is the value we want then exit
            if ($time==$array['time']) return $mid;

            // If the query range is as small as it can be 1 datapoint wide: exit
            if (($end-$start)==9) return ($mid-9);

            // If the time of the last middle of the range is
            // more than our query time then next itteration is lower half
            // less than our query time then nest ittereation is higher half
            if ($time>$array['time']) $start = $mid; else $end = $mid;
        }
    }

    private function binarysearch_exact($fh,$time,$filesize)
    {
        if ($filesize==0) return -1;
        $start = 0; $end = $filesize-9;
        for ($i=0; $i<30; $i++)
        {
            $mid = $start + round(($end-$start)/18)*9;
            fseek($fh,$mid);
            $d = fread($fh,9);
            $array = unpack("x/Itime/fvalue",$d);
            if ($time==$array['time']) return $mid;
            if (($end-$start)==9) return -1;
            if ($time>$array['time']) $start = $mid; else $end = $mid;
        }
        return -1;
    }

    public function trim($feedid,$start_time){
        return array('success'=>false,'message'=>'"Trim" not available for this storage engine');
    }
    public function clear($feedid){
        return array('success'=>false,'message'=>'"Clear" not available for this storage engine');
    }

}
