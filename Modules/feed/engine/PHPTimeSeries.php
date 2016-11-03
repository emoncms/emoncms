<?php

class PHPTimeSeries
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
            $msg = "could not write data file " . error_get_last()['message'];
            $this->log->error("create() ".$msg);
            return $msg;
        }

        if (!flock($fh, LOCK_EX)) {
            $msg = "data file '".$this->dir.$feedname."' is locked by another process";
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
        $meta = new stdClass();
        $meta->id = $feedid;
        $meta->start_time = 0; // tbd
        $meta->nlayers = 1;
        $meta->npoints = -1;
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
        $this->log->info("post() feedid=$feedid time=$time value=$value");
        
        // Get last value
        $fh = @fopen($this->dir."feed_$feedid.MYD", 'rb');
        if (!$fh) {
            $this->log->warn("post() could not open data file feedid=$feedid");
            return false;
        }
        
        clearstatcache($this->dir."feed_$feedid.MYD");
        $filesize = filesize($this->dir."feed_$feedid.MYD");

        $csize = round($filesize / 9.0, 0, PHP_ROUND_HALF_DOWN) *9.0;
        if ($csize!=$filesize) {
        
            $this->log->warn("post() filesize not integer multiple of 9 bytes, correcting feedid=$feedid");
            // correct corrupt data
            fclose($fh);

            // extend file by required number of bytes
            if (!$fh = $this->fopendata($this->dir."feed_$feedid.MYD", 'wb')) return false;
            fseek($fh,$csize);
            fwrite($fh, pack("CIf",249,$time,$value));

            fclose($fh);
            return $value;
        }

        // If there is data then read last value
        if ($filesize>=9) {

            // read the last value appended to the file
            fseek($fh,$filesize-9);
            $d = fread($fh,9);
            $array = unpack("x/Itime/fvalue",$d);

            // check if new datapoint is in the future: append if so
            if ($time>$array['time'])
            {
                // append
                fclose($fh);
                if (!$fh = $this->fopendata($this->dir."feed_$feedid.MYD", 'a')) return false;
            
                fwrite($fh, pack("CIf",249,$time,$value));
                fclose($fh);
            }
            else
            {
                // if its not in the future then to update the feed
                // the datapoint needs to exist with the given time
                // - search for the datapoint
                // - if it exits update
                $pos = $this->binarysearch_exact($fh,$time,$filesize);

                if ($pos!=-1)
                {
                    fclose($fh);
                    if (!$fh = $this->fopendata($this->dir."feed_$feedid.MYD", 'c+')) return false;
                    fseek($fh,$pos);
                    fwrite($fh, pack("CIf",249,$time,$value));
                    fclose($fh);
                }
            }
        }
        else
        {
            // If theres no data in the file then we just append a first datapoint
            // append
            fclose($fh);
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
     * Return the data for the given timerange
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $interval The number os seconds for each data point to return (used by some engines)
     * @param integer $skipmissing Skip null values from returned data (used by some engines)
     * @param integer $limitinterval Limit datapoints returned to this value (used by some engines)
    */
    public function get_data($feedid,$start,$end,$interval,$skipmissing,$limitinterval)
    {
        $start = intval($start/1000);
        $end = intval($end/1000);
        $interval= (int) $interval;

        // Minimum interval
        if ($interval<1) $interval = 1;
        // Maximum request size
        $req_dp = round(($end-$start) / $interval);
        if ($req_dp>8928) return array("success"=>false, "message"=>"request datapoint limit reached (8928), increase request interval or time range, requested datapoints = $req_dp");
        
        $fh = fopen($this->dir."feed_$feedid.MYD", 'rb');
        $filesize = filesize($this->dir."feed_$feedid.MYD");

        $data = array();
        $time = 0; $i = 0;
        $atime = 0;

        while ($time<=$end)
        {
            $time = $start + ($interval * $i);
            $pos = $this->binarysearch($fh,$time,$filesize);
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

            $i++;
        }

        return $data;
    }
    
    public function get_data_DMY($id,$start,$end,$mode,$timezone) 
    {
        $start = intval($start/1000);
        $end = intval($end/1000);
        
        $data = array();
        
        $date = new DateTime();
        if ($timezone===0) $timezone = "UTC";
        $date->setTimezone(new DateTimeZone($timezone));
        $date->setTimestamp($start);
        $date->modify("midnight");
        $date->modify("+1 day");

        $fh = fopen($this->dir."feed_$id.MYD", 'rb');
        $filesize = filesize($this->dir."feed_$id.MYD");

        $n = 0;
        $array = array("time"=>0, "value"=>0);
        while($n<10000) // max itterations
        {
            $time = $date->getTimestamp();
            if ($time>$end) break;
            
            $pos = $this->binarysearch($fh,$time,$filesize);
            fseek($fh,$pos);
            $d = fread($fh,9);
            
            $lastarray = $array;
            $array = unpack("x/Itime/fvalue",$d);
            
            if ($array['time']!=$lastarray['time']) {
                $data[] = array($array['time']*1000,$array['value']);
            }
            $date->modify("+1 day");
            $n++;
        }
        
        fclose($fh);
        
        return $data;
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

    public function csv_export($feedid,$start,$end,$outinterval,$usertimezone)
    {
        global $csv_decimal_places, $csv_decimal_place_separator, $csv_field_separator;

        require_once "Modules/feed/engine/shared_helper.php";
        $helperclass = new SharedHelper();

        $feedid = (int) $feedid;
        $start = (int) $start;
        $end = (int) $end;
        $outinterval = (int) $outinterval;
        
        if ($outinterval<1) $outinterval = 1;
        $dp = ceil(($end - $start) / $outinterval);
        $end = $start + ($dp * $outinterval);
        if ($dp<1) return false;

        $fh = fopen($this->dir."feed_$feedid.MYD", 'rb');
        $filesize = filesize($this->dir."feed_$feedid.MYD");

        $pos = $this->binarysearch($fh,$start,$filesize);

        $interval = ($end - $start) / $dp;

        // Ensure that interval request is less than 1
        // adjust number of datapoints to request if $interval = 1;
        if ($interval<1) {
            $interval = 1;
            $dp = ($end - $start) / $interval;
        }

        $data = array();

        $time = 0;
        
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

        for ($i=0; $i<$dp; $i++)
        {
            $pos = $this->binarysearch($fh,$start+($i*$interval),$filesize);

            fseek($fh,$pos);

            // Read the datapoint at this position
            $d = fread($fh,9);

            // Itime = unsigned integer (I) assign to 'time'
            // fvalue = float (f) assign to 'value'
            $array = unpack("x/Itime/fvalue",$d);

            $last_time = $time;
            $time = $array['time'];
            $timenew = $helperclass->getTimeZoneFormated($time,$usertimezone);
            // $last_time = 0 only occur in the first run
            if (($time!=$last_time && $time>$last_time) || $last_time==0) {
                fwrite($exportfh, $timenew.$csv_field_separator.number_format($array['value'],$csv_decimal_places,$csv_decimal_place_separator,'')."\n");
            }
        }
        fclose($exportfh);
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



    private function binarysearch($fh,$time,$filesize)
    {
        // Binary search works by finding the file midpoint and then asking if
        // the datapoint we want is in the first half or the second half
        // it then finds the mid point of the half it was in and asks which half
        // of this new range its in, until it narrows down on the value.
        // This approach usuall finds the datapoint you want in around 20
        // itterations compared to the brute force method which may need to
        // go through the whole file that may be millions of lines to find a
        // datapoint.
        $start = 0; $end = $filesize-9;

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

}
