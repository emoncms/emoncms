<?php
defined('EMONCMS_EXEC') or die('Restricted access');

/*
  Structured description of the input module HTTP API.

  Schema per endpoint (consumed by Lib/api_explorer_view.php):
  - description: short human name
  - path:        URL path relative to the emoncms base
  - parameters:  name => array(default, type, options, description)
                 type: "feed" (feed selector), "bool", "select" or text input
  - mode:        "read" or "write" (which apikey is required)
  - group:       section heading used to group endpoints in the docs
  - http:        "post" where one or more parameters must be sent in the POST
                 body (noted in the parameter description)
  - notry:       true for endpoints the interactive explorer cannot exercise
                 (binary or encrypted request bodies)
  - notes:       extra documentation: aliases, formats, gotchas
*/

function input_api_obj() {

  $time_now = time();

  return array(
    // ------------------------------------------------------------------
    // Posting data
    // ------------------------------------------------------------------
    array(
      "description" => tr("Post data (JSON)"),
      "path" => "input/post",
      "parameters" => array(
        "node" => array( "default" => "emontx", "description" => tr("Node name, see the naming rules below") ),
        "fulljson" => array( "default" => '{"power1":100,"power2":200,"power3":300}', "description" => tr("Strict JSON, recommended for new integrations") )
      ),
      "mode" => "write",
      "group" => tr("Posting data"),
      "notes" => tr("Responds {\"success\": true} when the fulljson parameter is used, plain text 'ok' otherwise. The node name can also be given as a sub-action: input/post/emontx. Legacy parameters json={power1:100} and csv=100,200,300 are parsed leniently: non-numeric values are silently skipped.")." "
                 .tr("Naming rules: inputs are created automatically, named by the keys of the posted data. Node and input names may contain letters (including accented and non latin letters), numbers, underscore, hyphen, period and spaces. Any other character is removed without warning, so \"power(W)\" becomes \"powerW\".")." "
                 .tr("Name length is set by the database rather than by emoncms: emoncms.org allows 16 characters for a node name and 64 for an input name. Where those limits apply, posting a longer name does not create the input and the values sent under it are dropped, and the response is still 'ok'. Keep within these limits for portability.")
    ),
    array(
      "description" => tr("Post data (CSV)"),
      "path" => "input/post",
      "parameters" => array(
        "node" => array( "default" => "emontx", "description" => tr("Node name, see the naming rules below") ),
        "csv" => array( "default" => "100,200,300", "description" => tr("Values are named 1,2,3... in order") )
      ),
      "mode" => "write",
      "group" => tr("Posting data")
    ),
    array(
      "description" => tr("Post data with timestamp"),
      "path" => "input/post",
      "parameters" => array(
        "node" => array( "default" => "emontx" ),
        "fulljson" => array( "default" => '{"power1":100}' ),
        "time" => array( "default" => $time_now, "description" => tr("Unix timestamp in seconds (not milliseconds)") )
      ),
      "mode" => "write",
      "group" => tr("Posting data"),
      "notes" => tr("Time may also be included as a 'time' key inside the JSON data, as a unix timestamp or ISO8601 string. The time parameter takes precedence if both are given.")
    ),
    array(
      "description" => tr("Bulk upload"),
      "path" => "input/bulk",
      "parameters" => array(
        "data" => array( "default" => "[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]", "description" => tr("Array of updates: [interval, node, value1, value2...]") )
      ),
      "mode" => "write",
      "group" => tr("Posting data"),
      "notes" => tr("Without a time parameter the last row is taken as 'now' and earlier rows are offset relative to it. A value may also be an object to name the input, e.g [4,19,{\"power\":1412}]. Rows with fewer than 3 elements are skipped.")
    ),
    array(
      "description" => tr("Bulk upload with offset"),
      "path" => "input/bulk",
      "parameters" => array(
        "data" => array( "default" => "[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]" ),
        "offset" => array( "default" => -10, "description" => tr("Row times are relative to now + offset") )
      ),
      "mode" => "write",
      "group" => tr("Posting data")
    ),
    array(
      "description" => tr("Bulk upload with sentat time"),
      "path" => "input/bulk",
      "parameters" => array(
        "data" => array( "default" => "[[520,16,1137],[530,17,1437,3164],[535,19,1412,3077]]" ),
        "sentat" => array( "default" => 543, "description" => tr("Positive increasing time index of the moment of sending") )
      ),
      "mode" => "write",
      "group" => tr("Posting data")
    ),
    array(
      "description" => tr("Bulk upload with absolute time"),
      "path" => "input/bulk",
      "parameters" => array(
        "data" => array( "default" => "[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]" ),
        "time" => array( "default" => $time_now, "description" => tr("Unix timestamp the row offsets are relative to") )
      ),
      "mode" => "write",
      "group" => tr("Posting data")
    ),
    array(
      "description" => tr("Encrypted post"),
      "path" => "input/post",
      "parameters" => array(),
      "mode" => "write",
      "group" => tr("Posting data"),
      "notry" => true,
      "notes" => tr("For devices without HTTPS: POST to input/post or input/bulk with header Content-Type: aes128cbc (or aes128cbcgz) and Authorization: USERID:HMAC_SHA256. Body is base64(IV + ciphertext) of a standard request string, AES-128-CBC encrypted with the write apikey as pre-shared key. The response is a base64 sha256 hash of the decrypted payload for verification.")
    ),

    // ------------------------------------------------------------------
    // Listing inputs
    // ------------------------------------------------------------------
    array(
      "description" => tr("List nodes and inputs"),
      "path" => "input/get",
      "parameters" => array(),
      "mode" => "read",
      "group" => tr("Listing inputs"),
      "notes" => tr("Returns {node: {input: {time, value, processList}}} for all nodes.")
    ),
    array(
      "description" => tr("List inputs for a node"),
      "path" => "input/get",
      "parameters" => array(
        "node" => array( "default" => "emontx", "description" => tr("Can also be given as sub-action: input/get/emontx") )
      ),
      "mode" => "read",
      "group" => tr("Listing inputs")
    ),
    array(
      "description" => tr("Get a specific input"),
      "path" => "input/get",
      "parameters" => array(
        "node" => array( "default" => "emontx" ),
        "name" => array( "default" => "power1", "description" => tr("Can also be given as sub-action: input/get/emontx/power1") )
      ),
      "mode" => "read",
      "group" => tr("Listing inputs")
    ),
    array(
      "description" => tr("List inputs with latest values"),
      "path" => "input/list",
      "parameters" => array(),
      "mode" => "read",
      "group" => tr("Listing inputs"),
      "notes" => tr("Flat list with id, nodeid, name, description, processList, time and value.")
    ),
    array(
      "description" => tr("List inputs configuration"),
      "path" => "input/getinputs",
      "parameters" => array(),
      "mode" => "read",
      "group" => tr("Listing inputs"),
      "notes" => tr("Returns {node: {input: {id, processList}}} without last time and value. input/get_inputs is an alias.")
    ),

    // ------------------------------------------------------------------
    // Managing inputs
    // ------------------------------------------------------------------
    array(
      "description" => tr("Set input fields"),
      "path" => "input/set",
      "parameters" => array(
        "inputid" => array( "default" => 1 ),
        "fields" => array( "default" => '{"description":"Input Description"}', "description" => tr("Only name and description can be set") )
      ),
      "mode" => "write",
      "group" => tr("Managing inputs"),
      "notes" => tr("Unlike posting, which removes characters it does not accept, this endpoint rejects the whole request with 'invalid characters in input name' and changes nothing.")." "
                 .tr("A name here may contain letters, numbers, underscore, hyphen and spaces. A description may also contain a period. Note that a period is accepted in an input name created by posting but not when renaming here.")
    ),
    array(
      "description" => tr("Set descriptions for multiple inputs"),
      "path" => "input/set-descriptions",
      "parameters" => array(
        "inputs" => array( "default" => '[{"id":1,"description":"Power"}]', "post" => true, "description" => tr("Must be sent in the POST body") )
      ),
      "mode" => "write",
      "group" => tr("Managing inputs"),
      "http" => "post"
    ),
    array(
      "description" => tr("Set descriptions for a node's inputs"),
      "path" => "input/set-node-input-descriptions",
      "parameters" => array(
        "node" => array( "default" => "emontx" ),
        "names" => array( "default" => "Power 1,Power 2,Power 3", "description" => tr("Comma-separated, applied in input id order") )
      ),
      "mode" => "write",
      "group" => tr("Managing inputs")
    ),
    array(
      "description" => tr("Delete an input"),
      "path" => "input/delete",
      "parameters" => array(
        "inputid" => array( "default" => 1 )
      ),
      "mode" => "write",
      "group" => tr("Managing inputs")
    ),
    array(
      "description" => tr("Delete multiple inputs"),
      "path" => "input/delete",
      "parameters" => array(
        "inputids" => array( "default" => "[1,2]", "description" => tr("JSON array of input ids") )
      ),
      "mode" => "write",
      "group" => tr("Managing inputs")
    ),
    array(
      "description" => tr("Delete inputs without a process list"),
      "path" => "input/clean",
      "parameters" => array(),
      "mode" => "write",
      "group" => tr("Managing inputs"),
      "notes" => tr("Responds in plain text with the number of inputs deleted.")
    ),
    array(
      "description" => tr("Remove processes referencing deleted feeds"),
      "path" => "input/cleanprocesslistfeeds",
      "parameters" => array(),
      "mode" => "write",
      "group" => tr("Managing inputs"),
      "notes" => tr("Responds in plain text with a report of the process list entries removed.")
    ),
    array(
      "description" => tr("Disable automatic input creation"),
      "path" => "input/disable",
      "parameters" => array(),
      "mode" => "write",
      "group" => tr("Managing inputs"),
      "notes" => tr("Posted data for inputs that do not already exist is discarded until input/enable is called. Check the current state with input/isdisabled.")
    ),
    array(
      "description" => tr("Enable automatic input creation"),
      "path" => "input/enable",
      "parameters" => array(),
      "mode" => "write",
      "group" => tr("Managing inputs")
    ),
    array(
      "description" => tr("Check if input creation is disabled"),
      "path" => "input/isdisabled",
      "parameters" => array(),
      "mode" => "write",
      "group" => tr("Managing inputs")
    ),

    // ------------------------------------------------------------------
    // Process lists
    // ------------------------------------------------------------------
    array(
      "description" => tr("Get input process list"),
      "path" => "input/process/get",
      "parameters" => array(
        "inputid" => array( "default" => 1 )
      ),
      "mode" => "write",
      "group" => tr("Process lists"),
      "notes" => tr("Returns the stored short form e.g. process__log_to_feed:1,process__scale:0.1")
    ),
    array(
      "description" => tr("Set input process list"),
      "path" => "input/process/set",
      "parameters" => array(
        "inputid" => array( "default" => 1 ),
        "processlist" => array( "default" => '[{"fn":"process__log_to_feed","args":[1]}]', "post" => true, "description" => tr("Must be sent in the POST body") )
      ),
      "mode" => "write",
      "group" => tr("Process lists"),
      "http" => "post",
      "notes" => tr("JSON array of {fn, args} entries. Process function names and argument types are listed by process/list.json. The legacy colon/comma format is no longer accepted when setting.")
    ),
    array(
      "description" => tr("Reset input process list"),
      "path" => "input/process/reset",
      "parameters" => array(
        "inputid" => array( "default" => 1 )
      ),
      "mode" => "write",
      "group" => tr("Process lists")
    )
  );
}
