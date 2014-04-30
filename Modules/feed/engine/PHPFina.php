<?php

// This timeseries engine implements:
// Fixed Interval No Averaging

class PHPFina
{
    private $dir = "/var/lib/phpfina/";
    private $log;
    
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
     * @param integer $feedid The id of the feed to be created
    */
    public function create($id,$options)
    {
        $interval = (int) $options['interval'];
        if ($interval<5) $interval = 5;
        
        //if (!$this->checkpermissions()) return false;
        
        // Check to ensure we dont overwrite an existing feed
        if (!$meta = $this->get_meta($id))
        {
            // Set initial feed meta data
            $meta = new stdClass();
            $meta->id = $id;
            $meta->npoints = 0;
            $meta->interval = $interval;
            $meta->start_time = 0;

        
            // Save meta data
            $this->create_meta($id,$meta);
            
            $fh = fopen($this->dir.$meta->id.".dat", 'c+');
            
            if (!$fh) {
                $this->log->warn("PHPFina:create could not create data file id=$id");
                return false;
            }
            fclose($fh);
        }
        
        $feedname = "$id.meta";
        if (file_exists($this->dir.$feedname)) {
            return true;
        } else {
            $this->log->warn("PHPFina:create failed to create feed id=$id");
            return false;
        }
    }
    
    //private function checkpermissions()
    //{
    //    $uid = fileowner( $this->dir );
    //    $uinfo = posix_getpwuid( $uid ); 
    //    
    //    if ($uinfo['name']=="www-data") return true; else return false;
    //}


    /**
     * Adds a data point to the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function post($id,$timestamp,$value)
    {
        $this->log->info("PHPFina:post post id=$id timestamp=$timestamp value=$value");
        
        $id = (int) $id;
        $timestamp = (int) $timestamp;
        $value = (float) $value;
        
        $now = time();
        $start = $now-(3600*24*365*5); // 5 years in past
        $end = $now+(3600*48);         // 48 hours in future
        
        if ($timestamp<$start || $timestamp>$end) {
            $this->log->warn("PHPFina:post timestamp out of range");
            return false;
        }
        
        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($id)) {
            $this->log->warn("PHPFina:post failed to fetch meta id=$id");
            return false;
        }

        // Calculate interval that this datapoint belongs too
        $timestamp = floor($timestamp / $meta->interval) * $meta->interval;

        // If this is a new feed (npoints == 0) then set the start time to the current datapoint
        if ($meta->npoints == 0) {
            $meta->start_time = $timestamp;
            $this->create_meta($id,$meta);
        }

        if ($timestamp < $meta->start_time) {
            $this->log->warn("PHPFina:post timestamp older than feed start time id=$id");
            return false; // in the past
        }	

        // Calculate position in base data file of datapoint
        $pos = floor(($timestamp - $meta->start_time) / $meta->interval);

        $last_pos = $meta->npoints - 1;

        // if ($pos<=$last_pos) {
        // return false;
        // }

        $fh = fopen($this->dir.$meta->id.".dat", 'c+');
        if (!$fh) {
            $this->log->warn("PHPFina:post could not open data file id=$id");
            return false;
        }
        
        // Write padding
        $padding = ($pos - $last_pos)-1;
        
        if ($padding>0) {
            if ($this->write_padding($fh,$meta->npoints,$padding)===false)
            {
                // Npadding returned false = max block size was exeeded
                
                $this->log->warn("PHPFina:post padding max block size exeeded id=$id");
                return false;
            }
        } else {
            //$this->log->warn("PHPFINA padding less than 0 id=$id");
            //return false;
        }
        
        // Write new datapoint
	    fseek($fh,4*$pos);
        if (!is_nan($value)) fwrite($fh,pack("f",$value)); else fwrite($fh,pack("f",NAN));
        
        // Close file
        fclose($fh);
        
        $meta->npoints = $pos + 1;
        $this->set_npoints($id,$meta);
        
        return $value;
    }
    
    /**
     * Updates a data point in the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function update($id,$timestamp,$value)
    {
        return $this->post($id,$timestamp,$value);
    }

    /**
     * Return the data for the given timerange
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $dp The number of data points to return (used by some engines)
    */
    public function get_data($feedid,$start,$end,$outinterval)
    {
        $feedid = intval($feedid);
        $start = intval($start/1000);
        $end = intval($end/1000);
        $outinterval= (int) $outinterval;
        
        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($feedid)) return false;
        
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

        // The datapoints are selected within a loop that runs until we reach a
        // datapoint that is beyond the end of our query range
        $fh = fopen($this->dir.$meta->id.".dat", 'rb');
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

            // add to the data array if its not a nan value
            if (!is_nan($val[1])) $data[] = array($time*1000,$val[1]);

            $i++;
        }
        return $data;
    }

    /**
     * Get the last value from a feed
     *
     * @param integer $feedid The id of the feed
    */
    public function lastvalue($id)
    {
        $id = (int) $id;
        
        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($id)) return false;
        
        if ($meta->npoints>0)
        {
            $fh = fopen($this->dir.$meta->id.".dat", 'rb');
            $size = $meta->npoints*4;
            fseek($fh,$size-4);
            $d = fread($fh,4);
            fclose($fh);

            $val = unpack("f",$d);
            $time = date("Y-n-j H:i:s", $meta->start_time + $meta->interval * $meta->npoints);
            
            return array('time'=>$time, 'value'=>$val[1]);
        }
        else
        {
            return array('time'=>0, 'value'=>0);
        }
    }
    
    public function export($feedid,$start)
    {
    
    }
    
    public function delete($id)
    {
        if (!$meta = $this->get_meta($id)) return false;
        unlink($this->dir.$meta->id.".meta");
        unlink($this->dir.$meta->id.".dat");
    }
    
    public function get_feed_size($id)
    {
        if (!$meta = $this->get_meta($id)) return false;
        return (filesize($this->dir.$meta->id.".meta") + filesize($this->dir.$meta->id.".dat"));
    }
    

    public function get_meta($id)
    {
        $id = (int) $id;
        $feedname = "$id.meta";
        
        if (!file_exists($this->dir.$feedname)) {
            $this->log->warn("PHPFina:get_meta meta file does not exist id=$id");
            return false;
        }
        
        $meta = new stdClass();
        $metafile = fopen($this->dir.$feedname, 'rb');

        $tmp = unpack("I",fread($metafile,4)); 
        $meta->id = $tmp[1];
        
        // Legacy npoints
        $tmp = unpack("I",fread($metafile,4));
        $legacy_npoints = $tmp[1];
        
        $tmp = unpack("I",fread($metafile,4)); 
        $meta->interval = $tmp[1];
        
        $tmp = unpack("I",fread($metafile,4)); 
        $meta->start_time = $tmp[1];
        
        fclose($metafile);
        
        // Double verification of npoints
        $filesize = filesize($this->dir.$meta->id.".dat");
        $filesize_npoints = $filesize / 4.0;
        
        if ($filesize_npoints!=(int)$filesize_npoints) {
            // filesize result is corrupt
            
            $this->log->warn("PHPFina:get_meta php filesize() is not integer multiple of 4 bytes id=$id");
            return false;
        }
        
        if (!file_exists($this->dir."$id.npoints")) {
            // 1) Transitioning to new system that saves npoints in a seperate file
            if ($legacy_npoints!=$filesize_npoints)
            {
                // discrepancy between legacy npoints and filesize npoints, they should be the same at this point
                $this->log->warn("PHPFina:get_meta legacy npoints does not match filesize npoints id=$id");
                return false;
            } else {
                $meta->npoints = $filesize_npoints;
            }
            
        } else {
            $metafile = fopen($this->dir."$id.npoints", 'rb');
            $tmp = unpack("I",fread($metafile,4));
            $npoints = $tmp[1];
            fclose($metafile);
            $meta->npoints = $npoints;
        }
        
        if ($npoints!=$filesize_npoints)
        {
            // filesize npoints and npoints from the .npoints meta file should be the same
            // if there is a discrepancy then this suggests corrupt data.
            $this->log->warn("PHPFina:get_meta meta file npoints ($npoints) does not match filesize npoints ($filesize_npoints) id=$id");
            return false;
            
            // $meta->npoints = $filesize_npoints;
        }
  
        return $meta;
    }
    
    private function create_meta($id,$meta)
    {
        $id = (int) $id;
        
        $feedname = "$id.meta";
        $metafile = fopen($this->dir.$feedname, 'wb');
        
        if (!$metafile) {
            $this->log->warn("PHPFina:create_meta could not open meta data file id=".$meta->id);
            return false;
        }
        
        if (!flock($metafile, LOCK_EX)) {
            $this->log->warn("PHPFina:create_meta meta file id=".$meta->id." is locked by another process");
            fclose($metafile);
            return false;
        }
        
        fwrite($metafile,pack("I",$meta->id));
        // Legacy npoints, npoints moved to seperate file
        fwrite($metafile,pack("I",0)); 
        fwrite($metafile,pack("I",$meta->interval));
        fwrite($metafile,pack("I",$meta->start_time)); 
        fclose($metafile);
        
        $this->set_npoints($id,$meta);
    }
    
    private function set_npoints($id,$meta)
    {
        $id = (int) $id;
        
        $feedname = "$id.npoints";    
        $metafile = fopen($this->dir.$feedname, 'wb');
        
        if (!$metafile) {
            $this->log->warn("PHPFina:set_npoints could not open meta data file id=".$meta->id);
            return false;
        }
        
        if (!flock($metafile, LOCK_EX)) {
            $this->log->warn("PHPFina:set_npoints meta file id=".$meta->id." is locked by another process");
            fclose($metafile);
            return false;
        }
        
        fwrite($metafile,pack("I",$meta->npoints));
        fclose($metafile);
    }
    
    private function write_padding($fh,$npoints,$npadding)
    {
        $tsdb_max_padding_block = 1024 * 1024;
        
        // Padding amount too large
        if ($npadding>$tsdb_max_padding_block*2) {
            return false;
        }

        // Maximum points per block
        $pointsperblock = $tsdb_max_padding_block / 4; // 262144

        // If needed is less than max set to padding needed:
        if ($npadding < $pointsperblock) $pointsperblock = $npadding;

        // Fill padding buffer
        $buf = '';
        for ($n = 0; $n < $pointsperblock; $n++) {
            $buf .= pack("f",NAN);
        }

        fseek($fh,4*$npoints);

        do {
            if ($npadding < $pointsperblock) 
            { 
                $pointsperblock = $npadding;
                $buf = ''; 
                for ($n = 0; $n < $pointsperblock; $n++) {
                    $buf .= pack("f",NAN);
                }
            }
            
            fwrite($fh, $buf);
            $npadding -= $pointsperblock;
        } while ($npadding); 
    }
    
    public function csv_export($feedid,$start,$end,$outinterval)
    {
        $feedid = intval($feedid);
        $start = intval($start);
        $end = intval($end);
        $outinterval= (int) $outinterval;

        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($feedid)) return false;
        
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
        $fh = fopen($this->dir.$meta->id.".dat", 'rb');
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

            // add to the data array if its not a nan value
            if (!is_nan($val[1])) fwrite($exportfh, $time.",".number_format($val[1],2)."\n");

            $i++;
        }
        fclose($exportfh);
        exit;
    }

}
