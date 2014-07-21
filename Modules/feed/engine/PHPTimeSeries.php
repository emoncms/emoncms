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

    // POST OR UPDATE
    //
    // - fix if filesize is incorrect (not a multiple of 9)
    // - append if file is empty
    // - append if datapoint is in the future
    // - update if datapoint is older than last datapoint value
    public function post($feedid,$time,$value)
    {
        $this->log->info("PHPTimeSeries:post feedid=$feedid time=$time value=$value");
        
        // Get last value
        $fh = fopen($this->dir."feed_$feedid.MYD", 'rb');
        if (!$fh) {
            $this->log->warn("PHPTimeSeries:post could not open data file feedid=$feedid");
            return false;
        }
        
        $filesize = filesize($this->dir."feed_$feedid.MYD");

        $csize = round($filesize / 9.0, 0, PHP_ROUND_HALF_DOWN) *9.0;
        if ($csize!=$filesize) {
        
            $this->log->warn("PHPTimeSeries:post filesize not integer multiple of 9 bytes, correcting feedid=$feedid");
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

    public function get_data($feedid,$start,$end,$outinterval)
    {
        $start = $start/1000; $end = $end/1000;

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
                $data[] = array($time*1000,$array['value']);
            }
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
            $array['time'] = date("Y-n-j H:i:s", $array['time']);
            return $array;
        }
        else
        {
            return false;
        }
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
