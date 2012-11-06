<?php

  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  Feeds table

  id | name | userid | tag | time | value | status | datatype

  status 0: active
  status 1: wastebin
  
  datatype 0: UNDEFINED
  datatype 1: REALTIME
  datatype 2: DAILY
  datatype 3: HISTOGRAM

  */

  function create_feed($userid,$name,$datatype)
  {
    // If feed of given name by the user already exists
    $feedid = get_feed_id($userid,$name);
    if ($feedid!=0) return $feedid;

    $result = db_query("INSERT INTO feeds (userid,name,status,datatype) VALUES ('$userid','$name','0','$datatype')");
    $feedid = db_insert_id();

    if ($feedid>0) 
    {
      $feedname = "feed_".$feedid;

      if ($datatype!=3) {										
        $result = db_query(
        "CREATE TABLE $feedname (
	  time INT UNSIGNED, data float,
        INDEX ( `time` ))");
      }

      if ($datatype==3) {										
        $result = db_query(										
        "CREATE TABLE $feedname (
	  time INT UNSIGNED, data float, data2 float,
        INDEX ( `time` ))");
      }

      return $feedid;											
    } else return 0;
  }

  function get_feed_id($userid,$name)
  {
    $result = db_query("SELECT id FROM feeds WHERE userid = '$userid' AND name = '$name'");
    $row = db_fetch_array($result);
    return $row['id'];
  }

  function feed_belongs_user($feedid,$userid)
  {
    $result = db_query("SELECT id FROM feeds WHERE `userid` = '$userid' AND `id` = '$feedid'");
    $row = db_fetch_array($result);
    if ($row) return 1;
    return 0;
  }

  /*

  User Feed lists

  Returns a specified user's feedlist in different forms:
  get_user_feeds: 	all the feeds table data
  get_user_feed_ids: 	only the id's
  get_user_feed_names: 	id's and names

  */

  function get_user_feeds($userid,$status)
  {
    $result = db_query("SELECT * FROM feeds WHERE userid = $userid AND status = '$status'");
    if (!$result) return 0;
    $feeds = array();
    while ($row = db_fetch_object($result)) 
    { 
      $row->size = get_feedtable_size($row->id);
      $row->time = strtotime($row->time)*1000;
      $feeds[] = $row; 
    }
    return $feeds;
  }

  function get_user_feed_ids($userid)
  {
    $result = db_query("SELECT id FROM feeds WHERE userid = '$userid' AND status = '0'");
    if (!$result) return 0;
    $feeds = array();
    while ($row = db_fetch_object($result)) { $feeds[] = $row->id; }
    return $feeds;
  }

  function get_user_feed_names($userid)
  {
    $result = db_query("SELECT id,name,datatype FROM feeds WHERE userid = '$userid' AND status = '0'");
    if (!$result) return 0;
    $feeds = array();
    while ($row = db_fetch_array($result)) { $feeds[] = array('id'=>$row['id'],'name'=>$row['name'],'datatype'=>$row['datatype']); }
    return $feeds;
  }

  /*

  Feeds table GET functions

  */

  function get_feed($id)
  {
    $result = db_query("SELECT * FROM feeds WHERE id = $id");
    return db_fetch_object($result);
  }

  function get_feed_field($id,$field)
  {
    $result = db_query("SELECT `$field` FROM feeds WHERE `id` = '$id'");
    $row = db_fetch_array($result);
    return $row[0];
  }

  function get_feed_timevalue($id)
  {
    $result = db_query("SELECT time,value FROM feeds WHERE id = '$id'");
    return db_fetch_array($result);
  }

  /*

  Feeds table SET functions

  */

  function set_feed_field($id,$field,$value)
  {
    if ($field!='id') $result = db_query("UPDATE feeds SET `$field` = '$value' WHERE id = $id");
    return $result;
  }

  /*

  Feed data functions

  insert, update, get and specialist histogram functions

  */

  function insert_feed_data($feedid,$updatetime,$feedtime,$value)
  { 
    if (get_feed_field($feedid,'status')==1) return $value;	// Dont insert if deleted

    $feedname = "feed_".trim($feedid)."";

    // a. Insert data value in feed table
    db_query("INSERT INTO $feedname (`time`,`data`) VALUES ('$feedtime','$value')");

    // b. Update feeds table
    $updatetime = date("Y-n-j H:i:s", $updatetime); 
    db_query("UPDATE feeds SET value = '$value', time = '$updatetime' WHERE id='$feedid'");

    return $value;
  }

  function update_feed_data($feedid,$updatetime,$feedtime,$value)
  {        
    if (get_feed_field($feedid,'status')==1) return $value;	// Dont update if deleted
             
    $feedname = "feed_".trim($feedid)."";

    // a. update or insert data value in feed table
    $result = db_query("SELECT * FROM $feedname WHERE time = '$feedtime'");
    $row = db_fetch_array($result);

    if ($row) db_query("UPDATE $feedname SET data = '$value', time = '$feedtime' WHERE time = '$feedtime'");
    if (!$row) {$value = 0; db_query("INSERT INTO $feedname (`time`,`data`) VALUES ('$feedtime','$value')"); }

    // b. Update feeds table
    $updatetime = date("Y-n-j H:i:s", $updatetime); 
    db_query("UPDATE feeds SET value = '$value', time = '$updatetime' WHERE id='$feedid'");
    return $value;
  }

  function get_feed_data($feedid,$start,$end,$dp)
  {
    if ($end == 0) $end = time()*1000;

    $feedname = "feed_".trim($feedid)."";
    $start = $start/1000; $end = $end/1000;

    $data = array();
    if (($end - $start) > (5000) && $dp>0) //why 5000?
    {
      $range = $end - $start;
      $td = $range / $dp;

      for ($i=0; $i<$dp; $i++)
      {
        $t = $start + $i*$td;
        $tb = $start + ($i+1)*$td;
        $result = db_query("SELECT * FROM $feedname WHERE `time` >$t AND `time` <$tb LIMIT 1");

        if($result){
          $row = db_fetch_array($result);
          $dataValue = $row['data'];               
          if ($dataValue!=NULL) { // Remove this to show white space gaps in graph      
            $time = $row['time'] * 1000;     
            $data[] = array($time , $dataValue); 
          } 
        }         
      }
    } else {
      $result = db_query("select * from $feedname WHERE time>$start AND time<$end order by time Desc");
      while($row = db_fetch_array($result)) {
        $dataValue = $row['data'];
        $time = $row['time'] * 1000;  
        $data[] = array($time , $dataValue); 
      }
    }

    return $data;
  }

  function get_histogram_data($feedid,$start,$end)
  {
    if ($end == 0) $end = time()*1000;
    $feedname = "feed_".trim($feedid)."";
    $start = $start/1000; $end = $end/1000;
    $data = array();

    // Histogram has an extra dimension so a sum and group by needs to be used.
    $result = db_query("select data2, sum(data) as kWh from $feedname WHERE time>='$start' AND time<'$end' group by data2 order by data2 Asc"); 
	
    $data = array();                                      // create an array for them
    while($row = db_fetch_array($result))                 // for all the new lines
    {
      $dataValue = $row['kWh'];                           // get the datavalue
      $data2 = $row['data2'];            		  // and the instant watts
      $data[] = array($data2 , $dataValue);               // add time and data to the array
    }
    return $data;
  }

  function get_kwhd_atpower($feedid, $min, $max)
  {
    $feedname = "feed_".trim($feedid)."";
    $result = db_query("SELECT time, sum(data) as kWh FROM `$feedname` WHERE `data2`>='$min' AND `data2`<='$max' group by time");

    $data = array();
    while($row = db_fetch_array($result)) $data[] = array($row['time']* 1000 , $row['kWh']); 

    return $data;
  }

  /*

  Feed table size

  */

  function get_feedtable_size($feedid)
  {
    $feedname = "feed_".$feedid;
    $result = db_query("SHOW TABLE STATUS LIKE '$feedname'");
    $row = db_fetch_array($result);
    $tablesize = $row['Data_length']+$row['Index_length'];
    return $tablesize;
  }

  function get_user_feeds_size($userid)
  {
    $result = db_query("SELECT id FROM feeds WHERE userid = '$userid'");
    $total = 0;
    if ($result) {
      while ($row = db_fetch_array($result)) {
        $total += get_feedtable_size($row['id']);
      }
    }

    return $total;
  }

  /*

  Feed wastebin, restore and permanent deletion

  */

  function delete_feed($userid,$feedid)
  {
    // feed status of 1 = deleted, this provides a way to soft delete so that if the delete was a mistake
    // it can be taken out of the recycle bin.
    db_query("UPDATE feeds SET status = 1 WHERE id='$feedid'");
  }

  function restore_feed($userid,$feedid)
  {
    // feed status of 1 = deleted, this provides a way to soft delete so that if the delete was a mistake
    // it can be taken out of the recycle bin.
    db_query("UPDATE feeds SET status = 0 WHERE id='$feedid'");
  }

  function permanently_delete_feeds($userid)
  {
    $result = db_query("SELECT * FROM feeds WHERE userid = '$userid'");
    $feeds = array();
    if ($result) {
      while ($row = db_fetch_object($result)) {
        $feed = get_feed($row->id);
        $feedid = $feed->id;
        if ($feed && $feed->status==1){
          db_query("DELETE FROM feeds WHERE id = '$feedid'");
          db_query("DROP TABLE feed_".$feedid);
        }
      }
    }
  }

?>
