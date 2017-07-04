<?php

// This timeseries engine implements:
// Fixed Interval No Averaging

class PHPFina
{
    private $dir = "/var/lib/phpfina/";
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
        $feedid = (int)$feedid;
        $interval = (int) $options['interval'];
        if ($interval<5) $interval = 5;
        
        // Check to ensure we dont overwrite an existing feed
        
        if (!file_exists($this->dir.$feedid.".meta")) {
            // Set initial feed meta data
            $meta = new stdClass();
            $meta->interval = $interval;
            $meta->start_time = 0;
            $meta->npoints = 0;

            // Save meta data
            $msg=$this->create_meta($feedid,$meta);
            if ($msg !== true) {
                return $msg;
            }

            $fh = @fopen($this->dir.$feedid.".dat", 'c+');
            if (!$fh) {
                $msg = "could not create meta data file " . error_get_last()['message'];
                $this->log->error("create() ".$msg);
                return $msg;
            }
            fclose($fh);
            $this->log->info("create() feedid=$feedid");
        }

        $feedname = "$feedid.meta";
        if (file_exists($this->dir.$feedname)) {
            return true;
        } else {
            $msg = "create failed, could not find meta data file '".$this->dir.$feedname."'";
            $this->log->error("create() ".$msg);
            return $msg;
        }
    }

    /**
     * Delete feed
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function delete($feedid)
    {
        $feedid = (int)$feedid;
        $meta = $this->get_meta($feedid);
        if (!$meta) return false;
        unlink($this->dir.$feedid.".meta");
        unlink($this->dir.$feedid.".dat");
        if (isset($metadata_cache[$feedid])) { unset($metadata_cache[$feedid]); } // Clear static cache
    }

    /**
     * Gets engine metadata
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_meta($feedid)
    {
        $feedid = (int) $feedid;
        $feedname = "$feedid.meta";

        if (!file_exists($this->dir.$feedname)) {
            $this->log->warn("get_meta() meta file does not exist '".$this->dir.$feedname."'");
            return false;
        }

        static $metadata_cache = array(); // Array to hold the cache
        if (isset($metadata_cache[$feedid])) {
            return $metadata_cache[$feedid]; // Retrieve from static cache
        } else {
            // Open and read meta data file
            // The start_time and interval are saved as two consecutive unsigned integers
            $meta = new stdClass();
            $metafile = fopen($this->dir.$feedname, 'rb');
            fseek($metafile,8);
            $tmp = unpack("I",fread($metafile,4)); 
            $meta->interval = $tmp[1];
            $tmp = unpack("I",fread($metafile,4)); 
            $meta->start_time = $tmp[1];
            $meta->npoints = $this->get_npoints($feedid);
            fclose($metafile);

            $metadata_cache[$feedid] = $meta; // Cache it
            return $meta;
        }
    }

    /**
     * Returns engine occupied size in bytes
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_feed_size($feedid)
    {
        $meta = $this->get_meta($feedid);
        if (!$meta) return false;
        return (16 + filesize($this->dir.$feedid.".dat"));
    }

    /**
     * Adds a data point to the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @param array $arg optional padding mode argument
    */
    public function post($id,$timestamp,$value,$padding_mode=null)
    {
        $this->log->info("post() id=$id timestamp=$timestamp value=$value padding=$padding_mode");
        
        $id = (int) $id;
        $timestamp = (int) $timestamp;
        $value = (float) $value;
        
        $now = time();
        $start = $now-(3600*24*365*5); // 5 years in past
        $end = $now+(3600*48);         // 48 hours in future
        
        if ($timestamp<$start || $timestamp>$end) {
            $this->log->warn("post() timestamp out of range");
            return false;
        }
        
        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($id)) {
            $this->log->warn("post() failed to fetch meta id=$id");
            return false;
        }
        $meta->npoints = $this->get_npoints($id);
        
        // Calculate interval that this datapoint belongs too
        $timestamp = floor($timestamp / $meta->interval) * $meta->interval;
        
        // If this is a new feed (npoints == 0) then set the start time to the current datapoint
        if ($meta->npoints == 0 && $meta->start_time==0) {
            $meta->start_time = $timestamp;
            $this->create_meta($id,$meta);
        }

        if ($timestamp < $meta->start_time) {
            $this->log->warn("post() timestamp older than feed start time id=$id");
            return false; // in the past
        }

        // Calculate position in base data file of datapoint
        $pos = floor(($timestamp - $meta->start_time) / $meta->interval);

        $last_pos = $meta->npoints - 1;


        $fh = fopen($this->dir.$id.".dat", 'c+');
        if (!$fh) {
            $this->log->warn("post() could not open data file id=$id");
            return false;
        }
        
        // Write padding
        $padding = ($pos - $last_pos)-1;
        
        // Max padding = 1 million datapoints ~4mb gap of 115 days at 10s
        $maxpadding = 1000000;
        
        if ($padding>$maxpadding) {
            $this->log->warn("post() padding max block size exeeded id=$id, $padding dp");
            return false;
        }
        
        if ($padding>0) {
            $padding_value = NAN;
            
            if ($last_pos>=0 && $padding_mode!=null) {
                fseek($fh,$last_pos*4);
                $val = unpack("f",fread($fh,4));
                $last_val = (float) $val[1];
                
                $padding_value = $last_val;
                $div = ($value - $last_val) / ($padding+1);
            }
            
            $buffer = "";
            for ($i=0; $i<$padding; $i++) {
                if ($padding_mode=="join") $padding_value += $div;
                $buffer .= pack("f",$padding_value);
                //$this->log->info("post() ##### paddings ". ((4*$meta->npoints) + (4*$i)) ." $i $padding_mode $padding_value");
            }
            fseek($fh,4*$meta->npoints);
            fwrite($fh,$buffer);
            
        }
        
        // Write new datapoint
        fseek($fh,4*$pos);
        if (!is_nan($value)) fwrite($fh,pack("f",$value)); else fwrite($fh,pack("f",NAN));
        //$this->log->info("post() ##### value    ". (4*$pos)." $value");
        
        // Close file
        fclose($fh);
        
        return $value;
    }
    
    /**
     * Updates a data point in the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function update($feedid,$timestamp,$value)
    {
        if (isset($this->writebuffer[$feedid]) && strlen($this->writebuffer[$feedid]) > 0) {
            $this->post_bulk_save();// if data on buffer, flush buffer now, then update it
            $this->log->info("update() $feedid with buffer");
        }
        return $this->post($feedid,$timestamp,$value);  //post can also update 
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
        
        // If meta data file does not exist exit
        if (!$meta = $this->get_meta($feedid)) return false;
        $meta->npoints = $this->get_npoints($feedid);

        if ($meta->npoints>0) {
            $fh = fopen($this->dir.$feedid.".dat", 'rb');
            $size = filesize($this->dir.$feedid.".dat");
            fseek($fh,$size-4);
             $d = fread($fh,4);
            fclose($fh);

            $value = null;
            $val = unpack("f",$d);
            $time = $meta->start_time + ($meta->interval * $meta->npoints);
            if (!is_nan($val[1])) {
                $value = (float) $val[1];
            } 
            return array('time'=>(int)$time, 'value'=>$value);
        }
        return false;
    }

    /**
     * Return the data for the given timerange
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $interval The number os seconds for each data point to return (used by some engines)
     * @param integer $skipmissing Skip null values from returned data (used by some engines)
     * @param integer $limitinterval Limit datapoints returned to this value (used by some engines)
    */
    public function get_data($name,$start,$end,$interval,$skipmissing,$limitinterval)
    {
        $start = intval($start/1000);
        $end = intval($end/1000);
        $interval= (int) $interval;

        // Minimum interval
        if ($interval<1) $interval = 1;
        // Maximum request size
        $req_dp = round(($end-$start) / $interval);
        if ($req_dp>8928) return array('success'=>false, 'message'=>"Request datapoint limit reached (8928), increase request interval or time range, requested datapoints = $req_dp");
        
        // If meta data file does not exist exit
        if (!$meta = $this->get_meta($name)) return array('success'=>false, 'message'=>"Error reading meta data feedid=$name");
        $meta->npoints = $this->get_npoints($name);
        
        if ($limitinterval && $interval<$meta->interval) $interval = $meta->interval; 

        $this->log->info("get_data() feed=$name st=$start end=$end int=$interval sk=$skipmissing lm=$limitinterval pts=$meta->npoints st=$meta->start_time");

        $data = array();
        $time = 0; $i = 0;
        $numdp = 0;
        // The datapoints are selected within a loop that runs until we reach a
        // datapoint that is beyond the end of our query range
        $fh = fopen($this->dir.$name.".dat", 'rb');
        while($time<=$end)
        {
            $time = $start + ($interval * $i);
            $pos = round(($time - $meta->start_time) / $meta->interval);
            $value = null;

            if ($pos>=0 && $pos < $meta->npoints)
            {
                // read from the file
                fseek($fh,$pos*4);
                $val = unpack("f",fread($fh,4));

                // add to the data array if its not a nan value
                if (!is_nan($val[1])) {
                    $value = (float) $val[1];
                } else {
                    $value = null;
                }
                //$this->log->info("get_data() ". ($pos*4) ." time=$time value=$value"); 
            }
            
            if ($value!==null || $skipmissing===0) {
                // see https://openenergymonitor.org/emon/node/11260
                $data[] = array($time*1000,$value);
            }

            $i++;
        }
        return $data;
    }
    
    public function get_data_DMY($id,$start,$end,$mode,$timezone) 
    {
        if ($mode!="daily" && $mode!="weekly" && $mode!="monthly") return false;
        
        $start = intval($start/1000);
        $end = intval($end/1000);
               
        // If meta data file does not exist exit
        if (!$meta = $this->get_meta($id)) return array('success'=>false, 'message'=>"Error reading meta data feedid=$name");
        $meta->npoints = $this->get_npoints($id);
        
        $data = array();
        
        $fh = fopen($this->dir.$id.".dat", 'rb');
        
        $date = new DateTime();
        if ($timezone===0) $timezone = "UTC";
        $date->setTimezone(new DateTimeZone($timezone));
        $date->setTimestamp($start);
        $date->modify("midnight");
        if ($mode=="weekly") $date->modify("this monday");
        if ($mode=="monthly") $date->modify("first day of this month");
        
        $n = 0;
        while($n<10000) // max itterations
        {
            $time = $date->getTimestamp();
            if ($time>$end) break;
            
            $pos = round(($time - $meta->start_time) / $meta->interval);
            $value = null;
            
            if ($pos>=0 && $pos < $meta->npoints)
            {
                // read from the file
                fseek($fh,$pos*4);
                $val = unpack("f",fread($fh,4));
                
                // add to the data array if its not a nan value
                if (!is_nan($val[1])) {
                    $value = $val[1];
                } else {
                    $value = null;
                }
            }
            if ($time>=$start && $time<$end) {
                $data[] = array($time*1000,$value);
            }
            
            if ($mode=="daily") $date->modify("+1 day");
            if ($mode=="weekly") $date->modify("+1 week");
            if ($mode=="monthly") $date->modify("+1 month");
            $n++;
        }
        
        fclose($fh);
        
        return $data;
    }
    
    public function get_data_DMY_time_of_day($id,$start,$end,$mode,$timezone,$split) 
    {
        if ($mode!="daily" && $mode!="weekly" && $mode!="monthly") return false;
        
        $start = intval($start/1000);
        $end = intval($end/1000);
        $split = json_decode($split);  
        if (gettype($split)!="array") return false;
        if (count($split)>24) return false;     
               
        // If meta data file does not exist exit
        if (!$meta = $this->get_meta($id)) return array('success'=>false, 'message'=>"Error reading meta data feedid=$name");
        $meta->npoints = $this->get_npoints($id);
        
        $data = array();
        
        $fh = fopen($this->dir.$id.".dat", 'rb');
        
        $date = new DateTime();
        if ($timezone===0) $timezone = "UTC";
        $date->setTimezone(new DateTimeZone($timezone));
        $date->setTimestamp($start);
        $date->modify("midnight");
        if ($mode=="weekly") $date->modify("this monday");
        if ($mode=="monthly") $date->modify("first day of this month");
        
        $n = 0;
        while($n<10000) // max itterations
        {
            $time = $date->getTimestamp();
            if ($time>$end) break;
            
            $value = null;
            
            $split_values = array();
            
            foreach ($split as $splitpoint) 
            {
                $splitpoint = (float) $splitpoint;
                $split_offset = (int) $splitpoint * 3600;
                
                $pos = round((($time+$split_offset) - $meta->start_time) / $meta->interval);
                $value = null;
                
                if ($pos>=0 && $pos < $meta->npoints)
                {
                    // read from the file
                    fseek($fh,$pos*4);
                    $val = unpack("f",fread($fh,4));
                    
                    // add to the data array if its not a nan value
                    if (!is_nan($val[1])) {
                        $value = $val[1];
                    } else {
                        $value = null;
                    }
                }
                
                $split_values[] = $value;
            
            }
            
            if ($time>=$start && $time<$end) {
                $data[] = array($time*1000,$split_values);
            }
            
            if ($mode=="daily") $date->modify("+1 day");
            if ($mode=="weekly") $date->modify("+1 week");
            if ($mode=="monthly") $date->modify("+1 month");
            $n++;
        }
        
        fclose($fh);
        
        return $data;
    }

    public function export($id,$start)
    {
        $id = (int) $id;
        $start = (int) $start;
        
        $feedname = $id.".dat";
        
        // If meta data file does not exist exit
        if (!$meta = $this->get_meta($id)) {
            $this->log->warn("PHPFina:post failed to fetch meta id=$id");
            return false;
        }

        $meta->npoints = $this->get_npoints($id);
        
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
        
        $primary = fopen($this->dir.$feedname, 'rb');
        $primarysize = filesize($this->dir.$feedname);
        
        $localsize = $start;
        $localsize = intval($localsize / 4) * 4;
        if ($localsize<0) $localsize = 0;

        // Get the first point which will be updated rather than appended
        if ($localsize>=4) $localsize = $localsize - 4;
        
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

    public function csv_export($feedid,$start,$end,$outinterval,$usertimezone)
    {
        global $csv_decimal_places, $csv_decimal_place_separator, $csv_field_separator;

        require_once "Modules/feed/engine/shared_helper.php";
        $helperclass = new SharedHelper();

        $feedid = intval($feedid);
        $start = intval($start);
        $end = intval($end);
        $outinterval= (int) $outinterval;

        // If meta data file does not exist exit
        if (!$meta = $this->get_meta($feedid)) return false;

        $meta->npoints = $this->get_npoints($feedid);
        
        if ($outinterval<$meta->interval) $outinterval = $meta->interval;
        $dp = ceil(($end - $start) / $outinterval);
        $end = $start + ($dp * $outinterval);
        
        // $dpratio = $outinterval / $meta->interval;
        if ($dp<1) return false;

        // The number of datapoints in the query range:
        $dp_in_range = ($end - $start) / $meta->interval;

        // Divided by the number we need gives the number of datapoints to skip
        // i.e if we want 1000 datapoints out of 100,000 then we need to get one
        // datapoints every 100 datapoints.
        $skipsize = round($dp_in_range / $dp);
        if ($skipsize<1) $skipsize = 1;

        // Calculate the starting datapoint position in the timestore file
        if ($start>$meta->start_time){
            $startpos = ceil(($start - $meta->start_time) / $meta->interval);
        } else {
            $start = ceil($meta->start_time / $outinterval) * $outinterval;
            $startpos = ceil(($start - $meta->start_time) / $meta->interval);
        }

        $data = array();
        $time = 0; $i = 0;
        
        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: application/octet-stream");
        $filename = $feedid.".csv";
        header("Content-Disposition: attachment; filename={$filename}");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $exportfh = @fopen( 'php://output', 'w' );


        // The datapoints are selected within a loop that runs until we reach a
        // datapoint that is beyond the end of our query range
        $fh = fopen($this->dir.$feedid.".dat", 'rb');
        while($time<=$end)
        {
            // $position steps forward by skipsize every loop
            $pos = ($startpos + ($i * $skipsize));

            // Exit the loop if the position is beyond the end of the file
            if ($pos > $meta->npoints-1) break;

            // read from the file
            fseek($fh,$pos*4);
            $val = unpack("f",fread($fh,4));

            // calculate the datapoint time
            $time = $meta->start_time + $pos * $meta->interval;
            $timenew = $helperclass->getTimeZoneFormated($time,$usertimezone);
            // add to the data array if its not a nan value
            if (!is_nan($val[1])) fwrite($exportfh, $timenew.$csv_field_separator.number_format($val[1],$csv_decimal_places,$csv_decimal_place_separator,'')."\n");

            $i++;
        }
        fclose($exportfh);
        exit;
    }

// #### /\ Above are required methods


// #### \/ Below are buffer write methods

    // Insert data in post write buffer, parameters like post()
    public function post_bulk_prepare($feedid,$timestamp,$value,$padding_mode)
    {   
        $feedid = (int) $feedid;
        $timestamp = (int) $timestamp;
        $value = (float) $value;
        $this->log->info("post_bulk_prepare() feedid=$feedid timestamp=$timestamp value=$value");
        
        // Check timestamp range
        $now = time();
        $start = $now-(3600*24*365*5); // 5 years in past
        $end = $now+(3600*48);         // 48 hours in future
        if ($timestamp<$start || $timestamp>$end) {
            $this->log->warn("post_bulk_prepare() timestamp out of range");
            return false;
        }

        // Check meta data file exists
        if (!$meta = $this->get_meta($feedid)) {
            $this->log->warn("post_bulk_prepare() failed to fetch meta feedid=$feedid");
            return false;
        }

        $meta->npoints = $this->get_npoints($feedid);

        // Calculate interval that this datapoint belongs too
        $timestamp = floor($timestamp / $meta->interval) * $meta->interval;

        // If this is a new feed (npoints == 0) then set the start time to the current datapoint
         if ($meta->npoints == 0 && $meta->start_time==0) {
            $meta->start_time = $timestamp;
            $this->create_meta($feedid,$meta);
        }

        if ($timestamp < $meta->start_time) {
            $this->log->warn("post_bulk_prepare() timestamp=$timestamp older than feed starttime=$meta->start_time feedid=$feedid");
            return false; // in the past
        }

        // Calculate position in base data file of datapoint
        $pos = floor(($timestamp - $meta->start_time) / $meta->interval);
        $last_pos = $meta->npoints - 1;
        
        $this->log->info("post_bulk_prepare() pos=$pos last_pos=$last_pos timestampinterval=$timestamp");
        
        if ($pos>$last_pos) {
            $npadding = ($pos - $last_pos)-1;
            
            if (!isset($this->writebuffer[$feedid])) {
                $this->writebuffer[$feedid] = "";    
            }
            
            if ($npadding>0) {
                $padding_value = NAN;
                if ($padding_mode!=null) {
                    static $lastvalue_static_cache = array(); // Array to hold the cache
                    if (!isset($lastvalue_static_cache[$feedid])) { // Not set, cache it from file data
                        $lastvalue_static_cache[$feedid] = $this->lastvalue($feedid)['value'];
                    }
                    $div = ($value - $lastvalue_static_cache[$feedid]) / ($npadding+1);
                    $padding_value = $lastvalue_static_cache[$feedid];
                    $lastvalue_static_cache[$feedid] = $value; // Set static cache last value
                }
                
                for ($n=0; $n<$npadding; $n++)
                {
                    if ($padding_mode!=null) $padding_value += $div; 
                    $this->writebuffer[$feedid] .= pack("f",$padding_value);
                    //$this->log->info("post_bulk_prepare() ##### paddings ". ((4*$meta->npoints) + (4*$n)) ." $n $padding_mode $padding_value");
                }
            }
            
            $this->writebuffer[$feedid] .= pack("f",$value);
            //$this->log->info("post_bulk_prepare() ##### value saved $value");
        } else {
            // if data is in past, its not supported, could call update here to fix on file before continuing
            // but really this should not happen for past data has process_feed_buffer uses update for that.
            // so this must be data posted in less time of the feed interval and can be ignored
            
            // This error crouds out the log's but is behaviour expected if data is coming in at a quicker interval than the feed interval
            // EmonPi posts at 5s where feed engine default size is 10s which means a log entry for every datapoint with this uncommented
            // $this->log->warn("post_bulk_prepare() data in past or before next interval, nothing saved. Posting too fast? slot=$meta->interval feedid=$feedid timestamp=$timestamp pos=$pos last_pos=$last_pos value=$value");
        }
        
        return $value;
    }

    // Saves post buffer to engine in bulk
    // Writing data in larger blocks saves reduces disk write load
    public function post_bulk_save()
    {
        $byteswritten = 0;
        foreach ($this->writebuffer as $feedid=>$data) {
            // Auto-correction if something happens to the datafile, it gets partitally written to
            // this will correct the file size to always be an integer number of 4 bytes.
            $filename = $this->dir.$feedid.".dat";
            clearstatcache($filename);
            if (@filesize($filename)%4 != 0) {
                $npoints = floor(filesize($filename)/4.0);
                $fh = fopen($filename,"c");
                if (!$fh) {
                    $this->log->warn("post_bulk_save() could not open data file '$filename'");
                    return false;
                }

                fseek($fh,$npoints*4.0);
                fwrite($fh,$data);
                fclose($fh);
                print "PHPFINA: FIXED DATAFILE WITH INCORRECT LENGHT '$filename'\n";
                $this->log->warn("post_bulk_save() FIXED DATAFILE WITH INCORRECT LENGHT '$filename'");
            }
            else
            {
                $fh = fopen($filename,"ab");
                if (!$fh) {
                    $this->log->warn("save() could not open data file '$filename'");
                    return false;
                }
                fwrite($fh,$data);
                fclose($fh);
            }
            
            $byteswritten += strlen($data);
        }
        
        $this->writebuffer = array(); // clear buffer
        
        return $byteswritten;
    }



// #### \/ Below engine specific methods


// #### \/ Bellow are engine private methods    
    
    private function create_meta($feedid, $meta)
    {
        $feedname = $feedid . ".meta";
        $metafile = @fopen($this->dir.$feedname, 'wb');
        
        if (!$metafile) {
            $msg = "could not write meta data file " . error_get_last()['message'];
            $this->log->error("create_meta() ".$msg);
            return $msg;
        }
        if (!flock($metafile, LOCK_EX)) {
            $msg = "meta data file '".$this->dir.$feedname."' is locked by another process";
            $this->log->error("create_meta() ".$msg);
            fclose($metafile);
            return $msg;
        }
        fwrite($metafile,pack("I",0));
        fwrite($metafile,pack("I",0)); 
        fwrite($metafile,pack("I",$meta->interval));
        fwrite($metafile,pack("I",$meta->start_time)); 
        fclose($metafile);
        return true;
    }
    
    private function get_npoints($feedid)
    {
        $bytesize = 0;
        
        if (file_exists($this->dir.$feedid.".dat")) {
            clearstatcache($this->dir.$feedid.".dat");
            $bytesize += filesize($this->dir.$feedid.".dat");
        }
            
        if (isset($this->writebuffer[$feedid]))
            $bytesize += strlen($this->writebuffer[$feedid]);
            
        return floor($bytesize / 4.0);
    }   

    // -----------------------------------------------------------------------------------
    // Post processed average layer
    // -----------------------------------------------------------------------------------
    
    /*
    
    Averaging (mean)
    
    The standard data request method returns the value of the data point at the specified timestamp 
    this works well for most data requests and is the required method for extracting accumulating kWh data
    
    However its useful for many applicaitons to be able to extract the average value for a given period
    An example would be requesting hourly temperature over number of days and wanting to see the average temperature for each hour
    rather than the temperature at the start of each hour.
    
    Emoncms initially did this with a dedicated feed engine called PHPFiwa the implementation of that engine calculated the average 
    values on the fly as the data came in which is not a particularly write efficient method as each average layer is opened for every
    update and only one 4 byte float is appended.
    
    A better approach is to post process the average layers when the data request is made and to cache the calculated averages at this 
    point. This significantly reduces the write load and converts the averaged layers into a recompilable cached property rather than
    and integral part of the core engine.
    
    */
        
    public function get_average($id,$start,$end,$interval)
    {   
        $start = intval($start/1000);
        $end = intval($end/1000);
        $interval= (int) $interval;
        
        // Minimum interval
        if ($interval<1) $interval = 1;
        // Maximum request size
        $req_dp = round(($end-$start) / $interval);
        if ($req_dp>8928) return array('success'=>false, 'message'=>"Request datapoint limit reached (8928), increase request interval or time range, requested datapoints = $req_dp");
        
        
        $layer_interval = 0;
        //if ($interval>=600) $layer_interval = 600;
        //if ($interval>=3600) $layer_interval = 3600;
        
        // Only return an average if the $interval is more than a layer interval
        // and check that the interval is an integer number of layer intervals
        if ($layer_interval>0 && $interval%$layer_interval==0) {
            //if (!$this->calculate_average($id,$layer_interval)) {
                // return $this->get_data($id,$start*1000,$end*1000,$interval,0,0);
            //    return array('success'=>false);
            //}
        }
        
        $dir = $this->dir;
        //if ($layer_interval>0) $dir = $dir."averages/$layer_interval/";
        
        $meta = new stdClass();
        $metafile = fopen($dir.$id.".meta", 'rb');
        fseek($metafile,8);
        $tmp = unpack("I",fread($metafile,4)); 
        $meta->interval = $tmp[1];
        $tmp = unpack("I",fread($metafile,4)); 
        $meta->start_time = $tmp[1];
        fclose($metafile);
        $meta->npoints = floor(filesize($dir.$id.".dat") / 4.0);
        
        if ((($end-$start) / $meta->interval)>69120) {
            return $this->get_data($id,$start*1000,$end*1000,$interval,0,0);
        }
        
        if ($interval % $meta->interval !=0) return array('success'=>false, 'message'=>"Request interval is not an integer multiple of the layer interval");
        
        $dp_to_read = $interval / $meta->interval;
        
        $data = array();
        $time = 0; $i = 0;
        $numdp = 0;
        // The datapoints are selected within a loop that runs until we reach a
        // datapoint that is beyond the end of our query range
        $fh = fopen($dir.$id.".dat", 'rb');
        while($time<=$end)
        {
            $time = $start + ($interval * $i);
            $pos = round(($time - $meta->start_time) / $meta->interval);
            $average = null;

            if ($pos>=0 && $pos < $meta->npoints)
            {
                // read from the file
                fseek($fh,$pos*4);
                $s = fread($fh,4*$dp_to_read);
                if (strlen($s)!=4*$dp_to_read) break;
                
                $tmp = unpack("f*",$s);
                $sum = 0.0; $n = 0;
                
                /*
                for ($x=0; $x<$dp_to_read; $x++) {
                  if (!is_nan($tmp[$x+1])) {
                      $sum += 1.0*$tmp[$x+1];
                      $n++;
                  }
                }*/
                
                $val = NAN;
                for ($x=0; $x<$dp_to_read; $x++) {
                  if (!is_nan($tmp[$x+1])) $val = 1*$tmp[$x+1];
                  if (!is_nan($val)) {
                    $sum += $val;
                    $n++;
                  }
                }
                
                $average = null;
                if ($n>0) $average = $sum / $n;
            }

            if ($time>=$start && $time<$end) {
                $data[] = array($time*1000,$average);
            }

            $i++;
        }
        
        return $data;        
    }

    public function get_average_DMY($id,$start,$end,$mode,$timezone)
    {   
        $start = intval($start/1000);
        $end = intval($end/1000);
        
        if ($mode!="daily" && $mode!="weekly" && $mode!="monthly") return false;
        
        if ($mode=="daily") $interval = 86400;
        if ($mode=="weekly") $interval = 86400*7;
        if ($mode=="monthly") $interval = 86400*30;
        
        $layer_interval = 0;
        //if ($interval>=600) $layer_interval = 600;
        //if ($interval>=3600) $layer_interval = 3600;
        
        // Only return an average if the $interval is more than a layer interval
        // and check that the interval is an integer number of layer intervals
        if ($layer_interval>0 && $interval%$layer_interval==0) {
            //if (!$this->calculate_average($id,$layer_interval)) {
                // return $this->get_data($id,$start*1000,$end*1000,$interval,0,0);
            //    return array('success'=>false);
            //}
        }
        
        $dir = $this->dir;
        //if ($layer_interval>0) $dir = $dir."averages/$layer_interval/";
        
        $meta = new stdClass();
        $metafile = fopen($dir.$id.".meta", 'rb');
        fseek($metafile,8);
        $tmp = unpack("I",fread($metafile,4)); 
        $meta->interval = $tmp[1];
        $tmp = unpack("I",fread($metafile,4)); 
        $meta->start_time = $tmp[1];
        fclose($metafile);
        $meta->npoints = floor(filesize($dir.$id.".dat") / 4.0);
        
        //if ((($end-$start) / $meta->interval)>69120) {
        //    return array('success'=>false, 'message'=>"Range to long");
        //}
        
        if ($interval % $meta->interval !=0) return array('success'=>false, 'message'=>"Request interval is not an integer multiple of the layer interval");
        
        $dp_to_read = $interval / $meta->interval;
        
        $data = array();
        $time = 0; $i = 0;
        $numdp = 0;
        $total_read_count = 0;
        // The datapoints are selected within a loop that runs until we reach a
        // datapoint that is beyond the end of our query range
        
        $fh = fopen($dir.$id.".dat", 'rb');
        
        $date = new DateTime();
        if ($timezone===0) $timezone = "UTC";
        $date->setTimezone(new DateTimeZone($timezone));
        $date->setTimestamp($start);
        $date->modify("midnight");
        if ($mode=="weekly") $date->modify("this monday");
        if ($mode=="monthly") $date->modify("first day of this month");
        
        $itterations = 0;
        while(true) // max itterations
        {
            $time = $date->getTimestamp();
            if ($mode=="daily") $date->modify("+1 day");
            if ($mode=="weekly") $date->modify("+1 week");
            if ($mode=="monthly") $date->modify("+1 month");
            $nexttime = $date->getTimestamp();
            
            if ($time>$end) break;
            
            $pos = round(($time - $meta->start_time) / $meta->interval);
            $nextpos = round(($nexttime - $meta->start_time) / $meta->interval);
            $dp_to_read = $nextpos - $pos;
            if ($dp_to_read==0) return false;
            
            $average = null;
            
            if ($pos>=0 && $pos < $meta->npoints)
            {
                // read from the file
                fseek($fh,$pos*4);
                $s = fread($fh,4*$dp_to_read);
                
                $len = strlen($s);
                $total_read_count += $len / 4.0;
                
                if ($len==4*$dp_to_read) {

                    $tmp = unpack("f*",$s);
                    $sum = 0; $n = 0;
                    
                    $val = NAN;
                    for ($x=0; $x<$dp_to_read; $x++) {
                      if (!is_nan($tmp[$x+1])) $val = 1*$tmp[$x+1];
                      if (!is_nan($val)) {
                        $sum += $val;
                        $n++;
                      }
                    }
                    
                    $average = null;
                    if ($n>0) $average = $sum / $n;
                }
            }
            
            if ($time>=$start) {
                $data[] = array($time*1000,$average);
            }
            
            $itterations++;
        }
        
        fclose($fh);

        
        return $data;        
    }


    private function calculate_average($id,$layer_interval)
    {
        $idir = $this->dir;
        $odir = $this->dir."averages/$layer_interval/";
        
        // Open PHPFina meta file, get start time and interval       
        $base_meta = $this->get_meta($id);
        $base_meta->npoints = floor(filesize($idir.$id.".dat") / 4.0);
        
        // Calculate start time of average layer
        $start_time = ceil($base_meta->start_time / $layer_interval) * $layer_interval;

        // Check if the average layer already exists
        // if it does load its meta file and check that the base layer calculated start_time matches
        $layer_npoints = 0;

        if (file_exists($odir.$id.".meta")) {
            $layer_meta = new stdClass();
            $metafile = fopen($odir.$id.".meta", 'rb');
            fseek($metafile,8);
            $tmp = unpack("I",fread($metafile,4)); 
            $layer_meta->interval = $tmp[1];
            $tmp = unpack("I",fread($metafile,4)); 
            $layer_meta->start_time = $tmp[1];
            fclose($metafile);
            
            if ($layer_meta->start_time != $start_time) {
                echo "ERROR: average layer start time does not match base layer\n";
                return false;
            }
            
            if ($layer_meta->interval != $layer_interval) {
                echo "ERROR: average layer interval does not match base layer\n";
                return false;
            }
            
            if (file_exists($odir.$id.".dat")) {
                $layer_npoints = floor(filesize($odir.$id.".dat") / 4.0);
            }
        } else {
            $layer_meta = clone $base_meta;
            $layer_meta->start_time = $start_time;
            $layer_meta->interval = $layer_interval;
            
            $metafile = fopen($odir.$id.".meta", 'wb');
            fwrite($metafile,pack("I",0));
            fwrite($metafile,pack("I",0)); 
            fwrite($metafile,pack("I",$layer_meta->interval));
            fwrite($metafile,pack("I",$layer_meta->start_time)); 
            fclose($metafile);
        }

        if (!$if = @fopen($idir.$id.".dat", 'rb')) {
            echo "ERROR: could not open $idir $id.dat\n";
            return false;
        }

        if (!$of = @fopen($odir.$id.".dat", 'c+')) {
            echo "ERROR: could not open $odir $id.dat\n";
            return false;
        }

        $dp_to_read = $layer_meta->interval / $base_meta->interval;

        $start_time = $layer_meta->start_time + ($layer_npoints*$layer_meta->interval);
        $base_start_pos = ($start_time - $base_meta->start_time) / $base_meta->interval;
        
        // If amount to process is more than 1 week of 10 second data or 86400*7 / 10 = 60480 datapoints.
        // return false as this will take too long in a http request and needs to be processed in the background.
        
        $npoints_to_process = $base_meta->npoints - $base_start_pos;
        if ($npoints_to_process>60480) {
            echo "ERROR: amount to process is too much $npoints_to_process \n";
            return false;
        }
        
        fseek($if,$base_start_pos*4);
        fseek($of,$layer_npoints*4);

        $buffer = "";

        while (true) 
        {
            $s = fread($if,4*$dp_to_read);
            if (strlen($s)!=4*$dp_to_read) break;
            
            $tmp = unpack("f*",$s);
            $sum = 0; $n = 0;
            for ($i=0; $i<$dp_to_read; $i++) {
              if (!is_nan($tmp[$i+1])) {
                  $sum += 1*$tmp[$i+1];
                  $n++;
              }
            }
            $average = NAN;
            if ($n>0) $average = $sum / $n;
            $buffer .= pack("f",$average);
        }
        
        // Final stage write buffer and close files
        fwrite($of,$buffer);
        fclose($of);
        fclose($if);
        
        return true;
    }
    
    public function upload_fixed_interval($id,$start,$interval,$npoints)
    {
        $id = (int) $id;
        $start = (int) $start;
        $interval = (int) $interval;
        $npoints = (int) $npoints;
        /*
        // Initial implementation using post_bulk_prepare
        if (!$fh=fopen('php://input','r')) return false;
        for ($i=0; $i<$npoints; $i++) {
            $time = $start + ($interval * $i);
            $tmp = unpack("f",fread($fh,4));
            $value = $tmp[1];
            $this->post_bulk_prepare($id,$time,$value,null);
        }
        $this->post_bulk_save();
        fclose($fh);
        */
        
        // Faster direct block write method
        
        // Fetch data from post body and check length match
        $data = file_get_contents('php://input');
        if ($npoints!=(strlen($data) / 4.0)) {
            $this->log->warn("upload() data body does not match blocksize param id=$id");
            return false;
        }
        
        // Load feed meta to fetch start time and interval
        if (!$meta = $this->get_meta($id)) {
            $this->log->warn("upload() failed to fetch meta id=$id");
            return false;
        }
        $meta->npoints = $this->get_npoints($id);
        
        if ($meta->start_time==0 && $meta->npoints != 0) {
            $this->log->warn("upload() start time is zero but data in feed =$id");
            return false;
        }
        
        // If no data in feed and start time is zero, create meta
        if ($meta->npoints == 0 && $meta->start_time==0) {
            $meta->start_time = $start;
            $this->create_meta($id,$meta);
        }
        
        // Calculate start position
        $pos = floor(($start - $meta->start_time)/$meta->interval);
        
        // Open feed data file, seek to position and write in data block
        $fh = fopen($this->dir.$id.".dat","c");
        fseek($fh,$pos*4);
        fwrite($fh,$data);
        fclose($fh);
        
        return true;
    }
    
    public function upload_variable_interval($feedid,$npoints)
    {
        $feedid = (int) $feedid;
        $npoints = (int) $npoints;
        
        if (!$fh=fopen('php://input','r')) return false;
        
        for ($i=0; $i<$npoints; $i++) {
            $tmp = unpack("If",fread($fh,8));
            $time = $tmp[1];
            $value = $tmp[2];
            //print $time." ".$value."\n";
            $this->post_bulk_prepare($feedid,$time,$value,null);
        }
        $this->post_bulk_save();

        fclose($fh);
        
        return true;
    }
}
