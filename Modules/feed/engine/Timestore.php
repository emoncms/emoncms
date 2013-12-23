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
  
  public function __construct($timestore_adminkey)
  {
    require "Modules/feed/engine/TimestoreApi.php";
    $this->timestoreApi = new TimestoreAPI($timestore_adminkey);
  }
  
  public function create($feedid,$newfeedinterval)
  {
    $ret = $this->timestoreApi->create_node($feedid,$newfeedinterval);
  }
  
  public function post($feedid,$time,$value)
  {

    // IMPORTANT: This puts a limit on the range of timestamps that can be accepted
    // if you need to post or update datapoints that are older than 5 years and newer than the current time + 48 hours in the future
    // then change these values:
    
    $now = time();
    $start = $now-(3600*24*365*5); // 5 years in past
    $end = $now+(3600*48);         // 48 hours in future
    
    $this->timestoreApi->post_values($feedid,$time*1000,array($value),null);
  }
  
  public function get_data($feedid,$start,$end)
  {
    $feedid = intval($feedid);
    $start = intval($start/1000);
    $end = intval($end/1000);

    if ($end == 0) $end = time();

    $meta = $this->get_meta($feedid);

    $start = round($start/$meta['interval'])*$meta['interval'];
    $end = round($end/$meta['interval'])*$meta['interval'];
    $npoints = round(($end - $start) / $meta['interval']);
 
    if ($npoints>1000) $npoints = 1000;

    $data = json_decode($this->timestoreApi->get_series($feedid,0,$npoints,$start,$end,null));
    return $data;
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
    
    
    $meta = $this->get_timestore_meta($feedid);

    $npoints = round(($end - $start) / $meta['interval']);
    $data = json_decode($this->timestore->get_series($feedid,0,$npoints,$start,$end,null));

    foreach ($data as $point)
    {
      $time = $point[0];
      $this->timestore->post_values($feedid,$time,array($point[1] * $value),null);
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

    $primaryfeedname = "/var/lib/timestore/$feedname";
    $primary = fopen($primaryfeedname, 'rb');
    $primarysize = filesize($primaryfeedname);
    
    //$localsize = intval((($start - $meta['start']) / $meta['interval']) * 4);
    
    $localsize = $start;
    $localsize = intval($localsize / 4) * 4;
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
    $meta = fopen("/var/lib/timestore/$feedname", 'rb');
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
    $meta = fopen("/var/lib/timestore/$feedname", 'rb');

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
    $size += filesize("/var/lib/timestore/".str_pad($feedid, 16, '0', STR_PAD_LEFT)."_0_.dat");
    $size += filesize("/var/lib/timestore/".str_pad($feedid, 16, '0', STR_PAD_LEFT)."_1_.dat");
    $size += filesize("/var/lib/timestore/".str_pad($feedid, 16, '0', STR_PAD_LEFT)."_2_.dat");
    $size += filesize("/var/lib/timestore/".str_pad($feedid, 16, '0', STR_PAD_LEFT)."_3_.dat");
    $size += filesize("/var/lib/timestore/".str_pad($feedid, 16, '0', STR_PAD_LEFT)."_4_.dat");
    $size += filesize("/var/lib/timestore/".str_pad($feedid, 16, '0', STR_PAD_LEFT)."_5_.dat");
    return $size;
  }
  
  
}
