<?php

class PHPTimestore
{
    private $dir = "/var/lib/timestore/";
    private $log;
    
    public function __construct($settings)
    {
        if (isset($settings['datadir'])) $this->dir = $settings['datadir'];
        $this->log = new EmonLogger(__FILE__);
    }

    public function create($feedid,$options)
    {
        $interval = (int) $options['interval'];
        if ($interval<5) $interval = 5;
        // Check to ensure we dont overwrite an existing feed
        if (!$meta = $this->get_meta($feedid)) {

            // Set initial feed meta data
            $meta = new stdClass();
            $meta->feedid = $feedid;
            $meta->npoints = 0;
            $meta->start = 0;
            $meta->nmetrics = 1;
            $meta->interval = $interval;

            // Save meta data
            $this->create_meta($feedid,$meta);
            
            for ($l=0; $l<6; $l++) {
                $fh = fopen($this->dir.str_pad($meta->feedid, 16, '0', STR_PAD_LEFT)."_".$l."_.dat", 'c+');
                if (!$fh) {
                    $this->log->warn("PHPTimestore:create could not create data file for layer $l feedid=$feedid");
                    return false;
                } else {
                    fclose($fh);
                }
            }
        }

        if (file_exists($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT).".tsdb")) {
            return true;
        } else {
            $this->log->warn("PHPTimestore:create meta file does not exist id=$feedid");
            return false;
        }
    }

    public function post($feedid, $timestamp, $value)
    {
        $this->log->info("PHPTimestore:post id=$feedid timestamp=$timestamp value=$value");
        
        $now = time();
        $start = $now-(3600*24*365*5); // 5 years in past
        $end = $now+(3600*48);         // 48 hours in future
        $rc = 0;

        if ($timestamp<$start || $timestamp>$end) {
            $this->log->warn("PHPTimestore:post timestamp out of range");
            return false;
        }

        $value = (float) $value;
        // If meta data file does not exist then exit
        if (!$meta = $this->get_meta($feedid)) {
            $this->log->warn("PHPTimestore:post failed to fetch meta id=$feedid");
            return false;
        }
        
        /* For a new file this point represents the start of the database */
        $timestamp = floor(($timestamp / $meta->interval)) * $meta->interval; /* round down */
        if ($meta->npoints == 0) {
            $meta->start = $timestamp;
            $this->create_meta($feedid,$meta);
        }

        /* Sanity checks */
        if ($timestamp < $meta->start) {
            $this->log->warn("PHPTimestore:post timestamp older than start time feedid=$feedid");
            return false; // in the past
        }

        /* Determine position of point in the top-level */
        $point = floor(($timestamp - $meta->start) / $meta->interval);

        /* Update layers */
        $rc = $this->update_layer($meta,0,$point,$meta->npoints,$value);
        if ($rc == 0) {
            /* Update metadata with new number of top-level points */
            if ($point >= $meta->npoints)
            {
                $meta->npoints = $point + 1;
                $this->set_npoints($feedid,$meta);
            }
        }

        return $rc;
    }
    
    public function update($feedid,$time,$value)
    {
      $this->post($feedid,$time,$value);
    }

    public function update_layer($meta,$layer,$point,$npoints,$value)
    {
        $decimation = array(20, 6, 6, 4, 7);


        // print "$layer $point $npoints $value\n";
        $tsdb_max_padding_block = 1024 * 1024;

        $fh = fopen($this->dir.str_pad($meta->feedid, 16, '0', STR_PAD_LEFT)."_".$layer."_.dat", 'c+');
        
        if (!$fh)
        {
            $this->log->warn("PHPTimestore:update_layer could not open data file for layer $layer feedid=$feedid");
            return false;
        }
        
        if (!flock($fh, LOCK_EX)) {
            $this->log->warn("PHPTimestore:update_layer data file for layer=$layer feedid=$feedid is locked by another process");
            fclose($fh);
            return false;
        }

        if ($point > $npoints) {
            $npadding = ($point - $npoints);

            if ($npadding>2500000) {
                $this->log->warn("PHPTimestore:update_layer npadding=$npadding > 2500000! exit feedid=$feedid");
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
                if ($npadding < $pointsperblock) {
                    $pointsperblock = $npadding;
                    $buf = ''; for ($n = 0; $n < $pointsperblock; $n++) $buf .= pack("f",NAN);
                }
                fwrite($fh, $buf);
                $npadding -= $pointsperblock;
            } while ($npadding);
        }

        // Write point back to file
        fseek($fh, 4*$point);
        if (!is_nan($value)) fwrite($fh,pack("f",$value));

        if ($layer<5) {

            // Averaging
            $first_point = floor($point / $decimation[$layer]) * $decimation[$layer];

            // Read in points
            fseek($fh, 4*$first_point);
            $d = fread($fh, 4 * $decimation[$layer]);
            $count = strlen($d)/4;
            $d = unpack("f*",$d);
            fclose($fh);

                // Calculate average of points
            $sum_count = 0;
            $sum = 0.0;

            $i=0;
            while ($count--) {
                $i++;
                if (is_nan($d[$i])) {
                    // Skip unknown values
                    continue;
                }

                // Summing
                $sum += $d[$i];
                $sum_count ++;
            }

            if ($sum_count>0) {
                    $average = $sum / $sum_count;
            } else {
                    $average = NAN;
            }


            $this->update_layer($meta,$layer+1,floor($point/$decimation[$layer]),floor(($npoints+$decimation[$layer]-1)/$decimation[$layer]),$average);
        }

        return 0;
    }

    // Alternate timestore version

    public function get_data($feedid,$start,$end,$outinterval)
    {
        $feedid = intval($feedid);
        $start = intval($start/1000);
        $end = intval($end/1000);

        if ($end == 0) $end = time();

        if (!$meta = $this->get_meta($feedid)) return false;

        $start = round($start/$meta->interval)*$meta->interval;
        
        if ($outinterval<1) $outinterval = 1;
        $npoints = ceil(($end - $start) / $outinterval);
        $end = $start + ($npoints * $outinterval);
        if ($npoints<1) return false;

        $data = $this->tsdb_get_series($meta,$start,$end,$npoints);
        return $data;
    }

    public function tsdb_get_series($meta,$start,$end,$npoints)
    {
        $meta->decimation = array(20, 6, 6, 4, 7);

        /* Sanity check */
        if ($end < $start) return false;
        if ($npoints == 0) return false;

        /* Determine best layer to use for sourcing the result */
        if ($npoints == 1) {
            /* Special case - returns the value in between the start and end points */
            $end = $start = ($start + $end) / 2;
            $out_interval = 0;
        } else {
            $out_interval = floor(($end - $start) / ($npoints-1)); /* 1 less interval than points */
            if (($end - $start) < ($npoints - 1)) {
                /* Minimum interval for output points is 1 second */
                $npoints = $end - $start + 1;
                $out_interval = 1;
            }
        }

        $layer_interval = $meta->interval;
        for ($layer = 0; $layer < 5; $layer++) {
            if ($meta->decimation[$layer] == 0) {
                /* This is the last layer - we have to use it */
                break;
            }
            if ($layer_interval * $meta->decimation[$layer] > $out_interval) {
                /* Next layer is downsampled too much, so use this one */
                break;
            }
            $layer_interval *= $meta->decimation[$layer];
        }


        if ($out_interval > $layer_interval) $naverage = floor($out_interval / $layer_interval);
        else $naverage = 1;
        
        // equivalent to: $naverage = ($out_interval > $layer_interval) ? $out_interval / $layer_interval : 1;

        /* Generate output points by averaging all available input points between the start
         * and end times for each output step.  Output timestamps are rounded down onto the
         * input interval - there is no interpolation. */

        // Alternative approach, all reads in one block at the start

        $data = array();

        // Open the timestore layer file for reading in data in range between start and end
        $feedname = str_pad($meta->feedid, 16, '0', STR_PAD_LEFT)."_".$layer."_.dat";
        $primaryfeedname = $this->dir.$feedname;
        $fh = fopen($primaryfeedname, 'rb');

        // Ensure start and end are within limits
        if ($start<$meta->start) $start = $meta->start;
        //if ($end>$meta->start+($meta->npoints*$meta->interval)) $end = $meta->start+($meta->npoints*$meta->interval);
        if ($end<$start) return array();

        // Calculate start point in file
        $point = floor(($start - $meta->start) / $layer_interval);
        // and range of datapoints to read
        $range = ceil(($end - $start) / $layer_interval);
        // seek to the position of the start point
        fseek($fh, 4 * $point);
        // Read in the full range of datapoints
        $layer_values = unpack("f*",fread($fh, 4 * $range));
        fclose($fh);
        // Downsample to the desired number of datapoints - or as close as we can get within an integer multiple of the lower layer

        $count = count($layer_values);

        //print "point: ".$point."<br>";
        //print "range: ".$range."<br>";
        $ts = $meta->start + $layer_interval * $point;
        //print "time: ".date("Y-n-j H:i:s", $ts)."<br>";

        //print "out_interval: ".$out_interval."<br>";
        //print "layer_interval: ".$layer_interval."<br>";
        //print "naverage: ".$naverage."<br>";
        //print "count: ".$count."<br>";

        //print "Layer values: <br>";

        // Read in steps of tge averaged block size
        for ($i=1; $i<=($count-$naverage+1); $i+=$naverage)
        {
            // Calculate the average value of each block
            $point_sum = 0;
            $points_in_sum = 0;
            for ($n=0; $n<$naverage; $n++)
            {
                if (!is_nan($layer_values[$i+$n]))
                {
                    $point_sum += $layer_values[$i+$n];
                    $points_in_sum++;

                    $ts = $meta->start + $layer_interval * ($point+$i+$n-1);
                    //print date("Y-n-j H:i:s",$ts)." ".$layer_values[$i+$n]."<br>";
                }
            }

                // If there was a value in the block then add to data array
            if ($points_in_sum) {
                $timestamp = $meta->start + $layer_interval * ($point+$i-1);
                $average = $point_sum / $points_in_sum;
                $data[] = array($timestamp*1000,$average);
                //print "--".$average."<br>";
            }

        }

        return $data;
    }

        // Alternative implementation of the get_data function, uses timestore pre-compiled averages but does not calculate
        // further averages from the averaged layers, this method is not by default in use
    public function get_data_alternative($feedid,$start,$end)
    {
        $decimation = array(20, 6, 6, 4, 7);
        //$feedid = 21; $start = 1317572497; $end = 1317692497; $dp = 10;
        $feedid = intval($feedid);
        $start = intval($start/1000);
        $end = intval($end/1000);
        $dp = 1000;

        // Load the timestore meta data
        if (!$meta = $this->get_meta($feedid)) return false;

        // The number of datapoints in the query range:
        $dp_in_range = ($end - $start) / $meta->interval;

        // Divided by the number we need gives the number of datapoints to skip
        // i.e if we want 1000 datapoints out of 100,000 then we need to get one
        // datapoints every 100 datapoints.
        $skipsize = round($dp_in_range / $dp);
        if ($skipsize<1) $skipsize = 1;

        $interval = $meta->interval;

        $layer = 0;

        if ($skipsize>=20) {
            $layer = 1;
            $interval *= 20;
        }
        if ($skipsize>=20*6) {
            $layer = 2;
            $interval *= 6;
        }
        if ($skipsize>=20*6*6) {
            $layer = 3;
            $interval *= 6;
        }
        if ($skipsize>=20*6*6*4) {
            $layer = 4;
            $interval *= 4;
        }
        if ($skipsize>=20*6*6*4*7) {
            $layer = 5;
            $interval *= 7;
        }

        $dp_in_range = ($end - $start) / $interval;
        $skipsize = round($dp_in_range / $dp);
        if ($skipsize<1) $skipsize = 1;

        $feedname = str_pad($feedid, 16, '0', STR_PAD_LEFT)."_".$layer."_.dat";
        $primaryfeedname = $this->dir.$feedname;
        $filesize = filesize($primaryfeedname);

        // Calculate the starting datapoint position in the timestore file
        if ($start>$meta->start){
            $startpos = ceil(($start - $meta->start) / $interval);
        } else {
            $startpos = 0;
        }

        $data = array();
        $time = 0; $i = 0;

        // The datapoints are selected within a loop that runs until we reach a
        // datapoint that is beyond the end of our query range
        $fh = fopen($primaryfeedname, 'rb');
        while($time<=$end)
        {
            // $position steps forward by skipsize every loop
            $pos = ($startpos + ($i * $skipsize));

            // Exit the loop if the position is beyond the end of the file
            if ($pos*4 > $filesize-4) break;

            // read from the file
            fseek($fh,$pos*4);
            $val = unpack("f",fread($fh,4));

            // calculate the datapoint time
            $time = $meta->start + $pos * $interval;

            // add to the data array if its not a nan value
            if (!is_nan($val[1])) $data[] = array($time*1000,$val[1]);

            $i++;
        }
        return $data;
    }

    public function lastvalue($feedid)
    {
        $feedid = (int) $feedid;
        if (!$meta = $this->get_meta($feedid)) return false;
        
        $feedname = str_pad($feedid, 16, '0', STR_PAD_LEFT)."_0_.dat";

        $primaryfeedname = $this->dir.$feedname;

        if (file_exists($primaryfeedname))
        {
            $fh = fopen($primaryfeedname, 'rb');
            $size = filesize($primaryfeedname);
            
            if ($size>=4) {
                fseek($fh,$size-4);
                $d = fread($fh,4);
                fclose($fh);
                $val = unpack("f",$d);
            } else {
                $val = 0;
            }
            $time = date("Y-n-j H:i:s", $meta->start + $meta->interval * $meta->npoints);
            return array('time'=>$time, 'value'=>$val[1]);
        }
        else
        {
            return array('time'=>0, 'value'=>0);
        }
    }

    public function scale_range($feedid,$start,$end,$value)
    {
        // not yet implemented
    }

    public function delete_range($feedid,$start,$end)
    {
        // not yet implemented
    }

    public function get_meta($feedid)
    {
        $feedid = (int) $feedid;
        $feedname = str_pad($feedid, 16, '0', STR_PAD_LEFT).".tsdb";

        if (!file_exists($this->dir.$feedname)) {
            //$this->log->warn("PHPTimestore:get_meta feed:$feedid metadata does not exist");
            return false;
        }
        $meta = new stdClass();
        
        $size = filesize($this->dir.$feedname);
        
        if (!($size==36 || $size == 272)) {
            $this->log->warn("PHPTimestore:get_meta feed:$feedid metadata filesize error, size = $size");
            return false;
        } 
        
        $metafile = fopen($this->dir.$feedname, 'rb');

        fseek($metafile,8);
        $d = fread($metafile,8);
        $tmp = unpack("h*",$d);
        $meta->feedid = (int) strrev($tmp[1]);
        $tmp = unpack("I",fread($metafile,4));
        $meta->nmetrics = $tmp[1];
        $tmp = unpack("I",fread($metafile,4));
        $legacy_npoints = $tmp[1];
        $tmp = unpack("I",fread($metafile,8));
        $meta->start = $tmp[1];
        $tmp = unpack("I",fread($metafile,4));
        $meta->interval = $tmp[1];
        fclose($metafile);
        
        // Sanity checks
        
        if ($meta->feedid != $feedid)
        {
            $this->log->warn("PHPTimestore:get_meta feed:$feedid meta data mismatch, meta feedid: ".$meta->feedid);
            return false;
        }
        
        if ($meta->nmetrics!=1) {
            $this->log->warn("PHPTimestore:get_meta feed:$feedid nmetrics is not 1");
            return false;
        }
        
        if ($meta->interval<5 || $meta->interval>(24*3600))
        {
            $this->log->warn("PHPTimestore:get_meta feed:$feedid interval is out of range, interval is: ".$meta->interval);
            return false;
        }
        
        // Double verification of npoints
        
        $filesize = filesize($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_0_.dat");
        $filesize_npoints = $filesize / 4.0;
        
        if ($filesize_npoints!=(int)$filesize_npoints) {
            // filesize result is corrupt
            $this->log->warn("PHPTimestore:get_meta php filesize() is not integer multiple of 4 bytes id=$feedid");
            return false;
        }
        
        $feedname = str_pad($feedid, 16, '0', STR_PAD_LEFT).".npoints";
        
        if (!file_exists($this->dir.$feedname)) {
            // 1) Transitioning to new system that saves npoints in a seperate file
            if ($legacy_npoints!=$filesize_npoints)
            {
                $this->log->warn("PHPTimestore:get_meta legacy npoints does not match filesize npoints id=$feedid");
                return false;
            } else {
                $meta->npoints = $filesize_npoints;
            }

        } else {
            $metafile = fopen($this->dir.$feedname, 'rb');
            $tmp = unpack("I",fread($metafile,4)); 
            $npoints = $tmp[1];
            fclose($metafile);
            $meta->npoints = $npoints;
        }
        
        if ($npoints!=$filesize_npoints)
        {
            // filesize npoints and npoints from the .npoints meta file should be the same
            // if there is a discrepancy then this suggests corrupt data.
            $this->log->warn("PHPTimestore:get_meta meta file npoints ($npoints) does not match filesize npoints ($filesize_npoints) feedid=$feedid");
            return false;
            
            // $meta->npoints = $filesize_npoints;
        }
        
        if ($meta->start <= 0 && $npoints>=1) {
          error_log("PHPTimestore:get_meta feed:$feedid start time must be greater than zero");
          return false;
        }


        return $meta;
    }

    public function create_meta($feedid,$meta)
    {
        $feedid = (int) $feedid;
        $feedname = str_pad($feedid, 16, '0', STR_PAD_LEFT).".tsdb";

        $metafile = fopen($this->dir.$feedname, 'wb');
        
        if (!$metafile) {
            $this->log->warn("PHPTimestore:create_meta could not open metafile feedid=$feedid");
            return false;
        }
        
        if (!flock($metafile, LOCK_EX)) {
            $this->log->warn("PHPTimestore:create_meta ".$this->dir.$feedname." is locked by another process");
            fclose($metafile);
            return false;
        }

        fwrite($metafile,pack("I",0));
        fwrite($metafile,pack("I",0));
        fwrite($metafile,pack("h*",strrev(str_pad($feedid, 16, '0', STR_PAD_LEFT))));
        fwrite($metafile,pack("I",$meta->nmetrics));
        fwrite($metafile,pack("I",0));                  // Legacy
        fwrite($metafile,pack("I",$meta->start));
        fwrite($metafile,pack("I",0));
        fwrite($metafile,pack("I",$meta->interval));

        //$decimation = array(20, 6, 6, 4, 7);
        //foreach ($decimation as $d) fwrite($metafile,pack("I",$d));

        //$flags = array(); for($i=0; $i<32; $i++) $flags[]=0;
        //foreach ($flags as $d) fwrite($metafile,pack("I",$d));

        fclose($metafile);
        
        if (!$this->set_npoints($feedid,$meta)) return false;
        
        return $meta;
    }
    
    public function set_npoints($feedid,$meta)
    {
        $feedid = (int) $feedid;
        $feedname = str_pad($feedid, 16, '0', STR_PAD_LEFT).".npoints";
        $metafile = fopen($this->dir.$feedname, 'wb');
        
        if (!$metafile) {
            $this->log->warn("PHPTimestore:set_npoints could not open npoints metafile feedid=$feedid");
            return false;
        }
        
        if (!flock($metafile, LOCK_EX)) {
            $this->log->warn("PHPTimestore:set_npoints ".$this->dir.$feedname." is locked by another process");
            fclose($metafile);
            return false;
        }
        
        fwrite($metafile,pack("I",$meta->npoints));
        fclose($metafile);
        return $meta;
    }

    public function get_feed_size($feedid)
    {
        $size = 272;
        $size += filesize($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_0_.dat");
        $size += filesize($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_1_.dat");
        $size += filesize($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_2_.dat");
        $size += filesize($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_3_.dat");
        $size += filesize($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_4_.dat");
        $size += filesize($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_5_.dat");
        return $size;
    }

    public function export($feedid,$layer,$start)
    {
        $feedid = (int) $feedid;
        $layer = (int) $layer;
        $start = (int) $start;

        if (!$meta = $this->get_meta($feedid)) return false;

        $feedname = str_pad($feedid, 16, '0', STR_PAD_LEFT)."_".$layer."_.dat";

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

    public function export_meta($feedid)
    {
        $feedid = (int) $feedid;

        $feedname = str_pad($feedid, 16, '0', STR_PAD_LEFT).".tsdb";

        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename={$feedname}");

        header("Expires: 0");
        header("Pragma: no-cache");

        $fh = @fopen( 'php://output', 'w' );
        $meta = fopen($this->dir.$feedname, 'rb');
        fwrite($fh,fread($meta,272));

        fclose($meta);
        fclose($fh);
        exit;
    }

    public function delete($feedid)
    {
        unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT).".tsdb");
	unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT).".npoints");
        unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_0_.dat");
        unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_1_.dat");
        unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_2_.dat");
        unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_3_.dat");
        unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_4_.dat");
        unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_5_.dat");
    }
    
    public function csv_export($feedid,$start,$end,$outinterval)
    {
        $feedid = (int) $feedid;
        $start = (int) $start;
        $end = (int) $end;
        $outinterval = (int) $outinterval;

        if ($end == 0) $end = time();

        if (!$meta = $this->get_meta($feedid)) return false;

        $start = round($start/$meta->interval)*$meta->interval;
        
        if ($outinterval<1) $outinterval = 1;
        $npoints = ceil(($end - $start) / $outinterval);
        $end = $start + ($npoints * $outinterval);
        if ($npoints<1) return false;
        
        $meta->decimation = array(20, 6, 6, 4, 7);

        /* Sanity check */
        if ($end < $start) return false;
        if ($npoints == 0) return false;

        /* Determine best layer to use for sourcing the result */
        if ($npoints == 1) {
            /* Special case - returns the value in between the start and end points */
            $end = $start = ($start + $end) / 2;
            $out_interval = 0;
        } else {
            $out_interval = floor(($end - $start) / ($npoints-1)); /* 1 less interval than points */
            if (($end - $start) < ($npoints - 1)) {
                /* Minimum interval for output points is 1 second */
                $npoints = $end - $start + 1;
                $out_interval = 1;
            }
        }

        $layer_interval = $meta->interval;
        for ($layer = 0; $layer < 5; $layer++) {
            if ($meta->decimation[$layer] == 0) {
                /* This is the last layer - we have to use it */
                break;
            }
            if ($layer_interval * $meta->decimation[$layer] > $out_interval) {
                /* Next layer is downsampled too much, so use this one */
                break;
            }
            $layer_interval *= $meta->decimation[$layer];
        }


        if ($out_interval > $layer_interval) $naverage = floor($out_interval / $layer_interval);
        else $naverage = 1;
        // equivalent to: $naverage = ($out_interval > $layer_interval) ? $out_interval / $layer_interval : 1;

        /* Generate output points by averaging all available input points between the start
         * and end times for each output step.  Output timestamps are rounded down onto the
         * input interval - there is no interpolation. */

        // Alternative approach, all reads in one block at the start

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
        
        $data = array();

        // Open the timestore layer file for reading in data in range between start and end
        $feedname = str_pad($meta->feedid, 16, '0', STR_PAD_LEFT)."_".$layer."_.dat";
        $primaryfeedname = $this->dir.$feedname;
        $fh = fopen($primaryfeedname, 'rb');

        // Ensure start and end are within limits
        if ($start<$meta->start) $start = $meta->start;
        //if ($end>$meta->start+($meta->npoints*$meta->interval)) $end = $meta->start+($meta->npoints*$meta->interval);
        if ($end<$start) return array();

        // Calculate start point in file
        $point = floor(($start - $meta->start) / $layer_interval);
        // and range of datapoints to read
        $range = ceil(($end - $start) / $layer_interval);
        // seek to the position of the start point
        fseek($fh, 4 * $point);
        // Read in the full range of datapoints
        $layer_values = unpack("f*",fread($fh, 4 * $range));
        fclose($fh);

        // Downsample to the desired number of datapoints - or as close as we can get within an integer multiple of the lower layer

        $count = count($layer_values)-1;

        //print "point: ".$point."<br>";
        //print "range: ".$range."<br>";
        $ts = $meta->start + $layer_interval * $point;
        //print "time: ".date("Y-n-j H:i:s", $ts)."<br>";

        //print "out_interval: ".$out_interval."<br>";
        //print "layer_interval: ".$layer_interval."<br>";
        //print "naverage: ".$naverage."<br>";
        //print "count: ".$count."<br>";

        //print "Layer values: <br>";

        // Read in steps of tge averaged block size
        for ($i=1; $i<$count-$naverage; $i+=$naverage)
        {
            // Calculate the average value of each block
            $point_sum = 0;
            $points_in_sum = 0;
            for ($n=0; $n<$naverage; $n++)
            {
                if (!is_nan($layer_values[$i+$n]))
                {
                    $point_sum += $layer_values[$i+$n];
                    $points_in_sum++;

                    $ts = $meta->start + $layer_interval * ($point+$i+$n-1);
                    //print date("Y-n-j H:i:s",$ts)." ".$layer_values[$i+$n]."<br>";
                }
            }

                // If there was a value in the block then add to data array
            if ($points_in_sum) {
                $timestamp = $meta->start + $layer_interval * ($point+$i-1);
                $average = $point_sum / $points_in_sum;
                fwrite($exportfh, $timestamp.",".number_format($average,2)."\n");
                //print "--".$average."<br>";
            }

        }
        fclose($exportfh);
        exit;
    }

}

    /*

    For reference

    // Current Timestore approach to second part of tsdb_get_series above, many reads in small blocks

    // while ($npoints--)
    for ($i=0; $i<$npoints; $i++)
    {
        $start += $out_interval;
            // Determine if this point is in-range of the input table
            if ($start < $meta->start || $start >= $meta->start + $meta->npoints * $meta->interval) {
                    // No - there is no data at this time point
                    continue;
            }

            // There may be data for this point in the table.  Calculate the range of input points
            // covered by the output period and read them for averaging
            $point = floor(($start - $meta->start) / $layer_interval);

            fseek($fh, 4 * $point);
            $layer_values = unpack("f*",fread($fh, 4 * $naverage));

            // Generate average ignoring any NAN points
            $timestamp = $start;
            $value = 0.0;
            $actual_naverage = 0;

            for ($n=0; $n<$naverage; $n++)
            {
                    if (!is_nan($layer_values[$n+1])) {
                            $value += $layer_values[$n+1];
                            $actual_naverage++;
                    }
            }

            if ($actual_naverage) {
                    // A valid point was generated
                    $value /= (double) $actual_naverage;
                    $actual_npoints[] = array($timestamp*1000,$value);
            }
    }
    */
