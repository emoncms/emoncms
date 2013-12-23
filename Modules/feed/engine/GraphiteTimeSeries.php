<?php

class GraphiteTimeSeries
{

  private $host;
  private $port;
  private $write_socket;
  private $root;

  /**
   * Constructor.
   *
   * @api
  */
  public function __construct($host, $port)
  {
    $this->host = $host;
    $this->port = $port;
    if ($host && $port) $this->connect_write();
    $this->root = "emoncms";
  }

  /**
   * Utility method to connect to the Graphite submission port
   * Uses the $host and $port instance values and sets the $write_socket value
  */
  private function connect_write()
  {
    $this->write_socket = stream_socket_client("tcp://$this->host:$this->port");
  }

  /**
   * Utility method to convert a feedid to a Graphite target name
   *
   * @param integer $feedid The feedid of the timestore
   * @returns A string name of the Graphite target to use
  */
  private function get_graphite_graphname($feedid)
  {
    $feedid = intval($feedid);
    return "$this->root.feed_$feedid";
  }

  /**
   * Utility method to flip an array of points
   * Given: [value, timestamp]
   * Returns: [timestamp, value]

   * @param array $data An array of [value, timestamp] pairs
   * @returns array An array of [timestamp, value] pairs
  */
  private function flip_array($data)
  {
    $out = array();
    foreach ($data as $item) {
      $out[] = array($item[1], $item[0]);
    }
    return $out;
  }

  /**
   * Gets raw data from a Graphite server
   *
   * @param integer $feedid The feedid of the timestore to fetch from
   * @param integer $start The unix timestamp in seconds of the start of the data range
   * @param integer $end The unix timestamp in seconds of the end of the data range
   * @returns array The requested raw data from Graphite, as [timestamp,value]
  */
  private function get_graphite_data($feedid,$start,$end)
  {
    $data = array();

    $feedname = $this->get_graphite_graphname($feedid);

    $url = "http://$this->host/render?target=$feedname&from=$start&until=$end&format=json";
    #error_log($url);
    $reader = fopen($url, "r");
    $raw_data = stream_get_contents($reader);
    $json_data = json_decode($raw_data, true);
    $raw_datapoints = array();
    if (count($json_data) == 1) {
      $raw_datapoints = $this->flip_array($json_data[0]['datapoints']);
    }
    return $raw_datapoints;
  }

  /**
   * Utility method to average points between a specific range
   * data format must be an array of [timestamp, value] data points
   * The indexhint can be used to avoid searching through the array
   * The indexhint will be updated to point to the next index after the $end value
   *
   * @param array $data An array of [timestamp, value] points
   * @param integer $start The unix timestamp in seconds of the start of the range, inclusive
   * @param integer $end The unix timestamp in seconds of the end of the range, exclusive
   * @param integer $indexhint The index in the data array where $start is found
   * @returns The average of the values between that range, or FALSE if none are found
  */
  private function average_data_range($data, $start, $end, &$indexhint=0)
  {
    // seek backwards to find the start of the data
    while ($indexhint > 0 && $data[$indexhint-1][0] >= $start) $indexhint--;
    // seek forwards to find the start of the data
    while ($indexhint < count($data)-1 && $data[$indexhint][0] < $start) $indexhint++;

    // average the numbers together
    $sum = 0.0;
    $count = 0;
    while ($indexhint < count($data)-1 && $data[$indexhint][0] < $end) {
      $value = $data[$indexhint][1];
      if ($value != null) {
        $sum += $value;
        $count++;
      }
      $indexhint++;
    }
    if ($count == 0) return FALSE;
    return $sum / $count;
  }

  /**
   * Utility method to interpolate between the data fetched from Graphite
   * and the requested data points
   *
   * @param array $data An array of [timestamp, value] points
   * @param integer $start The unix timestamp in seconds of the start of the data range
   * @param integer $end The unix timestamp in seconds of the end of the data range
   * @param integer $dp The number of data points to return
   * @return array An array of [timestamp, value] points
  */
  private function interpolate_data($data,$start,$end,$dp)
  {
    $outdata = array();
    $curDataIndex = 0;
    $range = $end - $start;
    $td = $range / $dp;    // the time length of each requested data point
    $tb = $start;          // earlier boundary of data point
    for ($i=0; $i<$dp; $i++)
    {
      $tn = $start + intval(($i+1)*$td);   // later boundary of data point
      $oldDataIndex = $curDataIndex;
      $value = $this->average_data_range($data, $tb, $tn, $curDataIndex);
      if (($curDataIndex - $oldDataIndex) == 1) {   // single number was used
        $tb = $data[$oldDataIndex][0];	// use the actual data's timestamp
      }
      if ($value !== FALSE) {
        $outdata[] = array(intval($tb*1000.0), strval(intval($value)));
      }
      $tb = $tn;
    }
    return $outdata;
  }

  /**
   * Creates a Graphite timestore graph
   *
   * @param integer $feedid The feedid of the timestore to be created
  */
  public function create($feedid)
  {
    // Graphite will build this when the first data is inserted
  }
  
  /**
   * Adds a data point to the time store
   *
   * @param integer $feedid The feedid of the timestore to add to
   * @param integer $time The unix timestamp of the data point, in seconds
   * @param integer $value The value of the data point
  */
  public function post($feedid,$time,$value)
  {
    $feedname = $this->get_graphite_graphname($feedid);
    $line = "$feedname $value $time\n";
    $result = fwrite($this->write_socket, $line);
    if ($result === FALSE) {
      $this->connect_write();
      $result = fwrite($this->write_socket, $line);
    }
    fflush($this->write_socket);
  }
  
  /**
   * Return the data for the given timerange
   * If there are less data points available than requested, only the available ones will be returned
   * If there are more data points available than requested, some will be averaged together
   *
   * @param integer $feedid The feedid of the timestore to fetch from
   * @param integer $start The unix timestamp in ms of the start of the data range
   * @param integer $end The unix timestamp in ms of the end of the data range
   * @param integer $dp The number of data points to return
  */
  public function get_data($feedid,$start,$end,$dp)
  {
    $feedid = intval($feedid);
    $start = floatval($start);
    $end = floatval($end);
    $start = intval($start/1000); $end = intval($end/1000);

    if ($end == 0) $end = intval(time());
    $dp = intval($dp);
    if ($dp < 50) $dp = 50;    # realtime widget is silly
    $raw_datapoints = $this->get_graphite_data($feedid, $start, $end);
    $data = $this->interpolate_data($raw_datapoints, $start, $end, $dp);

    return $data;
  }

  /**
   * Return the average value for the given timerange
   *
   * @param integer $feedid The feedid of the timestore to fetch from
   * @param integer $start The unix timestamp in ms of the start of the data range
   * @param integer $end The unix timestamp in ms of the end of the data range
   * @return integer The average value for the timerange
  */
  public function get_average($feedid,$start,$end,$interval)
  {
    $feedid = intval($feedid);
    $start = floatval($start);
    $end = floatval($end);
    $start = intval($start/1000); $end = intval($end/1000);
    if ($end == 0) $end = intval(time());

    $raw_datapoints = $this->get_graphite_data($feedid, $start, $end);
    $count = 0;
    $sum = 0;
    foreach ($raw_datapoints as $item) {
      $value = $item[1];
      if ($value != null) {
        $sum += $value;
        $count++;
      }
    }
    if ($sum > 0) {
      return $sum/$count;
    }
    return 0;
  }
  
  public function export($feedid,$start)
  {
      // Feed id and start time of feed to export
      $feedid = intval($feedid);
      $start = intval($start);
      $start = intval($start/1000);

      // Open database etc here
      // Extend timeout limit from 30s to 2mins
      set_time_limit (120);

      // Regulate graphite and apache load.
      $block_size = 8192;
      $sleep = 80000;

      $feedname = get_graphite_graphname($feedid);
      $fileName = "feed_".trim(feedid).'.csv';

      // There is no need for the browser to cache the output
      header("Cache-Control: no-cache, no-store, must-revalidate");

      // Tell the browser to handle output as a csv file to be downloaded
      header('Content-Description: File Transfer');
      header("Content-type: text/csv");
      header("Content-Disposition: attachment; filename={$fileName}");

      header("Expires: 0");
      header("Pragma: no-cache");

      // Write to output stream
      $reader = @fopen("http://$this->host/render?target=$feedname&from=$start&format=raw", "r");
      $fh = @fopen( 'php://output', 'w' );

      // Header information
      $returnedstart = null;
      $returnedstep = null;
      $curtime = null;  // time of this row
      $prevchunk = "";  // unparsed from previous chunk

      // Load new feed blocks until there is no more data 
      $moredata_available = 1;
      while ($moredata_available)
      {
          // read in a chunk of data
          $chunk = fread($reader, $block_size);
          // if it's the first chunk, parse the header that says what the step size is
          if ($returnedstep == null) {
              $pos = strpos($chunk, "|");
              if ($pos !== FALSE) {
                  $header = substr($chunk, 0, $pos);
                  $sections = split(",", $header);
                  $sections = array_slice($sections, -3);
                  $returnedstart = intval($sections[0]);
                  $returnedstep = intval($sections[2]);
                  $curtime = $returnedstart;

                  $chunk = substr($chunk, $pos+1); // remove the header
              }
          }
          // parse the actual data
          if ($returnedstep != null) {
              // Add any leftover data from the previous chunk
              $chunk = $prevchunk + $chunk;
              $pos = 0;
              $next = 0;
              while ($next !== FALSE) {
                  $value = null;
                  $next = strpos($chunk, ",", $pos);
                  if ($next === FALSE && $feof($reader)) {
                      $value = substr($chunk, $pos, $next);
                      $moredata_available = 0;
                  }
                  if ($next !== FALSE) {
                      $value = substr($chunk, $pos, $next);
                      $pos = $next + 1;
                  }
                  if ($value) {
                      fputcsv($fh, array($curtime,$value));
                      $curtime += $returnedstep;
                  }
              }
              $leftover = substr($chunk, $pos);
          }
          // Sleep for a bit
          usleep($sleep);
      }

      fclose($fh);
      exit;
  }
  
  public function delete_data_point($feedid,$time)
  {
    $this->post($feedid, $time, null);
  }

  public function deletedatarange($feedid,$start,$end)
  {
    $feedid = intval($feedid);
    $start = floatval($start);
    $end = floatval($end);
    $start = intval($start/1000); $end = intval($end/1000);
    if ($end == 0) $end = intval(time());

    $raw_datapoints = $this->get_graphite_data($feedid, $start, $end);
    $count = 0;
    $sum = 0;
    foreach ($raw_datapoints as $item) {
      $this->delete_data_point($feedid, $item[0]);
    }
    return true;
  }
  
  public function delete($feedid)
  {
    // Graphite can't do this remotely
  }
  
  public function get_feed_size($feedid)
  {  
    // Graphite can't do this remotely
    // Entirely dependent on the storage-schemas.conf file
    return 0;
  }
  
}
