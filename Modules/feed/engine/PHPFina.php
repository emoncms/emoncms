<?php

// This timeseries engine implements:
// Fixed Interval No Averaging

class PHPFina
{
    private $dir = "/var/lib/phpfina/";

    /**
     * Constructor.
     *
     * @api
    */
    public function __construct()
    {

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
            $this->set_meta($id,$meta);
        }

        $feedname = "$id.meta";
        if (file_exists($this->dir.$feedname)) return true;
        return false;
    }


    /**
     * Adds a data point to the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function post($id,$timestamp,$value)
    {
        $id = (int) $id;
        $timestamp = (int) $timestamp;
        $value = (float) $value;

        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($id)) return false;

        // Calculate interval that this datapoint belongs too
        $timestamp = floor($timestamp / $meta->interval) * $meta->interval;

        // If this is a new feed (npoints == 0) then set the start time to the current datapoint
        if ($meta->npoints == 0) {
            $meta->start_time = $timestamp;
        }

        if ($timestamp < $meta->start_time) {
            return false; // in the past
        }	

        // Calculate position in base data file of datapoint
        $pos = floor(($timestamp - $meta->start_time) / $meta->interval);

        $last_pos = $meta->npoints - 1;

        if ($pos<=$last_pos) {
            return false; // updating of datapoints to be made available via update function
        }

        $fh = fopen($this->dir.$meta->id.".dat", 'c+');
        
        // Write padding
        $padding = ($pos - $last_pos)-1;
        if ($padding>0) $this->write_padding($fh,$meta->npoints,$padding);
        
        // Write new datapoint
	    fseek($fh,4*$pos);
        if (!is_nan($value)) fwrite($fh,pack("f",$value)); else fwrite($fh,pack("f",NAN));
        
        // Close file
        fclose($fh);
        
        $meta->npoints = $pos + 1;
        $this->set_meta($id,$meta);
        
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
        $id = (int) $id;
        $timestamp = (int) $timestamp;
        $value = (float) $value;

        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($id)) return false;

        // Calculate interval that this datapoint belongs too
        $timestamp = floor($timestamp / $meta->interval) * $meta->interval;

        // If this is a new feed (npoints == 0) then set the start time to the current datapoint
        if ($meta->npoints == 0) {
            $meta->start_time = $timestamp;
        }

        if ($timestamp < $meta->start_time) {
            return false; // in the past
        }	

        // Calculate position in base data file of datapoint
        $pos = floor(($timestamp - $meta->start_time) / $meta->interval);

        $last_pos = $meta->npoints - 1;

        $fh = fopen($this->dir.$meta->id.".dat", 'c+');
        
        // Write padding
        $padding = ($pos - $last_pos)-1;
        if ($padding>0) $this->write_padding($fh,$meta->npoints,$padding);
        
        // Write new datapoint
	    fseek($fh,4*$pos);
        if (!is_nan($value)) fwrite($fh,pack("f",$value)); else fwrite($fh,pack("f",NAN));
        
        // Close file
        fclose($fh);
        
        if (($pos+1)>$meta->npoints) {
          $meta->npoints = $pos + 1;
          $this->set_meta($id,$meta);
        }
        
        return $value;
    }

    /**
     * Return the data for the given timerange
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $dp The number of data points to return (used by some engines)
    */
    public function get_data($feedid,$start,$end,$dp)
    {
        $feedid = intval($feedid);
        $start = intval($start/1000);
        $end = intval($end/1000);
        $dp = 1000;

        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($feedid)) return false;

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
            $startpos = 0;
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
        
        if (!file_exists($this->dir.$feedname)) return false;
        
        $meta = new stdClass();
        $metafile = fopen($this->dir.$feedname, 'rb');

        $tmp = unpack("I",fread($metafile,4)); 
        $meta->id = $tmp[1];
        
        $tmp = unpack("I",fread($metafile,4)); 
        $meta->npoints = $tmp[1];
        
        $tmp = unpack("I",fread($metafile,4)); 
        $meta->interval = $tmp[1];
        
        $tmp = unpack("I",fread($metafile,4)); 
        $meta->start_time = $tmp[1];
        
        fclose($metafile);
        
        return $meta;
    }
    
    private function set_meta($id,$meta)
    {
        $id = (int) $id;
        $feedname = "$id.meta";
    
        $metafile = fopen($this->dir.$feedname, 'wb');
        fwrite($metafile,pack("I",$meta->id));
        fwrite($metafile,pack("I",$meta->npoints));
        fwrite($metafile,pack("I",$meta->interval));
        fwrite($metafile,pack("I",$meta->start_time)); 
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

}
