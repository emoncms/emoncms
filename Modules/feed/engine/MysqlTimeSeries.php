<?php
include_once dirname(__FILE__) . '/shared_helper.php';

class MysqlTimeSeries implements engine_methods
{
    protected $dir = '';
    protected $generic = true;
    protected $prefix = "feed_";
    protected $mysqli;
    protected $redis;
    protected $log;
    private $writebuffer = array();

    /**
     * Constructor.
     *
     * @api
    */
    public function __construct($mysqli, $redis=false, $settings=array()) {
        $this->log = new EmonLogger(__FILE__);
        if (isset($settings['datadir'])) {
            $this->dir = $settings['datadir'];
        }
        if (isset($settings['database'])) {
            $database = $settings['database'];

            if (isset($settings['server']) && isset($settings['port'])) {
                $server = $settings['server'];
                $port = $settings['port'];
            }
            else {
                global $server, $port;
            }
            if (isset($settings['username']) && isset($settings['password'])) {
                $username = $settings['username'];
                $password = $settings['password'];
            }
            else {
                global $username, $password;
            }

            $this->mysqli = @new mysqli($server,$username,$password,$database,$port);
            if ($this->mysqli->connect_error) {
                $this->log->error("Can't connect to database:". $mysqli->connect_error);
                $this->mysqli = $mysqli;
            }
        }
        else {
            $this->mysqli = $mysqli;
        }
        $this->redis = $redis;

        if (isset($settings['prefix'])) {
            $this->prefix = $settings['prefix'];
        }
        if (isset($settings['generic'])) {
            $this->generic = $settings['generic'];
        }
    }

// #### \/ Below are required methods

    /**
     * Create feed
     *
     * @param integer $feedid The id of the feed to be created
     * @param array $options for the engine
    */
    public function create($feedid, $options)
    {
        $result = $this->create_meta($feedid, $options);
        if (is_string($result)) {
            return $result;
        }
        $table = $this->get_table(intval($feedid));
        $name = $table['name'];
        $type = $table['type'];

        $this->mysqli->query("CREATE TABLE $name (time INT UNSIGNED NOT NULL, data $type, UNIQUE (time)) ENGINE=MYISAM");
        return true;
    }

    /**
     * Delete feed
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function delete($feedid)
    {
        $meta = $this->dir."$feedid.meta";
        if (!file_exists($meta)) {
            unlink($meta);
        }
        $table = $this->get_table_name(intval($feedid));
        $this->mysqli->query("DROP TABLE $table");
    }

    /**
     * Gets engine metadata
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_meta($feedid)
    {
        $feedid= intval($feedid);
        if (!$this->generic) {
            $meta = $this->read_meta($feedid);
        }
        else {
            $meta = new stdClass();
            $meta->table_name = ($this->prefix ? $this->prefix : "").trim($feedid);
            $meta->value_type = "FLOAT NOT NULL";
            $meta->start_time = 0;
        }
        if ($meta->start_time == 0) {
            $result = $this->mysqli->query("SELECT time FROM ".$meta->table_name." ORDER BY time ASC LIMIT 1");
            if ($result && $row = $result->fetch_array()) {
                $meta->start_time = (int) $row['time'];

                if (!$this->generic) {
                    $this->write_meta($feedid, $meta);
                }
            }
        }
        $result = $this->mysqli->query("SELECT COUNT(*) FROM ".$meta->table_name);
        if ($result && $row = $result->fetch_array()) {
            $meta->npoints = $row[0];
        }
        else {
            $meta->npoints = -1;
        }
        return $meta;
    }

    /**
     * Return the averaged data over interval for the given timerange. The returned timestamp denotes the intervals start time. Averaging is performed over all values from time to time+interval.
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $interval The number os seconds for each data point to return (used by some engines)
    */
    public function get_average($feedid, $start, $end, $interval)
    {
        $feedid = (int) $feedid;
        $start = intval($start/1000);
        $end = intval($end/1000);
        $interval= (int) $interval;

        // Minimum interval
        if ($interval < 1) $interval = 1;
        // Maximum request size
        $req_dp = round(($end - $start)/$interval);
        if ($req_dp > 10000) return array('success'=>false, 'message'=>"Request datapoint limit reached (10000), increase request interval or time range, requested datapoints = $req_dp");

        $table = $this->get_table_name($feedid);
        $data = array();

        $sql = "SELECT time, AVG(data) AS data_avg FROM $table WHERE time >= $start AND time < $end GROUP BY FLOOR(time/$interval)";
        $result = $this->mysqli->query($sql);
        if ($result) {
            while($row = $result->fetch_array()) {
                $data[] = array((int) $row['time']*1000, (float) $row['data_avg']);
            }
        }
        return $data;
    }

    /**
     * Return the averaged data over interval for the given timerange. The returned timestamp denotes the intervals start time. Averaging is performed over all values from time to time+interval.
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param string $mode The name of the interval. Possible values are: daily, weekly, monthly, annual
     * @param string $timezone The time zone to which the intervals refer
    */
    public function get_average_DMY($feedid, $start, $end, $mode, $timezone)
    {
        $feedid = (int) $feedid;
        if ($mode!="daily" && $mode!="weekly" && $mode!="monthly" && $mode!="annual") return false;

        $start = intval($start/1000);
        $end = intval($end/1000);
        $table = $this->get_table_name($feedid);
        $data = array();

        // Set interval based on timezone
        $date = new DateTime();
        if ($timezone===0) $timezone = "UTC";
        $date->setTimezone(new DateTimeZone($timezone));
        $date->setTimestamp($start);
        $date->modify("midnight");
        $increment="+1 day";
        if ($mode=="weekly") { $date->modify("this monday"); $increment="+1 week"; }
        if ($mode=="monthly") { $date->modify("first day of this month"); $increment="+1 month"; }
        if ($mode=="annual") { $date->modify("first day of January this year"); $increment="+1 year"; }

        $n = 0;
        while($n < 10000) // max iterations
        {
            $interval_start = $date->getTimestamp();
            $date->modify($increment);
            $interval_end = $date->getTimestamp();
            if ($interval_start>$end) break;

            $sql = "SELECT AVG(data) AS dp FROM $table WHERE time >= $interval_start AND time < $interval_end";
            $result = $this->mysqli->query($sql);
            if($result) {
                $dp = $result->fetch_array();
                if ($dp != null) {
                    if ($dp['dp'] !== null) $dp['dp'] = (float) $dp['dp'];
                    $data[] = array( $interval_start*1000 , $dp['dp']);
                } else {
                    $data[] = array( $interval_start*1000 , null);
                }
            }
            $n++;
        }
        return $data;
    }

    /**
     * Returns engine occupied size in bytes
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_feed_size($feedid)
    {
        $table = $this->get_table_name(intval($feedid));
        $result = $this->mysqli->query("SHOW TABLE STATUS LIKE '$table'");
        $row = $result->fetch_array();
        return $row['Data_length']+$row['Index_length'];
    }

    /**
     * Adds a data point to the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @param array arg $value optional padding mode argument
     * $feedname, $time and $value are all typecased in feed->insert and feed->update
    */
    public function post($feedid, $time, $value, $arg=null)
    {
        $table = $this->get_table_name(intval($feedid));
        $this->mysqli->query("INSERT INTO $table (time,data) VALUES ('$time','$value') ON DUPLICATE KEY UPDATE data=VALUES(data)");
    }

    /**
     * Updates a data point in the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function update($feedid, $time, $value)
    {
        $feedid = intval($feedid);
        if ($this->writebuffer_update_time($feedid, (int) $time, $value)) {
            $this->post_bulk_save();// if data is on buffer, update it and flush buffer now
            $this->log->info("update() feedid=$feedid with buffer");
        }
        else {
            //$this->log->info("update() feedid=$feedid");
            // else, update or insert data value in feed table
            $table = $this->get_table_name(intval($feedid));
            $result = $this->mysqli->query("SELECT * FROM $table WHERE time = '$time'");

            if (!$result) return $value;
            $row = $result->fetch_array();

            if ($row) $this->mysqli->query("UPDATE $table SET data = '$value' WHERE time = '$time'");
            if (!$row) {$value = 0; $this->mysqli->query("INSERT INTO $table (`time`,`data`) VALUES ('$time','$value')");}
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
        $table = $this->get_table_name(intval($feedid));

        $result = $this->mysqli->query("SELECT time, data FROM $table ORDER BY time Desc LIMIT 1");
        if ($result && $row = $result->fetch_array()) {
            if ($row['data'] !== null) $row['data'] = (float) $row['data'];
            return array('time'=>(int)$row['time'], 'value'=>$row['data']);
        } else {
            return false;
        }
    }

    /**
     * Return the data for the given timerange - cf shared_helper.php
     *
     * @param integer $limitinterval not implemented
     *
    */
    public function get_data($feedid, $start, $end, $interval, $skipmissing, $limitinterval)
    {
        global $settings;

        $feedid = intval($feedid);
        $start = round($start/1000);
        $end = round($end/1000);
        $interval = intval($interval); // time gap in seconds

        if ($interval < 1) $interval = 1;
        $dp = ceil(($end - $start) / $interval); // datapoints for desired range with set interval time gap
        $end = $start + ($dp * $interval);
        if ($dp < 1) return false;

        // Check if datatype is daily so that select over range is used rather than skip select approach
        $data_type = $this->get_data_type($feedid);
        if ($data_type == 2) $dp = 0;

        $table = $this->get_table_name($feedid);
        $range = $end - $start; // window duration in seconds
        $data = array();
        $data_time = null;
        $data_value = null;
        if ($settings["feed"]["mysqltimeseries"]["data_sampling"] && $range > 180000 && $dp > 0) // 50 hours
        {
            $td = $range / $dp; // time duration for each datapoint
            $stmt = $this->mysqli->prepare("SELECT time, data FROM $table WHERE time BETWEEN ? AND ? ORDER BY time ASC LIMIT 1");
            $t = $start; $tb = 0;
            $stmt->bind_param("ii", $t, $tb);
            $stmt->bind_result($data_time, $data_value);
            for ($i=0; $i<$dp; $i++) {
                $tb = $start + intval(($i+1)*$td);
                $stmt->execute();
                if ($stmt->fetch()) {
                    if ($data_value != null || $skipmissing === 0) { // Remove this to show white space gaps in graph
                        $time = $data_time * 1000;
                        if ($data_value !== null) $data_value = (float) $data_value ;
                        $data[] = array($time, $data_value);
                    }
                }
                $t = $tb;
            }
        }
        else {
            if ($range > 5000 && $dp > 0) // 83.33 min
            {
                $td = intval($range / $dp);
                $sql = "SELECT time DIV $td AS time, AVG(data) AS data".
                    " FROM $table WHERE time BETWEEN $start AND $end".
                    " GROUP BY 1 ORDER BY time ASC";
            } else if ($range == 1){
                $td = 1;
                $sql = "SELECT time, data FROM $table".
                    " WHERE time = $start LIMIT 1";
            } else {
                $td = 1;
                $sql = "SELECT time, data FROM $table".
                    " WHERE time BETWEEN $start AND $end ORDER BY time ASC";
            }

            $result = $this->mysqli->query($sql);
            if ($result) {
                while($row = $result->fetch_array()) {
                    $data_value = $row['data'];
                    if ($data_value != null || $skipmissing === 0) { // Remove this to show white space gaps in graph
                        $time = $row['time'] * 1000 * $td;
                        if ($data_value !== null) $data_value = (float) $data_value ;
                        $data[] = array($time , $data_value);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Return datapoints for intervals in the given timerange.
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param string $mode The name of the interval. Possible values are: daily, weekly, monthly, annual
     * @param string $timezone The time zone to which the intervals refer
    */
    public function get_data_DMY($feedid, $start, $end, $mode, $timezone)
    {
        if ($mode!="daily" && $mode!="weekly" && $mode!="monthly" && $mode!="annual") return false;

        $feedid = (int) $feedid;
        $start = intval($start/1000);
        $end = intval($end/1000);
        $table = $this->get_table_name($feedid);
        $data = array();

        // Set interval based on timezone
        $date = new DateTime();
        if ($timezone === 0) $timezone = "UTC";
        $date->setTimezone(new DateTimeZone($timezone));
        $date->setTimestamp($start);
        $date->modify("midnight");
        $increment="+1 day";
        if ($mode=="weekly") { $date->modify("this monday"); $increment="+1 week"; }
        if ($mode=="monthly") { $date->modify("first day of this month"); $increment="+1 month"; }
        if ($mode=="annual") { $date->modify("first day of January this year"); $increment="+1 year"; }

        // Get first and last datapoint of feed
        $sql = "SELECT DISTINCT time, data FROM $table WHERE ("
                ." time = (SELECT min(time) FROM $table )"
                ."OR  time = (SELECT max(time) FROM $table )"
                .")";
        $result = $this->mysqli->query($sql);
        if ($result) {
            $range = $result->fetch_all(MYSQLI_ASSOC);
            if (count($range) < 2) return array('success'=>false, 'message'=>"Feed $feedid does not contain enough datapoints yet");;
        }
        else {
            return false;
        }

        // Iterate intervals
        $n = 0;
        while($n < 10000) // max iterations
        {
            $time = $date->getTimestamp();
            if ($time > $end) break;

            // Limit DB requests to available datapoints in feed
            if ($range[0]['time'] < $time &&  $time < $range[1]['time']) {
                // get datapoint using interpolation if necessary
                $data[] = $this->get_datapoint_interpolated($feedid, $time * 1000);
            }
            elseif($time >= $range[1]['time']) {
                // return latest feed value
                $data[] = array($time *1000, (float) $range[1]['data']);
                break;
            }
            else {
                // return NULL if requested time is out of feed range
                $data[] = array($time *1000, null);
            }
            $date->modify($increment);
            $n++;
        }
        return $data;
    }

    public function get_data_DMY_time_of_day($feedid, $start, $end, $mode, $timezone, $split)
    {
        if ($mode!="daily" && $mode!="weekly" && $mode!="monthly" && $mode!="annual") return false;

        $feedid = (int) $feedid;
        $start = intval($start/1000);
        $end = intval($end/1000);
        $table = $this->get_table_name($feedid);
        $data = array();
        $split = json_decode($split);
        if (gettype($split) != "array") return false;

        /* SP Increase to 48 points to allow a days worth of half hour readings */
        if (count($split) > 48) return false;

        // Set interval based on timezone
        $date = new DateTime();
        if ($timezone === 0) $timezone = "UTC";
        $date->setTimezone(new DateTimeZone($timezone));
        $date->setTimestamp($start);
        $date->modify("midnight");
        $increment="+1 day";
        if ($mode=="weekly") { $date->modify("this monday"); $increment="+1 week"; }
        if ($mode=="monthly") { $date->modify("first day of this month"); $increment="+1 month"; }
        if ($mode=="annual") { $date->modify("first day of January this year"); $increment="+1 year"; }

        // Get first and last datapoint of feed
        $sql = "SELECT DISTINCT time, data FROM $table WHERE ("
                ." time = (SELECT min(time) FROM $table )"
                ."OR  time = (SELECT max(time) FROM $table )"
                .")";
        $result = $this->mysqli->query($sql);
        if($result) {
            $range = $result->fetch_all(MYSQLI_ASSOC);
            if (count($range) < 2) {
                return array('success'=>false, 'message'=>"Feed $feedid does not contain enough datapoints yet");;
            }
        }
        else {
            return false;
        }

        // Iterate intervals
        $n = 0;
        while($n < 10000) // max iterations
        {
            $time = $date->getTimestamp();
            if ($time > $end) break;

            $split_values = array();
            foreach ($split as $splitpoint) {
                //Fix issue with rounding to nearest 30 minutes
                $split_offset = (int) (((float)$splitpoint) * 3600.0);
                $split_time = $time+$split_offset;

                // Limit DB requests to available datapoints in feed
                if ($range[0]['time'] < $time &&  $time < $range[1]['time']) {
                    // get datapoint using interpolation if necessary
                    $result = $this->get_datapoint_interpolated($feedid, $split_time * 1000);
                    $value = $result[1];
                }
                elseif($time >= $range[1]['time']) {
                    // return latest feed value
                    $value =  (float) $range[1]['data'];
                    break;
                }
                else {
                    $value = null;
                }
                $split_values[] = $value;
            }
            $data[] = array($time*1000, $split_values);
            $date->modify($increment);
            $n++;
        }
        return $data;
    }

    public function export($feedid, $start)
    {
        // Feed id and start time of feed to export
        $feedid = intval($feedid);
        $start = intval($start) - 1;

        // Open database etc here
        // Extend timeout limit from 30s to 2mins
        set_time_limit (120);

        // Regulate mysql and apache load.
        $block_size = 400;
        $sleep = 80000;

        $table = $this->get_table_name($feedid);
        $file = $table.'.csv';

        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$file}");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $fh = @fopen( 'php://output', 'w' );

        // Load new feed blocks until there is no more data
        $data_available = 1;
        while ($data_available) {
            // 1) Load a block
            $result = $this->mysqli->query("SELECT * FROM $table WHERE time>$start
            ORDER BY time Asc Limit $block_size");

            $data_available = 0;
            while($row = $result->fetch_array())
            {
                // Write block as csv to output stream
                if (!isset($row['data2'])) {
                    fputcsv($fh, array($row['time'],$row['data']));
                }
                else {
                    fputcsv($fh, array($row['time'],$row['data'],$row['data2']));
                }

                // Set new start time so that we read the next block along
                $start = $row['time'];
                $data_available = 1;
            }
            // 2) Sleep for a bit
            usleep($sleep);
        }

        fclose($fh);
        exit;
    }

    public function csv_export($feedid, $start, $end, $interval, $timezone)
    {
        global $settings;

        require_once "Modules/feed/engine/shared_helper.php";
        $helperclass = new SharedHelper();

        $feedid = intval($feedid);
        $start = round($start);
        $end = round($end);
        $interval = intval($interval);
        $skipmissing = 0;

        if ($interval < 1) $interval = 1;
        $dp = ceil(($end - $start) / $interval); // datapoints for desied range with set interval time gap
        $end = $start + ($dp * $interval);
        if ($dp < 1) return false;
        if ($end == 0) $end = time();

        // Check if datatype is daily so that select over range is used rather than skip select approach
        $data_type = $this->get_data_type($feedid);
        if ($data_type == 2) $dp = 0;

        $table = $this->get_table_name($feedid);
        $file = $table.".csv";

        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename={$file}");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $exportfh = @fopen( 'php://output', 'w' );
        $range = $end - $start; // window duration in seconds
        if ($settings["feed"]["mysqltimeseries"]["data_sampling"] && $range > 180000 && $dp > 0) // 50 hours
        {
            $time = null;
            $data = null;
            $td = $range / $dp; // time duration for each datapoint
            $stmt = $this->mysqli->prepare("SELECT time, data FROM $table WHERE time BETWEEN ? AND ? ORDER BY time ASC LIMIT 1");
            $t = $start; $tb = 0;
            $stmt->bind_param("ii", $t, $tb);
            $stmt->bind_result($time, $data);
            for ($i=0; $i<$dp; $i++) {
                $tb = $start + intval(($i+1)*$td);
                $stmt->execute();
                if ($stmt->fetch()) {
                    if ($data != null || $skipmissing === 0) { // Remove this to show white space gaps in graph
                        $timenew = $helperclass->getTimeZoneFormated($time, $timezone);
                        fwrite($exportfh, $timenew.$settings["feed"]["csv_field_separator"].number_format((float)$data, $settings["feed"]["csv_decimal_places"], $settings["feed"]["csv_decimal_place_separator"], '')."\n");
                    }
                }
                $t = $tb;
            }
        }
        else {
            if ($range > 5000 && $dp > 0) // 83.33 min
            {
                $td = intval($range/$dp);
                $sql = "SELECT time DIV $td AS time, AVG(data) AS data".
                    " FROM $table WHERE time BETWEEN $start AND $end".
                    " GROUP BY 1 ORDER BY time ASC";
            } else {
                $td = 1;
                $sql = "SELECT time, data FROM $table".
                    " WHERE time BETWEEN $start AND $end ORDER BY time ASC";
            }
            $result = $this->mysqli->query($sql);
            if($result) {
                while($row = $result->fetch_array()) {
                    $data = $row['data'];
                    if ($data != null || $skipmissing === 0) { // Remove this to show white space gaps in graph
                        $time = $row['time'] * $td;
                        $timenew = $helperclass->getTimeZoneFormated($time, $timezone);
                        fwrite($exportfh, $timenew.$settings["feed"]["csv_field_separator"].number_format((float)$data, $settings["feed"]["csv_decimal_places"], $settings["feed"]["csv_decimal_place_separator"], '')."\n");
                    }
                }
            }
        }
        fclose($exportfh);
        exit;
    }

    public function clear($feedid) {
        $feedid = filter_var($feedid, FILTER_SANITIZE_NUMBER_INT);
        $table = $this->get_table_name($feedid);
        $sql = "TRUNCATE TABLE $table";
        if (!$this->mysqli->query($sql)) {
            return array('success'=>false,'message'=>"0 rows deleted");
        } else {
            return array('success'=>true,'message'=>"All database rows deleted");
        }
    }

    public function trim($feedid, $start){
        $feedid = filter_var ($feedid, FILTER_SANITIZE_NUMBER_INT);
        $start = filter_var ($start, FILTER_SANITIZE_NUMBER_INT);
        $table = $this->get_table_name($feedid);
        $stmt = $this->mysqli->prepare("DELETE FROM $table WHERE time < ?");
        if (!$stmt) return array('success'=>false,'message'=>"Error accessing database");
        if (!$stmt->bind_param("i", $start)) return array('success'=>false,'message'=>"Error passing parameters to database");
        if (!$stmt->execute()) return array('success'=>false,'message'=>"Error executing commands on database");
        $affected_rows = $stmt->affected_rows;
        if ($affected_rows > 0) {
            return array('success'=>true,'message'=>"$affected_rows rows deleted");
        } else {
            return array('success'=>false,'message'=>"0 rows deleted");
        }
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
            $table = $this->get_table_name($feedid);
            $cnt = count($data);
            if ($cnt > 0) {
                $p = 0; // point
                while ($p < $cnt) {
                    $sql_values="";
                    $s=0; // data point step
                    while ($s < $stepcnt) {
                        if (isset($data[$p][0]) && isset($data[$p][1])) {
                            $sql_values .= "(".$data[$p][0].",".$data[$p][1]."),";
                        }
                        $s++;
                        $p++;
                        if ($p >= $cnt) break;
                    }
                    if ($sql_values!="") {
                        $this->log->info("post_bulk_save() " . "INSERT INTO $table (`time`,`data`) VALUES " . substr($sql_values,0,-1) . " ON DUPLICATE KEY UPDATE data=VALUES(data)");
                        $this->mysqli->query("INSERT INTO $table (`time`,`data`) VALUES " . substr($sql_values,0,-1) . " ON DUPLICATE KEY UPDATE data=VALUES(data)");
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
        $table = $this->get_table_name($feedid);
        $this->mysqli->query("DELETE FROM $table where `time` = '$time' LIMIT 1");
    }

    public function delete_data_range($feedid,$start,$end)
    {
        $feedid = intval($feedid);
        $start = intval($start/1000.0);
        $end = intval($end/1000.0);
        $table = $this->get_table_name($feedid);
        $this->mysqli->query("DELETE FROM $table where `time` >= '$start' AND `time`<= '$end'");

        return true;
    }


    public function get_table_name($feedid)
    {
        $feedid = intval($feedid);
        $name = "";
        if ($this->prefix) {
            $name .= $this->prefix;
        }
        if ($this->generic) {
            $name .= "".trim($feedid);
        }
        else {
            $name .= $this->get_table($feedid, "name");
        }
        return $name;
    }

    public function get_table($feedid, $field=null)
    {
        if ($this->generic) {
            $table = array(
                "name" => $this->get_table_name($feedid),
                "type" => "FLOAT NOT NULL"
            );
        }
        else if ($this->redis && $this->redis->exists("feed:$feedid:table")) {
            if (!empty($field)) {
                return $this->redis->hget("feed:$feedid:table", $field);
            }
            else {
                return $this->redis->get("feed:$feedid:table");
            }
        }
        else {
            $meta = $this->get_meta($feedid);

            $name = $meta->table_name;
            $type = $meta->value_type;
            if (!$meta->value_empty) {
                $type .= " NOT NULL";
            }
            $table = array(
                "name" => $name,
                "type" => $type
            );
            if ($this->redis) {
                $this->redis->hMSet("feed:$feedid:table", $table);
            }
        }
        if (!empty($field)) {
            return $table[$field];
        }
        return $table;
    }

// #### \/ Bellow are engine private methods

    private function get_data_type($feedid)
    {
        if ($this->redis) {
            return $this->redis->hget("feed:$feedid", "datatype");
        }
        global $mysqli;
        $result = $mysqli->query("SELECT datatype FROM feeds WHERE `id` = '$feedid'");
        $row = $result->fetch_array();
        return $row["datatype"];
    }

    // Search time in buffer if found update its value and return true
    private function writebuffer_update_time($feedid, $time, $newvalue)
    {
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

    private function create_meta($feedid, $options)
    {
        // Check to ensure ne existing feed will be overridden
        if (empty($this->dir) || !is_dir($this->dir) || !is_writable($this->dir)) {
            return true;
        }
        else if (file_exists($this->dir.$feedid.".meta")) {
            $result = "Unable to create MySQL already existing meta file '".$this->dir.$feedid.".meta'";
            $this->log->error($result);
            return $result;
        }
        if ($this->generic) {
            $name = ($this->prefix ? $this->prefix : "").trim($feedid);
            $type = "FLOAT";
            $empty = false;
        }
        else {
            $name = "";
            if ($this->prefix) {
                $name .= $this->prefix;
            }
            if (empty($options["name"])) {
                $name .= "".trim($feedid);
            }
            else {
                $name .= preg_replace('/[^\p{N}\p{L}\_]/u', '_', $options['name']);
            }
            $type = !empty($options['type']) ? $options['type'] : "FLOAT";
            $empty = isset($options['empty']) && boolval($options['empty']);
        }
        // Set initial feed meta data
        $meta = new stdClass();
        $meta->table_name = $name;
        $meta->value_type = $type;
        $meta->value_empty = $empty;
        $meta->start_time = 0;

        // Save meta data
        $result = $this->write_meta($feedid, $meta);
        if ($result !== true) {
            return $result;
        }
        if (!file_exists($this->dir.$feedid.".meta")) {
            $this->log->error("Creating MySQL meta data failed. Unable to find file '".$this->dir.$feedid.".meta'");
            return $result;
        }
        return $meta;
    }

    private function read_meta($feedid)
    {
        $file = "$feedid.meta";
        if (!file_exists($this->dir.$file)) {
            $error = "meta file does not exist '".$this->dir.$file."'";
            $this->log->warn("read_meta() ".$error);
            throw new Exception($error);
        }

        $meta_file = parse_ini_file($this->dir.$file, false, INI_SCANNER_TYPED);
        $meta = new stdClass();
        foreach ($meta_file as $key => $value) {
            $meta->$key = $value;
        }
        return $meta;
    }

    private function write_meta($feedid, $meta)
    {
        $file = $feedid.".meta";
        if (!is_dir($this->dir) || !is_writable($this->dir) ||
            (is_file($this->dir.$file) and !is_writable($this->dir.$file))) {
                $result = "unable to write meta data file: ".$this->dir.$file;
                $this->log->error("write_meta() ".$result);
                return $result;
        }

        $meta_file = @fopen($this->dir.$file, 'w');
        if (!$meta_file) {
            $error = error_get_last();
            $result = "could not write meta data file ".$error['message'];
            $this->log->error("write_meta() ".$result);
            return $result;
        }
        if (!flock($meta_file, LOCK_EX)) {
            $result = "meta data file '".$this->dir.$file."' is locked by another process";
            $this->log->error("write_meta() ".$result);
            fclose($meta_file);
            return $result;
        }
        foreach ($meta as $key => $value) {
            if (is_bool($value)) $value = $value ? 'true' : false;
            fwrite($meta_file, $key.'='.$value.PHP_EOL);
        }
        fclose($meta_file);
        return true;
    }

    /**
     * Return datapoint for requested timestamp. If feed does not contain a datapoint for requested timestamp, the value is calculated using linear interpolation.
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $time The unix timestamp in ms of the requested datapoint
    */
    private function get_datapoint_interpolated($feedid, $time)
    {
        $feedid = (int) $feedid;
        $time = intval($time/1000);
        $table = $this->get_table_name($feedid);
        $data = array();

        // Search for previous and next datapoint
        $sql = "SELECT time, data FROM $table WHERE ("
                ." time = IFNULL( (SELECT max(time) FROM $table where time <= $time), 0) "
                ."OR  time = IFNULL( (SELECT min(time) FROM $table where time > $time), 0) "
                .")";
        $result = $this->mysqli->query($sql);
        if($result) {
            $dp = $result->fetch_all(MYSQLI_ASSOC);
            if (count($dp) == 2) {
                if ($dp[0]['time'] == $time) {
                    // Datapoint to given timestamp found
                    $data = array($time*1000 , (float) $dp[0]['data']);
                }
                else {
                    // No datapoint to given timestamp found. Datapoint will be interpolated
                    $delta_t = $dp[1]['time'] - $dp[0]['time'];
                    $delta_data = $dp[1]['data'] - $dp[0]['data'];
                    if ($delta_t != 0){
                        // Linear interpolation
                        $value = $dp[0]['data'] + ($delta_data / $delta_t) * ($time - $dp[0]['time']);
                        $data = array($time*1000 , (float) $value);
                    }
                }
            }
            else {
                // only one datapoint found, interpolation not possible.
                $data = array($time*1000 , null);
            }
        }
        return $data;
    }

}
