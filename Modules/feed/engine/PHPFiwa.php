<?php

// This timeseries engine implements:
// Fixed Interval With Averaging

class PHPFiwa
{
    private $dir = "/var/lib/phpfiwa/";

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
            $meta->start_time = 0;
            $meta->nlayers = 4;
            $meta->npoints = array(0,0,0,0);
            $meta->interval = array(5,60,3600,86400);

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
        
        $layer = 0;

        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($id)) return false;

        // Calculate interval that this datapoint belongs too
        $timestamp = floor($timestamp / $meta->interval[$layer]) * $meta->interval[$layer];

        // If this is a new feed (npoints == 0) then set the start time to the current datapoint
        if ($meta->npoints[0] == 0) {
            $meta->start_time = $timestamp;
        }

        if ($timestamp < $meta->start_time) {
            return false; // in the past
        }	

        // Calculate position in base data file of datapoint
        $point = floor(($timestamp - $meta->start_time) / $meta->interval[$layer]);

        $last_point = $meta->npoints[0] - 1;

        if ($point<=$last_point) {
             return false; // updating of datapoints to be made available via update function
        }
        
        $result = $this->update_layer($meta,$layer,$point,$timestamp,$value);
        
        if ($result!=false)
        {
            error_log(json_encode($result));
            $this->set_meta($id,$result);
        }
    }
    
    private function update_layer($meta,$layer,$point,$timestamp,$value)
    {
        $fh = fopen($this->dir.$meta->id."_$layer.dat", 'c+');
        
        // 1) Write padding
        $last_point = $meta->npoints[$layer] - 1;
        $padding = ($point - $last_point)-1;
        if ($padding>0) $this->write_padding($fh,$meta->npoints[$layer],$padding);
        
        // 2) Write new datapoint
	    fseek($fh,4*$point);
        if (!is_nan($value)) fwrite($fh,pack("f",$value)); else fwrite($fh,pack("f",NAN));
        
        if ($point >= $meta->npoints[$layer])
        {
          $meta->npoints[$layer] = $point + 1;
        }
        // fclose($fh);
        
        // 3) Averaging
        $layer ++;

        if ($layer<$meta->nlayers)
        {        
            $start_time_avl = floor($meta->start_time / $meta->interval[$layer]) * $meta->interval[$layer];
            $timestamp_avl = floor($timestamp / $meta->interval[$layer]) * $meta->interval[$layer];
            $point_avl = ($timestamp_avl-$start_time_avl) / $meta->interval[$layer];
            $point_in_avl = ($timestamp - $timestamp_avl) / $meta->interval[$layer-1];
           
            $first_point = $point - $point_in_avl;
            
            if ($first_point<0) $first_point = 0;
            
            // Read in points
            fseek($fh, 4*$first_point);
            $d = fread($fh, 4 * ($point_in_avl+1));
            $count = strlen($d)/4;
            $d = unpack("f*",$d);
            fclose($fh);
        
            // Calculate average of points
            $sum_count = 0;
            $sum = 0.0;

            $i=0;
            while ($count--) {
                $i++;
                if (is_nan($d[$i])) continue;   // Skip unknown values
                $sum += $d[$i];                 // Summing
                $sum_count ++;
            }

            if ($sum_count>0) {
                $average = $sum / $sum_count;
            } else {
                $average = NAN;
            }
            
            $meta = $this->update_layer($meta,$layer,$point_avl,$timestamp_avl,$average);
            
            error_log($point_avl." ".$first_point."->".$point_in_avl." ($point) $average");
            
        }
        
        return $meta;
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
    
    }

    /**
     * Return the data for the given timerange
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $dp The number of data points to return (used by some engines)
    */
    public function get_data_basic($feedid,$start,$end,$dp)
    {
        $feedid = intval($feedid);
        $start = intval($start/1000);
        $end = intval($end/1000);
        $dp = 800;
        
        $layer = 0;

        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($feedid)) return false;

        // The number of datapoints in the query range:
        $dp_in_range = ceil(($end - $start) / $meta->interval[$layer]);
        
        // Cant return more datapoints than exists in bottom layer
        if ($dp>$dp_in_range) $dp = $dp_in_range;
        
        // Find out the closest layer to the range we have selected
        $dpratio = $dp_in_range / $dp;
        if ($dpratio > ($meta->interval[1] / $meta->interval[0])) $layer = 1;   
        if ($dpratio > ($meta->interval[2] / $meta->interval[0])) $layer = 2;
        if ($dpratio > ($meta->interval[3] / $meta->interval[0])) $layer = 3;
        
        $dp_in_range = ceil(($end - $start) / $meta->interval[$layer]);
        error_log($dp_in_range." ".$dp." ".($dp_in_range/$dp));
                
        $start_time_avl = floor($meta->start_time / $meta->interval[$layer]) * $meta->interval[$layer];

        // Divided by the number we need gives the number of datapoints to skip
        // i.e if we want 1000 datapoints out of 100,000 then we need to get one
        // datapoints every 100 datapoints.
        $skipsize = round($dp_in_range / $dp);
        if ($skipsize<1) $skipsize = 1;

        // Calculate the starting datapoint position in the timestore file
        if ($start>$meta->start_time){
            $startpos = ceil(($start - $start_time_avl) / $meta->interval[$layer]);
        } else {
            $startpos = 0;
        }

        $data = array();
        $time = 0; $i = 0;

        // The datapoints are selected within a loop that runs until we reach a
        // datapoint that is beyond the end of our query range
        $fh = fopen($this->dir.$meta->id."_$layer.dat", 'rb');
        while($time<=$end)
        {
            // $position steps forward by skipsize every loop
            $pos = ($startpos + ($i * $skipsize));

            // Exit the loop if the position is beyond the end of the file
            if ($pos > $meta->npoints[$layer]-1) break;

            // read from the file
            fseek($fh,$pos*4);
            $val = unpack("f",fread($fh,4));

            // calculate the datapoint time
            $time = $start_time_avl + $pos * $meta->interval[$layer];

            // add to the data array if its not a nan value
            if (!is_nan($val[1])) $data[] = array($time*1000,$val[1]);

            $i++;
        }
        return $data;
    }

    public function get_data($feedid,$start,$end,$dp)
    {
        $feedid = intval($feedid);
        $start = intval($start/1000);
        $end = intval($end/1000);
        $dp = 800;
        
        $layer = 0;

        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($feedid)) return false;

        // The number of datapoints in the query range:
        $dp_in_range = ceil(($end - $start) / $meta->interval[$layer]);
        
        // Cant return more datapoints than exists in bottom layer
        if ($dp>$dp_in_range) $dp = $dp_in_range;
        
        // Find out the closest layer to the range we have selected
        $dpratio = $dp_in_range / $dp;
        if ($dpratio > ($meta->interval[1] / $meta->interval[0])) $layer = 1;   
        if ($dpratio > ($meta->interval[2] / $meta->interval[0])) $layer = 2;
        if ($dpratio > ($meta->interval[3] / $meta->interval[0])) $layer = 3;
        
        $dp_in_range = ceil(($end - $start) / $meta->interval[$layer]);
        error_log($dp_in_range." ".$dp." ".($dp_in_range/$dp));
                
        $start_time_avl = floor($meta->start_time / $meta->interval[$layer]) * $meta->interval[$layer];

        // Divided by the number we need gives the number of datapoints to skip
        // i.e if we want 1000 datapoints out of 100,000 then we need to get one
        // datapoints every 100 datapoints.
        $skipsize = round($dp_in_range / $dp);
        if ($skipsize<1) $skipsize = 1;

        // Calculate the starting datapoint position in the timestore file
        if ($start>$meta->start_time){
            $startpos = ceil(($start - $start_time_avl) / $meta->interval[$layer]);
        } else {
            $startpos = 0;
        }

        $data = array();
        $time = 0; $i = 0;
     
        // The datapoints are selected within a loop that runs until we reach a
        // datapoint that is beyond the end of our query range
        $mstart = microtime(true);
        $fh = fopen($this->dir.$meta->id."_$layer.dat", 'rb');
        fseek($fh,$startpos*4);
        $layer_values = unpack("f*",fread($fh, 4 * $dp_in_range));
        fclose($fh);
        
        $count = count($layer_values)-1;
        
        $naverage = $skipsize;
        for ($i=1; $i<$count-$naverage; $i+=$naverage)
        {
            // Calculate the average value of each block
            $point_sum = 0;
            $points_in_sum = 0;
            
            for ($n=0; $n<$naverage; $n++) {
                if (!is_nan($layer_values[$i+$n])) {
                    $point_sum += $layer_values[$i+$n];
                    $points_in_sum++;
                }
            }

            // If there was a value in the block then add to data array
            if ($points_in_sum) {
                $timestamp = $start_time_avl + $meta->interval[$layer] * ($startpos+$i-1);
                $average = $point_sum / $points_in_sum;
                $data[] = array($timestamp*1000,$average);
            }
        }
        
        error_log(round((microtime(true)-$mstart)*1000)."ms");
        
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
        if ($meta->npoints[0]>0)
        {
            $fh = fopen($this->dir.$meta->id."_0.dat", 'rb');
            $size = $meta->npoints[0]*4;
            fseek($fh,$size-4);
            $d = fread($fh,4);
            fclose($fh);

            $val = unpack("f",$d);
            $time = date("Y-n-j H:i:s", $meta->start_time + $meta->interval[0] * $meta->npoints[0]);
            
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
        
        for ($i=0; $i<$meta->nlayers; $i++)
        {
          unlink($this->dir.$meta->id."_$i.dat");
        }
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
        $meta->start_time = $tmp[1];
        
        $tmp = unpack("I",fread($metafile,4)); 
        $meta->nlayers = $tmp[1];
        
        $meta->npoints = array();
        for ($i=0; $i<$meta->nlayers; $i++)
        {
          $tmp = unpack("I",fread($metafile,4)); 
          $meta->npoints[$i] = $tmp[1];
        }
        
        $meta->interval = array();
        for ($i=0; $i<$meta->nlayers; $i++)
        {
          $tmp = unpack("I",fread($metafile,4)); 
          $meta->interval[$i] = $tmp[1];
        }
        
        fclose($metafile);
        
        return $meta;
    }
    
    private function set_meta($id,$meta)
    {
        $id = (int) $id;
        $feedname = "$id.meta";
    
        $metafile = fopen($this->dir.$feedname, 'wb');
        fwrite($metafile,pack("I",$meta->id));
        fwrite($metafile,pack("I",$meta->start_time)); 
        fwrite($metafile,pack("I",$meta->nlayers));
        foreach ($meta->npoints as $n) fwrite($metafile,pack("I",$n));
        foreach ($meta->interval as $d) fwrite($metafile,pack("I",$d));
        
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
