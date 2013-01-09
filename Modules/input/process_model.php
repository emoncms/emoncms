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

function get_process_list()
{
  $list = array();

  // Process description
  // Arg type
  // Function Name
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
    _("input max"),
    ProcessArg::FEEDID,
    "input_max",
    1,
    DataType::DAILY
  );
  $list[25] = array(
    _("input min"),
    ProcessArg::FEEDID,
    "input_min",
    1,
    DataType::DAILY
  );

  return $list;
}

 function new_process_inputs($userid,$inputs)
  {
  //--------------------------------------------------------------------------------------------------------------
  // 3) Process inputs according to input processlist
  //--------------------------------------------------------------------------------------------------------------
	  foreach ($inputs as $input)            
	  {
            $value = $input['value'];
	    $input_processlist =  get_input_processlist($userid,$input['id']);
	    if ($input_processlist)
	    {
	      $processlist = explode(",",$input_processlist);
	      foreach ($processlist as $inputprocess)    			        
	      {
	        $inputprocess = explode(":", $inputprocess); 				// Divide into process id and arg
	        $processid = $inputprocess[0];						// Process id
	        $arg = $inputprocess[1];	 					// Can be value or feed id
	
	        $process_list = get_process_list();
	        $process_function = $process_list[$processid][2];			// get process function name
	        $value = $process_function($arg,$input['time'],$value);	// execute process function
	      }
	    }
	  }
  }

 function process_inputs($userid,$inputs,$time)
  {
  //--------------------------------------------------------------------------------------------------------------
  // 3) Process inputs according to input processlist
  //--------------------------------------------------------------------------------------------------------------
	  foreach ($inputs as $input)            
	  {
	    $id = $input[0];
	    $input_processlist =  get_input_processlist($userid,$id);
	    if ($input_processlist)
	    {
	      $processlist = explode(",",$input_processlist);				
	      $value = $input[2];
	      foreach ($processlist as $inputprocess)    			        
	      {
	        $inputprocess = explode(":", $inputprocess); 		// Divide into process id and arg
	        $processid = $inputprocess[0];				// Process id
	        $arg = $inputprocess[1];	 			// Can be value or feed id
	
	        $process_list = get_process_list();
	        $process_function = $process_list[$processid][2];	// get process function name
	        $value = $process_function($arg,$time,$value);		// execute process function
	      }
	    }
	  }
  }

//---------------------------------------------------------------------------------------------
// Setup input processing automatically according to naming convention
//---------------------------------------------------------------------------------------------
function auto_configure_inputs($userid, $id, $name)
{
  // If a power or solar (power) feed
  if (preg_match("/power/i", $name) || preg_match("/solar/i", $name))
  {
    $feedid = create_feed($userid, $name, DataType::REALTIME);
    add_input_process($userid, $id, 1, $feedid);

    $feedid = create_feed($userid, $name . "-kwhd", DataType::DAILY);
    add_input_process($userid, $id, 5, $feedid);

    $feedid = create_feed($userid, $name . "-histogram", DataType::HISTOGRAM);
    add_input_process($userid, $id, 16, $feedid);
  }

  if (preg_match("/temperature/i", $name) || preg_match("/temp/i", $name))
  {
    // 1) log to feed
    $feedid = create_feed($userid, $name, DataType::REALTIME);
    add_input_process($userid, $id, 1, $feedid);
  }
}

function get_process($id)
{
  $list = get_process_list();
  
  if ($id>0 && $id<count($list)+1) return $list[$id];
}

function scale($arg, $time, $value)
{
  return $value * $arg;
}

function divide($arg, $time, $value)
{
  return $value / $arg;
}

function offset($arg, $time, $value)
{
  return $value + $arg;
}

function log_to_feed($id, $time, $value)
{
  insert_feed_data($id, $time, $time, $value);

  return $value;
}

//---------------------------------------------------------------------------------------
// Times value by current value of another input
//---------------------------------------------------------------------------------------
function times_input($id, $time, $value)
{
  $result = db_query("SELECT value FROM input WHERE id = '$id'");
  $row = db_fetch_array($result);
  $value = $value * $row['value'];
  return $value;
}

function divide_input($id, $time, $value)
{
  $result = db_query("SELECT value FROM input WHERE id = '$id'");
  $row = db_fetch_array($result);
 
  if($row['value'] > 0){
      return $value / $row['value'];
  }else{
      return null; // should this be null for a divide by zero?
  }
}

function add_input($id, $time, $value)
{
  $result = db_query("SELECT value FROM input WHERE id = '$id'");
  $row = db_fetch_array($result);
  $value = $value + $row['value'];
  return $value;
}

function subtract_input($id, $time, $value)
{
  $result = db_query("SELECT value FROM input WHERE id = '$id'");
  $row = db_fetch_array($result);
  $value = $value - $row['value'];
  return $value;
}

function add_feed($id, $time, $value)
{
  $result = db_query("SELECT value FROM feeds WHERE id = '$id'");
  $row = db_fetch_array($result);
  $value = $value + $row['value'];
  return $value;
}

//---------------------------------------------------------------------------------------
// Power to kwh
//---------------------------------------------------------------------------------------
function power_to_kwh($feedid, $time_now, $value)
{
  $new_kwh = 0;

  // Get last value
  $last = get_feed_timevalue($feedid);
  $last_kwh = $last['value'];
  $last_time = strtotime($last['time']);

  if ($last_time)
  {
    // kWh calculation
    $time_elapsed = ($time_now - $last_time);
    $kwh_inc = ($time_elapsed * $value) / 3600000;
    $new_kwh = $last_kwh + $kwh_inc;
  }

  insert_feed_data($feedid, $time_now, $time_now, $new_kwh);

  return $value;
}

function power_to_kwhd($feedid, $time_now, $value)
{
  $new_kwh = 0;

  // Get last value
  $last = get_feed_timevalue($feedid);
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
  update_feed_data($feedid, $time_now, $feedtime, $new_kwh);

  return $value;
}

function kwhinc_to_kwhd($feedid, $time_now, $value)
{
  $new_kwh = 0;

  // Get last value
  $last = get_feed_timevalue($feedid);
  $last_kwh = $last['value'];

  $kwh_inc = $value / 1000.0;
  $new_kwh = $last_kwh + $kwh_inc;

  $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));
  update_feed_data($feedid, $time_now, $feedtime, $new_kwh);

  return $value;
}

//---------------------------------------------------------------------------------------
// input on-time counter
//---------------------------------------------------------------------------------------
function input_ontime($feedid, $time_now, $value)
{
  // Get last value
  $last = get_feed($feedid);
  $ontime = $last->value;
  $last_time = strtotime($last->time);

  if ($value == 1 || $value == 0.5 || $value == 2)
  {
    $time_elapsed = ($time_now - $last_time) / 3600;
    $ontime = ($ontime + $time_elapsed);
  }

  

  $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));
  update_feed_data($feedid, $time_now, $feedtime, $ontime);

  return $value;
}

//---------------------------------------------------------------------------------------
// input max finder
//---------------------------------------------------------------------------------------
function input_max($feedid, $time_now, $value)
{
  // Get last value
  $last = get_feed($feedid);
  $inmax = $last->value;
  $last_time = strtotime($last->time);

  if ($value > $inmax)
  {
    //$time_elapsed = ($time_now - $last_time) / 3600;
    $inmax = $value;
  }

  $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));
  update_feed_data($feedid, $time_now, $feedtime, $inmax);

  return $value;
}

//---------------------------------------------------------------------------------------
// input min finder
//---------------------------------------------------------------------------------------
function input_min($feedid, $time_now, $value)
{

  if ()
  // Get last value
  $last = get_feed($feedid);
  $inmin = $last->value;
  $last_time = strtotime($last->time);

  if ($inmin == 0)
  {
  	//initialize variable high
  	$inmin = 100;
  }

  if ($value < $inmin)
  {
    //$time_elapsed = ($time_now - $last_time) / 3600;
    $inmin = $value;
  }

  $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));
  update_feed_data($feedid, $time_now, $feedtime, $inmin);

  return $value;
}

//---------------------------------------------------------------------------------
// This method converts accumulated energy to kwhd
//---------------------------------------------------------------------------------
function kwh_to_kwhd($feedid, $time_now, $value)
{
  $time = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));

  // First we check if there is an entry for the feed in the kwhdproc table
  $result = db_query("SELECT * FROM kwhdproc WHERE feedid = '$feedid'");
  $row = db_fetch_array($result);

  // If there is not we create an entry
  if (!$row)
    db_query("INSERT INTO kwhdproc (feedid,time,kwh) VALUES ('$feedid','0','0')");

  // We then check if the entries time is the same as todays time if it isnt its a new day
  // and we need to put the kwh figure for the start of the day in the kwhdproc table
  if ($time != $row['time'])
  {
    db_query("UPDATE kwhdproc SET kwh = '$value', time = '$time' WHERE feedid='$feedid'");
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
  update_feed_data($feedid, $time_now, $time, $kwhd);

  return $value;
}

function kwh_to_kwhd2($feedid, $time_now, $value)
{
  $time = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));

  $feedname = "feed_".trim($feedid)."";
  $result = db_query("SELECT * FROM $feedname WHERE `time` = '$time'");
  $row = db_fetch_array($result);

  if (!$row)
  {
    db_query("INSERT INTO $feedname (time,data,data2) VALUES ('$time','0','$value')");
  }
  else
  {
    $kwh_start_of_day = $row['data2'];
    $kwh_today = $value - $kwh_start_of_day;
    db_query("UPDATE $feedname SET data = '$kwh_today' WHERE `time` = '$time'");
  }

  $updatetime = date("Y-n-j H:i:s", $time_now);
  db_query("UPDATE feeds SET value = '$kwh_today', time = '$updatetime', datatype = '2' WHERE id='$feedid'");

  return $value;
}

function phaseshift($feedid, $time, $value)
{
  $rad = acos($value);
  $rad = $rad + (($arg / 360.0) * (2.0 * 3.14159265));
  return cos($rad);
}

//--------------------------------------------------------------------------------
// Display the rate of change for the current and last entry
//--------------------------------------------------------------------------------
function ratechange($feedid, $time_now, $value)
{
  // Get the feed
  $feedname = "feed_" . trim($feedid) . "";

  // Get the current input id
  $result = db_query("Select * from input where processList like '%:$feedid%';");
  $rowfound = db_fetch_array($result);
  if ($rowfound)
  {
    $inputid = trim($rowfound['id']);
    $processlist = $rowfound['processList'];
    // Now get the feed for the log to feed command for the input
    $logfeed = preg_match('/1:(\d+)/', $processlist, $matches);
    $logfeedid = trim($matches[1]);
    // Now need to get the last but one value in the main log to feed table
    $oldfeedname = "feed_" . trim($logfeedid) . "";
    $lastentry = db_query("Select * from $oldfeedname order by time desc LIMIT 2;");
    $lastentryrow = db_fetch_array($lastentry);
    // Calling again so can get the 2nd row
    $lastentryrow = db_fetch_array($lastentry);
    $prevValue = trim($lastentryrow['data']);
    $ratechange = $value - $prevValue;
    // now put this rate change into the correct feed table
    insert_feed_data($feedid, $time_now, $time_now, $ratechange);
  }

}

function save_to_input($arg, $time, $value)
{
  $name = $arg;
  $userid = $_SESSION['userid'];

  $id = get_input_id($userid, $name);
  // If input does not exist this return's a zero
  if ($id == 0)
  {
    create_input_timevalue($userid, $name, $time, $value);
    // Create input if it does not exist
  }
  else
  {
    set_input_timevalue($id, $time, $value);
    // Set time and value if it does
  }

  return $value;
}

function accumulator($arg, $time, $value)
{
  $feedid = $arg;

  $last_value = get_feed_field($feedid,'value');

  $value = $last_value + $value;

  insert_feed_data($feedid, $time, $time, $value);

  return $value;
}

//---------------------------------------------------------------------------------
// This method converts power to energy vs power (Histogram)
//---------------------------------------------------------------------------------
function histogram($feedid, $time_now, $value)
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
  $result = db_query("SELECT * FROM feeds WHERE id = '$feedid'");
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
  $result = db_query("SELECT * FROM $feedname WHERE time = '$time' AND data2 = '$new_value'");
  $last_row = db_fetch_array($result);

  if (!$last_row)
  {
    $result = db_query("INSERT INTO $feedname (time,data,data2) VALUES ('$time','0.0','$new_value')");

    $updatetime = date("Y-n-j H:i:s", $time_now);
    db_query("UPDATE feeds SET value = $new_value, time = '$updatetime' WHERE id='$feedid'");
    $new_kwh = $kwh_inc;
  }
  else
  {
    $last_kwh = $last_row['data'];
    $new_kwh = $last_kwh + $kwh_inc;
  }

  // update kwhd feed
  db_query("UPDATE $feedname SET data = '$new_kwh' WHERE time = '$time' AND data2 = '$new_value'");

  $updatetime = date("Y-n-j H:i:s", $time_now);
  db_query("UPDATE feeds SET value = '$new_value', time = '$updatetime' WHERE id='$feedid'");

  return $value;
}

// Calculates a daily average of a value
function average($feedid, $time_now, $value)
{
  $feedname = "feed_" . trim($feedid) . "";
  $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));

  $result = db_query("SELECT * FROM $feedname WHERE time = '$feedtime'");
  $row = db_fetch_array($result);

  $average = $row['data'];
  $size = $row['data2'];

  $new_average = (($average * $size) + $value) / ($size + 1);
  $size = $size + 1;

  if ($row)
  {
    db_query("UPDATE $feedname SET data = '$new_average', data2 = '$size' WHERE time = '$feedtime'");
  }
  else
  {
    db_query("INSERT INTO $feedname (`time`,`data`,`data2`) VALUES ('$feedtime','$value','1')");
  }

  $updatetime = date("Y-n-j H:i:s", $time_now);
  db_query("UPDATE feeds SET value = '$new_average', time = '$updatetime' WHERE id='$feedid'");

  return $value;
}

/*
 function histogram_history($feedid,$inputfeedid, $start, $end)
 {
 ///return $value;

 $feedname = "feed_".trim($feedid)."";
 $feedinput = "feed_".trim($inputfeedid)."";
 $last_dt = 0;

 ///$start = "2011-09-01";
 ///$end = "2011-10-01";

 // Get the input feed data
 $result = db_query("SELECT time, data, date(time) as dt FROM $feedinput WHERE time BETWEEN '$start' AND '$end' ORDER BY time ASC");
 while($row = db_fetch_array($result))             // for all the new lines
 {
 $value = $row['data'] ;                        //get the datavalue
 $time = (strtotime($row['time']))*1000;            //and the time value - converted to unix time * 1000
 $dt = $row['dt'] ;

 // Allocate power values into pots of varying sizes
 if ($value < 500) 	 	{$pot = 50;}
 elseif ($value < 2000) 	{$pot = 100;}
 else 					{$pot = 500;}
 $watts = round($value/$pot,0,PHP_ROUND_HALF_UP)*$pot;

 // kWh calculation
 $time_elapsed = ($time - $last_time);
 $kwh_inc = ($time_elapsed * $value) / 3600000;

 // Clear original data for each new date.
 if ($dt != $last_dt)
 {
 $result3 = db_query("DELETE FROM $feedname WHERE time = '$dt'");
 }

 // Don't process the first row or too long since the last reading
 if (($last_dt != 0) && $time_elapsed < 20*60*60*1000)
 {
 // Find if that pot already exists
 $result2 = db_query("SELECT * FROM $feedname WHERE time = '$last_dt' AND data2 = '$last_watts'");
 $last_row = db_fetch_array($result2);

 if (!$last_row)
 {
 $result3 = db_query("INSERT INTO $feedname (time,data,data2) VALUES ('$last_dt','$kwh_inc','$last_watts')");
 $rows++;
 }
 else
 {
 $result3 = db_query("UPDATE $feedname SET data = data + $kwh_inc WHERE time = '$last_dt' AND data2 = '$last_watts'");
 }
 }
 $last_time 	= $time;
 $last_dt 	= $dt;
 $last_value = $value;
 $last_watts = $watts;
 }

 return $rows;
 }
 */
 
 
   //------------------------------------------------------------------------------------------------------
  // Calculate the energy used to heat up water based on the rate of change for the current and a previous temperature reading
  // See http://harizanov.com/2012/05/measuring-the-solar-yield/ for more info on how to use it
  //------------------------------------------------------------------------------------------------------
  function heat_flux($feedid,$time_now,$value)
  {
 // Get the feed
	$feedname = "feed_".trim($feedid)."";
     
	// Get the current input id 
	$result = db_query("Select * from input where processList like '%:$feedid%';");
	$rowfound = db_fetch_array($result);
	if ($rowfound)
	{
		$inputid = trim($rowfound['id']);
		$processlist = $rowfound['processList'];
		// Now get the feed for the log to feed command for the input 
		$logfeed = preg_match('/1:(\d+)/',$processlist,$matches);
		$logfeedid = trim($matches[1]);
		// Now need to get the last but one value in the main log to feed table
		$oldfeedname = "feed_".trim($logfeedid)."";

		// Read previous N readings, starting not from the latest one, but the one before it (LIMIT 1,N)
		// Find a previous reading that is at least 10 minutes apart from the current reading and average the in-between readings to smooth out fluctuations
		// Without this we will get unstable readings

		
		$lastentry = db_query("Select * from $oldfeedname order by time desc LIMIT 1,128;");  
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

  function power_acc_to_kwhd($feedid,$time_now,$value)
  {

    if($value>0) {
 
    $new_kwh = 0;

    // Get last value
    $last = get_feed_timevalue($feedid);
    $last_kwh = $last['value'];
    $last_time = strtotime($last['time']);

    if ($last_time) {
      // kWh calculation
      $time_elapsed = ($time_now - $last_time);
      $kwh_inc = ($time_elapsed * $value) / 3600000;
      $new_kwh = $last_kwh + $kwh_inc;
    }

    $feedtime = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));
    update_feed_data($feedid,$time_now,$feedtime,$new_kwh);

    return $value;


  }
  }

  function pulse_diff($feedid,$time_now,$value)
  {

    if($value>0) {
 
    $pulse_diff = 0;

    // Get last value
    error_log("Feed:".$feedid);
    $last = get_feed_timevalue($feedid);
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
    insert_feed_data($feedid,$time_now,$time_now,$value);

    return $pulse_diff;
  	}
  }
  
  function kwh_to_power($feedid,$time_now,$value)
  {

    $power = 0;

    // Get last time
    error_log("Feed:".$feedid);
    $last = get_feed_timevalue($feedid);
    $last_value = $last['value'];
    $last_time = strtotime($last['time']); 

    if ($last_time) {
      $time_elapsed = ($time_now - $last_time);   // seconds
      error_log("Time elapsed:".$time_elapsed);
      $power = ($value * 3600 / $time_elapsed);
    }
    
    insert_feed_data($feedid,$time_now,$time_now,$power);

    return $power;
  }
  
?>
