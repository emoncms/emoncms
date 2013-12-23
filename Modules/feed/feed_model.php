<?php

/*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

   ---------------------------------------------------------------------
   Emoncms - open source energy visualisation
   Part of the OpenEnergyMonitor project:
   http://openenergymonitor.org

   test commit
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Feed
{
    private $mysqli;
    private $timestore;
    private $histogram;
    private $redis;
    private $graphitetimeseries;
    
    private $default_engine = Engine::PHPTIMESERIES;
    
    public function __construct($mysqli,$redis,$timestore_adminkey)
    {
        // Not the best way to bring the variable in but a quick fix for now
        // while the feature is tested.
        global $default_engine;
        if (isset($default_engine)) $this->default_engine = $default_engine;
        
        $this->mysqli = $mysqli;
        
        // Load different storage engines

        require "Modules/feed/engine/MysqlTimeSeries.php";
        $this->mysqltimeseries = new MysqlTimeSeries($mysqli);
        
        require "Modules/feed/engine/Timestore.php";
        $this->timestore = new Timestore($timestore_adminkey);
       
        require "Modules/feed/engine/Histogram.php";
        $this->histogram = new Histogram($mysqli);
        
        require "Modules/feed/engine/PHPTimeSeries.php";
        $this->phptimeseries = new PHPTimeSeries();
        
        $this->redis = $redis;
        
        global $graphite_host;
        global $graphite_port;
        
        require "Modules/feed/engine/GraphiteTimeSeries.php";
        $this->graphitetimeseries = new GraphiteTimeSeries($graphite_host, $graphite_port);
    }
    
    public function set_update_value_redis($feedid, $value, $time)
    {
      $updatetime = date("Y-n-j H:i:s", $time); 
      
      $this->redis->hMset("feed:lastvalue:$feedid", array('value' => $value, 'time' => $updatetime));
    }
   
    
    public function create($userid,$name,$datatype,$newfeedinterval)
    {
        $newfeedinterval = (int) $newfeedinterval;
        $userid = intval($userid);
        $name = preg_replace('/[^\w\s-]/','',$name);
        $datatype = intval($datatype);

        // If feed of given name by the user already exists
        $feedid = $this->get_id($userid,$name);
        if ($feedid!=0) return array('success'=>false, 'message'=>'feed already exists');
        
        if ($datatype == 1) $engine = $this->default_engine; else $engine = Engine::MYSQL;

        $result = $this->mysqli->query("INSERT INTO feeds (userid,name,datatype,public,engine) VALUES ('$userid','$name','$datatype',false,'$engine')");
        $feedid = $this->mysqli->insert_id;

        if ($feedid>0) 
        {
          // Add the feed to redis
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
	              
          $feedname = "feed_".$feedid;

          if ($datatype==1) {
            if ($this->default_engine == Engine::TIMESTORE) $this->timestore->create($feedid,$newfeedinterval);
            if ($this->default_engine == Engine::MYSQL) $this->mysqltimeseries->create($feedid);
            if ($this->default_engine == Engine::PHPTIMESERIES) $this->phptimeseries->create($feedid);
            if ($this->default_engine == Engine::GRAPHITE) $this->graphitetimeseries->create($feedid);
          }
          elseif ($datatype==2) $this->mysqltimeseries->create($feedid);
          elseif ($datatype==3) $this->histogram->create($feedid);
        
          
          return array('success'=>true, 'feedid'=>$feedid);										
        } else return array('success'=>false);
  }

  public function exists($feedid)
  {
    $feedid = intval($feedid);
    $result = $this->mysqli->query("SELECT id FROM feeds WHERE id = '$feedid'");
    if ($result->num_rows>0) return true; else return false;
  }
  
  public function exist($id)
  {
    $feedexist = false;
    if (!$this->redis->exists("feed:$id")) {
      if ($this->load_feed_to_redis($id))
      {
        $feedexist = true;
      }
    } else {
      $feedexist = true;
    }
    return $feedexist;
  }

  public function get_id($userid,$name)
  {
    $userid = intval($userid);
    $name = preg_replace('/[^\w\s-]/','',$name);
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

  public function get_user_public_feeds($userid)
  {
    $userid = (int) $userid;
    if (!$this->redis->exists("user:feeds:$userid")) $this->load_to_redis($userid);

    $feeds = array();
    $feedids = $this->redis->sMembers("user:feeds:$userid");
    foreach ($feedids as $id)
    {
      $row = $this->redis->hGetAll("feed:$id");
      
      if ($row['public']) {
        $lastvalue = $this->redis->hmget("feed:lastvalue:$id",array('time','value'));
        $row['time'] = strtotime($lastvalue['time']);
        $row['value'] = $lastvalue['value'];
        $feeds[] = $row;
      }
    }
    return $feeds;
  }

  public function get_user_feed_ids($userid)
  {
    $userid = (int) $userid;
    if (!$this->redis->exists("user:feeds:$userid")) $this->load_to_redis($userid);
    return $this->redis->sMembers("user:feeds:$userid");
  }

  /*

  Feeds table GET public functions

  */

  public function get($id)
  {
    $id = (int) $id;
    if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');

    $row = $this->redis->hGetAll("feed:$id");
  
    $lastvalue = $this->redis->hmget("feed:lastvalue:$id",array('time','value'));
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
      $val = $this->redis->hget("feed:$id",$field);      
      if ($val) return $val; else return 0;
    }
    else return array('success'=>false, 'message'=>'Missing field parameter');
  }

  public function get_timevalue($id)
  {
    $id = (int) $id;
    
    // Get the timevalue from redis if it exists
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
    
    return $lastvalue;
  }

  public function get_value($id)
  {
    $id = (int) $id;
    
    // Get the timevalue from redis if it exists
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
    
    return $lastvalue['value'];
  }
  
  public function get_timevalue_from_data($feedid)
  {
    $feedid = (int) $feedid;
    if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
    
    $engine = $this->redis->hget("feed:$feedid",'engine');
    if ($engine==Engine::TIMESTORE) return $this->timestore->lastvalue($feedid);
    if ($engine==Engine::MYSQL) return $this->mysqltimeseries->lastvalue($feedid);
    if ($engine==Engine::PHPTIMESERIES) return $this->phptimeseries->lastvalue($feedid);  
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
    if (isset($fields->name)) $array[] = "`name` = '".preg_replace('/[^\w\s-]/','',$fields->name)."'";
    if (isset($fields->tag)) $array[] = "`tag` = '".preg_replace('/[^\w\s-]/','',$fields->tag)."'";
    if (isset($fields->public)) $array[] = "`public` = '".intval($fields->public)."'";

    // Convert to a comma seperated string for the mysql query
    $fieldstr = implode(",",$array);
    $this->mysqli->query("UPDATE feeds SET ".$fieldstr." WHERE `id` = '$id'");
    
    // Update redis
    if (isset($fields->name)) $this->redis->hset("feed:$id",'name',$fields->name);
    if (isset($fields->tag)) $this->redis->hset("feed:$id",'tag',$fields->tag);
    if (isset($fields->public)) $this->redis->hset("feed:$id",'public',$fields->public);

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

    $engine = $this->redis->hget("feed:$feedid",'engine');

    if ($engine==Engine::TIMESTORE) $this->timestore->post($feedid,$feedtime,$value);
    if ($engine==Engine::MYSQL) $this->mysqltimeseries->insert($feedid,$feedtime,$value);
    if ($engine==Engine::PHPTIMESERIES) $this->phptimeseries->post($feedid,$feedtime,$value);
    if ($engine==Engine::GRAPHITE) $this->graphitetimeseries->post($feedid,$feedtime,$value);

    $this->set_update_value_redis($feedid, $value, $updatetime);

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
    
    $engine = $this->redis->hget("feed:$feedid",'engine');

    if ($engine==Engine::TIMESTORE) $this->timestore->post($feedid,$feedtime,$value);
    if ($engine==Engine::MYSQL) $value = $this->mysqltimeseries->update($feedid,$feedtime,$value);
    if ($engine==Engine::PHPTIMESERIES) $this->phptimeseries->post($feedid,$feedtime,$value);
    if ($engine==Engine::GRAPHITE) $this->graphitetimeseries->post($feedid,$feedtime,$value);

    // need to find a way to not update if value being updated is older than the last value 
    // in the database, redis lastvalue is last update time rather than last datapoint time. 
    // So maybe we need to store both in redis.
    
    $this->set_update_value_redis($feedid, $value, $updatetime);

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
    if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
    
    $engine = $this->redis->hget("feed:$feedid",'engine');
    if ($engine==Engine::TIMESTORE) return $this->timestore->get_data($feedid,$start,$end);
    if ($engine==Engine::MYSQL) return $this->mysqltimeseries->get_data($feedid,$start,$end,$dp);
    if ($engine==Engine::PHPTIMESERIES) return $this->phptimeseries->get_data($feedid,$start,$end,$dp);
    if ($engine==Engine::GRAPHITE) return $this->graphitetimeseries->get_data($feedid,$start,$end,$dp);
  }
  
  public function get_timestore_average($feedid,$start,$end,$interval)
  {
    $feedid = (int) $feedid;
    if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
    
    $engine = $this->redis->hget("feed:$feedid",'engine');
    if ($engine==Engine::TIMESTORE) return $this->timestore->get_average($feedid,$start,$end,$interval);
    if ($engine==Engine::GRAPHITE) return $this->graphitetimeseries->get_average($feedid,$start,$end,$interval);
  }

  
  public function delete($feedid)
  {
    $feedid = (int) $feedid;
    if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
    
    $engine = $this->redis->hget("feed:$feedid",'engine');
    if ($engine==Engine::TIMESTORE) $this->timestore->delete($feedid);
    if ($engine==Engine::MYSQL) $this->mysqltimeseries->delete($feedid);
    if ($engine==Engine::PHPTIMESERIES) $this->phptimeseries->delete($feedid);
    if ($engine==Engine::GRAPHITE) $this->graphitetimeseries->delete($feedid);
        
    $this->mysqli->query("DELETE FROM feeds WHERE `id` = '$feedid'");
    
    $userid = $this->redis->hget("feed:$feedid",'userid');
    $this->redis->del("feed:$feedid");
    $this->redis->srem("user:feeds:$userid",$feedid);
  }
  
  public function update_user_feeds_size($userid)
  {
    $total = 0;
    $result = $this->mysqli->query("SELECT id,engine FROM feeds WHERE `userid` = '$userid'");
    while ($row = $result->fetch_array())
    {
      $size = 0;
      $feedid = $row['id'];
      if ($row['engine']==Engine::MYSQL) $size = $this->mysqltimeseries->get_feed_size($feedid);
      if ($row['engine']==Engine::TIMESTORE) $size = $this->timestore->get_feed_size($feedid);
      if ($row['engine']==Engine::PHPTIMESERIES) $size = $this->phptimeseries->get_feed_size($feedid);
      if ($row['engine']==Engine::GRAPHITE) $size = $this->graphitetimeseries->get_feed_size($feedid);
      $this->mysqli->query("UPDATE feeds SET `size` = '$size' WHERE `id`= '$feedid'");
      $this->redis->hset("feed:$feedid",'size',$size);
      $total += $size;
    }
    return $total;
  }
  
  // MysqlTimeSeries specific functions that we need to make available to the controller
  
  public function mysqltimeseries_export($feedid,$start) {
    return $this->mysqltimeseries->export($feedid,$start);
  }
  
  public function mysqltimeseries_delete_data_point($feedid,$time) {
    return $this->mysqltimeseries->delete_data_point($feedid,$time);
  }
  
  public function mysqltimeseries_delete_data_range($feedid,$start,$end) {
    return $this->mysqltimeseries->delete_data_range($feedid,$start,$end);
  }
  
  // Timestore specific functions that we need to make available to the controller
  
  public function timestore_export($feedid,$start,$layer) {
    return $this->timestore->export($feedid,$start,$layer);
  }
  
  public function timestore_export_meta($feedid) {
    return $this->timestore->export_meta($feedid);
  }

  public function timestore_get_meta($feedid) {
    return $this->timestore->get_meta($feedid);
  }
  
  public function timestore_scale_range($feedid,$start,$end,$value) {
    return $this->timestore->scale_range($feedid,$start,$end,$value);
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
    return $this->phptimeseries->export($feedid,$start);
  }
  
  private function load_to_redis($userid)
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
    
    if (!$row) return false;
    
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

