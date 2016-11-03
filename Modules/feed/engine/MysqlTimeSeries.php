<?php

class MysqlTimeSeries
{
    protected $mysqli;
    protected $log;
    private $writebuffer = array();

    /**
     * Constructor.
     *
     * @api
    */
    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
        $this->log = new EmonLogger(__FILE__);
    }

// #### \/ Below are required methods

    /**
     * Create feed
     *
     * @param integer $feedid The id of the feed to be created
     * @param array $options for the engine
    */
    public function create($feedid,$options)
    {
        $feedname = "feed_".trim($feedid)."";

        $result = $this->mysqli->query("CREATE TABLE $feedname (time INT UNSIGNED NOT NULL, data FLOAT NOT NULL, UNIQUE (time)) ENGINE=MYISAM");
        return true;
    }

    /**
     * Delete feed
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function delete($feedid)
    {
        $this->mysqli->query("DROP TABLE feed_".$feedid);
    }

    /**
     * Gets engine metadata
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_meta($feedid)
    {
        $meta = new stdClass();
        $meta->id = $feedid;
        $meta->start_time = 0;
        $meta->nlayers = 1;
        $meta->npoints = -1;
        $meta->interval = 1;
        return $meta;
    }

    /**
     * Returns engine occupied size in bytes
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_feed_size($feedid)
    {
        $feedname = "feed_".$feedid;
        $result = $this->mysqli->query("SHOW TABLE STATUS LIKE '$feedname'");
        $row = $result->fetch_array();
        $tablesize = $row['Data_length']+$row['Index_length'];
        return $tablesize;
    }

    /**
     * Adds a data point to the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @param arg $value optional padding mode argument
    */
    public function post($feedid,$time,$value,$arg=null)
    {
        $feedname = "feed_".trim($feedid)."";
        $this->mysqli->query("INSERT INTO $feedname (time,data) VALUES ('$time','$value') ON DUPLICATE KEY UPDATE data=VALUES(data)");
    }

    /**
     * Updates a data point in the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function update($feedid,$time,$value)
    {
        $feedid = (int) $feedid;
        if ($this->writebuffer_update_time($feedid,(int)$time,$value)) {
            $this->post_bulk_save();// if data is on buffer, update it and flush buffer now
            $this->log->info("update() feedid=$feedid with buffer");
        }
        else 
        {
            //$this->log->info("update() feedid=$feedid");
            // else, update or insert data value in feed table
            $feedname = "feed_".trim($feedid)."";
            $result = $this->mysqli->query("SELECT * FROM $feedname WHERE time = '$time'");

            if (!$result) return $value;
            $row = $result->fetch_array();

            if ($row) $this->mysqli->query("UPDATE $feedname SET data = '$value' WHERE time = '$time'");
            if (!$row) {$value = 0; $this->mysqli->query("INSERT INTO $feedname (`time`,`data`) VALUES ('$time','$value')");}
        }
        return $value;
    }

    /**
     * Get array with last time and value from a feed
     *
     * @param integer $feedid The id of the feed
    */
    public function lastvalue($feedid)
    {
        $feedid = (int) $feedid;
        $feedname = "feed_".trim($feedid)."";

        $result = $this->mysqli->query("SELECT time, data FROM $feedname ORDER BY time Desc LIMIT 1");
        if ($result && $row = $result->fetch_array()){
            if ($row['data'] !== null) $row['data'] = (float) $row['data'];
            return array('time'=>(int)$row['time'], 'value'=>$row['data']);
        } else {
            return false;
        }
    }

    /**
     * Return the data for the given timerange
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $interval The number os seconds for each data point to return (used by some engines)
     * @param integer $skipmissing Skip null values from returned data (used by some engines)
     * @param integer $limitinterval Limit datapoints returned to this value (used by some engines)
    */
    public function get_data($feedid,$start,$end,$interval,$skipmissing,$limitinterval)
    {
        global $data_sampling;
        
        $feedid = intval($feedid);
        $start = round($start/1000);
        $end = round($end/1000);
        $interval = intval($interval); // time gap in seconds
                
        if ($interval<1) $interval = 1;
        $dp = ceil(($end - $start) / $interval); // datapoints for desied range with set interval time gap
        $end = $start + ($dp * $interval);
        if ($dp<1) return false;

        // Check if datatype is daily so that select over range is used rather than skip select approach
        static $feed_datatype_cache = array(); // Array to hold the cache
        if (isset($feed_datatype_cache[$feedid])) {
            $datatype = $feed_datatype_cache[$feedid]; // Retrieve from static cache
        } else {
            $result = $this->mysqli->query("SELECT datatype FROM feeds WHERE `id` = '$feedid'");
            $row = $result->fetch_array();
            $datatype = $row['datatype'];
            $feed_datatype_cache[$feedid] = $datatype; // Cache it
        }
        if ($datatype==2) $dp = 0;

        $feedname = "feed_".trim($feedid)."";

        $data = array();
        $range = $end - $start; // window duration in seconds
        if ($data_sampling && $range > 180000 && $dp > 0) // 50 hours
        {
            $td = $range / $dp; // time duration for each datapoint
            $stmt = $this->mysqli->prepare("SELECT time, data FROM $feedname WHERE time BETWEEN ? AND ? ORDER BY time ASC LIMIT 1");
            $t = $start; $tb = 0;
            $stmt->bind_param("ii", $t, $tb);
            $stmt->bind_result($dataTime, $dataValue);
            for ($i=0; $i<$dp; $i++)
            {
                $tb = $start + intval(($i+1)*$td);
                $stmt->execute();
                if ($stmt->fetch()) {
                    if ($dataValue!=NULL || $skipmissing===0) { // Remove this to show white space gaps in graph
                        $time = $dataTime * 1000;
                        if ($dataValue !== null) $dataValue = (float) $dataValue ;
                        $data[] = array($time, $dataValue);
                    }
                }
                $t = $tb;
            }
        } else {
            if ($range > 5000 && $dp > 0) // 83.33 min
            {
                $td = intval($range / $dp);
                $sql = "SELECT time DIV $td AS time, AVG(data) AS data".
                    " FROM $feedname WHERE time BETWEEN $start AND $end".
                    " GROUP BY 1 ORDER BY time ASC";
            } else if ($range == 1){
                $td = 1;
                $sql = "SELECT time, data FROM $feedname".
                    " WHERE time = $start LIMIT 1";
            } else {
                $td = 1;
                $sql = "SELECT time, data FROM $feedname".
                    " WHERE time BETWEEN $start AND $end ORDER BY time ASC";
            }

            $result = $this->mysqli->query($sql);
            if($result) {
                while($row = $result->fetch_array()) {
                    $dataValue = $row['data'];
                    if ($dataValue!=NULL || $skipmissing===0) { // Remove this to show white space gaps in graph
                        $time = $row['time'] * 1000 * $td;
                        if ($dataValue !== null) $dataValue = (float) $dataValue ;
                        $data[] = array($time , $dataValue);
                    }
                }
            }
        }

        return $data;
    }

    public function export($feedid,$start)
    {
        // Feed id and start time of feed to export
        $feedid = intval($feedid);
        $start = intval($start)-1;

        // Open database etc here
        // Extend timeout limit from 30s to 2mins
        set_time_limit (120);

        // Regulate mysql and apache load.
        $block_size = 400;
        $sleep = 80000;

        $feedname = "feed_".trim($feedid)."";
        $fileName = $feedname.'.csv';

        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$fileName}");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $fh = @fopen( 'php://output', 'w' );

        // Load new feed blocks until there is no more data
        $moredata_available = 1;
        while ($moredata_available)
        {
            // 1) Load a block
            $result = $this->mysqli->query("SELECT * FROM $feedname WHERE time>$start
            ORDER BY time Asc Limit $block_size");

            $moredata_available = 0;
            while($row = $result->fetch_array())
            {
                // Write block as csv to output stream
                if (!isset($row['data2'])) {
                    fputcsv($fh, array($row['time'],$row['data']));
                } else {
                    fputcsv($fh, array($row['time'],$row['data'],$row['data2']));
                }

                // Set new start time so that we read the next block along
                $start = $row['time'];
                $moredata_available = 1;
            }
            // 2) Sleep for a bit
            usleep($sleep);
        }

        fclose($fh);
        exit;
    }

    public function csv_export($feedid,$start,$end,$outinterval,$usertimezone)
    {
        global $csv_decimal_places, $csv_decimal_place_separator, $csv_field_separator, $data_sampling;

        require_once "Modules/feed/engine/shared_helper.php";
        $helperclass = new SharedHelper();

        $interval = intval($outinterval);
        $feedid = intval($feedid);
        $start = round($start);
        $end = round($end);
        $skipmissing = 0;

        if ($interval<1) $interval = 1;
        $dp = ceil(($end - $start) / $interval); // datapoints for desied range with set interval time gap
        $end = $start + ($dp * $interval);
        if ($dp<1) return false;
        if ($end == 0) $end = time();
        
        // Check if datatype is daily so that select over range is used rather than skip select approach
        static $feed_datatype_cache = array(); // Array to hold the cache
        if (isset($feed_datatype_cache[$feedid])) {
            $datatype = $feed_datatype_cache[$feedid]; // Retrieve from static cache
        } else {
            $result = $this->mysqli->query("SELECT datatype FROM feeds WHERE `id` = '$feedid'");
            $row = $result->fetch_array();
            $datatype = $row['datatype'];
            $feed_datatype_cache[$feedid] = $datatype; // Cache it
        }
        if ($datatype==2) $dp = 0;

        $feedname = "feed_".trim($feedid)."";

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
        $range = $end - $start; // window duration in seconds
        if ($data_sampling && $range > 180000 && $dp > 0) // 50 hours
        {
            $td = $range / $dp; // time duration for each datapoint
            $stmt = $this->mysqli->prepare("SELECT time, data FROM $feedname WHERE time BETWEEN ? AND ? ORDER BY time ASC LIMIT 1");
            $t = $start; $tb = 0;
            $stmt->bind_param("ii", $t, $tb);
            $stmt->bind_result($time, $dataValue);
            for ($i=0; $i<$dp; $i++)
            {
                $tb = $start + intval(($i+1)*$td);
                $stmt->execute();
                if ($stmt->fetch()) {
                    if ($dataValue!=NULL || $skipmissing===0) { // Remove this to show white space gaps in graph
                        $timenew = $helperclass->getTimeZoneFormated($time,$usertimezone);
                        fwrite($exportfh, $timenew.$csv_field_separator.number_format((float)$dataValue,$csv_decimal_places,$csv_decimal_place_separator,'')."\n");
                    }
                }
                $t = $tb;
            }
        } else {
            if ($range > 5000 && $dp > 0) // 83.33 min
            {
                $td = intval($range / $dp);
                $sql = "SELECT time DIV $td AS time, AVG(data) AS data".
                    " FROM $feedname WHERE time BETWEEN $start AND $end".
                    " GROUP BY 1 ORDER BY time ASC";
            } else {
                $td = 1;
                $sql = "SELECT time, data FROM $feedname".
                    " WHERE time BETWEEN $start AND $end ORDER BY time ASC";
            }
            $result = $this->mysqli->query($sql);
            if($result) {
                while($row = $result->fetch_array()) {
                    $dataValue = $row['data'];
                    if ($dataValue!=NULL || $skipmissing===0) { // Remove this to show white space gaps in graph
                        $time = $row['time'] * $td;
                        $timenew = $helperclass->getTimeZoneFormated($time,$usertimezone);
                        fwrite($exportfh, $timenew.$csv_field_separator.number_format((float)$dataValue,$csv_decimal_places,$csv_decimal_place_separator,'')."\n");
                    }
                }
            }
        }

        fclose($exportfh);
        exit;
    }

// #### /\ Above are required methods


// #### \/ Below are buffer write methods

    // Insert data in post buffer
    public function post_bulk_prepare($feedid,$time,$value,$arg=null)
    {
        $this->writebuffer[(int)$feedid][] = array((int)$time,$value);
        //$this->log->info("post_bulk_prepare() $feedid, $time, $value, $arg");
    }

    // Saves post buffer to mysql feed_table, performing bulk inserts instead of an insert for each point
    public function post_bulk_save()
    {
        $stepcnt = 1048576/30; // Data points to save in each insert command limit is max_allowed_packet = 1Mb default ~20-30bytes are used for each data point
        foreach ($this->writebuffer as $feedid=>$data) {
            $feedname = "feed_".trim($feedid)."";
            $cnt=count($data);
            if ($cnt>0) {
                $p = 0; // point
                while($p<$cnt) {
                    $sql_values="";
                    $s=0; // data point step
                    while($s<$stepcnt) {
                        if (isset($data[$p][0]) && isset($data[$p][1])) {
                            $sql_values .= "(".$data[$p][0].",".$data[$p][1]."),";
                        }
                        $s++; $p++; 
                        if ($p>=$cnt) break;
                    }
                    if ($sql_values!="") {
                        $this->log->info("post_bulk_save() " . "INSERT INTO $feedname (`time`,`data`) VALUES " . substr($sql_values,0,-1) . " ON DUPLICATE KEY UPDATE data=VALUES(data)");
                        $this->mysqli->query("INSERT INTO $feedname (`time`,`data`) VALUES " . substr($sql_values,0,-1) . " ON DUPLICATE KEY UPDATE data=VALUES(data)");
                    }
                }
            }
        }
        $this->writebuffer = array(); // clear buffer
    }

    

// #### \/ Below engine specific public methods

    public function delete_data_point($feedid,$time)
    {
        $feedid = intval($feedid);
        $time = intval($time);

        $feedname = "feed_".trim($feedid)."";
        $this->mysqli->query("DELETE FROM $feedname where `time` = '$time' LIMIT 1");
    }

    public function deletedatarange($feedid,$start,$end)
    {
        $feedid = intval($feedid);
        $start = intval($start/1000.0);
        $end = intval($end/1000.0);

        $feedname = "feed_".trim($feedid)."";
        $this->mysqli->query("DELETE FROM $feedname where `time` >= '$start' AND `time`<= '$end'");

        return true;
    }


// #### \/ Bellow are engine private methods    

    // Search time in buffer if found update its value and return true 
    private function writebuffer_update_time($feedid,$time,$newvalue) {
       if (isset($this->writebuffer[$feedid])) {
           $array=$this->writebuffer[$feedid];
           foreach ($array as $key => $val) {
               if ($val[0] === $time) {
                   $this->writebuffer[$feedid][$key][1] = $newvalue;
                   return true;
               }
           }
       }
       return false;
    }

}