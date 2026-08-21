<?php
defined('EMONCMS_EXEC') or die('Restricted access');

/*
  Structured description of the feed module HTTP API.

  Schema per endpoint (consumed by Lib/api_explorer_view.php):
  - description: short human name
  - path:        URL path relative to the emoncms base
  - parameters:  name => array(default, type, options, description)
                 type: "feed" (feed selector), "bool", "select" or text input
  - mode:        "read" or "write" (which apikey is required)
  - group:       section heading used to group endpoints in the docs
  - public:      true where the endpoint also works without an apikey for
                 feeds marked public (and via /username/ profile routing)
  - http:        "post" where one or more parameters must be sent in the POST
                 body (noted in the parameter description)
  - notry:       true for endpoints the interactive explorer cannot exercise
                 (binary request or response bodies)
  - notes:       extra documentation: aliases, formats, gotchas
*/

function feed_api_obj() {
  // Auto generated data for feed post api
  $data = array(); for($i=0; $i<4; $i++) { $data[] = array(floor((time()+($i*10))*0.1)*10,100+50*$i); }

  global $session, $settings;

  // Request size limit is an install setting, not a fixed number
  $max_datapoints = isset($settings['feed']['max_datapoints']) ? $settings['feed']['max_datapoints'] : 20000;

  $public_username_str = "";
  if ($session['public_userid']) {
      $public_username_str = $session['public_username']."/";
  }

  return array(
    // ------------------------------------------------------------------
    // Feed list & metadata
    // ------------------------------------------------------------------
    array(
      "description" => tr("List feeds"),
      "path" =>  $public_username_str."feed/list.json",
      "parameters" => array(
        "meta" => array( "default" => 1, "type" => "bool", "description" => tr("Include engine meta: start time, interval, size") )
      ),
      "mode" => "read",
      "group" => tr("Feed list & metadata"),
      "public" => true,
      "notes" => tr("feed/listwithmeta.json is a legacy alias of feed/list.json?meta=1. A user's public feeds can be listed without an apikey via /username/feed/list.json.")
    ),
    array(
      "description" => tr("List public feeds of a user"),
      "path" => "feed/list.json",
      "parameters" => array(
        "userid" => array( "default" => 1, "description" => tr("Returns only feeds that user has marked public") )
      ),
      "mode" => "read",
      "group" => tr("Feed list & metadata"),
      "public" => true
    ),
    array(
      "description" => tr("Get feed id from name"),
      "path" => "feed/getid.json",
      "parameters" => array(
        "name" => array( "default" => "Power" ),
        "tag" => array( "default" => "Test", "description" => tr("Optional") )
      ),
      "mode" => "read",
      "group" => tr("Feed list & metadata"),
      "notes" => tr("Responds in plain text with the feed id.")
    ),
    array(
      "description" => tr("Get feed field"),
      "path" => "feed/get.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "field" => array( "default" => 'name' ),
      ),
      "mode" => "read",
      "group" => tr("Feed list & metadata"),
      "public" => true
    ),
    array(
      "description" => tr("Get all feed fields"),
      "path" => "feed/aget.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode" => "read",
      "group" => tr("Feed list & metadata"),
      "public" => true
    ),
    array(
      "description" => tr("Get feed meta"),
      "path" => "feed/getmeta.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode" => "read",
      "group" => tr("Feed list & metadata"),
      "public" => true,
      "notes" => tr("Returns start_time, interval and npoints for fixed interval engines.")
    ),
    array(
      "description" => tr("Get feed size on disk"),
      "path" => "feed/getfeedsize.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode" => "read",
      "group" => tr("Feed list & metadata"),
      "public" => true
    ),
    array(
      "description" => tr("Get feed data checksum"),
      "path" => "feed/sha256sum.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "npoints" => array( "default" => 0, "description" => tr("Number of most recent datapoints to include, 0 = whole feed") )
      ),
      "mode" => "read",
      "group" => tr("Feed list & metadata"),
      "public" => true,
      "notes" => tr("PHPFina and PHPTimeSeries feeds only.")
    ),

    // ------------------------------------------------------------------
    // Reading data
    // ------------------------------------------------------------------
    array(
      "description" => tr("Last updated time and value for feed"),
      "path" => "feed/timevalue.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode" => "read",
      "group" => tr("Reading data"),
      "public" => true
    ),
    array(
      "description" => tr("Last value of a given feed"),
      "path" => "feed/value.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode" => "read",
      "group" => tr("Reading data"),
      "public" => true
    ),
    array(
      "description" => tr("Fetch a value at a given time"),
      "path" => "feed/value.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "time" => array( "default" => 0, "description" => tr("Unix timestamp in seconds") )
      ),
      "mode" => "read",
      "group" => tr("Reading data"),
      "public" => true
    ),
    array(
      "description" => tr("Last value for multiple feeds"),
      "path" => "feed/fetch.json",
      "parameters" => array(
        "ids" => array( "default" => "1,2,3" )
      ),
      "mode" => "read",
      "group" => tr("Reading data"),
      "public" => true,
      "notes" => tr("Values are returned in the order requested. false indicates a missing feed or no access, null is a valid empty value.")
    ),
    array(
      "description" => tr("Fetch data from a feed"),
      "path" => "feed/data.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "start" => array( "default" => 0, "description" => tr("Unix time in seconds or milliseconds, or any php date string e.g: -1 week, or 01-12-2021") ),
        "end" => array( "default" => 0, "description" => tr("Same formats as start, e.g: now") ),
        "interval" => array( "default" => 60, "description" => tr("Seconds, 0 = auto (~800 points), or timezone aligned: daily, weekly, monthly, annual") ),
        "average" => array( "type" => "bool", "default" => 0, "description" => tr("Mean of each interval rather than the value at its start") ),
        "delta" => array( "type" => "bool", "default" => 0, "description" => tr("Difference per interval, turns cumulative kWh feeds into kWh per interval") ),
        "timeformat" => array( "type" => "select", "default" => "unix", "options" => array("unix","unixms","excel","iso8601","notime"), "description" => tr("unix: seconds, unixms: milliseconds") ),
        "skipmissing" => array( "type" => "bool", "default" => 0, "description" => tr("Omit null datapoints") ),
        "limitinterval" => array( "type" => "bool", "default" => 0, "description" => tr("Limit interval to the feed's native interval") ),
        "dp" => array( "default" => -1, "description" => tr("Round values to N decimal places, -1 = off") ),
        "timezone" => array( "default" => "", "description" => tr("Used for date strings and aligned intervals, defaults to the account timezone") )
      ),
      "mode" => "read",
      "group" => tr("Reading data"),
      "public" => true,
      "notes" => sprintf(tr("Maximum %s datapoints per request, set by the max_datapoints setting."), $max_datapoints)." ".tr("If timeformat is not given, response times are unix milliseconds (unixms). The legacy mode parameter is an alias of interval. feed/average.json is a shortcut for average=1. A split parameter, e.g. split=[0,7,13], returns for each daily, weekly or monthly interval the values at those hour offsets into the interval, for time of use segmentation: PHPFina and MysqlTimeSeries only, maximum 48 split points.")
    ),
    array(
      "description" => tr("Fetch data from multiple feeds"),
      "path" => "feed/data.json",
      "parameters" => array(
        "ids" => array( "default" => "1,2,3" ),
        "start" => array( "default" => 0 ),
        "end" => array( "default" => 0 ),
        "interval" => array( "default" => 60 ),
        "timeformat" => array( "type" => "select", "default" => "unix", "options" => array("unix","unixms","excel","iso8601","notime"), "description" => tr("unix: seconds, unixms: milliseconds") )
      ),
      "mode" => "read",
      "group" => tr("Reading data"),
      "public" => true,
      "notes" => tr("Returns [{feedid, data}, ...] rather than a bare data array. average and delta accept comma separated per-feed values, e.g. delta=1,0,1.")
    ),
    array(
      "description" => tr("CSV export"),
      "path" => "feed/csvexport.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "start" => array( "default" => 0 ),
        "end" => array( "default" => 0 ),
        "interval" => array( "default" => 60 ),
        "timeformat" => array( "type" => "select", "default" => "excel", "options" => array("unix","unixms","excel","iso8601","notime"))
      ),
      "mode" => "read",
      "group" => tr("Reading data"),
      "public" => true,
      "notry" => true,
      "notes" => tr("Streams a CSV file download. Equivalent to feed/data.json with csv=1, which also supports multi-column export with ids=1,2,3.")
    ),
    array(
      "description" => tr("Export raw feed file"),
      "path" => "feed/export.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "start" => array( "default" => 0, "description" => tr("Byte offset into the data file to resume from, 0 = whole feed") )
      ),
      "mode" => "read",
      "group" => tr("Reading data"),
      "public" => true,
      "notry" => true,
      "notes" => tr("Streams the feed's raw data file as a download, float32 little endian for PHPFina. Use feed/getmeta.json for the start time and interval needed to interpret it, and feed/upload.json to write it back. This is how the backup and sync modules move feed data.")
    ),

    // ------------------------------------------------------------------
    // Writing data
    // ------------------------------------------------------------------
    array(
      "description" => tr("Insert or update data point"),
      "path" => "feed/post.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "time" => array( "default" => 0, "description" => tr("Unix timestamp in seconds") ),
        "value" => array( "default" => 100.0 )
      ),
      "mode" => "write",
      "group" => tr("Writing data"),
      "notes" => tr("feed/insert.json and feed/update.json are legacy aliases of feed/post.json. On a fixed interval feed, a gap between the last datapoint and this one is padded with NAN, add join=join to fill it with a straight line interpolation instead.")
    ),
    array(
      "description" => tr("Insert or update multiple data points"),
      "path" => "feed/post.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "data" => array( "default" => json_encode($data), "description" => tr("[[time,value],...] in GET or POST") )
      ),
      "mode" => "write",
      "group" => tr("Writing data")
    ),
    array(
      "description" => tr("Bulk binary upload"),
      "path" => "feed/upload.json",
      "parameters" => array(),
      "mode" => "write",
      "group" => tr("Writing data"),
      "notry" => true,
      "notes" => tr("POST raw binary body of float32 values with query parameters id, start, interval and npoints. Fixed interval PHPFina feeds only. Used by the sync and backup modules.")
    ),
    array(
      "description" => tr("Sync feed data"),
      "path" => "feed/sync.json",
      "parameters" => array(),
      "mode" => "write",
      "group" => tr("Writing data"),
      "notry" => true,
      "notes" => tr("POST raw binary body of checksum framed feed segments, as sent by the emoncms sync module. PHPFina and PHPTimeSeries feeds only. Responds with updated feed meta.")
    ),

    // ------------------------------------------------------------------
    // Managing feeds
    // ------------------------------------------------------------------
    array(
      "description" => tr("Create new feed"),
      "path" => "feed/create.json",
      "parameters" => array(
        "tag" => array( "default" => "Test" ),
        "name" => array( "default" => "Power" ),
        "engine" => array( "default" => 5, "description" => tr("5: PHPFina fixed interval, 2: PHPTimeSeries variable interval") ),
        "options" => array( "default" => json_encode(array("interval"=>10)), "description" => tr("PHPFina interval in seconds, minimum 10") ),
        "unit" => array( "default" => "W", "description" => tr("Optional, max 10 characters") )
      ),
      "mode" => "write",
      "group" => tr("Managing feeds"),
      "notes" => tr("Returns {\"success\":true, \"feedid\":N, \"feed\":{...}} on success. Use the feedid in a log to feed process or when posting data directly.")
    ),
    array(
      "description" => tr("Delete existent feed"),
      "path" => "feed/delete.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode" => "write",
      "group" => tr("Managing feeds")
    ),
    array(
      "description" => tr("Update feed fields"),
      "path" => "feed/set.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "fields" => array( "default" => json_encode(array("name"=>"anewname")), "description" => tr("name, tag, unit and public can be set") )
      ),
      "mode" => "write",
      "group" => tr("Managing feeds"),
      "notes" => tr("The tag:name combination must be unique. Set public to make a feed readable without an apikey.")
    ),
    array(
      "description" => tr("Update multiple feeds"),
      "path" => "feed/set-multiple.json",
      "parameters" => array(
        "feeds" => array( "default" => '[{"id":1,"name":"Power"}]', "post" => true, "description" => tr("Must be sent in the POST body") )
      ),
      "mode" => "write",
      "group" => tr("Managing feeds"),
      "http" => "post"
    ),
    array(
      "description" => tr("Clear all feed data"),
      "path" => "feed/clear.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode" => "write",
      "group" => tr("Managing feeds"),
      "notes" => tr("Deletes all datapoints but keeps the feed.")
    ),
    array(
      "description" => tr("Trim feed data before time"),
      "path" => "feed/trim.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "start_time" => array( "default" => 0, "description" => tr("Unix timestamp, datapoints before this are removed") )
      ),
      "mode" => "write",
      "group" => tr("Managing feeds")
    ),
    array(
      "description" => tr("Scale a range of values"),
      "path" => "feed/scalerange.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "start" => array( "default" => 0 ),
        "end" => array( "default" => 0 ),
        "value" => array( "default" => 1, "description" => tr("Scale factor") )
      ),
      "mode" => "write",
      "group" => tr("Managing feeds"),
      "notes" => tr("PHPFina feeds only.")
    ),
    array(
      "description" => tr("Delete a single data point"),
      "path" => "feed/deletedatapoint.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "feedtime" => array( "default" => 0, "description" => tr("Unix timestamp of the datapoint to remove") )
      ),
      "mode" => "write",
      "group" => tr("Managing feeds"),
      "notes" => tr("MysqlTimeSeries feeds only. Fixed interval feeds have no concept of removing a point, overwrite it with feed/post.json instead.")
    ),
    array(
      "description" => tr("Delete a range of data points"),
      "path" => "feed/deletedatarange.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "start" => array( "default" => 0, "description" => tr("Unix timestamp, inclusive") ),
        "end" => array( "default" => 0, "description" => tr("Unix timestamp, inclusive") )
      ),
      "mode" => "write",
      "group" => tr("Managing feeds"),
      "notes" => tr("MysqlTimeSeries feeds only.")
    ),
    array(
      "description" => tr("Refresh feed disk use"),
      "path" => "feed/updatesize.json",
      "parameters" => array(
      ),
      "mode" => "write",
      "group" => tr("Managing feeds")
    ),
    array(
      "description" => tr("Get redis write buffer size"),
      "path" => "feed/buffersize.json",
      "parameters" => array(),
      "mode" => "write",
      "group" => tr("Managing feeds"),
      "notes" => tr("Number of datapoints waiting in the redis buffer on a low-write install. 0 when redis buffering is disabled.")
    ),

    // ------------------------------------------------------------------
    // Virtual feed process list
    // ------------------------------------------------------------------
    array(
      "description" => tr("Get feed process list"),
      "path" => "feed/process/get.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode" => "write",
      "group" => tr("Virtual feed process list"),
      "notes" => tr("Virtual feeds only. Returns the stored short form e.g. process__source_feed_data_time:1")
    ),
    array(
      "description" => tr("Set feed process list"),
      "path" => "feed/process/set.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "processlist" => array( "default" => '[{"fn":"process__source_feed_data_time","args":[1]}]', "post" => true, "description" => tr("Must be sent in the POST body") )
      ),
      "mode" => "write",
      "group" => tr("Virtual feed process list"),
      "http" => "post",
      "notes" => tr("JSON array of {fn, args} entries, virtual feeds only. Process function names and argument types are listed by process/list.json?context=1. The legacy colon/comma format is no longer accepted when setting.")
    ),
    array(
      "description" => tr("Reset feed process list"),
      "path" => "feed/process/reset.json",
      "parameters" => array(
        "id" => array( "type" => "feed" )
      ),
      "mode" => "write",
      "group" => tr("Virtual feed process list")
    )
  );
}
