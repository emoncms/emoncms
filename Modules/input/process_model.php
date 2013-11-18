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

class Process
{
    private $conn;
    private $input;
    private $feed;
    private $default_engine;

    public function __construct($conn,$input,$feed)
    {
        global $default_engine;

        $this->conn = $conn;
        $this->input = $input;
	$this->feed = $feed;
        $this->default_engine = $default_engine;
    }

    public function get_process_list()
    {
      $list = array();

      // Process description
      // Arg type
      // public function Name
      // No. of datafields if creating feed
      // Data type

      $list[1] = array(
        _("Log to feed"),
        ProcessArg::FEEDID,
        "log_to_feed",
        1,
        DataType::REALTIME
      );
      $list[2] = array(
        "x",
        ProcessArg::VALUE,
        "scale",
        0,
        DataType::UNDEFINED
      );
      $list[3] = array(
        "+",
        ProcessArg::VALUE,
        "offset",
        0,
        DataType::UNDEFINED
      );
      $list[4] = array(
        _("Power to kWh"),
        ProcessArg::FEEDID,
        "power_to_kwh",
        1,
        DataType::REALTIME
      );
      $list[5] = array(
        _("Power to kWh/d"),
        ProcessArg::FEEDID,
        "power_to_kwhd",
        1,
        DataType::DAILY
      );
      $list[6] = array(
        _("x input"),
        ProcessArg::INPUTID,
        "times_input",
        0,
        DataType::UNDEFINED
      );
      $list[7] = array(
        _("input on-time"),
        ProcessArg::FEEDID,
        "input_ontime",
        1,
        DataType::DAILY
      );
      $list[8] = array(
        _("kWhinc to kWh/d"),
        ProcessArg::FEEDID,
        "kwhinc_to_kwhd",
        1,
        DataType::DAILY
      );
      $list[9] = array(
        _("kWh to kWh/d (OLD)"),
        ProcessArg::FEEDID,
        "kwh_to_kwhd",
        1,
        DataType::DAILY
      );
      $list[10] = array(
        _("update feed @time"),
        ProcessArg::FEEDID,
        "update_feed_data",
        1,
        DataType::UNDEFINED
      );
      $list[11] = array(
        _("+ input"),
        ProcessArg::INPUTID,
        "add_input",
        0,
        DataType::UNDEFINED
      );
      $list[12] = array(
        _("/ input"),
        ProcessArg::INPUTID,
        "divide_input",
        0,
        DataType::UNDEFINED
      );
      $list[13] = array(
        _("phaseshift"),
        ProcessArg::VALUE,
        "phaseshift",
        0,
        DataType::UNDEFINED
      );
      $list[14] = array(
        _("accumulator"),
        ProcessArg::FEEDID,
        "accumulator",
        1,
        DataType::REALTIME
      );
      $list[15] = array(
        _("rate of change"),
        ProcessArg::FEEDID,
        "ratechange",
        1,
        DataType::REALTIME
      );
      $list[16] = array(
        _("histogram"),
        ProcessArg::FEEDID,
        "histogram",
        2,
        DataType::HISTOGRAM
      );
      $list[17] = array(
        _("average"),
        ProcessArg::FEEDID,
        "average",
        2,
        DataType::HISTOGRAM
      );

      $list[18] = array(
        _("heat flux"),
        ProcessArg::FEEDID,
        "heat_flux",
        1,
        DataType::REALTIME
      );

      $list[19] = array(
        _("power gained to kWh/d"),
        ProcessArg::FEEDID,
        "power_acc_to_kwhd",
        1,
        DataType::DAILY
      );
      
      $list[20] = array(
        _("pulse difference"),
        ProcessArg::FEEDID,
        "pulse_diff",
        1,
        DataType::REALTIME  
      );
      
      $list[21] = array(
        _("KWh to Power"),
        ProcessArg::FEEDID,
        "kwh_to_power",
        1,
        DataType::REALTIME  
      );
      $list[22] = array(
        _("- input"),
        ProcessArg::INPUTID,
        "subtract_input",
        0,
        DataType::UNDEFINED
      );
      $list[23] = array(
        _("kWh to kWh/d"),
        ProcessArg::FEEDID,
        "kwh_to_kwhd2",
        2,
        DataType::HISTOGRAM
      );

      $list[24] = array(
        "allow positive",
        ProcessArg::NONE,
        "allowpositive",
        0,
        DataType::UNDEFINED
      );

      $list[25] = array(
        "allow negative",
        ProcessArg::NONE,
        "allownegative",
        0,
        DataType::UNDEFINED
      );

      $list[26] = array(
        "signed to unsigned",
        ProcessArg::NONE,
        "signed2unsigned",
        0,
        DataType::UNDEFINED
      );
      $list[27] = array(
        _("max value"),
        ProcessArg::FEEDID,
        "max_value",
        1,
        DataType::DAILY
      );
      $list[28] = array(
        _("min value"),
        ProcessArg::FEEDID,
        "min_value",
        1,
        DataType::DAILY
      );
      
      return $list;
    }

    public function input($time, $value, $processlist)
    {
        $process_list = $this->get_process_list();
        $pairs = explode(",", $processlist);
        foreach ($pairs as $pair)    			        
        {
          $inputprocess = explode(":", $pair); 				                // Divide into process id and arg
          $processid = (int) $inputprocess[0];						            // Process id

          $arg = 0;
          if (isset($inputprocess[1])) $arg = $inputprocess[1];	 			// Can be value or feed id

          $process_public = $process_list[$processid][2];	            // get process public function name
          $value = $this->$process_public($arg,$time,$value);		      // execute process public function
        }
    }

    public function get_process($id)
    {
      $list = $this->get_process_list();
      
      if ($id>0 && $id<count($list)+1) return $list[$id];
    }

    public function scale($arg, $time, $value)
    {
      return $value * $arg;
    }

    public function divide($arg, $time, $value)
    {
      return $value / $arg;
    }

    public function offset($arg, $time, $value)
    {
      return $value + $arg;
    }

    public function allowpositive($arg, $time, $value)
    {
      if ($value<0) $value = 0;
      return $value;
    }

    public function allownegative($arg, $time, $value)
    {
      if ($value>0) $value = 0;
      return $value;
    }

    public function signed2unsigned($arg, $time, $value)
    {
      if($value < 0) $value = $value + 65536;
      return $value;
    }
    
    public function log_to_feed($id, $time, $value)
    {
      $this->feed->insert_data($id, $time, $time, $value);

      return $value;
    }

    public function update_feed_data($id, $time, $value)
    {

      return $value;
    }

    //---------------------------------------------------------------------------------------
    // Times value by current value of another input
    //---------------------------------------------------------------------------------------
    public function times_input($id, $time, $value)
    {
      $sql = ("SELECT value FROM input WHERE id = '$id';");
      $result = db_query($this->conn, $sql);
      $row = db_fetch_array($result);
      $value = $value * $row['value'];
      return $value;
    }

    public function divide_input($id, $time, $value)
    {
      $sql = ("SELECT value FROM input WHERE id = '$id';");
      $result = db_query($this->conn, $sql);
      $row = db_fetch_array($result);
     
      if($row['value'] > 0){
          return $value / $row['value'];
      }else{
          return null; // should this be null for a divide by zero?
      }
    }

    public function add_input($id, $time, $value)
    {
      $sql = ("SELECT value FROM input WHERE id = '$id';");
      $result = db_query($this->conn, $sql);
      $row = db_fetch_array($result);
      $value = $value + $row['value'];
      return $value;
    }

    public function subtract_input($id, $time, $value)
    {
      $sql = ("SELECT value FROM input WHERE id = '$id';");
      $result = db_query($this->conn, $sql);
      $row = db_fetch_array($result);
      $value = $value - $row['value'];
      return $value;
    }

    public function add_feed($id, $time, $value)
    {
      $sql = ("SELECT value FROM feeds WHERE id = '$id';");
      $result = db_query($this->conn, $sql);
      $row = db_fetch_array($result);
      $value = $value + $row['value'];
      return $value;
    }

    //---------------------------------------------------------------------------------------
    // Power to kwh
    //---------------------------------------------------------------------------------------
    public function power_to_kwh($feedid, $time_now, $value)
    {
      $new_kwh = 0;

      // Get last value
      $last = $this->feed->get_timevalue($feedid);
      $last_kwh = $last['value'];
      $last_time = strtotime($last['time']);

      if ($last_time)
      {
        // kWh calculation
        $time_elapsed = ($time_now - $last_time);
        $kwh_inc = ($time_elapsed * $value) / 3600000;
        $new_kwh = $last_kwh + $kwh_inc;
      }

      $this->feed->insert_data($feedid, $time_now, $time_now, $new_kwh);

      return $value;
    }

    public function power_to_kwhd($feedid, $time_now, $value)
    {
      $new_kwh = 0;

      // Get last value
      $last = $this->feed->get_timevalue($feedid);
      $last_kwh = $last['value'];
      $last_time = strtotime($last['time']);

      if ($last_time)
      {
        // kWh calculation
        $time_elapsed = ($time_now - $last_time);
        $kwh_inc = ($time_elapsed * $value) / 3600000;
        $new_kwh = $last_kwh + $kwh_inc;
      }

      $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));
      $this->feed->update_data($feedid, $time_now, $feedtime, $new_kwh);

      return $value;
    }

    public function kwhinc_to_kwhd($feedid, $time_now, $value)
    {
      $new_kwh = 0;

      // Get last value
      $last = $this->feed->get_timevalue($feedid);
      $last_kwh = $last['value'];

      $kwh_inc = $value / 1000.0;
      $new_kwh = $last_kwh + $kwh_inc;

      $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));
      $this->feed->update_data($feedid, $time_now, $feedtime, $new_kwh);

      return $value;
    }

    //---------------------------------------------------------------------------------------
    // input on-time counter
    //---------------------------------------------------------------------------------------
    public function input_ontime($feedid, $time_now, $value)
    {
      // Get last value
      $last = $this->feed->get($feedid);
      $ontime = $last->value;
      $last_time = strtotime($last->time);

      if ($value > 0)
      {
        $time_elapsed = ($time_now - $last_time);
        $ontime = $ontime + $time_elapsed;
      }

      $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));
      $this->feed->update_data($feedid, $time_now, $feedtime, $ontime);

      return $value;
    }

    public function kwh_to_kwhd($feedid, $time_now, $value)
    {
      $time = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));

      // First we check if there is an entry for the feed in the kwhdproc table
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("SELECT extract(epoch FROM date_trunc('day', time)) AS time, kwh FROM kwhdproc WHERE feedid = '$feedid';");
		break;
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("SELECT time, kwh FROM kwhdproc WHERE feedid = '$feedid';");
		break;
	default:
		$sql = "";
		break;
	}
	$result = db_query($this->conn, $sql);
	$row = db_fetch_array($result);

      // If there is not we create an entry
      if (!$row) {
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("INSERT INTO kwhdproc (feedid, time, kwh) VALUES ('$feedid', to_timestamp(0), '0');");
		break;
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("INSERT INTO kwhdproc (feedid, time, kwh) VALUES ('$feedid', '0', '0');");
		break;
	default:
		$sql = "";
		break;
	}
	db_query($this->conn, $sql);
      }

      // We then check if the entries time is the same as todays time if it isnt its a new day
      // and we need to put the kwh figure for the start of the day in the kwhdproc table
      if ($time != $row['time'])
      {
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("UPDATE kwhdproc SET kwh = '$value', time = to_timestamp($time) WHERE feedid = '$feedid';");
		break;
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("UPDATE kwhdproc SET kwh = '$value', time = '$time' WHERE feedid = '$feedid';");
		break;
	default:
		$sql = "";
		break;
	}
	db_query($this->conn, $sql);
        $start_day_kwh_value = $value;
      }
      else
      {
        // If it isnt the start of the day then we need to get the start of the day kwh figure
        $start_day_kwh_value = $row['kwh'];
      }

      // 3) Calculate todays kwh figure
      $kwhd = $value - $start_day_kwh_value;

      // 4) Update feed kwhd
      $this->feed->update_data($feedid, $time_now, $time, $kwhd);

      return $value;
    }
  
    public function kwh_to_kwhd2($feedid, $time_now, $value)
    {
      $time = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));

      $feedname = "feed_".trim($feedid)."";
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("SELECT data2 FROM $feedname WHERE time = to_timestamp($time));");
		break;
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("SELECT data2 FROM $feedname WHERE time = '$time';");
		break;
	default:
		$sql = "";
		break;
	}
	db_query($this->conn, $sql);
	$row = db_fetch_array($result);

      $kwh_today = 0;
      if (!$row)
      {
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("INSERT INTO $feedname (time, data, data2) VALUES (to_timestamp($time), '0', '$value');");
		break;
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("INSERT INTO $feedname (time, data, data2) VALUES ('$time', '0', '$value');");
		break;
	default:
		$sql = "";
		break;
	}
	db_query($this->conn, $sql);
      }
      else
      {
        $kwh_start_of_day = $row['data2'];
        $kwh_today = $value - $kwh_start_of_day;
	switch ($this->default_engine) {
	case (Engine::MYSQL):
		$sql = ("UPDATE $feedname SET data = '$kwh_today' WHERE time = to_timestamp($time);");
		break;
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("UPDATE $feedname SET data = '$kwh_today' WHERE time = '$time';");
		break;
	default:
		$sql = "";
		break;
	}
	db_query($this->conn, $sql);
      }

      $updatetime = date("Y-n-j H:i:s", $time_now);
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL): /* Fallthrough */
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("UPDATE feeds SET value = '$kwh_today', time = '$updatetime', datatype = '2' WHERE id = '$feedid';");
		break;
	default:
		$sql = "";
		break;
	}
	db_query($this->conn, $sql);

      return $value;
    }

    //--------------------------------------------------------------------------------
    // Display the rate of change for the current and last entry
    //--------------------------------------------------------------------------------
    public function ratechange($feedid, $time_now, $value)
    {
      // Get the feed
      $feedname = "feed_" . trim($feedid) . "";

      // Get the current input id
      $sql = ("SELECT id, processlist FROM input WHERE processlist LIKE '%:$feedid%';");
      $result = db_query($this->conn, $sql);
      $rowfound = db_fetch_array($result);
      if ($rowfound)
      {
        $inputid = trim($rowfound['id']);
        $processlist = $rowfound['processlist'];
        // Now get the feed for the log to feed command for the input
        $logfeed = preg_match('/1:(\d+)/', $processlist, $matches);
        $logfeedid = trim($matches[1]);
        // Now need to get the last but one value in the main log to feed table
        $oldfeedname = "feed_" . trim($logfeedid) . "";
        $sql = ("SELECT data FROM $oldfeedname ORDER BY time DESC LIMIT 2;");
        $lastentry = db_query($this->conn, $sql);
        $lastentryrow = db_fetch_array($lastentry);
        // Calling again so can get the 2nd row
        $lastentryrow = db_fetch_array($lastentry);
        $prevValue = trim($lastentryrow['data']);
        $ratechange = $value - $prevValue;
        // now put this rate change into the correct feed table
        $this->feed->insert_data($feedid, $time_now, $time_now, $ratechange);
      }

    }

    public function save_to_input($arg, $time, $value)
    {
      $name = $arg;
      $userid = $_SESSION['userid'];

      $id = $this->input->get_id($userid, $name);
      // If input does not exist this return's a zero
      if ($id == 0)
      {
        $this->input->create_timevalue($userid, $name, $time, $value);
        // Create input if it does not exist
      }
      else
      {
        $this->input->set_timevalue($id, $time, $value);
        // Set time and value if it does
      }

      return $value;
    }

    public function accumulator($arg, $time, $value)
    {
      $feedid = $arg;

      $last_value = $this->feed->get_field($feedid,'value');

      $value = $last_value + $value;

      $this->feed->insert_data($feedid, $time, $time, $value);

      return $value;
    }

    //---------------------------------------------------------------------------------
    // This method converts power to energy vs power (Histogram)
    //---------------------------------------------------------------------------------
    public function histogram($feedid, $time_now, $value)
    {
      ///return $value;

      $feedname = "feed_" . trim($feedid) . "";
      $new_kwh = 0;
      // Allocate power values into pots of varying sizes
      if ($value < 500)
      {
        $pot = 50;
      }
      elseif ($value < 2000)
      {
        $pot = 100;
      }
      else
      {
        $pot = 500;
      }
      $new_value = round($value / $pot, 0, PHP_ROUND_HALF_UP) * $pot;

      $time = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));

      // Get the last time
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("SELECT extract(epoch FROM time) AS time FROM feeds WHERE id = '$feedid';");
		break;
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("SELECT time FROM feeds WHERE id = '$feedid';");
		break;
	default:
		$sql = "";
		break;
	}
	$result = db_query($this->conn, $sql);
      $last_row = db_fetch_array($result);

      if ($last_row)
      {
        $last_time = strtotime($last_row['time']);
        if (!$last_time)
          $last_time = $time_now;
        // kWh calculation
        $time_elapsed = ($time_now - $last_time);
        $kwh_inc = ($time_elapsed * $value) / 3600000;
      }

      // Get last value
	switch ($this->default_engine) {
	case (Engine::MYSQL):
		$sql = ("SELECT data2 FROM $feedname WHERE time = to_timestamp($time) AND data2 = '$new_value';");
		break;
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("SELECT data2 FROM $feedname WHERE time = '$time' AND data2 = '$new_value';");
		break;
	default:
		$sql = "";
		break;
	}
	$result = db_query($this->conn, $sql);

      if (!$result) return $value;

      $last_row = db_fetch_array($result);

      if (!$last_row)
      {
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("INSERT INTO $feedname (time, data, data2) VALUES (to_timestamp($time), '0.0', '$new_value');");
		break;
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("INSERT INTO $feedname (time, data, data2) VALUES ('$time', '0.0', '$new_value');");
		break;
	default:
		$sql = "";
		break;
	}
	$result = db_query($this->conn, $sql);

        $updatetime = date("Y-n-j H:i:s", $time_now);
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL): /* Fallthrough */
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("UPDATE feeds SET value = '$new_value', time = '$updatetime' WHERE id = '$feedid';");
		break;
	default:
		$sql = "";
		break;
	}
	db_query($this->conn, $sql);
        $new_kwh = $kwh_inc;
      }
      else
      {
        $last_kwh = $last_row['data'];
        $new_kwh = $last_kwh + $kwh_inc;
      }

      // update kwhd feed
	switch ($this->default_engine) {
	case (Engine::MYSQL):
		$sql = ("UPDATE $feedname SET data = '$new_kwh' WHERE time = '$time' AND data2 = '$new_value';");
		break;
	default:
		$sql = "";
		break;
	}
	db_query($this->conn, $sql);

      $updatetime = date("Y-n-j H:i:s", $time_now);
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL): /* Fallthrough */
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("UPDATE feeds SET value = '$new_value', time = '$updatetime' WHERE id = '$feedid';");
		break;
	default:
		$sql = "";
		break;
	}
	db_query($this->conn, $sql);

      return $value;
    }

    // Calculates a daily average of a value
    public function average($feedid, $time_now, $value)
    {
      $feedname = "feed_" . trim($feedid) . "";
      $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));

	switch ($this->default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("SELECT data, data2 FROM $feedname WHERE time = to_timestamp($feedtime);");
		break;
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("SELECT data, data2 FROM $feedname WHERE time = '$feedtime';");
		break;
	default:
		$sql = "";
		break;
	}
	$result = db_query($this->conn, $sql);

      if (!$result)  return $value;
      $row = db_fetch_array($result);

      $average = $row['data'];
      $size = $row['data2'];

      $new_average = (($average * $size) + $value) / ($size + 1);
      $size = $size + 1;

      if ($row)
      {
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("UPDATE $feedname SET data = '$new_average', data2 = '$size' WHERE time = to_timestamp($feedtime);");
		break;
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("UPDATE $feedname SET data = '$new_average', data2 = '$size' WHERE time = '$feedtime';");
		break;
	default:
		$sql = "";
		break;
	}
	db_query($this->conn, $sql);
      }
      else
      {
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("INSERT INTO $feedname (time, data, data2) VALUES (to_timestamp($feedtime), '$value', '1');");
		break;
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("INSERT INTO $feedname (time, data, data2) VALUES ('$feedtime', '$value', '1');");
		break;
	default:
		$sql = "";
		break;
	}
	db_query($this->conn, $sql);
      }

      $updatetime = date("Y-n-j H:i:s", $time_now);
	switch ($this->default_engine) {
	case (Engine::POSTGRESQL): /* Fallthrough */
	case (Engine::SQLITE): /* Fallthrough */
	case (Engine::MYSQL):
		$sql = ("UPDATE feeds SET value = '$new_average', time = '$updatetime' WHERE id = '$feedid';");
		break;
	default:
		$sql = "";
		break;
	}
	db_query($this->conn, $sql);

      return $value;
    }

 
   //------------------------------------------------------------------------------------------------------
  // Calculate the energy used to heat up water based on the rate of change for the current and a previous temperature reading
  // See http://harizanov.com/2012/05/measuring-the-solar-yield/ for more info on how to use it
  //------------------------------------------------------------------------------------------------------
    public function heat_flux($feedid,$time_now,$value)
    {
   // Get the feed
	  $feedname = "feed_".trim($feedid)."";
       
	  // Get the current input id 
          $sql = ("SELECT id, processlist FROM input WHERE processlist LIKE '%:$feedid%';");
          $result = db_query($this->conn, $sql);
          $rowfound = db_fetch_array($result);
	  if ($rowfound)
	  {
		  $inputid = trim($rowfound['id']);
		  $processlist = $rowfound['processlist'];
		  // Now get the feed for the log to feed command for the input 
		  $logfeed = preg_match('/1:(\d+)/',$processlist,$matches);
		  $logfeedid = trim($matches[1]);
		  // Now need to get the last but one value in the main log to feed table
		  $oldfeedname = "feed_".trim($logfeedid)."";

		  // Read previous N readings, starting not from the latest one, but the one before it (LIMIT 1,N)
		  // Find a previous reading that is at least 10 minutes apart from the current reading and average the in-between readings to smooth out fluctuations
		  // Without this we will get unstable readings

		switch ($this->default_engine) {
		case (Engine::POSTGRESQL):
			$sql = ("SELECT extract(epoch FROM time) AS time, data FROM $oldfeedname ORDER BY time DESC LIMIT 1, 128;");
			break;
		case (Engine::SQLITE): /* Fallthrough */
		case (Engine::MYSQL):
			$sql = ("SELECT time, data FROM $oldfeedname ORDER BY time DESC LIMIT 1, 128;");
			break;
		default:
			$sql = "";
			break;
		}
		$lastentry = db_query($this->conn, $sql);
		  $lastentryrow = db_fetch_array($lastentry);

		  $time_prev  = trim($lastentryrow['time']);	//Read the time of previous reading
		  $prevValue  = trim($lastentryrow['data']);	//Get previous reading

		  while($lastentryrow = db_fetch_array($lastentry)) {

		  $time_prev  = trim($lastentryrow['time']);
		  $prevValue  = trim($lastentryrow['data']);	 
		  if(($time_now-$time_prev)> 60*10) {
			  break;
		  }
		  }

		  $ratechange = $value - $prevValue;
		  $TimeDelta  = $time_now - $time_prev;		//Calculate time in seconds that has elapsed since then
		
		  $ratechange = ($ratechange*4186/$TimeDelta);     //Calculate the temperature change per second
									  //Specific heat of Water (4186 J/kg/K)
									  //Multiply by the volume in liters in emoncms as a next step of the processing
      }
	  return($ratechange);
    }


  //For solar hot water heater, I need the positive amounts only to be able to calculate the energy harvested in a day. 
  //Negative values are when the hot water tank loses energy i.e. due to heat loss OR when being used for a shower, but I want the daily gain in energy only

    public function power_acc_to_kwhd($feedid,$time_now,$value)
    {
        if ($value>0) {
            $new_kwh = 0;

            // Get last value
            $last = $this->feed->get_timevalue($feedid);
            $last_kwh = $last['value'];
            $last_time = strtotime($last['time']);

            if ($last_time) {
                // kWh calculation
                $time_elapsed = ($time_now - $last_time);
                $kwh_inc = ($time_elapsed * $value) / 3600000;
                $new_kwh = $last_kwh + $kwh_inc;
            }

            $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));
            $this->feed->update_data($feedid,$time_now,$feedtime,$new_kwh);

            return $value;
        }
    }

    public function pulse_diff($feedid,$time_now,$value)
    {
      // Wrap around signed int to unsigned int
      if ($value < 0) {
              $value = 65536 + $value;
      }

      if($value>0) {
   
      $pulse_diff = 0;

      // Get last value
      error_log("Feed:".$feedid);
      $last = $this->feed->get_timevalue($feedid);
      $last_value = $last['value'];
      $last_time = strtotime($last['time']); 
     
      if ($last_time) {
        // Need to handle resets of the pulse value (and negative 2**15?)
        if ($value >= $last_value)
        {
          $pulse_diff = $value - $last_value;
        }
        else
        {
          $pulse_diff = $value;
        }
      }
      error_log("Value:".$value." Last:".$last_value." Diff:".$pulse_diff);
      
      // Save to allow next difference calc. 
      $this->feed->insert_data($feedid,$time_now,$time_now,$value);

      return $pulse_diff;
    	}
    }
    
    public function kwh_to_power($feedid,$time_now,$value)
    {

      $power = 0;

      // Get last time
      error_log("Feed:".$feedid);
      $last = $this->feed->get_timevalue($feedid);
      $last_value = $last['value'];
      $last_time = strtotime($last['time']); 

      if ($last_time) {
        $time_elapsed = ($time_now - $last_time);   // seconds
        error_log("Time elapsed:".$time_elapsed);
        $power = ($value * 3600 / $time_elapsed);
      }
      
      $this->feed->insert_data($feedid,$time_now,$time_now,$power);

      return $power;
    }
    
    	public function max_value($feedid, $time_now, $value)
    {

      // Get last values
      $last = $this->feed->get_timevalue($feedid);
      $last_val = $last['value'];
	  $last_time = strtotime($last['time']);
	  $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));
	  $time_check = mktime(0, 0, 0, date("m",$last_time), date("d",$last_time), date("Y",$last_time));
		
	  // Runs on setup and midnight to reset current value - (otherwise db sets 0 as new max)
	  if ($time_check != $feedtime) {
      $this->feed->insert_data($feedid, $time_now, $feedtime, $value);
	  }
	  
	  else
	  
	  // Otherwise runs to determine if there is a change in value
	  {
	  if ($value > $last_val) 
		{		
		$this->feed->update_data($feedid, $time_now, $feedtime, $value);
		}
	  }
      return $value;
    }

	public function min_value($feedid, $time_now, $value)
    {

      // Get last values
      $last = $this->feed->get_timevalue($feedid);
      $last_val = $last['value'];
	  $last_time = strtotime($last['time']);
	  $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));
	  $time_check = mktime(0, 0, 0, date("m",$last_time), date("d",$last_time), date("Y",$last_time));
		
	  // Runs on setup and midnight to reset current value - (otherwise db sets 0 as new min)
	  if ($time_check != $feedtime) {
	  $this->feed->insert_data($feedid, $time_now, $feedtime, $value);
	  }
	  
	  else
	  
	  // Otherwise runs to determine if there is a change in value
	  {
	  if ($value < $last_val) 
		{		
		$this->feed->update_data($feedid, $time_now, $feedtime, $value);
		}
	  }
      return $value;
    }
}
  
?>
