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
        if (file_exists($meta)) {
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
        $feedid = (int) $feedid;
        if (!$this->generic) {
            $meta = $this->read_meta($feedid);
        }
        else {
            $meta = new stdClass();
            $meta->id = $feedid;
            $meta->table_name = ($this->prefix ? $this->prefix : "").trim($feedid);
            $meta->value_type = "FLOAT NOT NULL";
            $meta->start_time = 0;
            $meta->end_time = 0;
        }
       
        $result = $this->mysqli->query("SELECT COUNT(*) FROM ".$meta->table_name);
        if ($result && $row = $result->fetch_array()) {
            $meta->npoints = (int) $row[0];
        } else {
            $meta->npoints = -1;
        }
        
        if ($meta->start_time == 0) {
            $table = $this->get_table_name($feedid);
            // Get first and last datapoint of feed
            $sql = "SELECT DISTINCT time, data FROM $table WHERE ("
                    ." time = (SELECT min(time) FROM $table )"
                    ."OR  time = (SELECT max(time) FROM $table )"
                    .")";
            if ($result = $this->mysqli->query($sql)) {
                $range = $result->fetch_all(MYSQLI_ASSOC);
                if (isset($range[0])) {
                    $meta->start_time = (int) $range[0]['time'];
                }
                if (isset($range[1])) {
                    $meta->end_time = (int) $range[1]['time'];
                }
                
                $meta->interval = 0;
                if ($meta->npoints>0) {
                    $meta->interval = floor(($meta->end_time - $meta->start_time) / $meta->npoints);
                }
                
                if (!$this->generic) {
                    $this->write_meta($feedid, $meta);
                }      
            }
        }

        return $meta;
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
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $interval output data point interval
     * @param integer $average enabled/disable averaging
     * @param string $timezone a name for a php timezone eg. "Europe/London"
     * @param string $timeformat csv datetime format e.g: unix timestamp, excel, iso8601 (NOT CURRENTLY SUPPORTED IN MYSQL)
     * @param integer $csv pipe output as csv                                            (NOT CURRENTLY SUPPORTED IN MYSQL)
     * @param integer $skipmissing skip null datapoints
     * @param integer $limitinterval limit interval to feed interval
     * @return void or array
     */
    
    public function get_data_combined($feedid,$start,$end,$interval,$average=0,$timezone="UTC",$timeformat="unix",$csv=false,$skipmissing=0,$limitinterval=1)
    {
        if (in_array($interval,array("daily","weekly","monthly","annual"))) {
            return $this->get_data_DMY($feedid, $start, $end, $interval, $average, $timezone, $timeformat, $csv, $skipmissing);
        } else {
            if (!$average) {
                return $this->get_data($feedid, $start, $end, $interval, $timezone, $timeformat, $csv, $skipmissing, $limitinterval);
            } else {
                return $this->get_average($feedid, $start, $end, $interval, $timezone, $timeformat, $csv, $skipmissing);
            }
        }
    }

    /**
     * Return the data for the given timerange - cf shared_helper.php
     *
     * @param integer $limitinterval not implemented
     *
    */
    public function get_data($feedid, $start, $end, $interval, $timezone, $timeformat, $csv, $skipmissing, $limitinterval)
    {
        $feedid = (int) $feedid;
        $start = (int) $start;
        $end = (int) $end;
        $interval = (int) $interval;
        $skipmissing = (int) $skipmissing;
        // Interval should not be less than 1 second
        if ($interval < 1) $interval = 1;
        // Set time to start
        $time = $start;

        $table = $this->get_table_name($feedid);
        
        $stmt = $this->mysqli->prepare("SELECT time, data FROM $table WHERE time BETWEEN ? AND ? ORDER BY time ASC LIMIT 1");
        $t = $start; $tb = 0;
        $stmt->bind_param("ii", $div_start, $div_end);
        $stmt->bind_result($data_time, $data_value);

        if ($csv) {
            global $settings;
            require_once "Modules/feed/engine/shared_helper.php";
            $helperclass = new SharedHelper($settings['feed']);
            $helperclass->set_time_format($timezone,$timeformat);
            $helperclass->csv_header($feedid);
        } else {
            $data = array();       
        }

        while($time<=$end)
        {
            // Start time of interval/division
            $div_start = $time;
            
            // calculate start of next interval 
            $div_end = $time + $interval;
            
            $value = null;
            $stmt->execute();
            if ($stmt->fetch() && $data_value !== null) {
                $value = (float) $data_value;
            }
            
            if ($value!==null || $skipmissing===0) {                
                // Write as csv or array
                if ($csv) { 
                    $helperclass->csv_write($div_start,$value);
                } else {
                    $data[] = array($div_start,$value);
                } 
            }

            // Advance position 
            $time = $div_end;
        }
        if ($csv) {
            $helperclass->csv_close();
            exit;
        } else {
            return $data;
        }
    }
    
    /**
     * Return the averaged data over interval for the given timerange. The returned timestamp denotes the intervals start time. Averaging is performed over all values from time to time+interval.
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $interval The number os seconds for each data point to return (used by some engines)
    */
    public function get_average($feedid, $start, $end, $interval, $timezone, $timeformat, $csv, $skipmissing)
    {
        $feedid = (int) $feedid;
        $start = (int) $start;
        $end = (int) $end;
        $interval= (int) $interval;
        $skipmissing = (int) $skipmissing;
        // Minimum interval
        if ($interval < 1) $interval = 1;
        
        $table = $this->get_table_name($feedid);
        
        // 1. Create associative array of time => values
        $data_assoc = array();
        $sql = "SELECT time, AVG(data) AS data_avg FROM $table WHERE time >= $start AND time < $end GROUP BY FLOOR(time/$interval)";
        $result = $this->mysqli->query($sql);
        if ($result) {
            while($row = $result->fetch_array()) {
                $time = floor((int)$row['time']/$interval)*$interval;
                $data_assoc[$time] = (float) $row['data_avg'];
            }
        }
        
        // 2. Assing values to correct output format 
        // returns null if output does not exist for that timestamp
        // allowing for easier cross feed comparison e.g in csv view
        if ($csv) {
            global $settings;   
            require_once "Modules/feed/engine/shared_helper.php";
            $helperclass = new SharedHelper($settings['feed']);
            $helperclass->set_time_format($timezone,$timeformat);
            $helperclass->csv_header($feedid);
        } else {
            $data = array();       
        }

        $time = $start;
        while($time<=$end)
        {
            $value = null;
            if (isset($data_assoc[$time])) {
                $value = $data_assoc[$time];
            }
            
            // Write as csv or array
            if ($value!==null || $skipmissing===0) {
                if ($csv) { 
                    $helperclass->csv_write($time,$value);
                } else {
                    $data[] = array($time,$value);
                }
            }
            $time += $interval;
        }
        
        if ($csv) {
            $helperclass->csv_close();
            exit;
        } else {
            return $data;
        }
    }

    /**
     * Return datapoints for intervals in the given timerange.
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param string $interval The name of the interval. Possible values are: daily, weekly, monthly, annual
     * @param string $timezone The time zone to which the intervals refer
    */
    public function get_data_DMY($feedid, $start, $end, $interval, $average, $timezone, $timeformat, $csv, $skipmissing)
    {
        if (!in_array($interval,array("daily","weekly","monthly","annual"))) return false;
        
        $feedid = (int) $feedid;
        $start = (int) $start;
        $end = (int) $end;
        $average = (int) $average;
        $skipmissing = (int) $skipmissing;       
        $table = $this->get_table_name($feedid);
        
        $meta = $this->get_meta($feedid);
        if (!$start_time = $meta->start_time) return false;
        if (!$end_time = $meta->end_time) return false;
        
        if ($timezone===0) $timezone = "UTC";

        $date = new DateTime();
        $date->setTimezone(new DateTimeZone($timezone));
        $date->setTimestamp($start);
        $date->modify("midnight");
        $modify = "+1 day";
        if ($interval=="weekly") {
            $date->modify("this monday");
            $modify = "+1 week";
        } else if ($interval=="monthly") {
            $date->modify("first day of this month");
            $modify = "+1 month";
        } else if ($interval=="annual") {
            $date->modify("first day of january this year");
            $modify = "+1 year";
        }
        // Set time to start 
        $time = $date->getTimestamp();
        
        if ($csv) {
            global $settings;     
            require_once "Modules/feed/engine/shared_helper.php";
            $helperclass = new SharedHelper($settings['feed']);
            $helperclass->set_time_format($timezone,$timeformat);
            $helperclass->csv_header($feedid);
        } else {
            $data = array();       
        }
        
        while($time<=$end)
        {   
            // Start time of interval/division
            $div_start = $time;
            // calculate start of next interval 
            $date->modify($modify);
            $div_end = $date->getTimestamp();
            
            $value = null;
            
            if ($average) {
                $sql = "SELECT AVG(data) AS dp FROM $table WHERE time >= $div_start AND time < $div_end";
                if ($result = $this->mysqli->query($sql)) {
                    if ($dp = $result->fetch_array()) {
                        if ($dp['dp'] !== null) $value = (float) $dp['dp'];
                    }
                }
            } else {
                // Limit DB requests to available datapoints in feed
                if ($start_time < $time && $time < $end_time) {
                    // get datapoint using interpolation if necessary
                    $dp = $this->get_datapoint_interpolated($feedid, $time);
                    $value = $dp[1];
                }
            }
            
            // Write as csv or array
            if ($value!==null || $skipmissing===0) {
                if ($csv) { 
                    $helperclass->csv_write($div_start,$value);
                } else {
                    $data[] = array($div_start,$value);
                }
            }
                      
            // Advance position 
            $time = $div_end;
        }
        if ($csv) {
            $helperclass->csv_close();
            exit;
        } else {
            return $data;
        }
    }

    public function get_data_DMY_time_of_day($feedid, $start, $end, $mode, $timezone, $split)
    {
        if (!in_array($mode,array("daily","weekly","monthly","annual"))) return false;

        $feedid = (int) $feedid;
        $start = (int) $start;
        $end = (int) $end;
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
                    $result = $this->get_datapoint_interpolated($feedid, $split_time);
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
            $data[] = array($time, $split_values);
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
        $start = (int) $start;
        $end = (int) $end;
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
        $time = (int) $time;
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
                    $data = array($time , (float) $dp[0]['data']);
                }
                else {
                    // No datapoint to given timestamp found. Datapoint will be interpolated
                    $delta_t = $dp[1]['time'] - $dp[0]['time'];
                    $delta_data = $dp[1]['data'] - $dp[0]['data'];
                    if ($delta_t != 0){
                        // Linear interpolation
                        $value = $dp[0]['data'] + ($delta_data / $delta_t) * ($time - $dp[0]['time']);
                        $data = array($time , (float) $value);
                    }
                }
            }
            else {
                // only one datapoint found, interpolation not possible.
                $data = array($time , null);
            }
        }
        return $data;
    }
    
    /**
     * Used for testing
     *
     */
    public function print_all($id) {
        $table = $this->get_table_name($id);
        $result = $this->mysqli->query("SELECT time, data FROM $table");
        $n = 0;
        while($row = $result->fetch_object()) {
            print $n." ".$row->time." ".$row->data."\n";     
            $n++; 
        }
    }
}
