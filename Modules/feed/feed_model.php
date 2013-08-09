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

    public function __construct($mysqli,$timestore)
    {
        $this->mysqli = $mysqli;
        $this->timestore = $timestore;
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

        $result = $this->mysqli->query("INSERT INTO feeds (userid,name,datatype,public,timestore,dpinterval) VALUES ('$userid','$name','$datatype','false','1','$newfeedinterval')");
        $feedid = $this->mysqli->insert_id;

        if ($feedid>0) 
        {
          $feedname = "feed_".$feedid;

          if ($datatype==1)
          {
            $this->timestore->create_node($feedid,$newfeedinterval);
          }

          elseif ($datatype==2) {										
            $result = $this->mysqli->query(
            "CREATE TABLE $feedname (
	      time INT UNSIGNED, data float,
            INDEX ( `time` ))");
          }

          elseif ($datatype==3) {										
            $result = $this->mysqli->query(										
            "CREATE TABLE $feedname (
	      time INT UNSIGNED, data float, data2 float,
            INDEX ( `time` ))");
          }

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

    $result = $this->mysqli->query("SELECT id,name,datatype,tag,time,value,public,dpinterval,size,`convert`,timestore FROM feeds WHERE userid = $userid");
    if (!$result) return 0;
    $feeds = array();
    while ($row = $result->fetch_object()) 
    { 
      // $row->size = get_feedtable_size($row->id);
      $row->time = strtotime($row->time)*1000;
      $row->tag = str_replace(" ","_",$row->tag);

      $row->dpinterval = $row->dpinterval."s";
      if ($row->dpinterval>=1*3600) $row->dpinterval = round($row->dpinterval / 3600)."h";

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
      //$row->tag = str_replace(" ","_",$row->tag);
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

    if (isset($fields->convert)) $array[] = "`convert` = '".intval($fields->convert)."'";

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

    $feedname = "feed_".trim($feedid)."";

    $qresult = $this->mysqli->query("SELECT timestore FROM feeds WHERE `id` = '$feedid'");
    $row = $qresult->fetch_array();

    if ($row['timestore']==1) {
      $this->timestore->post_values($feedid,$feedtime*1000,array($value),null);
    } else {
      // a. Insert data value in feed table
      $this->mysqli->query("INSERT INTO $feedname (`time`,`data`) VALUES ('$feedtime','$value')");
    }

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

  public function insert_data_timestore($feedid,$updatetime,$feedtime,$value)
  { 
    if ($feedtime == null) $feedtime = time();
    $feedid = intval($feedid);
    $updatetime = intval($updatetime);
    $feedtime = intval($feedtime);
    $value = floatval($value);

    $feedname = "feed_".trim($feedid)."";

    // a. Insert data value in feed table
    $this->timestore->post_values($feedid,$feedtime*1000,array($value),null);

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
             
    $feedname = "feed_".trim($feedid)."";

    // a. update or insert data value in feed table
    $result = $this->mysqli->query("SELECT * FROM $feedname WHERE time = '$feedtime'");

    if (!$result) return $value;

    $row = $result->fetch_array();
    if ($row) $this->mysqli->query("UPDATE $feedname SET data = '$value', time = '$feedtime' WHERE time = '$feedtime'");
    if (!$row) {$value = 0; $this->mysqli->query("INSERT INTO $feedname (`time`,`data`) VALUES ('$feedtime','$value')");}

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

  public function delete_data($feedid,$start,$end)
  {
    $feedid = intval($feedid);
    $start = intval($start);
    $end = intval($end);

    $feedname = "feed_".trim($feedid)."";
    $this->mysqli->query("DELETE FROM $feedname where `time` >= '$start' AND `time`<= '$end' LIMIT 1");
  }

  public function deletedatarange($feedid,$start,$end)
  {
    $feedid = intval($feedid);
    $start = intval($start/1000.0);
    $end = intval($end/1000.0);

    $feedname = "feed_".trim($feedid)."";
    $this->mysqli->query("DELETE FROM $feedname where `time` >= '$start' AND `time`<= '$end'");

    return true;
  }

  public function get_data_timestore($feedid,$start,$end,$dp)
  {
    $feedid = intval($feedid);
    $start = intval($start/1000);
    $end = intval($end/1000);
    $dp = intval($dp);

    if ($end == 0) $end = time();

    //$result = $this->mysqli->query("SELECT `interval` FROM feeds WHERE id = '$feedid'");
    //$row = $result->fetch_array();

    $interval = 10;
    //if (isset($row['interval'])) $interval = $row['interval'];

    $start = round($start/$interval)*$interval;
    $end = round($end/$interval)*$interval;
    $npoints = round(($end - $start) / $interval);
 
    if ($npoints>1000) $npoints = 1000;

    $data = json_decode($this->timestore->get_series($feedid,0,$npoints,$start,$end,null));
    return $data;
  }

  public function get_data_mysql($feedid,$start,$end,$dp)
  {
    $feedid = intval($feedid);
    $start = floatval($start);
    $end = floatval($end);
    $dp = intval($dp);

    if ($end == 0) $end = time()*1000;

    $feedname = "feed_".trim($feedid)."";
    $start = $start/1000; $end = $end/1000;

    $data = array();
    $range = $end - $start;
    if ($range > 180000 && $dp > 0) // 50 hours
    {
      $td = $range / $dp;
      $stmt = $this->mysqli->prepare("SELECT time, data FROM $feedname WHERE time BETWEEN ? AND ? LIMIT 1");
      $t = $start; $tb = 0;
      $stmt->bind_param("ii", $t, $tb);
      $stmt->bind_result($dataTime, $dataValue);
      for ($i=0; $i<$dp; $i++)
      {
        $tb = $start + intval(($i+1)*$td);
        $stmt->execute();
        if ($stmt->fetch()) {
          if ($dataValue!=NULL) { // Remove this to show white space gaps in graph      
            $time = $dataTime * 1000;
            $data[] = array($time, $dataValue);
          }
        }
        $t = $tb;
      }
    } else {
      if ($range > 5000 && $dp > 0)
      {
        $td = intval($range / $dp);
        $sql = "SELECT FLOOR(time/$td) AS time, AVG(data) AS data".
          " FROM $feedname WHERE time BETWEEN $start AND $end".
          " GROUP BY 1";
      } else {
        $td = 1;
        $sql = "SELECT time, data FROM $feedname".
          " WHERE time BETWEEN $start AND $end ORDER BY time DESC";
      }
     
      $result = $this->mysqli->query($sql);
      if($result) {
        while($row = $result->fetch_array()) {
          $dataValue = $row['data'];
          if ($dataValue!=NULL) { // Remove this to show white space gaps in graph      
            $time = $row['time'] * 1000 * $td;  
            $data[] = array($time , $dataValue); 
          }
        }
      }
    }

    return $data;
  }

  public function get_histogram_data($feedid,$start,$end)
  {
    $feedid = intval($feedid);
    $start = intval($start);
    $end = intval($end);

    if ($end == 0) $end = time()*1000;
    $feedname = "feed_".trim($feedid)."";
    $start = $start/1000; $end = $end/1000;
    $data = array();

    // Histogram has an extra dimension so a sum and group by needs to be used.
    $result = $this->mysqli->query("select data2, sum(data) as kWh from $feedname WHERE time>='$start' AND time<'$end' group by data2 order by data2 Asc"); 
	
    $data = array();                                      // create an array for them
    while($row = $result->fetch_array())                 // for all the new lines
    {
      $dataValue = $row['kWh'];                           // get the datavalue
      $data2 = $row['data2'];            		  // and the instant watts
      $data[] = array($data2 , $dataValue);               // add time and data to the array
    }
    return $data;
  }

  public function get_kwhd_atpower($feedid, $min, $max)
  {
    $feedid = intval($feedid);
    $min = intval($min);
    $max = intval($max);

    $feedname = "feed_".trim($feedid)."";
    $result = $this->mysqli->query("SELECT time, sum(data) as kWh FROM `$feedname` WHERE `data2`>='$min' AND `data2`<='$max' group by time");

    $data = array();
    while($row = $result->fetch_array()) $data[] = array($row['time']* 1000 , $row['kWh']); 

    return $data;
  }

  public function get_kwhd_atpowers($feedid, $points)
  {
    $feedid = intval($feedid);
    $feedname = "feed_".trim($feedid)."";

    $points = json_decode(stripslashes($points));
    
    $data = array();

    for ($i=0; $i<count($points)-1; $i++)
    {
      $min = intval($points[$i]);
      $max = intval($points[$i+1]);

      $result = $this->mysqli->query("SELECT time, sum(data) as kWh FROM `$feedname` WHERE `data2`>='$min' AND `data2`<='$max' group by time");

      while($row = $result->fetch_array()) 
      { 
        if (!isset($data[$row['time']])) {
          $data[$row['time']] = array(0,0,0,0,0);
          $data[$row['time']][0] = (int)$row['time']; 
        }
        $data[$row['time']][$i+1] = (float)$row['kWh']; 
      }
    }
    $out = array();
    foreach ($data as $item) $out[] = $item;

    return $out;
  }

  /*

  Feed table size

  */

  public function get_feedtable_size($feedid)
  {
    $feedid = intval($feedid);

    $feedname = "feed_".$feedid;
    $result = $this->mysqli->query("SHOW TABLE STATUS LIKE '$feedname'");
    $row = $result->fetch_array();
    $tablesize = $row['Data_length']+$row['Index_length'];
    return $tablesize;
  }

  public function get_user_feeds_size($userid)
  {
    $userid = intval($userid);

    $result = $this->mysqli->query("SELECT id FROM feeds WHERE userid = '$userid'");
    $total = 0;
    if ($result) {
      while ($row = $result->fetch_array()) {
        $total += get_feedtable_size($row['id']);
      }
    }

    return $total;
  }

  /*

  Feed wastebin, restore and permanent deletion

  */

  public function delete($feedid)
  {
    $feedid = intval($feedid);
    $this->mysqli->query("DELETE FROM feeds WHERE id = '$feedid'");
    $this->mysqli->query("DROP TABLE feed_".$feedid);
  }

  public function delete_timestore($feedid)
  {
    $feedid = intval($feedid);
    $this->mysqli->query("DELETE FROM feeds WHERE id = '$feedid'");
    $this->timestore->delete_node($feedid);
  }

  public function export($feedid,$start)
  {
      // Feed id and start time of feed to export
      $feedid = intval($feedid);
      $start = intval($start);

      // Open database etc here
      // Extend timeout limit from 30s to 2mins
      set_time_limit (120);

      // Regulate mysql and apache load.
      $block_size = 400;
      $sleep = 80000;

      $feedname = "feed_".trim($feedid)."";
      $fileName = $feedname.'.csv';

      // There is no need for the browser to cache the output
      header("Cache-Control: no-cache, no-store, must-revalidate");

      // Tell the browser to handle output as a csv file to be downloaded
      header('Content-Description: File Transfer');
      header("Content-type: text/csv");
      header("Content-Disposition: attachment; filename={$fileName}");

      header("Expires: 0");
      header("Pragma: no-cache");

      // Write to output stream
      $fh = @fopen( 'php://output', 'w' );

      // Load new feed blocks until there is no more data 
      $moredata_available = 1;
      while ($moredata_available)
      {
          // 1) Load a block
          $result = $this->mysqli->query("SELECT * FROM $feedname WHERE time>$start  
          ORDER BY time Asc Limit $block_size");

          $moredata_available = 0;
          while($row = $result->fetch_array()) 
          {
            
              // Write block as csv to output stream
              if (!isset($row['data2'])) {
                fputcsv($fh, array($row['time'],$row['data']));
              } else {
                fputcsv($fh, array($row['time'],$row['data'],$row['data2']));
              }

              // Set new start time so that we read the next block along
              $start = $row['time'];
              $moredata_available = 1;
          }
          // 2) Sleep for a bit
          usleep($sleep);
      }

      fclose($fh);
      exit;
  }

}

