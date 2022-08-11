<?php
defined('EMONCMS_EXEC') or die('Restricted access');

function feed_api_obj() {
  // Auto generated data for feed insert api
  $data = array(); for($i=0; $i<4; $i++) { $data[] = array(floor((time()+($i*10))*0.1)*10,100+50*$i); }

  global $session;
  
  $public_username_str = "";
  if ($session['public_userid']) {
      $public_username_str = $session['public_username']."/";
  }

  return array(
    // Read feed actions
    array(
      "description" => _("List feeds"),
      "path" =>  $public_username_str."feed/list.json",
      "parameters" => array(),
      "mode"=>"read"
    ),
    array(
      "description" => _("Get feed field"),
      "path" => "feed/get.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "field" => array( "default" => 'name' ),
      ),
      "mode"=>"read"
    ),
    array(
      "description" => _("Get all feed fields"),
      "path" => "feed/aget.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode"=>"read"
    ),
    array(
      "description" => _("Get feed meta"),
      "path" => "feed/getmeta.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode"=>"read"
    ),
    // Read feed data actions
    array(
      "description" => _("Last updated time and value for feed"),
      "path" => "feed/timevalue.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode"=>"read"
    ),
    array(
      "description" => _("Last value of a given feed"),
      "path" => "feed/value.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode"=>"read"
    ),
    array(
      "description" => _("Fetch a value at a given time"),
      "path" => "feed/value.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "time" => array( "default" => 0 )
      ),
      "mode"=>"read"
    ),
    array(
      "description" => _("Last value for multiple feeds"),
      "path" => "feed/fetch.json",
      "parameters" => array(
        "ids" => array( "default" => "1,2,3" )
      ),
      "mode"=>"read"
    ),
    array(
      "description" => _("Fetch data from a feed"),
      "path" => "feed/data.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "start" => array( "default" => 0, "description"=>"Can also be any php supported date time string e.g: -1 week, or 01-12-2021" ),
        "end" => array( "default" => 0, "description"=>"Can also be any php supported date time string e.g: now, or 10-12-2021" ),
        "interval" => array( "default" => 60, "description"=>"In addition to interval in seconds can also be timezone aligned: daily, weekly, monthly, annual" ),
        "average" => array( "type" => "bool", "default" => 0,  ),
        "timeformat" => array( "type" => "select", "default" => "unix", "options" => array("unix","unixms","excel","iso8601")),
        "skipmissing" => array( "type" => "bool", "default" => 0 ),
        "limitinterval" => array( "type" => "bool", "default" => 0 ),
        "delta" => array( "type" => "bool", "default" => 0 )      
      ),
      "mode"=>"read"
    ),
    // Write feed data actions
    array(
      "description" => _("Insert new data point"),
      "path" => "feed/insert.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "time" => array( "default" => 0 ),
        "value" => array( "default" => 100.0 )
      ),
      "mode"=>"write"
    ),
    array(
      "description" => _("Insert multiple data points"),
      "path" => "feed/insert.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "data" => array( "default" => json_encode($data) )
      ),
      "mode"=>"write"
    ),
    array(
      "description" => _("Delete data point"),
      "path" => "feed/deletedatapoint.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "feedtime" => array( "default" => 0 )
      ),
      "mode"=>"write"
    ),
    // Feed setup actions
    array(
      "description" => _("Create new feed"),
      "path" => "feed/create.json",
      "parameters" => array(
        "tag" => array( "default" => "Test" ),
        "name" => array( "default" => "Power" ),
        "engine" => array( "default" => 5 ),
        "options" => array( "default" => json_encode(array("interval"=>10)))
      ),
      "mode"=>"write"
    ),
    array(
      "description" => _("Delete existent feed"),
      "path" => "feed/delete.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode"=>"write"
    ),
    array(
      "description" => _("Update feed field"),
      "path" => "feed/set.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "fields" => array( "default" => json_encode(array("name"=>"anewname")))
      ),
      "mode"=>"write"
    ),
    array(
      "description" => _("Refresh feed disk use"),
      "path" => "feed/updatesize.json",
      "parameters" => array(
      ),
      "mode"=>"write"
    ),
    array(
      "description" => _("Return buffer points pending write"),
      "path" => "feed/buffersize.json",
      "parameters" => array(
      ),
      "mode"=>"write"
    ),
    // Virtual feed process actions
    array(
      "description" => _("Get feed process list"),
      "path" => "feed/process/get.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode"=>"write"
    ),
    array(
      "description" => _("Set feed process list"),
      "path" => "feed/process/set.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "processlist" => array( "default" => "0:0" ),      
      ),
      "mode"=>"write"
    ),
    array(
      "description" => _("Reset feed process list"),
      "path" => "feed/process/reset.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )  
      ),
      "mode"=>"write"
    )
  );
}
