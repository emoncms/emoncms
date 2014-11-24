<?php

class Timestore
{

    /**
     * Constructor.
     *
     * @param api $mysqli Instance of mysqli
     *
     * @api
    */

    private $timestoreApi;
    private $dir = "/var/lib/timestore/";
    
    public function __construct($settings)
    {
        require "Modules/feed/engine/TimestoreApi.php";
        $this->timestoreApi = new TimestoreAPI($settings['adminkey']);
        if (isset($settings['datadir'])) $this->dir = $settings['datadir'];
    }

    public function create($feedid,$options)
    {
        $newfeedinterval = (int) $options['interval'];
        if ($newfeedinterval<5) $newfeedinterval = 5;
        $this->timestoreApi->create_node($feedid,$newfeedinterval);

        if (file_exists($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT).".tsdb")) return true;
        return false;
    }

    public function post($feedid,$time,$value)
    {
        // IMPORTANT: This puts a limit on the range of timestamps that can be accepted
        // if you need to post or update datapoints that are older than 5 years and newer than the current time + 48 hours in the future
        // then change these values:

        $now = time();
        $start = $now-(3600*24*365*5); // 5 years in past
        $end = $now+(3600*48);         // 48 hours in future

        if ($time>$start && $time<$end)
        {
            $this->timestoreApi->post_values($feedid,$time*1000,array($value),null);
        }

    }
    
    public function update($feedid,$time,$value)
    {
      $this->post($feedid,$time,$value);
    }

    public function get_data($feedid,$start,$end,$outinterval)
    {
        $feedid = intval($feedid);
        $start = intval($start/1000);
        $end = intval($end/1000);

        if ($end == 0) $end = time();

        $meta = $this->get_meta($feedid);
        
        $start = round($start/$meta['interval'])*$meta['interval'];
        
        if ($outinterval<1) $outinterval = 1;
        $npoints = ceil(($end - $start) / $outinterval);
        $end = $start + ($npoints * $outinterval);
        if ($npoints<1) return false;

        $data = json_decode($this->timestoreApi->get_series($feedid,0,$npoints,$start,$end,null));
        return $data;
    }

    public function lastvalue($feedid)
    {
        $feedid = (int) $feedid;
        $meta = $this->get_meta($feedid);
        $feedname = str_pad($feedid, 16, '0', STR_PAD_LEFT)."_0_.dat";

        $primaryfeedname = $this->dir.$feedname;

        if (file_exists($primaryfeedname))
        {
            $fh = fopen($primaryfeedname, 'rb');
            $size = filesize($primaryfeedname);

            fseek($fh,$size-4);
            $d = fread($fh,4);
            fclose($fh);

            $val = unpack("f",$d);
            $time = date("Y-n-j H:i:s", $meta['start'] + $meta['interval'] * $meta['npoints']);
            return array('time'=>$time, 'value'=>$val[1]);
        }
        else
        {
            return array('time'=>0, 'value'=>0);
        }
    }

    public function get_average($feedid,$start,$end,$interval)
    {
        $feedid = intval($feedid);
        $start = intval($start/1000);
        $end = intval($end/1000);
        $interval = intval($interval);

        if ($end == 0) $end = time();

        $npoints = round(($end - $start) / $interval);
        $end = $start+(($npoints-1)*$interval);

        if ($npoints>1000) $npoints = 1000;

        $data = json_decode($this->timestoreApi->get_series($feedid,0,$npoints,$start,$end,null));
        return $data;
    }

    public function scale_range($feedid,$start,$end,$value)
    {
        $feedid = intval($feedid);
        $start = intval($start/1000.0);
        $end = intval($end/1000.0);

        $meta = $this->get_meta($feedid);

        $npoints = round(($end - $start) / $meta['interval']);
        $data = json_decode($this->timestoreApi->get_series($feedid,0,$npoints,$start,$end,null));

        foreach ($data as $point)
        {
            $time = $point[0];
            $this->timestoreApi->post_values($feedid,$time,array($point[1] * $value),null);
        }

        return true;
    }

    public function export($feedid,$start,$layer)
    {
        $feedid = (int) $feedid;
        $start = (int) $start;
        $layer = (int) $layer;

        $meta = $this->get_meta($feedid);

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

    public function get_meta($feedid)
    {
        $feedid = (int) $feedid;
        $feedname = str_pad($feedid, 16, '0', STR_PAD_LEFT).".tsdb";

        $out = array();
        $meta = fopen($this->dir.$feedname, 'rb');

        fseek($meta,8);
        $tmp = unpack("h*",fread($meta,8));
        $out['nodeid'] = (int) strrev($tmp[1]);
        $tmp = unpack("I",fread($meta,4));
        $out['nmetrics'] = $tmp[1];
        $tmp = unpack("I",fread($meta,4));
        $out['npoints'] = $tmp[1];
        $tmp = unpack("I",fread($meta,8));
        $out['start'] = $tmp[1];
        $tmp = unpack("I",fread($meta,4));
        $out['interval'] = $tmp[1];
        fclose($meta);

        return $out;
    }

    public function delete($feedid)
    {
        $this->timestoreApi->delete_node($feedid);
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
    
    public function csv_export($feedid,$start,$end,$outinterval)
    {
        $feedid = (int) $feedid;
        $start = (int) $start;
        $end = (int) $end;
        $outinterval = (int) $outinterval;
        
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
                fwrite($exportfh, $timestamp.",".number_format($average,2,'.','')."\n");
                //print "--".$average."<br>";
            }

        }
        fclose($exportfh);
        exit;
    }


}
