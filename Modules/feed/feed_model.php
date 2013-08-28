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
    
    public function __construct($mysqli,$timestore_adminkey)
    {
        $this->mysqli = $mysqli;
        
        // Load different storage engines

        require "Modules/feed/engine/MysqlTimeSeries.php";
        $this->mysqltimeseries = new MysqlTimeSeries($mysqli);
        
        require "Modules/feed/engine/Timestore.php";
        $this->timestore = new Timestore($timestore_adminkey);

        require "Modules/feed/engine/Histogram.php";
        $this->histogram = new Histogram($mysqli);
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
        
        if ($datatype == 1) $engine = Engine::TIMESTORE; else $engine = Engine::MYSQL;

        $result = $this->mysqli->query("INSERT INTO feeds (userid,name,datatype,public,engine) VALUES ('$userid','$name','$datatype','false','$engine')");
        $feedid = $this->mysqli->insert_id;

        if ($feedid>0) 
        {
          $feedname = "feed_".$feedid;

          if ($datatype==1)     $this->timestore->create($feedid,$newfeedinterval);
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

  public function get_id($userid,$name)
  {
    $userid = intval($userid);
    $name = preg_replace('/[^\w\s-]/','',$name);
    $result = $this->mysqli->query("SELECT id FROM feeds WHERE userid = '$userid' AND name = '$name'");
    if ($result->num_rows>0) { $row = $result->fetch_array(); return $row['id']; } else return false;
  }

  public function belongs_to_user($feedid,$userid)
  {
    $userid = intval($userid);
    $feedid = intval($feedid);

    $result = $this->mysqli->query("SELECT id FROM feeds WHERE `userid` = '$userid' AND `id` = '$feedid'");
    $row = $result->fetch_array();
    if ($row) return 1;
    return 0;
  }

  public function belongs_to_user_or_public($feedid,$userid)
  {
    $userid = intval($userid);
    $feedid = intval($feedid);

    $result = $this->mysqli->query("SELECT userid, public FROM feeds WHERE `id` = '$feedid'");
    $row = $result->fetch_array();

    if ($row['public']==true) return 1;
    if ($row['userid']==$userid && $userid!=0) return 1;

    return 0;
  }

  public function feedtype_belongs_user_or_public($feedid,$userid,$datatype)
  {
    $userid = intval($userid);
    $feedid = intval($feedid);
    $datatype = intval($datatype);

    $result = $this->mysqli->query("SELECT userid, public FROM feeds WHERE `id` = '$feedid' AND `datatype` = '$datatype'");
    $row = $result->fetch_array();

    if ($row['public']==true) return 1;
    if ($row['userid']==$userid && $userid!=0) return 1;

    return 0;
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
    $userid = intval($userid);

    $result = $this->mysqli->query("SELECT id,name,datatype,tag,time,value,public,size,engine FROM feeds WHERE userid = $userid");
    if (!$result) return 0;
    $feeds = array();
    while ($row = $result->fetch_object()) 
    { 
      // $row->size = get_feedtable_size($row->id);
      $row->time = strtotime($row->time)*1000;
      $row->tag = str_replace(" ","_",$row->tag);

      if ($row->size<1024*100) {
        $row->size = number_format($row->size/1024,1)."kb";
      } elseif ($row->size<1024*1024) {
        $row->size = round($row->size/1024)."kb";
      } elseif ($row->size>=1024*1024) {
        $row->size = round($row->size/(1024*1024))."Mb";
      }

      $row->public = (bool) $row->public;
      $feeds[] = $row; 
    }
    return $feeds;
  }

  public function get_user_public_feeds($userid)
  {
    $userid = intval($userid);

    $result = $this->mysqli->query("SELECT id,name,value FROM feeds WHERE `userid` = '$userid' AND public = '1'");
    if (!$result) return 0;
    $feeds = array();
    while ($row = $result->fetch_object()) 
    { 
      // $row->size = get_feedtable_size($row->id);
      // $row->time = strtotime($row->time)*1000;
      // $row->tag = str_replace(" ","_",$row->tag);
      $feeds[] = $row;
    }
    return $feeds;
  }

  public function get_user_feed_ids($userid)
  {
    $userid = intval($userid);

    $result = $this->mysqli->query("SELECT id FROM feeds WHERE userid = '$userid'");
    if (!$result) return 0;
    $feeds = array();
    while ($row = $result->fetch_object()) { $feeds[] = $row->id; }
    return $feeds;
  }

  public function get_user_feed_names($userid)
  {
    $userid = intval($userid);

    $result = $this->mysqli->query("SELECT id,name,datatype,public FROM feeds WHERE userid = '$userid'");
    if (!$result) return 0;
    $feeds = array();
    while ($row = $result->fetch_array()) { $feeds[] = array('id'=>$row['id'],'name'=>$row['name'],'datatype'=>$row['datatype'],'public'=>$row['public']); }
    return $feeds;
  }

  /*

  Feeds table GET public functions

  */

  public function get($id)
  {
    $id = intval($id);

    $result = $this->mysqli->query("SELECT id,name,datatype,tag,time,value,public FROM feeds WHERE id = $id");
    $row = $result->fetch_object();
    $row->public = (bool) $row->public;
    return $row;
  }

  public function get_field($id,$field)
  {
    $id = intval($id);

    if ($field!=NULL) {
      $field = preg_replace('/[^\w\s-]/','',$field);
      $result = $this->mysqli->query("SELECT `$field` FROM feeds WHERE `id` = '$id'");
      $row = $result->fetch_array();
      if ($row) return $row[0]; else return 0;
    }
    else return false;
  }

  public function get_timevalue($id)
  {
    $id = intval($id);

    $result = $this->mysqli->query("SELECT time,value FROM feeds WHERE id = '$id'");
    return $result->fetch_array();
  }

  /*

  Feeds table SET public functions

  */

  public function set_feed_fields($id,$fields)
  {
    $id = intval($id);
    $fields = json_decode(stripslashes($fields));

    $array = array();

    // Repeat this line changing the field name to add fields that can be updated:
    if (isset($fields->name)) $array[] = "`name` = '".preg_replace('/[^\w\s-]/','',$fields->name)."'";
    if (isset($fields->tag)) $array[] = "`tag` = '".preg_replace('/[^\w\s-]/','',$fields->tag)."'";
    if (isset($fields->datatype)) $array[] = "`datatype` = '".intval($fields->datatype)."'";

    if (isset($fields->public)) $array[] = "`public` = '".intval($fields->public)."'";
    if (isset($fields->time)) {
      $updatetime = date("Y-n-j H:i:s", intval($fields->time)); 
      $array[] = "`time` = '".$updatetime."'";
    }
    if (isset($fields->value)) $array[] = "`value` = '".intval($fields->value)."'";
    // Convert to a comma seperated string for the mysql query
    $fieldstr = implode(",",$array);
    $this->mysqli->query("UPDATE feeds SET ".$fieldstr." WHERE `id` = '$id'");

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
    if ($feedtime == null) $feedtime = time();
    $feedid = intval($feedid);
    $updatetime = intval($updatetime);
    $feedtime = intval($feedtime);
    $value = floatval($value);

    $qresult = $this->mysqli->query("SELECT engine FROM feeds WHERE `id` = '$feedid'");
    $row = $qresult->fetch_array();

    if ($row['engine']==Engine::TIMESTORE) $this->timestore->post($feedid,$feedtime,$value);
    if ($row['engine']==Engine::MYSQL) $this->mysqltimeseries->insert($feedid,$feedtime,$value);

    // b. Update feeds table
    $updatetime = date("Y-n-j H:i:s", $updatetime); 
    $this->mysqli->query("UPDATE feeds SET value = '$value', time = '$updatetime' WHERE id='$feedid'");

    //Check feed event if event module is installed
    if (is_dir(realpath(dirname(__FILE__)).'/../event/')) {
      require_once(realpath(dirname(__FILE__)).'/../event/event_model.php');
      $event = new Event($this->mysqli);
      $event->check_feed_event($feedid,$updatetime,$feedtime,$value);
    }

    return $value;
  }

  public function update_data($feedid,$updatetime,$feedtime,$value)
  {        
    if ($feedtime == null) $feedtime = time();
    $feedid = intval($feedid);
    $updatetime = intval($updatetime);
    $feedtime = intval($feedtime);
    $value = floatval($value);

    $qresult = $this->mysqli->query("SELECT engine FROM feeds WHERE `id` = '$feedid'");
    $row = $qresult->fetch_array();

    if ($row['engine']==Engine::TIMESTORE) $this->timestore->post($feedid,$feedtime,$value);
    if ($row['engine']==Engine::MYSQL) $this->mysqltimeseries->update($feedid,$feedtime,$value);
    
    // b. Update feeds table
    $updatetime = date("Y-n-j H:i:s", $updatetime); 
    $this->mysqli->query("UPDATE feeds SET value = '$value', time = '$updatetime' WHERE id='$feedid'");

    //Check feed event if event module is installed
    if (is_dir(realpath(dirname(__FILE__)).'/../event/')) {
      require_once(realpath(dirname(__FILE__)).'/../event/event_model.php');
      $event = new Event($this->mysqli);
      $event->check_feed_event($feedid,$updatetime,$feedtime,$value);
    }
    
    return $value;
  }
  
  public function get_data($feedid,$start,$end)
  {
    $qresult = $this->mysqli->query("SELECT engine FROM feeds WHERE `id` = '$feedid'");
    $row = $qresult->fetch_array();

    if ($row['engine']==Engine::TIMESTORE) return $this->timestore->get_data($feedid,$start,$end);
    if ($row['engine']==Engine::MYSQL) return $this->mysqltimeseries->get_data($feedid,$start,$end);
  }

  
  public function delete($feedid)
  {
    $feedid = intval($feedid);
    
    $qresult = $this->mysqli->query("SELECT engine FROM feeds WHERE `id` = '$feedid'");
    $row = $qresult->fetch_array();

    if ($row['engine']==Engine::TIMESTORE) return $this->timestore->delete($feedid);
    if ($row['engine']==Engine::MYSQL) return $this->mysqltimeseries->delete($feedid);
    
    $this->mysqli->query("DELETE FROM feeds WHERE id = '$feedid'");
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
      $this->mysqli->query("UPDATE feeds SET `size` = '$size' WHERE `id`= '$feedid'");
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
  
}

