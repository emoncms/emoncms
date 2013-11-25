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
    private $conn;
    private $timestore;
    private $histogram;
    
    private $default_engine;
    private $default_log_engine;
    
    public function __construct($conn,$timestore_adminkey)
    {
        // Not the best way to bring the variable in but a quick fix for now
        // while the feature is tested.
        global $default_engine, $default_log_engine;
        if (isset($default_engine)) $this->default_engine = $default_engine; else $this->default_engine = Engine::MYSQL;
        if (isset($default_log_engine)) $this->default_log_engine = $default_log_engine; else $this->default_log_engine = Engine::TIMESTORE;
        
        $this->conn = $conn;
        
        // Load different storage engines

	if (($default_log_engine == Engine::MYSQL) || ($default_log_engine == Engine::POSTGRESQL) || ($default_log_engine == Engine::SQLITE)) {
            require "Modules/feed/engine/dbTimeSeries.php";
            $this->dbtimeseries = new dbTimeSeries($conn);
	}
        
        require "Modules/feed/engine/Timestore.php";
        $this->timestore = new Timestore($timestore_adminkey);
       
        require "Modules/feed/engine/Histogram.php";
        $this->histogram = new Histogram($conn);
        
	if ($default_log_engine == Engine::PHPTIMESERIES) {
            require "Modules/feed/engine/PHPTimeSeries.php";
            $this->phptimeseries = new PHPTimeSeries();
	}
    }

    public function create($userid,$name,$datatype,$newfeedinterval)
    {
        $newfeedinterval = (int) $newfeedinterval;
        $userid = intval($userid);
        $name = preg_replace('/[^\w\s-]/','',$name);
        $datatype = intval($datatype);
	$retval = false;

        // If feed of given name by the user already exists
        $feedid = $this->get_id($userid,$name);
        if ($feedid!=0) return array('success'=>false, 'message'=>'feed already exists');
        
        if ($datatype == DataType::REALTIME) $engine = $this->default_log_engine; else $engine = $this->default_engine;

        $sql = ("INSERT INTO feeds (userid, name, datatype, public, engine) VALUES ('$userid', '$name', '$datatype', 'false', '$engine');");
        $result = db_query($this->conn, $sql);
        $feedid = db_lastval($this->conn, $result);

        if (!$feedid > 0)
	    return array('success'=>false);

	$feedname = "feed_" . $feedid . "";

	switch ($datatype) {
	case (DataType::REALTIME):
		switch ($this->default_log_engine) {
		case (Engine::POSTGRESQL): /* Fallthrough, dbtimeseries handles all db's */
		case (Engine::SQLITE): /* Fallthrough, dbtimeseries handles all db's */
		case (Engine::MYSQL):
			$retval = $this->dbtimeseries->create($feedid);
			break;
		case (Engine::PHPTIMESERIES):
			$retval = $this->phptimeseries->create($feedid);
			break;
		case (Engine::TIMESTORE):
		default: /* Fallthrough, TIMESTORE is default logging engine */
			$retval = $this->timestore->create($feedid,$newfeedinterval);
			break;
		}
		break;
	case (DataType::DAILY):
		$retval = $this->dbtimeseries->create($feedid);
		break;
	case (DataType::HISTOGRAM):
		$retval = $this->histogram->create($feedid);
		break;
	default:
		$retval = false;
		break;
	}

	return ($retval) ? array('success'=>true, 'feedid'=>$feedid) : array('success'=>false);
  }

  public function exists($feedid)
  {
	$feedid = intval($feedid);
	$sql = ("SELECT count(id) AS id FROM feeds WHERE id = '$feedid';");
	$result = db_query($this->conn, $sql);
	if ($result)
		$row = db_fetch_array($result);
	return ($row['id'] > 0) ? TRUE : FALSE;
  }

  public function get_id($userid,$name)
  {
	$userid = intval($userid);
	$name = preg_replace('/[^\w\s-]/','',$name);
	$sql = ("SELECT id FROM feeds WHERE userid = '$userid' AND name = '$name';");
	$result = db_query($this->conn, $sql);
	if ($result)
		$row = db_fetch_array($result);
	return ($row['id'] > 0) ? $row['id'] : FALSE;
  }

  public function belongs_to_user($feedid,$userid)
  {
	$userid = intval($userid);
	$feedid = intval($feedid);

	$sql = ("SELECT id FROM feeds WHERE userid = '$userid' AND id = '$feedid';");
	$result = db_query($this->conn, $sql);
	if ($result)
		$row = db_fetch_array($result);

	return ($row['id']) ? TRUE : FALSE;
  }

  public function belongs_to_user_or_public($feedid,$userid)
  {
	$userid = intval($userid);
	$feedid = intval($feedid);

	$sql = ("SELECT userid, public FROM feeds WHERE id = '$feedid';");
	$result = db_query($this->conn, $sql);
	if ($result)
		$row = db_fetch_array($result);

	return (($row['public'] == TRUE) || ($userid != 0 && $row['userid'] == $userid)) ? TRUE : FALSE;
  }

  public function feedtype_belongs_user_or_public($feedid,$userid,$datatype)
  {
	$userid = intval($userid);
	$feedid = intval($feedid);
	$datatype = intval($datatype);

	$sql = ("SELECT userid, public FROM feeds WHERE id = '$feedid' AND datatype = '$datatype';");
	$result = db_query($this->conn, $sql);
	if ($result)
		$row = db_fetch_array($result);

	return (($row['public'] == TRUE) || ($userid != 0 && $row['userid'] == $userid)) ? TRUE : FALSE;
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

	switch ($this->default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("SELECT id, name, datatype, tag, extract(epoch FROM time) AS time, value, public, size, engine FROM feeds WHERE userid = $userid;");
		break;
	case (Engine::SQLITE):
		$sql = ("SELECT id, name, datatype, tag, strftime('%s', time) AS time, value, public, size, engine FROM feeds WHERE userid = $userid;");
		break;
	case (Engine::MYSQL):
		$sql = ("SELECT id, name, datatype, tag, UNIX_TIMESTAMP(time) AS time, value, public, size, engine FROM feeds WHERE userid = $userid;");
		break;
	default:
		$sql = NULL;
		break;
	}
	$result = db_query($this->conn, $sql);
	if (!$result)
		return 0;

	$feeds = array();
	while ($row = db_fetch_object($result)) {
		// $row->size = get_feedtable_size($row->id);
		$row->time *= 1000;
		$row->tag = str_replace(" ", "_", $row->tag);

		if ($row->size < 1024 * 100) {
			$row->size = number_format($row->size / 1024, 1) . " KiB";
		} elseif ($row->size < 1024 * 1024) {
			$row->size = round($row->size / 1024) . " KiB";
		} elseif ($row->size >= 1024 * 1024) {
			$row->size = round($row->size / (1024 * 1024)) . " MiB";
		}

		$row->public = (bool)$row->public;
		$feeds[] = $row;
	}
	return $feeds;
  }

  public function get_user_public_feeds($userid)
  {
	$userid = intval($userid);

	$sql = ("SELECT id, name, value FROM feeds WHERE userid = '$userid' AND public = '1';");
	$result = db_query($this->conn, $sql);
	if (!$result)
		return 0;

	$feeds = array();
	while ($row = db_fetch_object($result)) {
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

	$sql = ("SELECT id FROM feeds WHERE userid = '$userid';");
	$result = db_query($this->conn, $sql);
	if (!$result)
		return 0;
	
	$feeds = array();
	while ($row = db_fetch_object($result))
		$feeds[] = $row->id;

	 return $feeds;
  }

  public function get_user_feed_names($userid)
  {
	$userid = intval($userid);

	$sql = ("SELECT id, name, datatype, public FROM feeds WHERE userid = '$userid';");
	$result = db_query($this->conn, $sql);

	if (!$result)
		return 0;

	$feeds = array();
	while ($row = db_fetch_array($result))
		$feeds[] = array('id'=>$row['id'],
				 'name'=>$row['name'],
				 'datatype'=>$row['datatype'],
				 'public'=>$row['public']);

	return $feeds;
  }

  /*

  Feeds table GET public functions

  */

  public function get($id)
  {
	$id = intval($id);

	switch ($this->default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("SELECT id, name, datatype, tag, extract(epoch FROM time) AS time, value, public FROM feeds WHERE id = '$id';");
		break;
	case (Engine::SQLITE):
		$sql = ("SELECT id, name, datatype, tag, strftime('%s', time) AS time, value, public FROM feeds WHERE id = '$id';");
		break;
	case (Engine::MYSQL):
		$sql = ("SELECT id, name, datatype, tag, UNIX_TIMESTAMP(time) AS time, value, public FROM feeds WHERE id = '$id';");
		break;
	default:
		$sql = NULL;
		break;
	}
	$result = db_query($this->conn, $sql);
	if ($result)
		$row = db_fetch_object($result);

	$row->public = (bool)$row->public;
	return $row;
  }

  public function get_field($id,$field)
  {
	$id = intval($id);

	if ($field != NULL) {
		$field = preg_replace('/[^\w\s-]/', '', $field);
		$sql = ("SELECT $field FROM feeds WHERE id = '$id';");
		$result = db_query($this->conn, $sql);
		if ($result)
			$row = db_fetch_array($result);
		return ($row) ? $row[0] : 0;
	} else {
		return FALSE;
	}
  }

  public function get_timevalue($id)
  {
	$id = intval($id);

	if ($this->default_engine == Engine::POSTGRESQL)
		$sql = ("SELECT extract(epoch FROM time) AS time, value FROM feeds WHERE id = '$id';");
	else
		$sql = ("SELECT time, value FROM feeds WHERE id = '$id';");
	$result = db_query($this->conn, $sql);
	return db_fetch_array($result);
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
    if (isset($fields->name)) $array[] = "name = '".preg_replace('/[^\w\s-]/','',$fields->name)."'";
    if (isset($fields->tag)) $array[] = "tag = '".preg_replace('/[^\w\s-]/','',$fields->tag)."'";
    if (isset($fields->datatype)) $array[] = "datatype = '".intval($fields->datatype)."'";

    if (isset($fields->public)) $array[] = "public = '".intval($fields->public)."'";
    if (isset($fields->time)) {
      $updatetime = date("Y-n-j H:i:s", intval($fields->time)); 
      $array[] = "time = '".$updatetime."'";
    }
    if (isset($fields->value)) $array[] = "value = '".intval($fields->value)."'";
    // Convert to a comma seperated string for the mysql query
    $fieldstr = implode(",",$array);
    $sql = ("UPDATE feeds SET " . $fieldstr . " WHERE id = '$id';");
    $result = db_query($this->conn, $sql);

    if (db_affected_rows($this->conn, $result)>0){
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
	if ($feedtime == NULL)
		$feedtime = time();
	$feedid = intval($feedid);
	$updatetime = intval($updatetime);
	$feedtime = intval($feedtime);
	$value = floatval($value);

	$sql = ("SELECT engine FROM feeds WHERE id = '$feedid';");
	$result = db_query($this->conn, $sql);
	if ($result)
		$row = db_fetch_array($result);

	switch ($row['engine']) {
	case (Engine::POSTGRESQL): /* Fallthrough, dbtimeseries handles all db's */
	case (Engine::SQLITE): /* Fallthrough, dbtimeseries handles all db's */
	case (Engine::MYSQL):
		$this->dbtimeseries->insert($feedid, $feedtime, $value);
		break;
	case (Engine::PHPTIMESERIES):
		$this->phptimeseries->post($feedid, $feedtime, $value);
		break;
	case (Engine::TIMESTORE): /* Fallthrough, TIMESTORE is default logging engine */
	default:
		$this->timestore->post($feedid, $feedtime, $value);
		break;
	}

	// b. Update feeds table
	$updatetime = date("Y-n-j H:i:s P", $updatetime);
	$sql = ("UPDATE feeds SET value = '$value', time = '$updatetime' WHERE id = '$feedid';");
	db_query($this->conn, $sql);

	//Check feed event if event module is installed
	if (is_dir(realpath(dirname(__FILE__)) . '/../event/')) {
		require_once(realpath(dirname(__FILE__)) . '/../event/event_model.php');
		$event = new Event($this->conn);
		$event->check_feed_event($feedid, $updatetime, $feedtime, $value);
	}

	return $value;
  }

  public function update_data($feedid,$updatetime,$feedtime,$value)
  {
	if ($feedtime == NULL)
		$feedtime = time();
	$feedid = intval($feedid);
	$updatetime = intval($updatetime);
	$feedtime = intval($feedtime);
	$value = floatval($value);

	$sql = ("SELECT engine FROM feeds WHERE id = '$feedid';");
	$result = db_query($this->conn, $sql);
	if ($result)
		$row = db_fetch_array($result);

	switch ($row['engine']) {
	case (Engine::POSTGRESQL): /* Fallthrough, dbtimeseries handles all db's */
	case (Engine::SQLITE): /* Fallthrough, dbtimeseries handles all db's */
	case (Engine::MYSQL):
		$this->dbtimeseries->update($feedid, $feedtime, $value);
		break;
	case (Engine::PHPTIMESERIES):
		$this->phptimeseries->post($feedid, $feedtime, $value);
		break;
	case (Engine::TIMESTORE): /* Fallthrough, TIMESTORE is default logging engine */
	default:
		$this->timestore->post($feedid, $feedtime, $value);
		break;
	}

	// b. Update feeds table
	$updatetime = date("Y-n-j H:i:s", $updatetime);
	$sql = ("UPDATE feeds SET value = '$value', time = '$updatetime' WHERE id = '$feedid';");
	db_query($this->conn, $sql);

	//Check feed event if event module is installed
	if (is_dir(realpath(dirname(__FILE__)) . '/../event/')) {
		require_once(realpath(dirname(__FILE__)) . '/../event/event_model.php');
		$event = new Event($this->conn);
		$event->check_feed_event($feedid, $updatetime, $feedtime, $value);
	}

	return $value;
  }

  public function get_data($feedid,$start,$end,$dp)
  {
	$sql = ("SELECT engine FROM feeds WHERE id = '$feedid';");
	$result = db_query($this->conn, $sql);
	if ($result)
		$row = db_fetch_array($result);
	$result = FALSE;

	switch ($row['engine']) {
	case (Engine::POSTGRESQL): /* Fallthrough, dbtimeseries handles all db's */
	case (Engine::SQLITE): /* Fallthrough, dbtimeseries handles all db's */
	case (Engine::MYSQL):
		$result = $this->dbtimeseries->get_data($feedid, $start, $end, $dp);
		break;
	case (Engine::PHPTIMESERIES):
		$result = $this->phptimeseries->get_data($feedid, $start, $end, $dp);
		break;
	case (Engine::TIMESTORE): /* Fallthrough, TIMESTORE is default logging engine */
	default:
		$result = $this->timestore->get_data($feedid, $start, $end);
		break;
	}

	return $result;
  }

  public function get_timestore_average($feedid,$start,$end,$interval)
  {
	$sql = ("SELECT engine FROM feeds WHERE id = '$feedid';");
	$result = db_query($this->conn, $sql);
	if ($result)
		$row = db_fetch_array($result);

	return ($row['engine']==Engine::TIMESTORE) ? $this->timestore->get_average($feedid,$start,$end,$interval) : FALSE;
  }

  public function delete($feedid)
  {
	$feedid = intval($feedid);

	$sql = ("SELECT engine FROM feeds WHERE id = '$feedid';");
	$result = db_query($this->conn, $sql);
	if ($result)
		$row = db_fetch_array($result);

	switch ($row['engine']) {
	case (Engine::POSTGRESQL): /* Fallthrough, dbtimeseries handles all db's */
	case (Engine::SQLITE): /* Fallthrough, dbtimeseries handles all db's */
	case (Engine::MYSQL):
		$this->dbtimeseries->delete($feedid);
		break;
	case (Engine::PHPTIMESERIES):
		$this->phptimeseries->delete($feedid);
		break;
	case (Engine::TIMESTORE): /* Fallthrough, TIMESTORE is default logging engine */
	default:
		$this->timestore->delete($feedid);
		break;
	}

	$sql = ("DELETE FROM feeds WHERE id = '$feedid';");
	db_query($this->conn, $sql);
  }

  public function update_user_feeds_size($userid)
  {
	$total = 0;
	$sql = ("SELECT id, engine FROM feeds WHERE userid = '$userid';");
	$result = db_query($this->conn, $sql);
	if (!$result)
		return 0;

	while ($row = db_fetch_array($result)) {
		$size = 0;
		$feedid = $row['id'];

		switch ($row['engine']) {
		case (Engine::POSTGRESQL): /* Fallthrough, dbtimeseries handles all db's */
		case (Engine::SQLITE): /* Fallthrough, dbtimeseries handles all db's */
		case (Engine::MYSQL):
			$size = $this->dbtimeseries->get_feed_size($feedid);
			break;
		case (Engine::PHPTIMESERIES):
			$size = $this->phptimeseries->get_feed_size($feedid);
			break;
		case (Engine::TIMESTORE): /* Fallthrough, TIMESTORE is default logging engine */
		default:
			$size = $this->timestore->get_feed_size($feedid);
			break;
		}
		$sql = ("UPDATE feeds SET size = '$size' WHERE id = '$feedid';");
		db_query($this->conn, $sql);
		$total += $size;
	}
	return $total;
  }

  // MysqlTimeSeries specific functions that we need to make available to the controller

  public function dbtimeseries_export($feedid,$start) {
    return $this->dbtimeseries->export($feedid,$start);
  }

  public function dbtimeseries_delete_data_point($feedid,$time) {
    return $this->dbtimeseries->delete_data_point($feedid,$time);
  }

  public function dbtimeseries_delete_data_range($feedid,$start,$end) {
    return $this->dbtimeseries->delete_data_range($feedid,$start,$end);
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
}
