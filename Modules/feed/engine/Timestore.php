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

    public function export($feedid,$layer,$start)
    {
        $feedid = (int) $feedid;
        $layer = (int) $layer;
        $start = (int) $start;

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
        
        $data = $this->get_average($feedid,$start,$end,$interval);
       
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
        
        foreach($data as $line)
        {
            fwrite($exportfh, ($line[0]/1000).",".number_format($line[1],2)."\n");
        }
        
        fclose($exportfh);
        exit;
    }


}
