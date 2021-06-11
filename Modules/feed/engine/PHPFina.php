<?php
// This timeseries engine implements:
// Fixed Interval No Averaging

// engine_methods interface in shared_helper.php
include_once dirname(__FILE__) . '/shared_helper.php';

class PHPFina implements engine_methods
{
    private $dir = "/var/lib/phpfina/";
    private $log;
    private $maxpadding = 3153600; // 1 year @ 10s
    
    private $fh = array();
    private $meta = array();
    private $pos = array();

    private $redis = false;

    /**
     * Constructor.
     *
     * @api
    */
    public function __construct($settings,$redis=false)
    {
        $this->redis = $redis;
        
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
        $id = (int)$id;
        $interval = (int) $options['interval'];
        if ($interval<5) $interval = 5;
        
        // Check to ensure we dont overwrite an existing feed
        $feedname = "$id.meta";
        if (!file_exists($this->dir.$feedname)) {
            // Set initial feed meta data
            $meta = new stdClass();
            $meta->interval = $interval;
            $meta->start_time = 0;
            $meta->npoints = 0;

            // Save meta data
            $msg=$this->create_meta($id,$meta);
            if ($msg !== true) {
                return $msg;
            }

            $fh = @fopen($this->dir.$id.".dat", 'c+');
            if (!$fh) {
                $error = error_get_last();
                $msg = "could not create meta data file ".$error['message'];
                $this->log->error("create() ".$msg);
                return $msg;
            }
            fclose($fh);
            $this->log->info("create() feedid=$id");
        }

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
     * @param integer $id The id of the feed to be created
    */
    public function delete($id)
    {
        $id = (int)$id;
        if (!$meta = $this->get_meta($id)) return false;
        unlink($this->dir.$id.".meta");
        unlink($this->dir.$id.".dat");
        if ($this->redis) $this->redis->del("phpfina:buffer:$id");
    }
    
    /**
     * Gets engine metadata
     *
     * @param integer $id The id of the feed to be created
    */
    public function get_meta($id)
    {
        $id = (int) $id;
        $feedname = "$id.meta";

        if (!file_exists($this->dir.$feedname)) {
            $this->log->warn("get_meta() meta file does not exist '".$this->dir.$feedname."'");
            return false;
        }

        // Open and read meta data file
        // The start_time and interval are saved as two consecutive unsigned integers
        $meta = new stdClass();
        $metafile = fopen($this->dir.$feedname, 'rb');
        fseek($metafile,8);
        $tmp = unpack("I",fread($metafile,4)); 
        $meta->interval = $tmp[1];
        $tmp = unpack("I",fread($metafile,4)); 
        $meta->start_time = $tmp[1];
        fclose($metafile);
        
        $meta->npoints = 0;
        if (file_exists($this->dir.$id.".dat")) {
            clearstatcache($this->dir.$id.".dat");
            $meta->npoints += floor(filesize($this->dir.$id.".dat")/4.0);
        }
        
        $meta->bufferlen = 0;
        if ($this->redis) $meta->bufferlen = $this->redis->llen("phpfina:buffer:$id");
        $meta->npoints += $meta->bufferlen;
        $meta->buffer_start = $meta->npoints - $meta->bufferlen;

        if ($meta->start_time>0 && $meta->npoints==0) {
            $this->log->warn("PHPFina:get_meta start_time already defined but npoints is 0");
            return false;
        }
        
        $meta->end_time = $meta->start_time + ($meta->interval * ($meta->npoints-1));
        
        return $meta;
    }

    /**
     * Returns engine occupied size in bytes
     *
     * @param integer $id The id of the feed to be created
    */
    public function get_feed_size($id)
    {
        if (!$meta = $this->get_meta($id)) return false;
        return (16 + filesize($this->dir.$id.".dat"));
    }

    /**
     * Adds a data point to the feed
     *
     * @param integer $id The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @param array $arg optional padding mode argument
    */
    public function post($id,$timestamp,$value,$padding_mode=null)
    {
        $this->log->info("post() id=$id timestamp=$timestamp value=$value padding_mode=$padding_mode");
        
        $id = (int) $id;
        $timestamp = (int) $timestamp;
        $value = (float) $value;
        if (is_nan($value)) $value = NAN;
        
        $now = time();
        $start = $now-(3600*24*365*5); // 5 years in past
        $end = $now+(3600*48);         // 48 hours in future
        
        if ($timestamp<$start || $timestamp>$end) {
            $this->log->warn("post() timestamp out of range");
            return false;
        }
        
        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($id)) return false;
        
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
        
        // If this is an update of an existing datapoint update it directly here and return
        if ($pos<$meta->npoints) {
            if ($pos<$meta->buffer_start) {
                $fh = fopen($this->dir.$id.".dat", 'c+');
                fseek($fh,4*$pos);
                fwrite($fh,pack("f",$value));
                fclose($fh);
            } else {
                // Update a value in the buffer
                $this->redis->lset("phpfina:buffer:$id",$pos-$meta->buffer_start,$value);
            }
            return $value;
        }
        
        // Calculate padding      
        $last_pos = $meta->npoints - 1;
        if ($last_pos<0) $last_pos = 0;
        $padding = ($pos - $last_pos)-1;
        if ($padding<0) $padding = 0;
            
        if ($padding>$this->maxpadding) {
            $this->log->warn("post() padding max block size exeeded id=$id, $padding dp");
            return false;
        }

        if (!$fh = fopen($this->dir.$id.".dat", 'c+')) {
            $this->log->warn("post() could not open data file id=$id");
            return false;
        }
        
        $padding_value = NAN;
        if ($padding>0 && $padding_mode!=null) {
        
            if ($last_pos<$meta->buffer_start) {
                fseek($fh,$last_pos*4);
                $val = unpack("f",fread($fh,4));
                $last_val = (float) $val[1];
            } else {
                $last_val = $this->redis->lrange("phpfina:buffer:$id",$last_pos,$last_pos)[0]; 
            }
            
            $padding_value = $last_val;
            $div = ($value - $last_val) / ($padding+1);
        }
        
        if (!$this->redis) {
            // If the buffer is not enabled write new datapoint and padding directly to data file
            $buffer = "";
            for ($i=0; $i<$padding; $i++) {
                if ($padding_mode!=null) $padding_value += $div;
                $buffer .= pack("f",$padding_value);
            }
            $buffer .= pack("f",$value);
            fseek($fh,4*$meta->npoints);
            fwrite($fh,$buffer);
            
        } else {
            // If the buffer is enabled write new datapoint and padding to the buffer
            for ($i=0; $i<$padding; $i++) {
                if ($padding_mode!=null) $padding_value += $div;
                $this->redis->rpush("phpfina:buffer:$id",$padding_value);
            }
            $this->redis->rpush("phpfina:buffer:$id",$value);
        }
        
        fclose($fh);
        
        // Persist buffer
        if (($meta->bufferlen*$meta->interval)>=300) {
            $this->save_buffer($id);
        }
        return $value;
    }

    /**
     * scale a portion of a feed
     * added by Alexandre CUER - january 2019 
     *
     * @param integer $id The id of the feed
     * @param integer $start unix time stamp in ms of the start of the data range
     * @param integer $end unix time stamp in ms of the end of the data rage
     * @param float $scale : numeric value for the scaling 
    */
    public function scalerange($id,$start,$end,$scale){
    
        // Save buffer before processing feed data
        if ($meta->bufferlen>0) {
            $this->save_buffer($id);
        }
    
        //echo("test on $scale not started");
        //case1: NAN
        if(preg_match("/^NAN$/i",$scale)){
            $this->log->warn("scale_range() : going to erase data range with NAN");
            $scale = NAN;
        //case2: scaling value - possible to use a fraction
        } else if(preg_match("/^(1\/|-1\/|-)?([0-9]+((\.|,)[0-9]+)?)$/",$scale,$a)){
            $this->log->warn("scale_range() : being given a float scale parameter");
            $scale = (float) $a[2];
            if ($a[1]=="1/")$scale = 1/$scale;
            else if ($a[1]=="-1/") $scale = -1/$scale;
            else if ($a[1]=="-") $scale = -$scale;
            //print_r($a);
        //case3: absolute value
        } else if(preg_match("/^abs\(x\)$/i",$scale)){
            $this->log->warn("scale_range() : conversion to absolute values on the data range");
            $scale="abs(x)"; 
        } else return false;
        
        //echo("test finished>");
        
        //echo("<br>$scale");
        //echo("<br>$start and $end");
        $id = (int) $id;
        $start = intval($start/1000);
        $end = intval($end/1000);
        //echo("<br>$start and $end");
        
        if(!$meta=$this->get_meta($id)){
            $this->log->warn("scale_range() failed to fetch meta id = $id");
            return false;
        }
        
        $this->log->warn("scale_range() successfully fetched meta id = $id");
        
        //integrity checks
        $start=floor($start/$meta->interval)*$meta->interval;
        $end=floor($end/$meta->interval)*$meta->interval;
        //debug
        //echo("<br>$start and $end");
        if($start>$end) {
            $this->log->warn("scale_range() : start should not be greater than end");
            return false;
        }
        if($start<$meta->start_time) $start=$meta->start_time;
        $end_time=$this->lastvalue($id)['time'];
        if($end>$end_time) $end=$end_time;
        
        //calculates address in dat file and number of values to write
        $pos_start=4*floor(($start-$meta->start_time)/$meta->interval);
        $pos_end=4*floor(($end-$meta->start_time)/$meta->interval);
        $nbwrites=($pos_end-$pos_start)/4;
        //echo("<br>$nbwrites");
        
        //open the dat file
        $fh = fopen ($this->dir.$id.".dat","c+");
        if (!$fh){
            $this->log->warn("scale_range() : unable to open data file with id=$id");
            return false;
        }
        $this->log->warn("scale_range() : going to write $nbwrites values from address $pos_start to $pos_end");
        
        //fetch the values to process
        fseek($fh,$pos_start);
        $values=unpack("f$nbwrites",fread($fh,4*$nbwrites));
        //print_r($values);
        
        //create a buffer with the processed values
        $buffer="";
        for($i=1;$i<=$nbwrites;$i++) {
            if($scale==NAN) $val=NAN;
            else if($scale=="abs(x)") $val=abs($values[$i]);
            else $val=$values[$i]*$scale;
            $buffer.=pack("f",$val);
        }
        
        //write the processed buffer to the dat file
        fseek($fh,$pos_start);
        if(!$written_bytes = fwrite($fh,$buffer)){
            $this->log->warn("scale_range() : unable to write to the file with id=$id");
            fclose($fh);
            return false;
        }
        $this->log->warn("scale_range() : wrote $written_bytes bytes");
        fclose($fh);
        return $written_bytes;
    }
    
    /**
     * Get array with last time and value from a feed
     *
     * @param integer $id The id of the feed
    */
    public function lastvalue($id)
    {
        $id = (int) $id;
        
        if (!$meta = $this->get_meta($id)) return false;
        if (!$meta->npoints) return false;
        
        $this->open($id, 'rb');
        $value = $this->read($id,$meta->npoints-1);
        $this->close($id);
        return array('time'=>$meta->end_time, 'value'=>$value);
        
        return false;
    }


    /**
     * Return the data for the given timerange - cf shared_helper.php
     *
    */
    public function get_value($id,$time)
    {        
        $id = (int) $id;
        $time = (int) $time;
        
        if (!$meta = $this->get_meta($id)) return false;
        
        $this->open($id, 'rb');
        $pos = round(($time - $meta->start_time) / $meta->interval);
        $value = $this->read($id,$pos);
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
        
        // todo: consider supporting a variety of time formats here
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

        // Load feed meta data
        // If meta data file does not exist exit
        // todo: combine npoints and end_time into get_meta
        if (!$meta = $this->get_meta($id)) return false;
        
        $fullres = false;
        if ($interval=="original") $interval = $meta->interval;
        
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
            if ($interval=="weekly") {
                $date->modify("this monday");
                $modify = "+1 week";
            } else if ($interval=="monthly") {
                $date->modify("first day of this month");
                $modify = "+1 month";
            } else if ($interval=="annual") {
                $date->modify("first day of this year");
                $modify = "+1 year";
            }
            $time = $date->getTimestamp();
        } else {
            // If interval codes are not specified then we advanced by a fixed numeric interval 
            $fixed_interval = true;
            $interval = (int) $interval;
            if ($interval<1) $interval = 1;
            if ($limitinterval) {
                if ($interval<$meta->interval) $interval = $meta->interval;       // limit interval by feed interval
                $interval = round($interval/$meta->interval)*$meta->interval;     // round interval to be integer multiple of feed interval
            }
            $time = $start;
            
            // turn off averaging if export interval is the same as the feed interval
            if ($interval==$meta->interval) {
                $average = false;
                $fullres = true;
            }
            if ($interval<$meta->interval) {
                $average = false;
            }
        }
        
        if ($csv) {
            $helperclass->csv_header($id);
        } else {
            $data = array();
        }
        
        $this->open($id, 'rb');
 
        // seek only once for full resolution export
        $first_seek = false;
             
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
            
            // seek to starting position
            $pos_start = floor(($div_start-$meta->start_time) / $meta->interval);

            $value = null;
            
            if ($average) {
                // Calculate average in period
                $sum = 0;
                $n = 0;
                
                // calculate end position
                $pos_end = floor(($div_end-$meta->start_time) / $meta->interval);
                
                // limit start and end by available data
                // results in dp_to_read being 0 outside of range
                if ($pos_start<0) $pos_start = 0;
                if ($pos_end<0) $pos_end = 0;
                if ($pos_start>$meta->npoints) $pos_start = $meta->npoints;                
                if ($pos_end>$meta->npoints) $pos_end = $meta->npoints;
                $dp_to_read = $pos_end-$pos_start;
                
                if ($dp_to_read) {
                    $tmp = $this->read_range($id,$pos_start,$dp_to_read);
                    for ($x=0; $x<$dp_to_read; $x++) {
                        if (!is_nan($tmp[$x])) {
                            $sum += $tmp[$x];
                            $n++;
                        }
                    }
                }
                
                if ($n>0) $value = 1.0*$sum/$n;
                
            } else {
                if ($time>=$meta->start_time && $time<=$meta->end_time) {                
                    $value = $this->read($id,$pos_start);
                    if (is_nan($value)) $value = null;
                }
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
    
    // Splits daily, weekly, monthly output into time of use segments defined by $split
    public function get_data_DMY_time_of_day($id,$start,$end,$mode,$timezone,$split) 
    {        
        if ($mode!="daily" && $mode!="weekly" && $mode!="monthly") return false;

        $start = intval($start/1000);
        $end = intval($end/1000);
        $split = json_decode($split);
        if (gettype($split)!="array") return false;
        if (count($split)>48) return false;

        // If meta data file does not exist exit
        if (!$meta = $this->get_meta($id)) return false;

        $data = array();
        
        $this->open($id, 'rb');

        $date = new DateTime();
        if ($timezone===0) $timezone = "UTC";
        $date->setTimezone(new DateTimeZone($timezone));
        $date->setTimestamp($start);

        $date->modify("midnight");
        $modify = "+1 day";
        if ($mode=="weekly") {
            $date->modify("this monday");
            $modify = "+1 week";
        } else if ($mode=="monthly") {
            $date->modify("first day of this month");
            $modify = "+1 month";
        }
        
        $n = 0;
        while($n<10000) // max iterations allows for approx 7 months with 1 day granularity
        {
            $time = $date->getTimestamp();
            if ($time>$end) break;

            $value = null;

            $split_values = array();

            foreach ($split as $splitpoint)
            {
                //Fix issue with rounding to nearest 30 minutes
                $split_offset = (int) (((float)$splitpoint) * 3600.0);

                $pos = round((($time+$split_offset) - $meta->start_time) / $meta->interval);
                
                $value = $this->read($id,$pos);
                if (is_nan($value)) $value = null;

                $split_values[] = $value;
            }
            if ($time>=$start && $time<$end) {
                $data[] = array($time*1000,$split_values);
            }
            $date->modify($modify);
            $n++;
        }
        $this->close($id);
        return $data;
    }

    public function export($id,$start)
    {
        $id = (int) $id;
        $start = (int) $start;
        
        $feedname = $id.".dat";
        
        // If meta data file does not exist exit
        if (!$meta = $this->get_meta($id)) return false;

        // Save buffer before export
        if ($meta->bufferlen>0) {
            $this->save_buffer($id);
        }
        
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

// #### /\ Above are required methods

// #### \/ Below engine specific methods

// #### \/ Bellow are engine private methods 
    
    private function create_meta($id, $meta)
    {
        $feedname = $id . ".meta";
        $metafile = @fopen($this->dir.$feedname, 'wb');
        
        if (!$metafile) {
            $error = error_get_last();
            $msg = "could not write meta data file ".$error['message'];
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
    
    public function upload_fixed_interval($id,$start,$interval,$npoints)
    {
        $id = (int) $id;
        $start = (int) $start;
        $interval = (int) $interval;
        $npoints = (int) $npoints;
        
        // Faster direct block write method
        
        // Fetch data from post body and check length match
        $data = file_get_contents('php://input');
        if ($npoints!=(strlen($data) / 4.0)) {
            $this->log->warn("upload() data body does not match blocksize param id=$id");
            return false;
        }
        
        // Load feed meta to fetch start time and interval
        if (!$meta = $this->get_meta($id)) return false;
        
        // Save local buffer before import
        if ($meta->bufferlen>0) {
            $this->save_buffer($id);
        }
        
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
    
    public function upload_variable_interval($id,$npoints)
    {
        $id = (int) $id;
        $npoints = (int) $npoints;
        
        if (!$fh=fopen('php://input','r')) return false;
        
        for ($i=0; $i<$npoints; $i++) {
            $tmp = unpack("If",fread($fh,8));
            $time = $tmp[1];
            $value = $tmp[2];
            //print $time." ".$value."\n";
            //$this->post_bulk_prepare($id,$time,$value,null);
        }
        // $this->post_bulk_save();

        fclose($fh);
        
        return true;
    }

    /**
     * delete feed .dat, re-create blank .dat and .meta with same interval
     *
     * @param integer $id
     * @return boolean true == success
     */
    public function clear($id) {
        $id = (int)$id;
        $meta = $this->get_meta($id);
        if (!$meta) return false;
        $meta->start_time = 0;
        $datafilePath = $this->dir.$id.".dat";
        $f = @fopen($datafilePath, "r+");
        if (!$f) {
            $this->log->error("unable to open $datafilePath for reading");
            return array('success'=>false,'message'=>'Error opening data file');
        } else {
            ftruncate($f, 0);
            fclose($f);
        }        
        if ($this->redis) $this->redis->del("phpfina:buffer:$id");
            
        $this->create_meta($id, $meta); // create meta first to avoid $this->create() from creating new one
        $this->create($id,array('interval'=>$meta->interval));

        $this->log->info("Feed $id datapoints deleted");
        return array('success'=>true,'message'=>"Feed cleared successfully");
    }
    
    /**
     * clear out data from file before $start_time
     *
     * @param integer $id
     * @param integer $start_time new timestamp to start the feed data from
     * @return boolean
     */
    public function trim($id,$start_time) {
    
        $meta = $this->get_meta($id); // get .dat meta info
        
        // Save local buffer before trim
        if ($meta->bufferlen>0) {
            $this->save_buffer($id);
        }
        
        $bytesize = $meta->npoints * 4.0; // total .dat file size
        if($bytesize <= 0) return array('success'=>false,'message'=>'Empty data file, nothing to trim.'); // empty data file - nothing to trim
        if($start_time < $meta->start_time) return array('success'=>false,'message'=>'New start time out of range'); //new start_time out of range
        
        $start_bytes = ceil((($start_time - $meta->start_time) / $meta->interval) * 4.0); // number of seconds devided by interval
        $datFileName = $this->dir.$id.'.dat';
        // non php file handling
        // ----------------------
        // $tmpFileName = $this->dir.'temp-trim.tmp';
        // exec(sprintf("tail -c +%s %s > %s",$start_bytes, $datFileName, $tmpFileName),$exec['tail']); // save byte safe output of tail to temp file
        // exec(sprintf("cp %s %s", $tmpFileName, $datFileName),$exec['cat']);// overwrite original .dat file with temp file
        // exec(sprintf("rm %s", $tmpFileName),$exec['rm']);// remove the temp file
        // $writtenBytes = filesize($datFileName);

        $fh = @fopen($datFileName,'rb');
        if (!$fh){
            $this->log->error("unable to open $datFileName for reading");
            return array('success'=>false,'message'=>'Error opening data file');
        }
        fseek($fh,$start_bytes);
        $tmp = @fread($fh,$bytesize-$start_bytes);
        if (!$tmp){
            $this->log->error("Error reading $datFileName");
            return array('success'=>false,'message'=>'Error reading data file');
        }
        fclose($fh);
        
        $fh = @fopen($datFileName,'wb');
        if (!$fh){
            $this->log->error("unable to open $datFileName for writing");
            return array('success'=>false,'message'=>'Error writing to data file');
        }
        $writtenBytes = fwrite($fh,$tmp);
        fclose($fh);

        $this->log->info(".data file trimmed to $writtenBytes bytes");
        $meta->start_time = $start_time;
        $this->create_meta($id, $meta); // set the new start time in the feed's meta
        return array('success'=>true,'message'=>"$writtenBytes bytes written");
    }
    
    /**
     * Abstracted open, read and close methods
     *
     */
    public function open($id,$mode) {
        $this->meta[$id] = $this->get_meta($id);
        $this->fh[$id] = fopen($this->dir.$id.".dat", $mode);
        $this->pos[$id] = 0;
        return $this->meta[$id];
    }
    
    public function read($id,$pos) {
        // If in data range read from dat file
        if ($pos>=0 && $pos < $this->meta[$id]->buffer_start) {
            // Only seek if necessary
            if ($pos!=$this->pos[$id]) fseek($this->fh[$id],4*$pos);
            $tmp = unpack("f",fread($this->fh[$id],4));
            $this->pos[$id] = $pos+1;
            return $tmp[1];
        }
        // If in tmpfs data range read from tmp file
        if ($pos>=$this->meta[$id]->buffer_start && $pos < $this->meta[$id]->npoints) {
            $buffer_pos = $pos-$this->meta[$id]->buffer_start;
            $value = $this->redis->lrange("phpfina:buffer:$id",$buffer_pos,$buffer_pos)[0];
            if ($value=="NAN") $value = null; else $value = (float) $value;
            return $value;
        }
        return NAN;
    }
    
    private function read_range($id,$pos,$len=1) {
        $tmp = array();
        // Work out if we need to read from the redis buffer
        $from_tmp = $pos+$len-$this->meta[$id]->buffer_start;
        if ($from_tmp<0) $from_tmp = 0;
            
        // If in persisted data range read from dat file
        if ($pos>=0 && $pos < $this->meta[$id]->buffer_start) {
            // Only seek if necessary
            if ($pos!=$this->pos[$id]) fseek($this->fh[$id],4*$pos);
            $tmp = array_values(unpack("f*",fread($this->fh[$id],4*($len-$from_tmp))));
            $this->pos[$id] = $pos+($len-$from_tmp);
            $pos = $this->pos[$id];
        }
        // If in tmpfs data range read from tmp file
        if ($pos>=$this->meta[$id]->buffer_start && $pos < $this->meta[$id]->npoints) {
            $values = $this->redis->lrange("phpfina:buffer:$id",$pos-$this->meta[$id]->buffer_start,$from_tmp);
            for ($i=0; $i<count($values); $i++) {
                if ($values[$i]=='NAN') $values[$i] = null; else $values[$i] = (float) $values[$i];
            }
            $tmp = array_merge($tmp,$values);
            $this->pos[$id] = $pos+$from_tmp;
        }
        return $tmp;
    }
    
    public function close($id) {
        $this->meta[$id] = false;
        $this->pos[$id] = 0;
        fclose($this->fh[$id]);
    }

    
    /**
     * Abstracted buffer
     *
     */
    public function save_buffer($id) 
    {
        // 1. read contents of buffer
        $bufferlen = $this->redis->llen("phpfina:buffer:$id");
        if (!$bufferlen) return false;
        
        $buffer = "";
        $values = $this->redis->lrange("phpfina:buffer:$id",0,$bufferlen);
        for ($n=0; $n<$bufferlen; $n++) {
            $value = $values[$n];
            if ($value=='NAN') $value = NAN;
            $buffer .= pack("f",$value);
        }
        
        // 2. persist to disk
        $fh = fopen($this->dir.$id.".dat", "a");
        fwrite($fh, $buffer);
        fclose($fh);
        
        // 3. clear buffer
        $this->redis->del("phpfina:buffer:$id");
    }

    /**
     * Used for testing
     *
     */
    public function print_all($id) {  
        $this->open($id,"rb");
        $values = $this->read_range($id,0,$this->meta[$id]->npoints);

        for ($pos=0; $pos<$this->meta[$id]->npoints; $pos++) {
            $timestamp = $this->meta[$id]->start_time + ($this->meta[$id]->interval * $pos);
            print $pos." ".$timestamp." ".$values[$pos]."\n";
        }
        $this->close($id);
    }
     
}
