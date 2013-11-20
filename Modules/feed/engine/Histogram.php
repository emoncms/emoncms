<?php

class Histogram
{
  private $conn;

  /**
   * Constructor.
   *
   * @param api $conn Instance of db
   *
   * @api
  */
  public function __construct($conn)
  {
    $this->conn = $conn;
  }
  
  /**
   * Creates a histogram type mysql table.
   *
   * @param integer $feedid The feedid of the histogram table to be created
  */
  public function create($feedid)
  {
    $feedname = "feed_" . $feedid;
    if ($default_engine == Engine::MYSQL)
        $sql = ("CREATE TABLE $feedname (" .
                "time INT UNSIGNED, data float, data2 float, " .
                "INDEX (time)) ENGINE = MYISAM");
    db_query($this->conn, $sql);
  }

  /**
   * Get total kwh used at different powers over a time window
   *
   * @param integer $feedid The feedid of the histogram table
   * @param integer $start  Start time UNIX Timestamp
   * @param integer $end    Start time UNIX Timestamp
   *
   * @return array of power vs kwh
  */
  public function get_power_vs_kwh($feedid,$start,$end)
  {
    $feedid = intval($feedid);
    $start = intval($start);
    $end = intval($end);

    if ($end == 0) $end = time()*1000;
    $feedname = "feed_".trim($feedid)."";
    $start = $start/1000; $end = $end/1000;
    $data = array();

    // Histogram has an extra dimension so a sum and group by needs to be used.
    $sql = ("SELECT data2, sum(data) AS kWh FROM '$feedname' WHERE time >= '$start' AND time < '$end' GROUP BY data2 ORDER BY data2 ASC;");
    $query = db_query($this->conn, $sql);
	
    $data = array();                                      // create an array for them
    while($row = db_fetch_array($result))                 // for all the new lines
    {
      $dataValue = $row['kWh'];                           // get the datavalue
      $data2 = $row['data2'];            		  // and the instant watts
      $data[] = array($data2 , $dataValue);               // add time and data to the array
    }
    return $data;
  }

  /**
   * Get daily kwh used in a power range
   *
   * @param integer $feedid The feedid of the histogram table
   * @param integer $min    Minimum power to take into account
   * @param integer $max    Maximum power to take into account
   *
   * @return array of kwh per day
  */
  public function get_kwhd_atpower($feedid, $min, $max)
  {
    $feedid = intval($feedid);
    $min = intval($min);
    $max = intval($max);

    $feedname = "feed_".trim($feedid)."";
    $sql = ("SELECT time, sum(data) AS kWh FROM '$feedname' WHERE 'data2' >= '$min' AND 'data2' <= '$max' GROUP BY time;");
    $result = db_query($this->conn, $sql);

    $data = array();
    while($row = db_fetch_array($result)) $data[] = array($row['time']* 1000 , $row['kWh']); 

    return $data;
  }

  /**
   * Get daily kwh used in multiple power ranges, similar to above.
   *
   * @param integer $feedid The feedid of the histogram table
   * @param array $points   Power range division points, ie: 0-1000W-2000W-5000W
   *
   * @return array of multiple kwh per day used at each requested point 
  */
  public function get_kwhd_atpowers($feedid, $points)
  {
    $feedid = intval($feedid);
    $feedname = "feed_".trim($feedid)."";

    $points = json_decode(stripslashes($points));
    
    $data = array();

    for ($i=0; $i<count($points)-1; $i++)
    {
      $min = intval($points[$i]);
      $max = intval($points[$i+1]);

      $sql = ("SELECT time, sum(data) AS kWh FROM '$feedname' WHERE 'data2' >= '$min' AND 'data2' <= '$max' GROUP BY time;");
      $result = db_query($this->conn, $sql);

      while($row = db_fetch_array($result))
      { 
        if (!isset($data[$row['time']])) {
          $data[$row['time']] = array(0,0,0,0,0);
          $data[$row['time']][0] = (int)$row['time']; 
        }
        $data[$row['time']][$i+1] = (float)$row['kWh']; 
      }
    }
    $out = array();
    foreach ($data as $item) $out[] = $item;

    return $out;
  }

}
