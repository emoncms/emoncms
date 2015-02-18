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
    private $mysqli;
    private $redis;
    public $engine;
    private $histogram;
    private $csvdownloadlimit_mb = 10;
    private $log;
    
    // 5 years of daily data
    private $max_npoints_returned = 1825;

    public function __construct($mysqli,$redis,$settings)
    {        
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        $this->log = new EmonLogger(__FILE__);
        
        // Load different storage engines
        require "Modules/feed/engine/MysqlTimeSeries.php";
        require "Modules/feed/engine/Timestore.php";
        require "Modules/feed/engine/PHPTimestore.php";
        require "Modules/feed/engine/Histogram.php";
        require "Modules/feed/engine/PHPTimeSeries.php";
        require "Modules/feed/engine/GraphiteTimeSeries.php";
        
        // Development engines
        require "Modules/feed/engine/PHPFina.php";
        require "Modules/feed/engine/PHPFiwa.php";     
           
        // Backwards compatibility 
        if (!isset($settings)) $settings= array();
        if (!isset($settings['timestore'])) {
            global $timestore_adminkey; 
            $settings['timestore'] = array('adminkey'=>$timestore_adminkey);
        }
        if (!isset($settings['graphite'])) $settings['graphite'] = array('host'=>"", 'port'=>0);
        if (!isset($settings['phpfiwa'])) $settings['phpfiwa'] = array();
        if (!isset($settings['phpfina'])) $settings['phpfina'] = array();
        if (!isset($settings['phptimestore'])) $settings['phptimestore'] = array();
        if (!isset($settings['phptimeseries'])) $settings['phptimeseries'] = array();
              
        // Load engine instances to engine array to make selection below easier
        $this->engine = array();
        $this->engine[Engine::MYSQL] = new MysqlTimeSeries($mysqli);
        $this->engine[Engine::TIMESTORE] = new Timestore($settings['timestore']);
        $this->engine[Engine::PHPTIMESTORE] = new PHPTimestore($settings['phptimestore']);
        $this->engine[Engine::PHPTIMESERIES] = new PHPTimeSeries($settings['phptimeseries']);
        $this->engine[Engine::GRAPHITE] = new GraphiteTimeSeries($settings['graphite']);
        $this->engine[Engine::PHPFINA] = new PHPFina($settings['phpfina']);
        $this->engine[Engine::PHPFIWA] = new PHPFiwa($settings['phpfiwa']);
                
        $this->histogram = new Histogram($mysqli);
        
        if (isset($settings['csvdownloadlimit_mb'])) {
            $this->csvdownloadlimit_mb = $settings['csvdownloadlimit_mb']; 
        }
        
        if (isset($settings['max_npoints_returned'])) {
            $this->max_npoints_returned = $settings['max_npoints_returned'];
        }
    }

    public function create($userid,$name,$datatype,$engine,$options_in)
    {
        $userid = (int) $userid;
        $name = preg_replace('/[^\w\s-:]/','',$name);
        $datatype = (int) $datatype;
        $engine = (int) $engine;
        
        // Histogram engine requires MYSQL
        if ($datatype==DataType::HISTOGRAM && $engine!=Engine::MYSQL) $engine = Engine::MYSQL;
        
        // If feed of given name by the user already exists
        $feedid = $this->get_id($userid,$name);
        if ($feedid!=0) return array('success'=>false, 'message'=>'feed already exists');

        $result = $this->mysqli->query("INSERT INTO feeds (userid,name,datatype,public,engine) VALUES ('$userid','$name','$datatype',false,'$engine')");
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
                    'tag'=>'',
                    'public'=>false,
                    'size'=>0,
                    'engine'=>$engine
                ));
            }
            
            $options = array();
            if ($engine==Engine::TIMESTORE) $options['interval'] = (int) $options_in->interval;
            if ($engine==Engine::PHPTIMESTORE) $options['interval'] = (int) $options_in->interval;
            if ($engine==Engine::PHPFINA) $options['interval'] = (int) $options_in->interval;
            if ($engine==Engine::PHPFIWA) $options['interval'] = (int) $options_in->interval;
            
            $engineresult = false;
            if ($datatype==DataType::HISTOGRAM) {
                $engineresult = $this->histogram->create($feedid,$options);
            } else {
                $engineresult = $this->engine[$engine]->create($feedid,$options);
            }

            if ($engineresult == false)
            {
                $this->log->warn("Feed model: failed to create feed model feedid=$feedid");
                // Feed engine creation failed so we need to delete the meta entry for the feed
                
                $this->mysqli->query("DELETE FROM feeds WHERE `id` = '$feedid'");

                if ($this->redis) {
                    $userid = $this->redis->hget("feed:$feedid",'userid');
                    $this->redis->del("feed:$feedid");
                    $this->redis->srem("user:feeds:$userid",$feedid);
                }

                return array('success'=>false, 'message'=>"");
            }

            return array('success'=>true, 'feedid'=>$feedid, 'result'=>$engineresult);
        } else return array('success'=>false);
    }

    public function exist($id)
    {
        $feedexist = false;
        if ($this->redis)
        {
            
            if (!$this->redis->exists("feed:$id")) {
                if ($this->load_feed_to_redis($id))
                {
                    $feedexist = true;
                }
            } else {
                $feedexist = true;
            }
        }
        else 
        {
            $id = intval($id);
            $result = $this->mysqli->query("SELECT id FROM feeds WHERE id = '$id'");
            if ($result->num_rows>0) $feedexist = true;
        }
        return $feedexist;
    }

    public function get_id($userid,$name)
    {
        $userid = intval($userid);
        $name = preg_replace('/[^\w\s-:]/','',$name);
        $result = $this->mysqli->query("SELECT id FROM feeds WHERE userid = '$userid' AND name = '$name'");
        if ($result->num_rows>0) { $row = $result->fetch_array(); return $row['id']; } else return false;
    }
    /*

    User Feed lists

    Returns a specified user's feedlist in different forms:
    get_user_feeds: 	all the feeds table data
    get_user_feed_ids: 	only the id's
    get_user_feed_names: 	id's and names

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
    
    public function redis_get_user_feeds($userid)
    {
        $userid = (int) $userid;
        if (!$this->redis->exists("user:feeds:$userid")) $this->load_to_redis($userid);
      
        $feeds = array();
        $feedids = $this->redis->sMembers("user:feeds:$userid");
        foreach ($feedids as $id)
        {
            $row = $this->redis->hGetAll("feed:$id");

            $lastvalue = $this->get_timevalue($id);
            $row['time'] = strtotime($lastvalue['time']);
            $row['value'] = $lastvalue['value'];
            $feeds[] = $row;
        }
        
        return $feeds;
    }
    
    public function mysql_get_user_feeds($userid)
    {
        $userid = (int) $userid;
        $feeds = array();
        $result = $this->mysqli->query("SELECT * FROM feeds WHERE `userid` = '$userid'");
        while ($row = (array)$result->fetch_object())
        {
            $row['time'] = strtotime($row['time']);
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

    Feeds table GET public functions

    */

    public function get($id)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');

        if ($this->redis) {
            // Get from redis cache
            $row = $this->redis->hGetAll("feed:$id");
            $lastvalue = $this->redis->hmget("feed:lastvalue:$id",array('time','value'));
            $row['time'] = $lastvalue['time'];
            $row['value'] = $lastvalue['value'];
        } else {
            // Get from mysql db
            $result = $this->mysqli->query("SELECT * FROM feeds WHERE `id` = '$id'");
            $row = (array) $result->fetch_object();
            $row['time'] = strtotime($row['time']);
        }

        return $row;
    }

    public function get_field($id,$field)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');

        if ($field!=NULL) // if the feed exists
        {
            $field = preg_replace('/[^\w\s-]/','',$field);
            
            if ($this->redis) {
                $val = $this->redis->hget("feed:$id",$field);
            } else {
                $result = $this->mysqli->query("SELECT `$field` FROM feeds WHERE `id` = '$id'");
                $row = $result->fetch_array();
                $val = $row[0];
            }
            
            if ($val) return $val; else return 0;
        }
        else return array('success'=>false, 'message'=>'Missing field parameter');
    }

    public function get_timevalue($id)
    {
        $id = (int) $id;

        // Get the timevalue from redis if it exists
        if ($this->redis) 
        {
            if ($this->redis->exists("feed:lastvalue:$id"))
            {
                $lastvalue = $this->redis->hmget("feed:lastvalue:$id",array('time','value'));
            }
            else
            {
                // if it does not load it in to redis from the actual feed data.
                $lastvalue = $this->get_timevalue_from_data($id);
                $this->redis->hMset("feed:lastvalue:$id", array('value' => $lastvalue['value'], 'time' => $lastvalue['time']));
            }
        }
        else 
        {
            $result = $this->mysqli->query("SELECT time,value FROM feeds WHERE `id` = '$id'");
            $row = $result->fetch_array();
            $lastvalue = array('time'=>$row['time'], 'value'=>$row['value']);
        }

        return $lastvalue;
    }

    public function get_timevalue_seconds($id)
    {
        $lastvalue = $this->get_timevalue($id);
        $lastvalue['time'] = strtotime($lastvalue['time']);
        return $lastvalue;
    }

    public function get_value($id)
    {
        $lastvalue = $this->get_timevalue($id);
        return $lastvalue['value'];
    }

    public function get_timevalue_from_data($feedid)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $engine = $this->get_engine($feedid);
        
        // Call to engine lastvalue method
        return $this->engine[$engine]->lastvalue($feedid);
    }

    /*

    Feeds table SET public functions

    */

    public function set_feed_fields($id,$fields)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');

        $fields = json_decode(stripslashes($fields));

        $array = array();

        // Repeat this line changing the field name to add fields that can be updated:
        if (isset($fields->name)) $array[] = "`name` = '".preg_replace('/[^\w\s-:]/','',$fields->name)."'";
        if (isset($fields->tag)) $array[] = "`tag` = '".preg_replace('/[^\w\s-:]/','',$fields->tag)."'";
        if (isset($fields->public)) $array[] = "`public` = '".intval($fields->public)."'";

        // Convert to a comma seperated string for the mysql query
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

    /*

    Feed data public functions

    insert, update, get and specialist histogram public functions

    */

    public function insert_data($feedid,$updatetime,$feedtime,$value)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        if ($feedtime == null) $feedtime = time();
        $updatetime = intval($updatetime);
        $feedtime = intval($feedtime);
        $value = floatval($value);

        $engine = $this->get_engine($feedid);
        
        // Call to engine post method
        $this->engine[$engine]->post($feedid,$feedtime,$value);

        $this->set_timevalue($feedid, $value, $updatetime);

        //Check feed event if event module is installed
        if (is_dir(realpath(dirname(__FILE__)).'/../event/')) {
            require_once(realpath(dirname(__FILE__)).'/../event/event_model.php');
            $event = new Event($this->mysqli,$this->redis);
            $event->check_feed_event($feedid,$updatetime,$feedtime,$value);
        }

        return $value;
    }

    public function update_data($feedid,$updatetime,$feedtime,$value)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        if ($feedtime == null) $feedtime = time();
        $updatetime = intval($updatetime);
        $feedtime = intval($feedtime);
        $value = floatval($value);

        $engine = $this->get_engine($feedid);
        
        // Call to engine update method
        $value = $this->engine[$engine]->update($feedid,$feedtime,$value);
       
        // need to find a way to not update if value being updated is older than the last value
        // in the database, redis lastvalue is last update time rather than last datapoint time.
        // So maybe we need to store both in redis.

        $this->set_timevalue($feedid, $value, $updatetime);

        //Check feed event if event module is installed
        if (is_dir(realpath(dirname(__FILE__)).'/../event/')) {
            require_once(realpath(dirname(__FILE__)).'/../event/event_model.php');
            $event = new Event($this->mysqli,$this->redis);
            $event->check_feed_event($feedid,$updatetime,$feedtime,$value);
        }

        return $value;
    }

    public function get_data($feedid,$start,$end,$dp)
    {
        $feedid = (int) $feedid;
        if ($end == 0) $end = time()*1000;
                
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
  
        $engine = $this->get_engine($feedid);
        
        // Call to engine get_data method
        $range = ($end - $start) * 0.001;
        if ($dp>$this->max_npoints_returned) $dp = $this->max_npoints_returned;
        if ($dp<1) $dp = 1;
        $outinterval = round($range / $dp);
        return $this->engine[$engine]->get_data($feedid,$start,$end,$outinterval);

    }

    public function get_average($feedid,$start,$end,$outinterval)
    {
        $feedid = (int) $feedid;
        if ($end == 0) $end = time()*1000;
        
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $engine = $this->get_engine($feedid);

        // Call to engine get_average method
        if ($outinterval<1) $outinterval = 1;
        $range = ($end - $start) * 0.001;
        $npoints = ($range / $outinterval);
        if ($npoints>$this->max_npoints_returned) $outinterval = round($range / $this->max_npoints_returned);
        return $this->engine[$engine]->get_data($feedid,$start,$end,$outinterval);
    }
    
    public function get_history($feedid,$start,$end,$outinterval)
    {
        $this->redis->incr("fiveseconds:getdatahits");
        $feedid = (int) $feedid;
        if ($end == 0) $end = time()*1000;
        
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $engine = $this->get_engine($feedid);
        
        if ($engine==Engine::PHPFINA || $engine==Engine::PHPFIWA) {
            // Call to engine get_average method
            if ($outinterval<1) $outinterval = 1;
            $range = ($end - $start) * 0.001;
            $npoints = ($range / $outinterval);
            if ($npoints>$this->max_npoints_returned) $outinterval = round($range / $this->max_npoints_returned);
            return $this->engine[$engine]->get_data_exact($feedid,$start,$end,$outinterval);
        }
        return false;
    }
    
    public function csv_export($feedid,$start,$end,$outinterval)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $engine = $this->get_engine($feedid);
        
        // Download limit
        $downloadsize = (($end - $start) / $outinterval) * 17; // 17 bytes per dp
        if ($downloadsize>($this->csvdownloadlimit_mb*1048576)) {
            $this->log->warn("Feed model: csv download limit exeeded downloadsize=$downloadsize feedid=$feedid");
            return false;
        }

        // Call to engine get_average method
        return $this->engine[$engine]->csv_export($feedid,$start,$end,$outinterval);
    }


    public function delete($feedid)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $engine = $this->get_engine($feedid);
        
        // Call to engine delete method
        $this->engine[$engine]->delete($feedid);

        $this->mysqli->query("DELETE FROM feeds WHERE `id` = '$feedid'");

        if ($this->redis) {
            $userid = $this->redis->hget("feed:$feedid",'userid');
            $this->redis->del("feed:$feedid");
            $this->redis->srem("user:feeds:$userid",$feedid);
        }
    }

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
            $size = $this->engine[$engine]->get_feed_size($feedid);
            
            $this->mysqli->query("UPDATE feeds SET `size` = '$size' WHERE `id`= '$feedid'");
            if ($this->redis) $this->redis->hset("feed:$feedid",'size',$size);
            $total += $size;
        }
        return $total;
    }

    public function get_meta($feedid) {
        $feedid = (int) $feedid;
        $engine = $this->get_engine($feedid);
        return $this->engine[$engine]->get_meta($feedid);
    }
    
    // MysqlTimeSeries specific functions that we need to make available to the controller

    public function mysqltimeseries_export($feedid,$start) {
        return $this->engine[Engine::MYSQL]->export($feedid,$start);
    }

    public function mysqltimeseries_delete_data_point($feedid,$time) {
        return $this->engine[Engine::MYSQL]->delete_data_point($feedid,$time);
    }

    public function mysqltimeseries_delete_data_range($feedid,$start,$end) {
        return $this->engine[Engine::MYSQL]->delete_data_range($feedid,$start,$end);
    }

    // Timestore specific functions that we need to make available to the controller

    public function timestore_export($feedid,$start,$layer) {
        return $this->engine[Engine::TIMESTORE]->export($feedid,$start,$layer);
    }

    public function timestore_export_meta($feedid) {
        return $this->engine[Engine::TIMESTORE]->export_meta($feedid);
    }

    public function timestore_scale_range($feedid,$start,$end,$value) {
        return $this->engine[Engine::TIMESTORE]->scale_range($feedid,$start,$end,$value);
    }

    // Histogram specific functions that we need to make available to the controller

    public function histogram_get_power_vs_kwh($feedid,$start,$end) {
        return $this->histogram->get_power_vs_kwh($feedid,$start,$end);
    }

    public function histogram_get_kwhd_atpower($feedid, $min, $max) {
        return $this->histogram->get_kwhd_atpower($feedid, $min, $max);
    }

    public function histogram_get_kwhd_atpowers($feedid, $points) {
        return $this->histogram->get_kwhd_atpowers($feedid, $points);
    }

    // PHPTimeSeries specific functions that we need to make available to the controller

    public function phptimeseries_export($feedid,$start) {
        return $this->engine[Engine::PHPTIMESERIES]->export($feedid,$start);
    }
    
    public function phpfiwa_export($feedid,$start,$layer) {
        return $this->engine[Engine::PHPFIWA]->export($feedid,$start,$layer);
    }
    
    public function phpfina_export($feedid,$start) {
        return $this->engine[Engine::PHPFINA]->export($feedid,$start);
    }



    public function set_timevalue($feedid, $value, $time)
    {
        $updatetime = date("Y-n-j H:i:s", $time);
        if ($this->redis) {
            $this->redis->hMset("feed:lastvalue:$feedid", array('value' => $value, 'time' => $updatetime));
        } else {
			// Explicitly set decimal separator, in case current locale doesn't use a dot (MySQL expects always a dot, regardless of locale)
            $value2=number_format($value,10,'.','');
            $this->mysqli->query("UPDATE feeds SET `time` = '$updatetime', `value` = '$value2' WHERE `id`= '$feedid'");
        }
    }
    
    private function get_engine($feedid)
    {
        if ($this->redis) {
            return $this->redis->hget("feed:$feedid",'engine');
        } else {
            $result = $this->mysqli->query("SELECT engine FROM feeds WHERE `id` = '$feedid'");
            $row = $result->fetch_object();
            return $row->engine;
        }
    }

    public function load_to_redis($userid)
    {
        $result = $this->mysqli->query("SELECT id,userid,name,datatype,tag,public,size,engine FROM feeds WHERE `userid` = '$userid'");
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
            'engine'=>$row->engine
            ));
        }
    }

    public function load_feed_to_redis($id)
    {
        $result = $this->mysqli->query("SELECT id,userid,name,datatype,tag,public,size,engine FROM feeds WHERE `id` = '$id'");
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
            'engine'=>$row->engine
        ));

        return true;
    }
}

