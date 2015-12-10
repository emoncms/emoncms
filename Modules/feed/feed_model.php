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
        if (isset($engines[$e])) {
            //$this->log->info("EngineClass() reused instance of '".get_class($engines[$e])."' id '".$e."'.");
            return $engines[$e];
        }
        else {
            // Load different storage engines
            if ($e == (string)Engine::MYSQL) {
                    require "Modules/feed/engine/MysqlTimeSeries.php";  // Mysql engine
                    $engines[$e] = new MysqlTimeSeries($this->mysqli);
            } else if ($e == (string)Engine::VIRTUALFEED) {
                    require "Modules/feed/engine/VirtualFeed.php";      // Takes care of Virtual Feeds
                    $engines[$e] =  new VirtualFeed($this->mysqli,$this->redis,$this);
            } else if ($e == (string)Engine::PHPFINA) {
                    require "Modules/feed/engine/PHPFina.php";          // Fixed interval no averaging
                    $engines[$e] =  new PHPFina($this->settings['phpfina']);
            } else if ($e == (string)Engine::PHPFIWA) {
                    require "Modules/feed/engine/PHPFiwa.php";          // Fixed interval with averaging
                    $engines[$e] = new PHPFiwa($this->settings['phpfiwa']);
            } else if ($e == (string)Engine::REDISBUFFER) {
                    require "Modules/feed/engine/RedisBuffer.php";      // Redis buffer for low-write mode
                    $engines[$e] = new RedisBuffer($this->redis,$this->settings['redisbuffer'],$this);
            } else if ($e == (string)Engine::PHPTIMESERIES) {
                    require "Modules/feed/engine/PHPTimeSeries.php";    // Variable interval no averaging
                    $engines[$e] =  new PHPTimeSeries($this->settings['phptimeseries']);
            } else if ($e == (string)Engine::MYSQLMEMORY) {
                    require_once "Modules/feed/engine/MysqlTimeSeries.php";  // Mysql engine
                    require "Modules/feed/engine/MysqlMemory.php";           // Mysql Memory engine
                    $engines[$e] =  new MysqlMemory($this->mysqli);
            } else if ($e == "histogram") {
                    require "Modules/feed/engine/Histogram.php";        // Histogram, depends on mysql
                    $engines[$e] = new Histogram($this->mysqli);
            } else {
                    $this->log->error("EngineClass() Engine id '".$e."' is not supported.");
                    throw new Exception("ABORTED: Engine id '".$e."' is not supported.");
            }
            $this->log->info("EngineClass() Autoloaded new instance of '".get_class($engines[$e])."'.");
            return $engines[$e];
        }
    }


    /*
    Configurations operations
    create, delete, exist, update_user_feeds_size, get_buffer_size, get_meta
    */
    public function create($userid,$tag,$name,$datatype,$engine,$options_in)
    {
        $userid = (int) $userid;
        $name = preg_replace('/[^\p{N}\p{L}_\s-:]/u','',$name);
        $tag = preg_replace('/[^\p{N}\p{L}_\s-:]/u','',$tag);
        $datatype = (int) $datatype;
        $engine = (int) $engine;

        // Histogram engine requires MYSQL
        if ($datatype==DataType::HISTOGRAM && $engine!=Engine::MYSQL) $engine = Engine::MYSQL;

        $result = $this->mysqli->query("INSERT INTO feeds (userid,tag,name,datatype,public,engine) VALUES ('$userid','$tag','$name','$datatype',false,'$engine')");
        $feedid = $this->mysqli->insert_id;

        if ($feedid>0)
        {
            // Add the feed to redis
            if ($this->redis) {
                $this->redis->sAdd("user:feeds:$userid", $feedid);
                $this->redis->hMSet("feed:$feedid",array(
                    'id'=>$feedid,
                    'userid'=>$userid,
                    'name'=>$name,
                    'datatype'=>$datatype,
                    'tag'=>$tag,
                    'public'=>false,
                    'size'=>0,
                    'engine'=>$engine
                ));
            }

            $options = array();
            if ($engine==Engine::PHPFINA) $options['interval'] = (int) $options_in->interval;
            if ($engine==Engine::PHPFIWA) $options['interval'] = (int) $options_in->interval;

            $engineresult = false;
            if ($datatype==DataType::HISTOGRAM) {
                $engineresult = $this->EngineClass("histogram")->create($feedid,$options);
            } else {
                $engineresult = $this->EngineClass($engine)->create($feedid,$options);
            }

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

        if (isset($feed_exists_cache[$feedid])) { unset($feed_exists_cache[$feedid]); } // Clear static cache
        if (isset($feed_engine_cache[$feedid])) { unset($feed_engine_cache[$feedid]); } // Clear static cache
        $this->log->info("delete() feedid=$feedid");
    }

    public function exist($feedid)
    {
        //$this->log->info("exist() feedid=$feedid");
        static $feed_exists_cache = array(); // Array to hold the cache
        if (isset($feed_exists_cache[$feedid])) {
            $feedexist = $feed_exists_cache[$feedid]; // Retrieve from static cache
        } else {
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
                $feedid = intval($feedid);
                $result = $this->mysqli->query("SELECT id FROM feeds WHERE id = '$feedid'");
                if ($result->num_rows>0) $feedexist = true;
            }
            $feed_exists_cache[$feedid] = $feedexist; // Cache it
        }
        return $feedexist;
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
            $feeds[] = $row;
        }

        return $feeds;
    }

    private function mysql_get_user_feeds($userid)
    {
        $userid = (int) $userid;
        $feeds = array();
        $result = $this->mysqli->query("SELECT id,name,userid,tag,datatype,public,size,engine,time,value,processList FROM feeds WHERE `userid` = '$userid'");
        while ($row = (array)$result->fetch_object())
        {
            if ($row['engine'] == Engine::VIRTUALFEED) { //if virtual get it now
                $this->log->info("mysql_get_user_feeds() calling VIRTUAL lastvalue " . $row['id']);
                $lastvirtual = $this->EngineClass(Engine::VIRTUALFEED)->lastvalue($row['id']);
                $row['time'] = $lastvirtual['time'];
                $row['value'] = $lastvirtual['value'];
            }
            $feeds[] = $row;
        }
        return $feeds;
    }

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
    csv_export      : feed data by time range in csv format
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
            $result = $this->mysqli->query("SELECT id,name,userid,tag,datatype,public,size,engine,processList FROM feeds WHERE `id` = '$id'");
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

        if ($field!=NULL) // if the feed exists
        {
            $field = preg_replace('/[^\w\s-]/','',$field);
         
            if ($field=='time' || $field=='value') {
                $lastvalue = $this->get_timevalue($id);
                $val = $lastvalue[$field];
            }
            else if ($this->redis) {
                $val = $this->redis->hget("feed:$id",$field);
            } else {
                $result = $this->mysqli->query("SELECT `$field` FROM feeds WHERE `id` = '$id'");
                $row = $result->fetch_array();
                $val = $row[0];
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
            } else {
                // if it does not, load it in to redis from the actual feed data because we have no updated data from sql feeds table with redis enabled.
                $lastvalue = $this->EngineClass($engine)->lastvalue($id);
                $this->redis->hMset("feed:$id", array('time' => $lastvalue['time'],'value' => $lastvalue['value']));
            }
        }
        else
        {
            // must read last timestamp as if feed is daily last engine time is not last updated time but midnight
            $result = $this->mysqli->query("SELECT time,value FROM feeds WHERE `id` = '$id'");
            $row = $result->fetch_array();
            if ($row) {
                $lastvalue = array('time'=>$row['time'], 'value'=>$row['value']);
            }
        }
        return $lastvalue;
    }

    public function get_value($id)
    {
        $lastvalue = $this->get_timevalue($id);
        return $lastvalue['value'];
    }

    public function get_data($feedid,$start,$end,$outinterval,$skipmissing,$limitinterval)
    {
        $feedid = (int) $feedid;
        if ($end<=$start) return array('success'=>false, 'message'=>"Request end time before start time");
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
        $engine = $this->get_engine($feedid);
        if ($engine == Engine::VIRTUALFEED) {
            $this->log->info("get_data() $feedid,$start,$end,$outinterval,$skipmissing,$limitinterval");
        }

        // Call to engine get_data
        $data = $this->EngineClass($engine)->get_data($feedid,$start,$end,$outinterval,$skipmissing,$limitinterval);

        if ($this->settings['redisbuffer']['enabled']) {
            // Add redisbuffer cache if available
            $bufferstart=end($data)[0];
            $bufferdata = $this->EngineClass(Engine::REDISBUFFER)->get_data($feedid,$bufferstart,$end,$outinterval,$skipmissing,$limitinterval);
            if (!empty($bufferdata)) {
                $this->log->info("get_data() Buffer cache merged feedid=$feedid start=". reset($data)[0]/1000 ." end=". end($data)[0]/1000 ." bufferstart=". reset($bufferdata)[0]/1000 ." bufferend=". end($bufferdata)[0]/1000);
                $data = array_merge($data, $bufferdata);
            }
        }

        return $data;
    }

    public function csv_export($feedid,$start,$end,$outinterval,$datetimeformat)
    {
        $feedid = (int) $feedid;
        if ($end<=$start) return array('success'=>false, 'message'=>"Request end time before start time");
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
        $engine = $this->get_engine($feedid);

        // Download limit
        $downloadsize = (($end - $start) / $outinterval) * 17; // 17 bytes per dp
        if ($downloadsize>($this->settings['csvdownloadlimit_mb']*1048576)) {
            $this->log->warn("csv_export() CSV download limit exeeded downloadsize=$downloadsize feedid=$feedid");
            return array('success'=>false, 'message'=>"CSV download limit exeeded downloadsize=$downloadsize");
        }

        if ($datetimeformat == 1) {
            global $user,$session;
            $usertimezone = $user->get_timezone($session['userid']);
        } else {
            $usertimezone = false;
        }
        // Call to engine csv_export method
        return $this->EngineClass($engine)->csv_export($feedid,$start,$end,$outinterval,$usertimezone);
    }


    /*
    Write operations
    set_feed_fields : set feed fields
    set_timevalue   : set feed last value
    insert_data     : insert current data
    update_data     : update data at specified time
    */
    public function set_feed_fields($id,$fields)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');
        $fields = json_decode(stripslashes($fields));
        $array = array();

        // Repeat this line changing the field name to add fields that can be updated:
        if (isset($fields->name)) $array[] = "`name` = '".preg_replace('/[^\p{N}\p{L}_\s-:]/u','',$fields->name)."'";
        if (isset($fields->tag)) $array[] = "`tag` = '".preg_replace('/[^\p{N}\p{L}_\s-:]/u','',$fields->tag)."'";
        if (isset($fields->public)) $array[] = "`public` = '".intval($fields->public)."'";

        // Convert to a comma separated string for the mysql query
        $fieldstr = implode(",",$array);
        $this->mysqli->query("UPDATE feeds SET ".$fieldstr." WHERE `id` = '$id'");

        // Update redis
        if ($this->redis && isset($fields->name)) $this->redis->hset("feed:$id",'name',$fields->name);
        if ($this->redis && isset($fields->tag)) $this->redis->hset("feed:$id",'tag',$fields->tag);
        if ($this->redis && isset($fields->public)) $this->redis->hset("feed:$id",'public',$fields->public);

        if ($this->mysqli->affected_rows>0){
            return array('success'=>true, 'message'=>'Field updated');
        } else {
            return array('success'=>false, 'message'=>'Field could not be updated');
        }
    }

    public function set_timevalue($id, $value, $time)
    {
        if ($value === null) $value = 'NULL'; // Null is a valid value
        if ($this->redis) {
            $this->redis->hMset("feed:$id", array('value' => $value, 'time' => $time));
        } else {
            $this->mysqli->query("UPDATE feeds SET `time` = '$time', `value` = $value WHERE `id`= '$id'");
        }
    }

    public function insert_data($feedid,$updatetime,$feedtime,$value,$arg=null)
    {
        $this->log->info("insert_data() feedid=$feedid updatetime=$updatetime feedtime=$feedtime value=$value arg=$arg");
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

    public function update_data($feedid,$updatetime,$feedtime,$value)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $updatetime = intval($updatetime);
        if ($feedtime == null) $feedtime = $updatetime;
        $feedtime = intval($feedtime);
        $value = floatval($value);

        $engine = $this->get_engine($feedid);
        if ($this->settings['redisbuffer']['enabled']) {
            // Call to buffer update
            $args = array('engine'=>$engine,'updatetime'=>$updatetime);
            $this->EngineClass(Engine::REDISBUFFER)->update($feedid,$feedtime,$value,$args);
        } else {
            // Call to engine update
            $this->EngineClass($engine)->update($feedid,$feedtime,$value);
        }

        $this->set_timevalue($feedid, $value, $updatetime);

        return $value;
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


    // Histogram specific functions that we need to make available to the controller
    public function histogram_get_power_vs_kwh($feedid,$start,$end) {
        return $this->EngineClass("histogram")->get_power_vs_kwh($feedid,$start,$end);
    }

    public function histogram_get_kwhd_atpower($feedid, $min, $max) {
        return $this->EngineClass("histogram")->get_kwhd_atpower($feedid, $min, $max);
    }

    public function histogram_get_kwhd_atpowers($feedid, $points) {
        return $this->EngineClass("histogram")->get_kwhd_atpowers($feedid, $points);
    }


    // PHPTimeSeries specific functions that we need to make available to the controller
    public function phptimeseries_export($feedid,$start) {
        return $this->EngineClass(Engine::PHPTIMESERIES)->export($feedid,$start);
    }

    public function phpfiwa_export($feedid,$start,$layer) {
        return $this->EngineClass(Engine::PHPFIWA)->export($feedid,$start,$layer);
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

    // USES: redis feed
    public function set_processlist($id, $processlist)
    {
        $this->mysqli->query("UPDATE feeds SET processList = '$processlist' WHERE id='$id'");
        if ($this->mysqli->affected_rows>0){
            // CHECK REDIS
            if ($this->redis) $this->redis->hset("feed:$id",'processList',$processlist);
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
        $result = $this->mysqli->query("SELECT id,userid,name,datatype,tag,public,size,engine,processList FROM feeds WHERE `userid` = '$userid'");
        while ($row = $result->fetch_object())
        {
            $this->redis->sAdd("user:feeds:$userid", $row->id);
            $this->redis->hMSet("feed:$row->id",array(
            'id'=>$row->id,
            'userid'=>$row->userid,
            'name'=>$row->name,
            'datatype'=>$row->datatype,
            'tag'=>$row->tag,
            'public'=>$row->public,
            'size'=>$row->size,
            'engine'=>$row->engine,
            'processList'=>$row->processList
            ));
        }
    }

    private function load_feed_to_redis($id)
    {
        $result = $this->mysqli->query("SELECT id,userid,name,datatype,tag,public,size,engine,processList FROM feeds WHERE `id` = '$id'");
        $row = $result->fetch_object();
        if (!$row) {
            $this->log->warn("Feed model: Requested feed does not exist feedid=$id");
            return false;
        }
        $this->redis->hMSet("feed:$row->id",array(
            'id'=>$row->id,
            'userid'=>$row->userid,
            'name'=>$row->name,
            'datatype'=>$row->datatype,
            'tag'=>$row->tag,
            'public'=>$row->public,
            'size'=>$row->size,
            'engine'=>$row->engine,
            'processList'=>$row->processList
        ));
        return true;
    }


    /* Other helpers */
    private function get_engine($feedid)
    {
        static $feed_engine_cache = array(); // Array to hold the cache
        if (isset($feed_engine_cache[$feedid])) {
            $engine = $feed_engine_cache[$feedid]; // Retrieve from static cache
        } else {
            if ($this->redis) {
                $engine = $this->redis->hget("feed:$feedid",'engine');
            } else {
                $result = $this->mysqli->query("SELECT engine FROM feeds WHERE `id` = '$feedid'");
                $row = $result->fetch_object();
                $engine = $row->engine;
            }
            $feed_engine_cache[$feedid] = $engine; // Cache it
        }
        return $engine;
    }
}

