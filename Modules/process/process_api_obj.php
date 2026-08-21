<?php
defined('EMONCMS_EXEC') or die('Restricted access');

/*
  Structured description of the process module HTTP API.

  Schema per endpoint (consumed by Lib/api_explorer_view.php):
  - description: short human name
  - path:        URL path relative to the emoncms base
  - parameters:  name => array(default, type, options, description)
                 type: "feed" (feed selector), "bool", "select" or text input
  - mode:        "read" or "write" (which apikey is required)
  - group:       section heading used to group endpoints in the docs
  - notes:       extra documentation: aliases, formats, gotchas

  Process lists themselves are read and written through the input and feed
  modules: input/process/set and feed/process/set.json.
*/

function process_api_obj() {

  return array(
    // ------------------------------------------------------------------
    // Process list
    // ------------------------------------------------------------------
    array(
      "description" => tr("List all processes"),
      "path" => "process/list.json",
      "parameters" => array(),
      "mode" => "read",
      "group" => tr("Available processes"),
      "notes" => tr("Every process the server supports, keyed by its function name, e.g. process__log_to_feed. Each entry gives id_num, name, short, group, description and an args array describing the argument types the process takes. This is the list to work from when building a process list.")
    ),
    array(
      "description" => tr("List processes valid for a context"),
      "path" => "process/list.json",
      "parameters" => array(
        "context" => array( "type" => "select", "default" => 0, "options" => array(0,1), "description" => tr("0: input process list, 1: virtual feed process list") )
      ),
      "mode" => "read",
      "group" => tr("Available processes"),
      "notes" => tr("The same list filtered to the processes that are valid in that context, with deleted processes removed. Not every process makes sense on a virtual feed: log to feed, for example, is input only.")
    ),
    array(
      "description" => tr("Process id to name map"),
      "path" => "process/map.json",
      "parameters" => array(),
      "mode" => "read",
      "group" => tr("Available processes"),
      "notes" => tr("Maps the numeric id_num stored in legacy process lists to the process function name, e.g. 1: process__log_to_feed. Use it to read process lists saved in the old colon and comma format.")
    ),

    // ------------------------------------------------------------------
    // Editing process lists
    // ------------------------------------------------------------------
    array(
      "description" => tr("Get input process list"),
      "path" => "input/process/get",
      "parameters" => array(
        "inputid" => array( "default" => 1 )
      ),
      "mode" => "write",
      "group" => tr("Editing process lists"),
      "notes" => tr("Process lists are read and written through the input and feed modules. See the input API for the full description of this endpoint.")
    ),
    array(
      "description" => tr("Set input process list"),
      "path" => "input/process/set",
      "parameters" => array(
        "inputid" => array( "default" => 1 ),
        "processlist" => array( "default" => '[{"fn":"process__log_to_feed","args":[1]}]', "post" => true, "description" => tr("Must be sent in the POST body") )
      ),
      "mode" => "write",
      "group" => tr("Editing process lists"),
      "http" => "post",
      "notes" => tr("JSON array of {fn, args} entries, where fn is a process function name from process/list.json?context=0 and args matches that process's args definition. See the input API for the full description.")
    ),
    array(
      "description" => tr("Set virtual feed process list"),
      "path" => "feed/process/set.json",
      "parameters" => array(
        "id" => array( "type" => "feed" ),
        "processlist" => array( "default" => '[{"fn":"process__source_feed_data_time","args":[1]}]', "post" => true, "description" => tr("Must be sent in the POST body") )
      ),
      "mode" => "write",
      "group" => tr("Editing process lists"),
      "http" => "post",
      "notes" => tr("Virtual feeds only. Valid process function names for this context are listed by process/list.json?context=1. See the feed API for the full description.")
    )
  );
}
