<?php

class PHPTimeSeries
{

    /**
     * Constructor.
     *
     *
     * @api
    */

    private $timestoreApi;

    private $dir = "/var/lib/phptimeseries/";
    private $log;
    
    private $buffers = array();
    private $lastvalue_cache = array();

    public function __construct($settings)
    {
        if (isset($settings['datadir'])) $this->dir = $settings['datadir'];
        $this->log = new EmonLogger(__FILE__);
    }

    public function create($feedid,$options)
    {
        $fh = fopen($this->dir."feed_$feedid.MYD", 'a');
        if (!$fh) {
            $this->log->warn("PHPTimeSeries:create could not create data file feedid=$feedid");
        }
        fclose($fh);

        if (file_exists($this->dir."feed_$feedid.MYD")) return true;
        return false;
    }
  
    public function prepare($feedid,$timestamp,$value)
    {
        $feedid = (int) $feedid;
        $timestamp = (int) $timestamp;
        $value = (float) $value;
        
        $filename = "feed_$feedid.MYD";
        $npoints = $this->get_npoints($feedid);
        
        // If there is data then read last value
        if ($npoints>=1) {
        
            if (isset($this->lastvalue_cache[$filename])) {
                $last = $this->lastvalue_cache[$filename];
            } else {
                $fh = fopen($this->dir.$filename, 'rb');
                fseek($fh,(($npoints-1) * 9.0));
                $last = unpack("x/Itime/fvalue",fread($fh,9));
                fclose($fh);
            }
        
            if ($timestamp<=$last['time']) {
                return false;
            } 
        }

        if (!isset($this->buffers[$filename])) {
            $this->buffers[$filename] = "";
        }
        
        $this->buffers[$filename] .= pack("CIf",249,$timestamp,$value);
        $this->lastvalue_cache[$filename] = array('time'=>$timestamp,'value'=>$value);
        return $value;
    }
    
    // Save data in data buffers to disk
    // Writing data in larger blocks saves reduces disk write load as 
    // filesystems have a minimum IO size which are usually 512 bytes or more.
    public function save()
    {
        $byteswritten = 0;
        foreach ($this->buffers as $name=>$data)
        {
            // Auto-correction if something happens to the datafile, it gets partitally written to
            // this will correct the file size to always be an integer number of 4 bytes.
            clearstatcache($this->dir.$name);
            if (@filesize($this->dir.$name)%9 != 0) {
                $npoints = floor(filesize($this->dir.$name)/9.0);
                $fh = fopen($this->dir.$name,"c");
                fseek($fh,$npoints*9.0);
                fwrite($fh,$data);
                fclose($fh);
                print "PHPTIMESERIES: FIXED DATAFILE WITH INCORRECT LENGHT\n";
            }
            else
            {
                $fh = fopen($this->dir.$name,"ab");
                fwrite($fh,$data);
                fclose($fh);
            }
            
            $byteswritten += strlen($data);
        }
        
        // Reset buffers
        $this->buffers = array();
        
        return $byteswritten;
    }
    
    private function fopendata($filename,$mode)
    {
        $fh = fopen($filename,$mode);

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
    
    public function update($feedid,$time,$value)
    {
      return $this->post($feedid,$time,$value);
    }

    public function delete($feedid)
    {
        unlink($this->dir."feed_$feedid.MYD");
    }

    public function get_feed_size($feedid)
    {
        return filesize($this->dir."feed_$feedid.MYD");
    }
    
    public function get_npoints($feedid)
    {
        $bytesize = 0;
        $filename = "feed_$feedid.MYD";
        
        if (file_exists($this->dir.$filename))
            clearstatcache($this->dir.$filename);
            $bytesize += filesize($this->dir.$filename);
            
        if (isset($this->buffers[$filename]))
            $bytesize += strlen($this->buffers[$filename]);
            
        return floor($bytesize / 9.0);
    } 

    public function get_data($feedid,$start,$end,$interval,$skipmissing,$limitinterval)
    {
        $start = intval($start/1000);
        $end = intval($end/1000);
        $interval= (int) $interval;

        // Minimum interval
        if ($interval<1) $interval = 1;
        // End must be larger than start
        if ($end<=$start) return array("success"=>false, "message"=>"request end time before start time");
        // Maximum request size
        $req_dp = round(($end-$start) / $interval);
        if ($req_dp>3000) return array("success"=>false, "message"=>"request datapoint limit reached (3000), increase request interval or time range, requested datapoints = $req_dp");
        
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
            $array = unpack("x/Itime/fvalue",$d);
            $dptime = $array['time'];
            
            $value = null;
            
            $lasttime = $atime;
            $atime = $time;
            
            if ($limitinterval)
            {
                $diff = abs($dptime-$time);
                if ($diff<($interval/2)) {
                    $value = $array['value'];
                } 
            } else {
                $value = $array['value'];
                $atime = $array['time'];
            }
            
            if ($atime!=$lasttime) {
                if ($value!==null || $skipmissing===0) $data[] = array($atime*1000,$value);
            }
            
            $i++;
        }

        return $data;
    }
    
    public function lastvalue($feedid)
    {
        if (!file_exists($this->dir."feed_$feedid.MYD"))  return false;

        $fh = fopen($this->dir."feed_$feedid.MYD", 'rb');
        $filesize = filesize($this->dir."feed_$feedid.MYD");

        if ($filesize>=9)
        {
            fseek($fh,$filesize-9);
            $d = fread($fh,9);
            $array = unpack("x/Itime/fvalue",$d);
            $array['time'] = $array['time'];
            fclose($fh);
            return $array;
        }
        else
        {
            fclose($fh);
            return false;
        }
        
        fclose($fh);
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
            $array = unpack("x/Itime/fvalue",$d);

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
    
    public function get_meta($feedid)
    {
    
    }
    
    public function csv_export($feedid,$start,$end,$outinterval)
    {
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

            // $last_time = 0 only occur in the first run
            if (($time!=$last_time && $time>$last_time) || $last_time==0) {
                fwrite($exportfh, $time.",".number_format($array['value'],2)."\n");
            }
        }
        fclose($exportfh);
        exit;
    }

}
