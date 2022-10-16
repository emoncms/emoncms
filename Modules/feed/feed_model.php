<?php
/*
     All Emoncms code is released under the GNU Affero General Public License.
     See COPYRIGHT.txt and LICENSE.txt.

     ---------------------------------------------------------------------
     Emoncms - open source energy visualisation
     Part of the OpenEnergyMonitor project:
     http://openenergymonitor.org

*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Feed
{
    private $log;
    private $mysqli;
    private $redis;
    private $settings;


    public function __construct($mysqli,$redis,$settings)
    {
        $this->log = new EmonLogger(__FILE__);
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        $this->settings = $settings;
    }


    // Return instance of engine class, autoload when needed
    public function EngineClass($e)
    {
        $e = (string)$e;
        static $engines = array();
        if (!Engine::is_valid($e)) {
            $this->log->error("EngineClass() Engine id '".$e."' is not supported.");
            return array('success'=>false, 'message'=>"ABORTED: Engine id $d is not supported.");
        }
        if (isset($engines[$e])) {
            //$this->log->info("EngineClass() reused instance of '".get_class($engines[$e])."' id '".$e."'.");
            return $engines[$e];
        }
        else {
            // Load different storage engines
            switch ($e) {
                case (string)Engine::MYSQL :
                    require "Modules/feed/engine/MysqlTimeSeries.php";  // Mysql engine
                    $engines[$e] = new MysqlTimeSeries($this->mysqli,$this->redis,$this->settings['mysqltimeseries']);
                    break;
                case (string)Engine::VIRTUALFEED :
                    require "Modules/feed/engine/VirtualFeed.php";      // Takes care of Virtual Feeds
                    $engines[$e] =  new VirtualFeed($this->mysqli,$this->redis,$this);
                    break;
                case (string)Engine::PHPFINA :
                    require "Modules/feed/engine/PHPFina.php";          // Fixed interval no averaging
                    $engines[$e] =  new PHPFina($this->settings['phpfina']);
                    break;
                case (string)Engine::REDISBUFFER :
                    require "Modules/feed/engine/RedisBuffer.php";      // Redis buffer for low-write mode
                    $engines[$e] = new RedisBuffer($this->redis,$this->settings['redisbuffer'],$this);
                    break;
                case (string)Engine::PHPTIMESERIES :
                    require "Modules/feed/engine/PHPTimeSeries.php";    // Variable interval no averaging
                    $engines[$e] = new PHPTimeSeries($this->settings['phptimeseries']);
                    break;
                case (string)Engine::MYSQLMEMORY : 
                    require_once "Modules/feed/engine/MysqlTimeSeries.php";  // Mysql engine
                    require "Modules/feed/engine/MysqlMemory.php";           // Mysql Memory engine
                    $engines[$e] = new MysqlMemory($this->mysqli);
                    break;
                case (string)Engine::CASSANDRA :
                    require "Modules/feed/engine/CassandraEngine.php";  // Cassandra engine
                    $engines[$e] = new CassandraEngine($this->settings['cassandra']);
                    break;
                default :
                    $this->log->error("EngineClass() Engine id '".$e."' is not supported.");        
                    // throw new Exception("ABORTED: Engine id '".$e."' is not supported.");
                    // Load blank template engine here to avoid errors that otherwise break the interface                 
                    require "Modules/feed/engine/TemplateEngine.php";
                    $engines[$e] =  new TemplateEngine(false);
                    break;
            }
            $this->log->info("EngineClass() Autoloaded new instance of '".get_class($engines[$e])."'.");
            return $engines[$e];
        }
    }


    /*
    Configurations operations
    create, delete, exist, update_user_feeds_size, get_buffer_size, get_meta
    */
    public function create($userid,$tag,$name,$engine,$options_in,$unit='')
    {
        $userid = (int) $userid;
        if (preg_replace('/[^\p{N}\p{L}_\s\-:]/u','',$name)!=$name) return array('success'=>false, 'message'=>'invalid characters in feed name');
        if (preg_replace('/[^\p{N}\p{L}_\s\-:]/u','',$tag)!=$tag) return array('success'=>false, 'message'=>'invalid characters in feed tag');
        $engine = (int) $engine;
        $public = false;
        
        if (!Engine::is_valid($engine)) {
            $this->log->error("Engine id '".$engine."' is not supported.");
            return array('success'=>false, 'message'=>"ABORTED: Engine id $engine is not supported.");
        }
        
        // If feed of given name by the user already exists
        if ($this->exists_tag_name($userid,$tag,$name)) return array('success'=>false, 'message'=>'feed already exists');
        
        $options = array();
        if ($engine == Engine::MYSQL || $engine == Engine::MYSQLMEMORY) {
            if (!empty($options_in->name)) $options['name'] = $options_in->name;
            if (!empty($options_in->type)) $options['type'] = $options_in->type;
            if (isset($options_in->empty)) $options['empty'] = $options_in->empty;
        }
        else if ($engine == Engine::PHPFINA) $options['interval'] = (int) $options_in->interval;
        
        // Datatype is no longer used but is required here for backwards 
        // compatibility with tables already containing the field
        $datatype = 1;
        
        $stmt = $this->mysqli->prepare("INSERT INTO feeds (userid,tag,name,public,datatype,engine,unit) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issiiis",$userid,$tag,$name,$public,$datatype,$engine,$unit);
        $stmt->execute();
        $stmt->close();
        
        $feedid = $this->mysqli->insert_id;
        if ($feedid > 0) {
            // Add the feed to redis
            if ($this->redis) {
                $this->redis->sAdd("user:feeds:$userid", $feedid);
                $this->redis->hMSet("feed:$feedid",array(
                    'id'=>$feedid,
                    'userid'=>$userid,
                    'name'=>$name,
                    'tag'=>$tag,
                    'public'=>false,
                    'size'=>0,
                    'engine'=>$engine,
                    'unit'=>$unit
                ));
            }
            
            $engineresult = $this->EngineClass($engine)->create($feedid,$options);

            if ($engineresult !== true)
            {
                $this->log->warn("create() failed to create feed model feedid=$feedid");
                // Feed engine creation failed so we need to delete the meta entry for the feed

                $this->mysqli->query("DELETE FROM feeds WHERE `id` = '$feedid'");

                if ($this->redis) {
                    $userid = $this->redis->hget("feed:$feedid",'userid');
                    $this->redis->del("feed:$feedid");
                    $this->redis->srem("user:feeds:$userid",$feedid);
                }

                return array('success'=>false, 'message'=> $engineresult);
            }
            $this->log->info("create() feedid=$feedid");
            return array('success'=>true, 'feedid'=>$feedid, 'result'=>$engineresult);
        } else return array('success'=>false, 'result'=>"SQL returned invalid insert feed id");
    }

    public function delete($feedid)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $engine = $this->get_engine($feedid);

        if ($this->settings['redisbuffer']['enabled']) {
            // Call to buffer delete
            $this->EngineClass(Engine::REDISBUFFER)->delete($feedid);
        }

        // Call to engine delete method
        $this->EngineClass($engine)->delete($feedid);

        $this->mysqli->query("DELETE FROM feeds WHERE `id` = '$feedid'");

        if ($this->redis) {
            $userid = $this->redis->hget("feed:$feedid",'userid');
            $this->redis->del("feed:$feedid");
            $this->redis->srem("user:feeds:$userid",$feedid);
        }
        $this->log->info("delete() feedid=$feedid");
    }

    public function trim($feedid,$start_time)
    {
        $response = false;
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $engine = $this->get_engine($feedid);

        if ($this->settings['redisbuffer']['enabled']) {
            // Call to buffer delete
            $this->EngineClass(Engine::REDISBUFFER)->delete($feedid);
        }

        // Call to engine trim method
        $response = $this->EngineClass($engine)->trim($feedid, $start_time);

        $this->log->info("feed model: trim() feedid=$feedid");
        return $response;
    }
    public function clear($feedid)
    {
        $response = false;
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $engine = $this->get_engine($feedid);

        if ($this->settings['redisbuffer']['enabled']) {
            // Call to buffer delete
            $this->EngineClass(Engine::REDISBUFFER)->delete($feedid);
        }

        // Call to engine clear method
        $response = $this->EngineClass($engine)->clear($feedid);
        
        // Clear feed last value (set to zero)
        if ($this->redis) {
            if ($this->redis->hExists("feed:$feedid",'value')) {
                $lastvalue = $this->redis->hset("feed:$feedid",'value',0);
            }
        }

        $this->log->info("feed model: clear() feedid=$feedid");
        return $response;
    }

    public function exist($feedid)
    {
        $feedid = (int) $feedid;

        $feedexist = false;
        if ($this->redis) {
            if (!$this->redis->exists("feed:$feedid")) {
                if ($this->load_feed_to_redis($feedid)) {
                    $feedexist = true;
                }
            } else {
                $feedexist = true;
            }
        } else {
            $result = $this->mysqli->query("SELECT id FROM feeds WHERE id = '$feedid'");
            if ($result->num_rows>0) $feedexist = true;
        }
        return $feedexist;
    }
    
    // Check both if feed exists and if the user has access to the feed
    public function access($userid,$feedid)
    {
        $userid = (int) $userid;
        $feedid = (int) $feedid;
        
        $stmt = $this->mysqli->prepare("SELECT id FROM feeds WHERE userid=? AND id=?");
        $stmt->bind_param("ii",$userid,$feedid);
        $stmt->execute();
        $stmt->bind_result($id);
        $result = $stmt->fetch();
        $stmt->close();
        
        if ($result && $id>0) return true; else return false;
    }
    
    public function get_id($userid,$name)
    {
        $userid = (int) $userid;
        $name = preg_replace('/[^\w\s\-:]/','',$name);
        
        $stmt = $this->mysqli->prepare("SELECT id FROM feeds WHERE userid=? AND name=?");
        $stmt->bind_param("is",$userid,$name);
        $stmt->execute();
        $stmt->bind_result($id);
        $result = $stmt->fetch();
        $stmt->close();
        
        if ($result && $id>0) return $id; else return false;
    }

    public function exists_tag_name($userid,$tag,$name)
    {
        $userid = (int) $userid;
        $name = preg_replace('/[^\p{N}\p{L}_\s\-:]/u','',$name);
        $tag = preg_replace('/[^\p{N}\p{L}_\s\-:]/u','',$tag);
        
        $stmt = $this->mysqli->prepare("SELECT id FROM feeds WHERE userid=? AND BINARY name=? AND BINARY tag=?");
        $stmt->bind_param("iss",$userid,$name,$tag);
        $stmt->execute();
        $stmt->bind_result($id);
        $result = $stmt->fetch();
        $stmt->close();
        
        if ($result && $id>0) return $id; else return false;
    }

    // Update feed size and return total
    public function update_user_feeds_size($userid)
    {
        $userid = (int) $userid;
        $total = 0;
        $result = $this->mysqli->query("SELECT id,engine FROM feeds WHERE `userid` = '$userid'");
        while ($row = $result->fetch_array())
        {
            $size = 0;
            $feedid = $row['id'];
            $engine = $row['engine'];

            // Call to engine get_feed_size method
            $size = $this->EngineClass($engine)->get_feed_size($feedid);

            $this->mysqli->query("UPDATE feeds SET `size` = '$size' WHERE `id`= '$feedid'");
            if ($this->redis) $this->redis->hset("feed:$feedid",'size',$size);
            $total += $size;
        }
        return $total;
    }

    // Get REDISBUFFER date value elements pending save to a feed
    public function get_buffer_size()
    {
        $total = 0;
        if ($this->redis) {
            $feedids = $this->redis->sMembers("feed:bufferactive");
            foreach ($feedids as $feedid) {
                $total += $this->EngineClass(Engine::REDISBUFFER)->get_feed_size($feedid);
            }
        }
        return $total;
    }

    // Expose metadata from engines
    public function get_meta($feedid) {
        $feedid = (int) $feedid;
        $engine = $this->get_engine($feedid);
        return $this->EngineClass($engine)->get_meta($feedid);
    }


    /*
    Get operations by user
    get_user_feeds         : all the feeds table data
    get_user_public_feeds  : all the public feeds table data
    get_user_feed_ids      : only the feeds id's
    */
    public function get_user_feeds($userid)
    {
        $userid = (int) $userid;
        if ($this->redis) {
            $feeds = $this->redis_get_user_feeds($userid);
        } else {
            $feeds = $this->mysql_get_user_feeds($userid);
        }
        return $feeds;
    }

    public function get_user_public_feeds($userid)
    {
        $feeds = $this->get_user_feeds($userid);
        $publicfeeds = array();
        foreach ($feeds as $feed) { if ($feed['public']) $publicfeeds[] = $feed; }
        return $publicfeeds;
    }

    private function redis_get_user_feeds($userid)
    {
        $userid = (int) $userid;
        if (!$this->redis->exists("user:feeds:$userid")) $this->load_to_redis($userid);
        $feeds = array();
        $feedids = $this->redis->sMembers("user:feeds:$userid");
        foreach ($feedids as $id)
        {
            $row = $this->redis->hGetAll("feed:$id");
            $lastvalue = $this->get_timevalue($id);
            $row['time'] = $lastvalue['time'];
            $row['value'] = $lastvalue['value'];
            $meta = $this->get_meta($id);
            if (isset($meta->start_time)) $row['start_time'] = $meta->start_time;
            if (isset($meta->end_time)) $row['end_time'] = $meta->end_time;
            if (isset($meta->interval)) $row['interval'] = $meta->interval;
            $feeds[] = $row;
        }

        return $feeds;
    }

    private function mysql_get_user_feeds($userid)
    {
        $userid = (int) $userid;
        $feeds = array();
        $result = $this->mysqli->query("SELECT id,name,userid,tag,public,size,engine,time,value,processList,unit FROM feeds WHERE `userid` = '$userid'");
        while ($row = (array)$result->fetch_object())
        {
            if ($row['engine'] == Engine::VIRTUALFEED) { //if virtual get it now
                $this->log->info("mysql_get_user_feeds() calling VIRTUAL lastvalue " . $row['id']);
                $lastvirtual = $this->EngineClass(Engine::VIRTUALFEED)->lastvalue($row['id']);
                $row['time'] = $lastvirtual['time'];
                $row['value'] = $lastvirtual['value'];
                $meta = $this->get_meta($row['id']);
                $row['start_time'] = $meta->start_time;
                $row['interval'] = $meta->interval;
            }
            $feeds[] = $row;
        }
        return $feeds;
    }

    public function get_user_feeds_with_meta($userid)
    {
        $userid = (int) $userid;
        $feeds = $this->get_user_feeds($userid);
        for ($i=0; $i<count($feeds); $i++) {
            $id = $feeds[$i]["id"];
            if ($meta = $this->get_meta($id)) {
                foreach ($meta as $meta_key=>$meta_val) {
                    $feeds[$i][$meta_key] = $meta_val;
                }
            }
        }
        return $feeds;
    }

    /**
     * get array of feed ids by associated to user
     *
     * @param [int] $userid
     * @return array
     */
    public function get_user_feed_ids($userid)
    {
        $userid = (int) $userid;
        if ($this->redis) {
            if (!$this->redis->exists("user:feeds:$userid")) $this->load_to_redis($userid);
            $feedids = $this->redis->sMembers("user:feeds:$userid");
        } else {
            $result = $this->mysqli->query("SELECT id FROM feeds WHERE `userid` = '$userid'");
            $feedids = array();
            while ($row = $result->fetch_array()) $feedids[] = $row['id'];
        }
        return $feedids;
    }


    /*
    Get operations by feed id
    get             : feed all fields
    get_field       : feed specific field
    get_timevalue   : feed last updated time and value
    get_value       : feed last updated value
    get_data        : feed data by time range
    */
    public function get($id)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');

        if ($this->redis) {
            // Get from redis cache
            $row = $this->redis->hGetAll("feed:$id");
        } else {
            // Get from mysql db
            $result = $this->mysqli->query("SELECT id,name,userid,tag,public,size,engine,processList FROM feeds WHERE `id` = '$id'");
            $row = (array) $result->fetch_object();
        }
        $lastvalue = $this->get_timevalue($id);
        $row['time'] = $lastvalue['time'];
        $row['value'] = $lastvalue['value'];
        return $row;
    }

    public function get_field($id,$field)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');

        if ($field!=null) // if the feed exists
        {
            $field = preg_replace('/[^\w\s\-]/','',$field);
         
            if ($field=='time' || $field=='value') {
                $lastvalue = $this->get_timevalue($id);
                $val = $lastvalue[$field];
            }
            else if ($this->redis) {
                $val = $this->redis->hget("feed:$id",$field);
            } else {
                $result = $this->mysqli->query("SELECT * FROM feeds WHERE `id` = '$id'");
                $row = $result->fetch_array();
                if (isset($row[$field])) $val = $row[$field];
            }
            return $val;
        }
        else return array('success'=>false, 'message'=>'Missing field parameter');
    }

    public function get_timevalue($id)
    {
        $id = (int) $id;
        //$this->log->info("get_timevalue() $id");
        if (!$this->exist($id)) {
            $this->log->error("get_timevalue() Feed '".$id."' does not exist.");
            return null;
        }
        $engine = $this->get_engine($id);

        if ($engine == Engine::VIRTUALFEED) { //if virtual get it now
            $this->log->info("get_timevalue() calling VIRTUAL lastvalue $id");
            $lastvirtual = $this->EngineClass(Engine::VIRTUALFEED)->lastvalue($id);
            return array('time'=>$lastvirtual['time'], 'value'=>$lastvirtual['value']);
        }

        if ($this->redis)
        {
            if ($this->redis->hExists("feed:$id",'time')) {
                $lastvalue = $this->redis->hmget("feed:$id",array('time','value'));
                if (!isset($lastvalue['time']) || !is_numeric($lastvalue['time']) || is_nan($lastvalue['time'])) {
                    $lastvalue['time'] = null;
                } else {
                    $lastvalue['time'] = (int) $lastvalue['time'];
                }
                if (!isset($lastvalue['value']) || !is_numeric($lastvalue['value']) || is_nan($lastvalue['value'])) {
                    $lastvalue['value'] = null;
                } else {
                    $lastvalue['value'] = (float) $lastvalue['value'];
                }
                // CHAVEIRO comment: Can return NULL as a valid number or else processlist logic will be broken
            } else {
                // if it does not, load it in to redis from the actual feed data because we have no updated data from sql feeds table with redis enabled.
                if ($lastvalue = $this->EngineClass($engine)->lastvalue($id)) {
                    $this->redis->hMset("feed:$id", array('time' => $lastvalue['time'],'value' => $lastvalue['value']));
                } else {
                    $lastvalue = array('time'=>null,'value'=>null);
                }
            }
        }
        else
        {
            // must read last timestamp as if feed is daily last engine time is not last updated time but midnight
            $result = $this->mysqli->query("SELECT time,value FROM feeds WHERE `id` = '$id'");
            $row = $result->fetch_array();
            if ($row) {
                $lastvalue = array('time'=>(int)$row['time'], 'value'=>(float)$row['value']);
            }
        }
        return $lastvalue;
    }

    public function get_value($feedid,$time=false)
    {
        $feedid = (int) $feedid;
        
        if (!$time) {
            $lastvalue = $this->get_timevalue($feedid);
            return $lastvalue['value'];
        } else {
            if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
            
            $engine = $this->get_engine($feedid);
            if ($engine!=Engine::PHPFINA && $engine != Engine::PHPTIMESERIES) return array('success'=>false, 'message'=>"This request is only supported by PHPFina");
            
            return $this->EngineClass($engine)->get_value($feedid,$time);
        }
    }

    public function get_data($feedid,$start,$end,$interval,$average=0,$timezone="UTC",$timeformat="unixms",$csv=false,$skipmissing=0,$limitinterval=0,$delta=false,$dp=-1)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) {
            return array('success'=>false, 'message'=>'Feed does not exist');
        }
                        
        $start = $this->convert_time($start,$timezone);
        $end = $this->convert_time($end,$timezone);

        if ($end<=$start) return array('success'=>false, 'message'=>"Request end time before start time");
        
        // Default interval if interval
        if (is_numeric($interval) && $interval<1) {
            $interval = round(($end-$start)/800);
        }
        
        // Delta mode prepare
        if ($delta && !$csv) {
            $end = $this->delta_mode_next_interval($end,$interval,$timezone);
        }
        
        // Maximum request size
        if (!$csv && is_numeric($interval)) {
            $period = $end-$start;
            $req_dp = round($period / $interval);
            if ($req_dp > $this->settings['max_datapoints']) {
                return array(
                    "success"=>false, 
                    "message"=>"request datapoint limit reached (".$this->settings['max_datapoints']."), increase request interval or reduce time range, requested datapoints = $req_dp"
                );
            }
        }

        if (!in_array($timeformat,array("unix","unixms","excel","iso8601","notime"))) {
            return array('success'=>false, 'message'=>'Invalid time format');
        }
        
        $engine = $this->get_engine($feedid);

        // Call to engine get_data_combined
        $data = $this->EngineClass($engine)->get_data_combined($feedid,$start,$end,$interval,$average,$timezone,$timeformat,$csv,$skipmissing,$limitinterval);

        if ($this->settings['redisbuffer']['enabled'] && !isset($data["success"]) && !$average && is_numeric($interval) && $csv==false) {
            // Add redisbuffer cache if available
            if ($data && $skipmissing) {
                $bufferstart=end($data)[0];
            } else {
                $bufferstart = $start;
            }
            
            $bufferdata = $this->EngineClass(Engine::REDISBUFFER)->get_data_combined($feedid,$start,$end,$interval,$average,$timezone,$timeformat,$csv,$skipmissing,$limitinterval);
            
            if (!empty($bufferdata)) {
                // $this->log->info("get_data_combined() Buffer cache merged feedid=$feedid start=". reset($data)[0] ." end=". end($data)[0] ." bufferstart=". reset($bufferdata)[0] ." bufferend=". end($bufferdata)[0]);

                // Merge buffered data into base data timeslots (over-writing null values where they exist)
                if (!$skipmissing && ($engine==Engine::PHPFINA || $engine==Engine::PHPTIMESERIES)) {
                    
                    // Convert buffered data to associative array - by timestamp
                    $bufferdata_assoc = array();
                    for ($z=0; $z<count($bufferdata); $z++) {
                        $time = floor($bufferdata[$z][0]/$interval)*$interval;
                        $bufferdata_assoc[$time] = $bufferdata[$z][1];
                    }
                    
                    // Merge data into base data
                    for ($z=0; $z<count($data); $z++) {
                        $time = $data[$z][0];
                        if (isset($bufferdata_assoc["".$time]) && $data[$z][1]==null) {
                            $data[$z][1] = $bufferdata_assoc["".$time];
                        }
                    }
                    
                } else {
                    $data = array_merge($data, $bufferdata);
                }
            }
        }
        
        if ($delta) $data = $this->delta_mode_convert($feedid,$data);
        
        // Apply dp setting
        if ($dp!=-1) {
            $dp = (int) $dp;
            for ($i=0; $i<count($data); $i++) {
                $data[$i][1] = round($data[$i][1],$dp);
            }
        }
        
        // Apply different timeformats if applicable
        if ($timeformat!="unix") $data = $this->format_output_time($data,$timeformat,$timezone);
        
        return $data;
    }
    
    /*
    Converts a data request to a cumulative kWh feed into kWh per day, week, month, year
    Includes the current day, week, month, year
    */ 
    private function delta_mode_next_interval($end,$interval,$timezone) {
        if (in_array($interval,array("weekly","daily","monthly","annual"))) {
            // align to day, month, year
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone($timezone));
            $date->setTimestamp($end);
            $date->modify("tomorrow midnight");
            if ($interval=="weekly") {
                $date->modify("next monday");
            } else if ($interval=="monthly") {
                $date->modify("first day of next month");
            } else if ($interval=="annual") {
                $date->modify("first day of january next year");
            }
            $end = $date->getTimestamp();
        } else {
            // standard interval
            $end = floor($end/$interval)*$interval;
            $end += $interval;
        }
        return $end;
    }
    
    private function delta_mode_convert($feedid,$data) {
        // Get last value
        $dp = $this->get_timevalue($feedid);
        $time = $dp["time"];
        
        // Calculate delta mode
        $last_val = null;
        for($i=0; $i<count($data)-1; $i++) {
            // Apply current value to end of day, week, month, year, interval
            if ($data[$i+1][1]===null && $time>$data[$i][0] && $time<=$data[$i+1][0]) {
                $data[$i+1][1] = $dp['value'];
            }
            // Delta calculation
            if ($data[$i][1]===null || $data[$i+1][1]===null) {
                $data[$i][1] = null;
            } else {
                $data[$i][1] = $data[$i+1][1] - $data[$i][1];
                $last_val = $data[$i+1][1];
            }
        }
        array_pop($data);
        
        return $data;
    }
    
    private function convert_time($time,$timezone) {
        // Option to specify times as date strings
        if (!is_numeric($time)) {
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone($timezone));
            $date->modify($time);
            $time = $date->getTimestamp();
        }    
        
        // If timestamp is in milliseconds convert to seconds
        if (($time/1000000000)>100) {
            $time *= 0.001;
        }
        return $time;
    }
    
    private function format_output_time($data,$timeformat,$timezone) {
        switch ($timeformat) {
            case "unixms":
                for ($i=0; $i<count($data); $i++) {
                    $data[$i][0] *= 1000;
                }
                break;
            case "excel":
                $date = new DateTime();
                $date->setTimezone(new DateTimeZone($timezone));          
                for ($i=0; $i<count($data); $i++) {
                    $date->setTimestamp($data[$i][0]);
                    $data[$i][0] = $date->format("d/m/Y H:i:s"); 
                }
                break;
            case "iso8601":
                $date = new DateTime();
                $date->setTimezone(new DateTimeZone($timezone)); 
                for ($i=0; $i<count($data); $i++) {
                    $date->setTimestamp($data[$i][0]);
                    $data[$i][0] = $date->format("c"); 
                }
                break;
            case "notime":
                $tmp = array();
                for ($i=0; $i<count($data); $i++) {
                    $tmp[] = $data[$i][1];
                }
                $data = $tmp;
                break;
        }
        return $data;
    }
    
    public function get_data_DMY_time_of_day($feedid,$start,$end,$interval,$timezone,$timeformat,$split)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
        
        $start = $this->convert_time($start,$timezone);
        $end = $this->convert_time($end,$timezone);
                
        if ($end<=$start) return array('success'=>false, 'message'=>"Request end time before start time");

        $engine = $this->get_engine($feedid);
        if ($engine != Engine::PHPFINA && $engine != Engine::MYSQL ) return array('success'=>false, 'message'=>"This request is only supported by PHPFina AND MySQLTimeseries");
            
        $data = $this->EngineClass($engine)->get_data_DMY_time_of_day($feedid,$start,$end,$interval,$timezone,$split);

        // Apply different timeformats if applicable
        if ($timeformat!="unix") $data = $this->format_output_time($data,$timeformat,$timezone);

        return $data;
    }
    
    public function csv_export_multi($feedids,$data,$timezone,$timeformat)
    {
        require_once "Modules/feed/engine/shared_helper.php";
        $helperclass = new SharedHelper($this->settings);
        $helperclass->set_time_format($timezone,$timeformat);
        $helperclass->csv_header(implode("-",$feedids));
        $keys = [];
        foreach ($data as $key=>$f) $keys[] = $key;
        if ($num_of_feeds = count($keys)) {
            $k = $keys[0];
            for ($i=0; $i<count($data[$k]['data']); $i++) {
                // Time is index 0
                $values = array($data[$k]['data'][$i][0]);
                foreach ($keys as $key) {
                    // Values index 1 upwards
                    $values[] = $data[$key]['data'][$i][1];
                }
                $helperclass->csv_write_multi($values);
            }
        }
        
        $helperclass->csv_close();
        exit;
    }
    
    /*
    Write operations
    set_feed_fields : set feed fields
    set_timevalue   : set feed last value
    post            : add or update datapoint
    */
    public function set_feed_fields($id,$fields)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');
        $fields = json_decode(stripslashes($fields));
        
        $success = false;

        if (isset($fields->name)) {
            //remove illegal characters
            $fields->name = trim(filter_var($fields->name, FILTER_SANITIZE_STRING));
            //prepare an sql statement that cannot be altered by sql injection
            if ($stmt = $this->mysqli->prepare("UPDATE feeds SET name = ? WHERE id = ?")) {
                $stmt->bind_param("si",$fields->name, $id);
                if (false === $stmt->execute()) {
                    return array('success'=>false, 'message'=>'field update failed');
                } else {
                    $success = true;
                }
                $stmt->close();
                if ($this->redis) $this->redis->hset("feed:$id",'name',$fields->name);
            } else {
                return array('success'=>false, 'message'=>'error setting up database update');
            }
        }
        
        if (isset($fields->tag)) {
            if (preg_replace('/[^\p{N}\p{L}_\s\-:]/u','',$fields->tag)!=$fields->tag) return array('success'=>false, 'message'=>'invalid characters in feed tag');
            if ($stmt = $this->mysqli->prepare("UPDATE feeds SET tag = ? WHERE id = ?")) {
                $stmt->bind_param("si",$fields->tag,$id);
                if ($stmt->execute()) $success = true;
                $stmt->close();
                if ($this->redis) $this->redis->hset("feed:$id",'tag',$fields->tag);
            }
        }

        if (isset($fields->unit)) {
        if ($fields->unit !== filter_var($fields->unit, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_BACKTICK | FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW)) {
            return array('success'=>false, 'message'=>'invalid characters in feed unit');
        }
            if (strlen($fields->unit) > 10) return array('success'=>false, 'message'=>'feed unit too long');
            if ($stmt = $this->mysqli->prepare("UPDATE feeds SET unit = ? WHERE id = ?")) {
                $stmt->bind_param("si",$fields->unit,$id);
                if ($stmt->execute()) $success = true;
                $stmt->close();
                if ($this->redis) $this->redis->hset("feed:$id",'unit',$fields->unit);
            }
        }

        if (isset($fields->public)) {
            $public = (int) $fields->public;
            if ($public>0) $public = 1;
            if ($stmt = $this->mysqli->prepare("UPDATE feeds SET public = ? WHERE id = ?")) {
                $stmt->bind_param("ii",$public,$id);
                if ($stmt->execute()) $success = true;
                $stmt->close();
                if ($this->redis) $this->redis->hset("feed:$id",'public',$public);
            }
        }

        if ($success){
            return array('success'=>true, 'message'=>'Field updated');
        } else {
            return array('success'=>false, 'message'=>'Field could not be updated');
        }
    }

    public function set_timevalue($id, $value, $time)
    {
        if ($this->redis) {
            $this->redis->hMset("feed:$id", array('value' => $value, 'time' => $time));
        } else {
            if ($stmt = $this->mysqli->prepare("UPDATE feeds SET time = ?, value = ? WHERE id = ?")) {
                $stmt->bind_param("idi", $time, $value, $id);
                $stmt->execute();
            }
        }
    }

    public function post($feedid,$updatetime,$feedtime,$value,$arg=null)
    {
        $this->log->info("post() feedid=$feedid updatetime=$updatetime feedtime=$feedtime value=$value arg=$arg");
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $updatetime = intval($updatetime);
        if ($feedtime == null) $feedtime = $updatetime;
        $feedtime = intval($feedtime);
        $value = floatval($value);

        $engine = $this->get_engine($feedid);
        if ($this->settings['redisbuffer']['enabled']) {
            // Call to buffer post
            $args = array('engine'=>$engine,'updatetime'=>$updatetime,'arg'=>$arg);
            $this->EngineClass(Engine::REDISBUFFER)->post($feedid,$feedtime,$value,$args);
        } else {
            // Call to engine post
            $this->EngineClass($engine)->post($feedid,$feedtime,$value,$arg);
        }

        $this->set_timevalue($feedid, $value, $updatetime);

        return $value;
    }
    
    public function post_multiple($feedid,$data,$arg=null)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
        if (!count($data)) return array('success'=>false, 'message'=>'Data empty');
        $engine = $this->get_engine($feedid);
        
        // Post directly if phpfina, phptimeseries and number of data points is more than 10
        if (($engine==Engine::PHPFINA || $engine==Engine::PHPTIMESERIES) && count($data)>10) {
            $this->EngineClass($engine)->post_multiple($feedid,$data,$arg);
        } else {
            foreach ($data as $dp) {
                if (count($dp)==2) {
                    $this->EngineClass($engine)->post($feedid,$dp[0],$dp[1]);
                }
            }
        }
        
        // Only update last time value if datapoint is >= the most recent datapoint
        $lastdp = end($data);
        if (count($lastdp)==2) {
            $last = $this->get_timevalue($feedid);
            if ($lastdp[0]>=$last['time']) {
                $this->set_timevalue($feedid, $lastdp[1], $lastdp[0]);
            }
        }
        return array('success'=>true);
    }
    
    public function upload_fixed_interval($feedid,$start,$interval,$npoints)
    {
        $feedid = (int) $feedid;
        $start = (int) $start;
        $interval = (int) $interval;
        $npoints = (int) $npoints;

        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
        $engine = $this->get_engine($feedid);
        if ($engine==Engine::PHPFINA) {
            return $this->EngineClass($engine)->upload_fixed_interval($feedid,$start,$interval,$npoints);
        } else {
            return array('success'=>false, 'message'=>'Feed upload not supported for this engine');
        }
    }

    public function upload_variable_interval($feedid,$npoints)
    {
        $feedid = (int) $feedid;
        $npoints = (int) $npoints;
        
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');        
        $engine = $this->get_engine($feedid);
        if ($engine==Engine::PHPFINA) {
            return $this->EngineClass($engine)->upload_variable_interval($feedid,$npoints);
        } else {
            return array('success'=>false, 'message'=>'Feed upload not supported for this engine');
        }
    }

    // MysqlTimeSeries specific functions that we need to make available to the controller
    public function mysqltimeseries_export($feedid,$start) {
        return $this->EngineClass(Engine::MYSQL)->export($feedid,$start);
    }

    public function mysqltimeseries_delete_data_point($feedid,$time) {
        return $this->EngineClass(Engine::MYSQL)->delete_data_point($feedid,$time);
    }

    public function mysqltimeseries_delete_data_range($feedid,$start,$end) {
        return $this->EngineClass(Engine::MYSQL)->delete_data_range($feedid,$start,$end);
    }
    
    // PHPTimeSeries specific functions that we need to make available to the controller
    public function phptimeseries_export($feedid,$start) {
        return $this->EngineClass(Engine::PHPTIMESERIES)->export($feedid,$start);
    }

    public function phpfina_export($feedid,$start) {
        return $this->EngineClass(Engine::PHPFINA)->export($feedid,$start);
    }

    /*
     Processlist functions
    */
    // USES: redis feed
    public function get_processlist($id)
    {
        // LOAD REDIS
        $id = (int) $id;

        if ($this->redis) {
            if (!$this->redis->exists("feed:$id")) $this->load_feed_to_redis($id);
            return $this->redis->hget("feed:$id",'processList');
        } else {
            $result = $this->mysqli->query("SELECT processList FROM feeds WHERE `id` = '$id'");
            $row = $result->fetch_array();
            if (!$row['processList']) $row['processList'] = "";
            return $row['processList'];
        }
    }

    public function set_processlist($userid, $id, $processlist, $process_list)
    {
        $userid = (int) $userid;
        
        // Validate processlist
        $pairs = explode(",",$processlist);
        $pairs_out = array();
        
        // Build map of processids where set
        $map = array();
        foreach ($process_list as $key=>$process) {
            if (isset($process['id_num'])) $map[$process['id_num']] = $key;
        }
        
        foreach ($pairs as $pair)
        {
            $inputprocess = explode(":", $pair);
            if (count($inputprocess)==2) {
            
                // Verify process id
                $processkey = $inputprocess[0];
                // If key is in the map, switch to associated full process key
                if (isset($map[$processkey])) $processkey = $map[$processkey];
            
                // Load process
                if (isset($process_list[$processkey])) {
                    $processarg = $process_list[$processkey]['argtype'];
                    $proccess_name = $process_list[$processkey]['function'];
                    
                    // remap process back to use map id if available
                    if (isset($process_list[$processkey]['id_num']))
                        $processkey = $process_list[$processkey]['id_num'];
                    
                } else {
                    return array('success'=>false, 'message'=>_("Invalid process processid:$processkey"));
                }
                
                // Verify argument
                $arg = $inputprocess[1];

                // Stop virtual feeds from adding email and mqtt processes.
                $isVirtual = $this->get_engine($id)===7;
                $not_for_virtual_feeds = array('publish_to_mqtt','sendEmail');
                if (in_array($proccess_name, $not_for_virtual_feeds) && $isVirtual) {
                    $this->log->error('Publish to MQTT and SendMail blocked for Virtual Feeds');
                    return array('success'=>false, 'message'=>_("Invalid proccess for Virtual Feed, try another."));
                }

                // Check argument against process arg type
                switch($processarg){
                
                    case ProcessArg::FEEDID:
                        $feedid = (int) $arg;
                        $isVirtual = $this->get_engine($id)===7;
                        if (!$this->access($userid,$feedid)) {
                            return array('success'=>false, 'message'=>_("Invalid feed"));
                        } else if ($isVirtual) {
                            return array('success'=>false, 'message'=>_("Cannot use virtual feed as source"));
                        }
                        break;
                        
                    case ProcessArg::INPUTID:
                        $inputid = (int) $arg;
                        if (!$this->input_access($userid,$inputid)) {
                            return array('success'=>false, 'message'=>_("Invalid input"));
                        }
                        break;

                    case ProcessArg::VALUE:
                        if (!is_numeric($arg)) {
                            return array('success'=>false, 'message'=>'Value is not numeric'); 
                        }
                        break;

                    case ProcessArg::TEXT:
                        if (preg_replace('/[^{}\p{N}\p{L}_\s\/.\-]/u','',$arg)!=$arg) 
                            return array('success'=>false, 'message'=>'Invalid characters in argx'); 
                        break;
                                                
                    case ProcessArg::SCHEDULEID:
                        $scheduleid = (int) $arg;
                        if (!$this->schedule_access($userid,$scheduleid)) { // This should really be in the schedule model
                            return array('success'=>false, 'message'=>'Invalid schedule'); 
                        }
                        break;
                        
                    case ProcessArg::NONE:
                        $arg = false;
                        break;
                        
                    default:
                        $arg = false;
                        break;
                }
                
                $pairs_out[] = implode(":",array($processkey,$arg));
            }
        }
        
        // rebuild processlist from verified content
        $processlist_out = implode(",",$pairs_out);
    
        $stmt = $this->mysqli->prepare("UPDATE feeds SET processList=? WHERE id=?");
        $stmt->bind_param("si", $processlist_out, $id);
        if (!$stmt->execute()) {
            return array('success'=>false, 'message'=>_("Error setting processlist"));
        }
        
        if ($this->mysqli->affected_rows>0){
            if ($this->redis) $this->redis->hset("feed:$id",'processList',$processlist_out);
            return array('success'=>true, 'message'=>'Feed processlist updated');
        } else {
            return array('success'=>false, 'message'=>'Feed processlist was not updated');
        }
    }
    
    public function reset_processlist($id)
    {
        $id = (int) $id;
        return $this->set_processlist($id, "");
    }


    /* Redis helpers */
    private function load_to_redis($userid)
    {
        $result = $this->mysqli->query("SELECT * FROM feeds WHERE `userid` = '$userid'");
        while ($row = $result->fetch_object())
        {
            $this->redis->sAdd("user:feeds:$userid", $row->id);
            $this->redis->hMSet("feed:$row->id",array(
            'id'=>$row->id,
            'userid'=>$row->userid,
            'name'=>$row->name,
            'tag'=>$row->tag,
            'public'=>$row->public,
            'size'=>$row->size,
            'engine'=>$row->engine,
            'processList'=>$row->processList,
            'unit'=> !empty($row->unit) ? $row->unit : ''
            ));
        }
    }

    private function load_feed_to_redis($id)
    {
        $result = $this->mysqli->query("SELECT * FROM feeds WHERE `id` = '$id'");
        $row = $result->fetch_object();
        if (!$row) {
            $this->log->warn("Feed model: Requested feed does not exist feedid=$id");
            return false;
        }
        $this->redis->hMSet("feed:$row->id",array(
            'id'=>$row->id,
            'userid'=>$row->userid,
            'name'=>$row->name,
            'tag'=>$row->tag,
            'public'=>$row->public,
            'size'=>$row->size,
            'engine'=>$row->engine,
            'processList'=>$row->processList,
            'unit'=> !empty($row->unit) ? $row->unit : ''
        ));
        return true;
    }


    /* Other helpers */
    private function get_engine($feedid)
    {
        if ($this->redis) {
            $engine = $this->redis->hget("feed:$feedid",'engine');
        } else {
            $result = $this->mysqli->query("SELECT engine FROM feeds WHERE `id` = '$feedid'");
            $row = $result->fetch_object();
            $engine = $row->engine;
        }
        return $engine;
    }
    
    public function get_user_timezone($userid) 
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT timezone FROM users WHERE id = '$userid';");
        $row = $result->fetch_object();

        $now = new DateTime();
        try {
            $now->setTimezone(new DateTimeZone($row->timezone));
            $timezone = $row->timezone;
        } catch (Exception $e) {
            $timezone = "UTC";
        }
        return $timezone;
    }
    
    // ------------------------------------------
    
    private function input_access($userid,$inputid)
    {
        $userid = (int) $userid;
        $inputid = (int) $inputid;
        $stmt = $this->mysqli->prepare("SELECT id FROM input WHERE userid=? AND id=?");
        $stmt->bind_param("ii",$userid,$inputid);
        $stmt->execute();
        $stmt->bind_result($id);
        $result = $stmt->fetch();
        $stmt->close();
        if ($result && $id>0) return true; else return false;
    }
    
    private function schedule_access($userid,$scheduleid)
    {
        $userid = (int) $userid;
        $scheduleid = (int) $scheduleid;
        $stmt = $this->mysqli->prepare("SELECT id FROM schedule WHERE userid=? AND id=?");
        $stmt->bind_param("ii",$userid,$scheduleid);
        $stmt->execute();
        $stmt->bind_result($id);
        $result = $stmt->fetch();
        $stmt->close();
        if ($result && $id>0) return true; else return false;
    }
}

