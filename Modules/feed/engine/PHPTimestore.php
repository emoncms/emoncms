<?php

class PHPTimestore
{
	private $dir = "/var/lib/timestore/";

	public function __construct()
	{

	}

	public function create($feedid,$interval)
	{
		if ($interval<5) $interval = 5;
		// Check to ensure we dont overwrite an existing feed
		if (!$meta = $this->get_meta($feedid))
		{

			// Set initial feed meta data
			$meta = new stdClass();
			$meta->feedid = $feedid;
			$meta->npoints = 0;
			$meta->start = 0;
			$meta->nmetrics = 1;
			$meta->interval = $interval;

			// Save meta data
			$this->save_meta($feedid,$meta);
		}

		if (file_exists($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT).".tsdb")) return true;
		return false;
	}

	public function post($feedid, $timestamp, $value)
	{
		$now = time();
		$start = $now-(3600*24*365*5); // 5 years in past
		$end = $now+(3600*48);         // 48 hours in future
		$rc = 0;

		if ($timestamp>$start && $timestamp<$end)
		{

			$value = (float) $value;
			// If meta data file does not exist then exit
			if (!$meta = $this->get_meta($feedid))
			{
				return false;
			}

			/* For a new file this point represents the start of the database */
			$timestamp = floor(($timestamp / $meta->interval)) * $meta->interval; /* round down */
			if ($meta->npoints == 0)
			{
				$meta->start = $timestamp;
			}

			/* Sanity checks */
			if ($timestamp < $meta->start)
			{
				return false; // in the past
			}

			/* Determine position of point in the top-level */
			$point = floor(($timestamp - $meta->start) / $meta->interval);

			/* Update layers */
			$rc = $this->update_layer($meta,0,$point,$meta->npoints,$value);
			if ($rc == 0)
			{
				/* Update metadata with new number of top-level points */
				if ($point >= $meta->npoints)
				{
					$meta->npoints = $point + 1;
					$this->save_meta($feedid,$meta);
				}
			}

		}
		return $rc;
	}

	public function update_layer($meta,$layer,$point,$npoints,$value)
	{
		$decimation = array(20, 6, 6, 4, 7);


		// print "$layer $point $npoints $value\n";
		$tsdb_max_padding_block = 1024 * 1024;

		$fh = fopen($this->dir.str_pad($meta->feedid, 16, '0', STR_PAD_LEFT)."_".$layer."_.dat", 'c+');

		if ($point > $npoints)
		{
			$npadding = ($point - $npoints);

			if ($npadding>2500000)
			{
				echo "ERROR 2!!!";
				return false;
			}

			// Maximum points per block
			$pointsperblock = $tsdb_max_padding_block / 4; // 262144

			// If needed is less than max set to padding needed:
			if ($npadding < $pointsperblock) $pointsperblock = $npadding;

			// Fill padding buffer
			$buf = '';
			for ($n = 0; $n < $pointsperblock; $n++)
			{
				$buf .= pack("f",NAN);
			}


			fseek($fh,4*$npoints);

			do {
				if ($npadding < $pointsperblock)
				{
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

		if ($layer<5)
		{

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
			while ($count--)
			{
					$i++;
					if (is_nan($d[$i]))
					{
						// Skip unknown values
						continue;
					}

					// Summing
					$sum += $d[$i];
					$sum_count ++;
			}

			if ($sum_count>0)
			{
				$average = $sum / $sum_count;
			} else {
				$average = NAN;
			}


			$this->update_layer($meta,$layer+1,floor($point/$decimation[$layer]),floor(($npoints+$decimation[$layer]-1)/$decimation[$layer]),$average);
		}

		return 0;
	}

	public function get_data($feedid,$start,$end)
	{
		$decimation = array(20, 6, 6, 4, 7);
		//$feedid = 21; $start = 1317572497; $end = 1317692497; $dp = 10;
		$feedid = intval($feedid);
		$start = intval($start/1000);
		$end = intval($end/1000);
		$dp = 1000;

		// Load the timestore meta data
		$meta = $this->get_meta($feedid);
		if (!$meta) return false;

		// The number of datapoints in the query range:
		$dp_in_range = ($end - $start) / $meta->interval;

		// Divided by the number we need gives the number of datapoints to skip
		// i.e if we want 1000 datapoints out of 100,000 then we need to get one
		// datapoints every 100 datapoints.
		$skipsize = round($dp_in_range / $dp);
		if ($skipsize<1) $skipsize = 1;

		$interval = $meta->interval;

		$layer = 0;

		if ($skipsize>=20)
		{
			$layer = 1; $interval *= 20;
		}
		if ($skipsize>=20*6)
		{
			$layer = 2; $interval *= 6;
		}
		if ($skipsize>=20*6*6)
		{
			$layer = 3; $interval *= 6;
		}
		if ($skipsize>=20*6*6*4)
		{
			$layer = 4; $interval *= 4;
		}
		if ($skipsize>=20*6*6*4*7)
		{
			$layer = 5; $interval *= 7;
		}

		$dp_in_range = ($end - $start) / $interval;
		$skipsize = round($dp_in_range / $dp);
		if ($skipsize<1)
		{
			$skipsize = 1;
		}

		$feedname = str_pad($feedid, 16, '0', STR_PAD_LEFT)."_".$layer."_.dat";
		$primaryfeedname = $this->dir.$feedname;
		$filesize = filesize($primaryfeedname);

		// Calculate the starting datapoint position in the timestore file
		if ($start>$meta->start){
			$startpos = ceil(($start - $meta->start) / $interval);
		}
		else
		{
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
			if ($pos*4 > $filesize-4)
			{
				break;
			}

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
			$time = date("Y-n-j H:i:s", $meta->start + $meta->interval * $meta->npoints);
			return array('time'=>$time, 'value'=>$val[1]);
		}
		else
		{
			return array('time'=>0, 'value'=>0);
		}
	}

	public function get_average($feedid,$start,$end,$interval)
	{
		// not yet implemented
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

		if (!file_exists($this->dir.$feedname)) return false;

		$meta = new stdClass();
		$metafile = fopen($this->dir.$feedname, 'rb');

		fseek($metafile,8);
		$d = fread($metafile,8);
		$tmp = unpack("h*",$d);
		$meta->feedid = (int) strrev($tmp[1]);
		$tmp = unpack("I",fread($metafile,4));
		$meta->nmetrics = $tmp[1];
		$tmp = unpack("I",fread($metafile,4));
		$meta->npoints = $tmp[1];
		$tmp = unpack("I",fread($metafile,8));
		$meta->start = $tmp[1];
		$tmp = unpack("I",fread($metafile,4));
		$meta->interval = $tmp[1];
		fclose($metafile);

		return $meta;
	}

	public function save_meta($feedid,$meta)
	{
		$feedid = (int) $feedid;
		$feedname = str_pad($feedid, 16, '0', STR_PAD_LEFT).".tsdb";


		$metafile = fopen($this->dir.$feedname, 'wb');

		fwrite($metafile,pack("I",0));
		fwrite($metafile,pack("I",0));
		fwrite($metafile,pack("h*",strrev(str_pad($feedid, 16, '0', STR_PAD_LEFT))));
		fwrite($metafile,pack("I",$meta->nmetrics));
		fwrite($metafile,pack("I",$meta->npoints));
		fwrite($metafile,pack("I",$meta->start));
		fwrite($metafile,pack("I",0));
		fwrite($metafile,pack("I",$meta->interval));

		//$decimation = array(20, 6, 6, 4, 7);
		//foreach ($decimation as $d) fwrite($metafile,pack("I",$d));

		//$flags = array(); for($i=0; $i<32; $i++) $flags[]=0;
		//foreach ($flags as $d) fwrite($metafile,pack("I",$d));

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

	public function delete($feedid)
	{
		unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT).".tsdb");
		unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_0_.dat");
		unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_1_.dat");
		unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_2_.dat");
		unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_3_.dat");
		unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_4_.dat");
		unlink($this->dir.str_pad($feedid, 16, '0', STR_PAD_LEFT)."_5_.dat");
	}

}
