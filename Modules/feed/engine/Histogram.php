<?php

class Histogram
{
    private $mysqli;

    /**
     * Constructor.
     *
     * @param api $mysqli Instance of mysqli
     *
     * @api
    */
    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

// #### \/ Below are required methods

    /**
     * Creates a histogram type mysql table.
     *
     * @param integer $feedid The feedid of the histogram table to be created
    */
    public function create($feedid,$options)
    {
        $feedname = "feed_".$feedid;
        $result = $this->mysqli->query(
        "CREATE TABLE $feedname (
        time INT UNSIGNED, data float, data2 float,
        INDEX ( `time` )) ENGINE=MYISAM");

        return true;
    }

    public function get_meta($feedid)
    {

    }

// #### /\ Above are required methods


// #### \/ Below engine public specific methods

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
        $result = $this->mysqli->query("select data2, sum(data) as kWh from $feedname WHERE time>='$start' AND time<'$end' group by data2 order by data2 Asc");

        $data = array();                                    // create an array for them
        while($row = $result->fetch_array())                // for all the new lines
        {
            $dataValue = $row['kWh'];                       // get the datavalue
            $data2 = $row['data2'];                         // and the instant watts
            $data[] = array($data2 , $dataValue);           // add time and data to the array
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
        $result = $this->mysqli->query("SELECT time, sum(data) as kWh FROM `$feedname` WHERE `data2`>='$min' AND `data2`<='$max' group by time");

        $data = array();
        while($row = $result->fetch_array()) $data[] = array($row['time']* 1000 , $row['kWh']);

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

            $result = $this->mysqli->query("SELECT time, sum(data) as kWh FROM `$feedname` WHERE `data2`>='$min' AND `data2`<='$max' group by time");

            while($row = $result->fetch_array())
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

    
    public function csv_export($feedid,$start,$end,$outinterval,$usertimezone)
    {
    
    }
}
