<?php
// This timeseries engine implements:
// Fixed Interval No Averaging

// engine_methods interface in shared_helper.php
include_once dirname(__FILE__) . '/shared_helper.php';

class PHPFina implements engine_methods
{
    private $dir = "/var/lib/phpfina/";
    private $log;
    private $writebuffer = array();
    private $lastvalue_cache = array();
    private $maxpadding = 3153600; // 1 year @ 10s

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
        $feedname = "$feedid.meta";
        if (!file_exists($this->dir.$feedname)) {
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
                $error = error_get_last();
                $msg = "could not create meta data file ".$error['message'];
                $this->log->error("create() ".$msg);
                return $msg;
            }
            fclose($fh);
            $this->log->info("create() feedid=$feedid");
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
     * @param integer $feedid The id of the feed to be created
    */
    public function delete($feedid)
    {
        $feedid = (int)$feedid;
        if (!$meta = $this->get_meta($feedid)) return false;
        unlink($this->dir.$feedid.".meta");
        unlink($this->dir.$feedid.".dat");
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
        
        $meta->npoints = $this->get_npoints($feedid);

        if ($meta->start_time>0 && $meta->npoints==0) {
            $this->log->warn("PHPFina:get_meta start_time already defined but npoints is 0");
            return false;
        }
        
        return $meta;
    }

    /**
     * Returns engine occupied size in bytes
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_feed_size($feedid)
    {
        if (!$meta = $this->get_meta($feedid)) return false;
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
        
        if ($padding>$this->maxpadding) {
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
            }
            fseek($fh,4*$meta->npoints);
            fwrite($fh,$buffer);
            
        }
        
        // Write new datapoint
        fseek($fh,4*$pos);
        if (!is_nan($value)) fwrite($fh,pack("f",$value)); else fwrite($fh,pack("f",NAN));
        
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
     * scale a portion of a feed
     * added by Alexandre CUER - january 2019 
     *
     * @param integer $feedid The id of the feed
     * @param integer $start unix time stamp in ms of the start of the data range
     * @param integer $end unix time stamp in ms of the end of the data rage
     * @param float $scale : numeric value for the scaling 
    */
    public function scalerange($id,$start,$end,$scale){
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
        
        //create a buffer with the processed values
        $buffer="";
        if ($scale==0 || $scale==NAN) {
             $this->log->warn("scale is $scale");
             for($i=1;$i<=$nbwrites;$i++) $buffer.=pack("f",$scale);
             fseek($fh,$pos_start);
        } else {
             //fetch the values to process
             fseek($fh,$pos_start);
             $values=unpack("f$nbwrites",fread($fh,4*$nbwrites));
             //print_r($values);
             for($i=1;$i<=$nbwrites;$i++) {
                 if ($scale=="abs(x)") {$val=abs($values[$i]);}
                 else {$val=$values[$i]*$scale;}
                 $buffer.=pack("f",$val);
             }
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
     * Return the data for the given timerange - cf shared_helper.php
     *
    */
    public function get_value($name,$time)
    {        
        $time = (int) $time;
        
        if (!$meta = $this->get_meta($name)) return array('success'=>false, 'message'=>"Error reading meta data feedid=$name");
        $meta->npoints = $this->get_npoints($name);
        
        $fh = fopen($this->dir.$name.".dat", 'rb');
        
        $value = null;
        $pos = round(($time - $meta->start_time) / $meta->interval);
        if ($pos>=0 && $pos < $meta->npoints) {
            fseek($fh,$pos*4);
            $val = unpack("f",fread($fh,4));
            if (!is_nan($val[1])) $value = (float) $val[1];
        }
        
        return $value;
    }

    /**
     * Return the data for the given timerange - cf shared_helper.php
     *
    */
    public function get_data($name,$start,$end,$interval,$skipmissing,$limitinterval)
    {
        global $settings;
        
        $skipmissing = (int) $skipmissing;
        $limitinterval = (int) $limitinterval;
        $start = intval($start/1000);
        $end = intval($end/1000);
        $interval= (int) $interval;

        // Minimum interval
        if ($interval<1) $interval = 1;
        // End must be larger than start
        if ($end<=$start) return array('success'=>false, 'message'=>"request end time before start time");
        // Maximum request size
        $req_dp = round(($end-$start) / $interval);
        if ($req_dp > $settings["feed"]["max_datapoints"]) return array('success'=>false, 'message'=>"Request datapoint limit reached (" . $settings["feed"]["max_datapoints"] . "), increase request interval or time range, requested datapoints = $req_dp");
        
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
        $increment="+1 day";
        if ($mode=="weekly") { $date->modify("this monday"); $increment="+1 week"; }
        if ($mode=="monthly") { $date->modify("first day of this month"); $increment="+1 month"; }
        
        $n = 0;
        while($n<10000) // max iterations
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
            if ($time>=$start) {
                $data[] = array($time*1000,$value);
            }

            $date->modify($increment);
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
        if (count($split)>48) return false;

        // If meta data file does not exist exit
        if (!$meta = $this->get_meta($id)) return array('success'=>false, 'message'=>"Error reading meta data feedid=$name");
        $meta->npoints = $this->get_npoints($id);

        $data = array();

	/* Open file */
        $fh = fopen($this->dir.$id.".dat", 'rb');

        $date = new DateTime();
        if ($timezone===0) $timezone = "UTC";
        $date->setTimezone(new DateTimeZone($timezone));
        $date->setTimestamp($start);
        $date->modify("midnight");
        if ($mode=="weekly") $date->modify("this monday");
        if ($mode=="monthly") $date->modify("first day of this month");

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
        global $settings;

        require_once "Modules/feed/engine/shared_helper.php";
        $helperclass = new SharedHelper();

        $feedid = (int) $feedid;
        $start = (int) $start;
        $end = (int) $end;
        $outinterval = (int) $outinterval;

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
            if (!is_nan($val[1])) fwrite($exportfh, $timenew.$settings["feed"]["csv_field_separator"].number_format($val[1],$settings["feed"]["csv_decimal_places"],$settings["feed"]["csv_decimal_place_separator"],'')."\n");

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
        if ($meta->start_time==0) {
            if ($meta->npoints == 0) {
                $meta->start_time = $timestamp;
                $this->create_meta($feedid,$meta);
                $this->log->info("post_bulk_prepare() start_time=0 setting meta start_time=$timestamp");
            } else {
                $this->log->error("post_bulk_prepare() start_time=0, npoints>0");
                return false;
            }
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
            
            if ($npadding>$this->maxpadding) {
                $this->log->warn("post() padding max block size exeeded id=$feedid, $npadding dp");
                return false;
            }
            
            if (!isset($this->writebuffer[$feedid])) {
                $this->writebuffer[$feedid] = "";    
            }
            
            if ($npadding>0) {
                $padding_value = NAN;
                if ($padding_mode!=null) {
                    if (!isset($this->lastvalue_cache[$feedid])) { // Not set, cache it from file data
                        $lastvalue = $this->lastvalue($feedid);
                        $this->lastvalue_cache[$feedid] = $lastvalue['value'];
                    }
                    $div = ($value - $this->lastvalue_cache[$feedid]) / ($npadding+1);
                    $padding_value = $this->lastvalue_cache[$feedid];
                }
                
                for ($n=0; $n<$npadding; $n++)
                {
                    if ($padding_mode!=null) $padding_value += $div; 
                    $this->writebuffer[$feedid] .= pack("f",$padding_value);
                    //$this->log->info("post_bulk_prepare() ##### paddings ". ((4*$meta->npoints) + (4*$n)) ." $n $padding_mode $padding_value");
                }
            }
            
            $this->writebuffer[$feedid] .= pack("f",$value);
            $this->lastvalue_cache[$feedid] = $value; // cache last value
            
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
        global $settings;

        $start = intval($start/1000);
        $end = intval($end/1000);
        $interval= (int) $interval;
        
        // Minimum interval
        if ($interval<1) $interval = 1;
        // Maximum request size
        $req_dp = round(($end-$start) / $interval);
        if ($req_dp > $settings["feed"]["max_datapoints"]) return array('success'=>false, 'message'=>"Request datapoint limit reached (" . $settings["feed"]["max_datapoints"] . "), increase request interval or time range, requested datapoints = $req_dp");
        
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
        //    return $this->get_data($id,$start*1000,$end*1000,$interval,0,0);
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
                
                $len = strlen($s);
                $total_read_count += $len / 4.0;
                
                if ($len!=4*$dp_to_read) break;
                
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
            
            if ($time>=$start) {
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
        
        // clearstatcache($idir.$id.".dat");
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
                // clearstatcache($odir.$id.".dat");
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

    /**
     * delete feed .dat, re-create blank .dat and .meta with same interval
     *
     * @param integer $feedid
     * @return boolean true == success
     */
    public function clear($feedid) {
        $feedid = (int)$feedid;
        $meta = $this->get_meta($feedid);
        if (!$meta) return false;
        $meta->start_time = 0;
        $datafilePath = $this->dir.$feedid.".dat";
        $f = @fopen($datafilePath, "r+");
        if (!$f) {
            $this->log->error("unable to open $datafilePath for reading");
            return array('success'=>false,'message'=>'Error opening data file');
        } else {
            ftruncate($f, 0);
            fclose($f);
        }
        if (isset($this->writebuffer[$feedid])) $this->writebuffer[$feedid] = "";
            
        $this->create_meta($feedid, $meta); // create meta first to avoid $this->create() from creating new one
        $this->create($feedid,array('interval'=>$meta->interval));

        $this->log->info("Feed $feedid datapoints deleted");
        return array('success'=>true,'message'=>"Feed cleared successfully");
    }
    
    /**
     * clear out data from file before $start_time
     *
     * @param integer $feedid
     * @param integer $start_time new timestamp to start the feed data from
     * @return boolean
     */
    public function trim($feedid,$start_time) {
        $meta = $this->get_meta($feedid); // get .dat meta info
        $bytesize = $meta->npoints * 4.0; // total .dat file size
        if($bytesize <= 0) return array('success'=>false,'message'=>'Empty data file, nothing to trim.'); // empty data file - nothing to trim
        if($start_time < $meta->start_time) return array('success'=>false,'message'=>'New start time out of range'); //new start_time out of range
        
        $start_bytes = ceil((($start_time - $meta->start_time) / $meta->interval) * 4.0); // number of seconds devided by interval
        $datFileName = $this->dir.$feedid.'.dat';
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
        if (isset($this->writebuffer[$feedid])) $this->writebuffer[$feedid] = "";
        $meta->start_time = $start_time;
        $this->create_meta($feedid, $meta); // set the new start time in the feed's meta
        return array('success'=>true,'message'=>"$writtenBytes bytes written");
    }

}
