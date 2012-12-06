<?php 
  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    INPUT CONTROLLER ACTIONS		ACCESS

    list				read
    delete?id=1				write

  */

  // no direct access
  defined('EMONCMS_EXEC') or die('Restricted access');

  class ProcessArg {
    const VALUE = 0;
    const INPUTID = 1;
    const FEEDID = 2;
  }

  class DataType {
    const UNDEFINED = 0;
    const REALTIME = 1;
    const DAILY = 2;
    const HISTOGRAM = 3;
  }

function input_controller()
{
  require "Modules/input/input_model.php";
  require "Modules/feed/feed_model.php";
  require "Modules/input/process_model.php";
  global $session, $route;

  $format = $route['format'];
  $action = $route['action'];
  $subaction = $route['subaction'];

  $output['content'] = "";
  $output['message'] = "";

  if ($action == 'api' && $session['read'])
  {
    $output['content'] = view("input/Views/input_api.php", array());
  }

  //---------------------------------------------------------------------------------------------------------
  // Post input data
  // http://yoursite/emoncms/input/post.json?csv= | json= ...
  //---------------------------------------------------------------------------------------------------------
  if ($action == 'bulk' && $session['write'])
  {
    $error = false;
    $data = get('data');
    $nodes = json_decode($data);
    if ($nodes == NULL) $error = true;

    //echo var_dump($nodes);
    $len = count($nodes);
    if ($len>0)
    {
      if (isset($nodes[$len-1][0])) $offset = intval($nodes[$len-1][0]); else $offset = 0;
      $start_time = time() - $offset;

      foreach ($nodes as $node) 
      {
        $inputs = array();
        if (isset($node[1]))
        {
          $nodeid = intval($node[1]);
          for ($i=2; $i<count($node); $i++)
          {
            $name = "node".$nodeid."_".($i-1);
            $id = get_input_id($session['userid'],$name);
            $value = $node[$i];
            $time = $start_time + intval($node[0]);

          if (!is_null($value)) {
            $value = intval($node[$i]);
            if ($id==0) {
              $id = create_input_timevalue($session['userid'],$name,$nodeid,$time,$value);
            } else {				
              set_input_timevalue($id,$time,$value);
            }
            $inputs[] = array('id'=>$id,'time'=>$time,'value'=>$value);
          }

          }
          new_process_inputs($session['userid'],$inputs);
        } else $error = true;
      }
    } else $error = true;
    if ($error == false) $output['message'] = "ok"; else $output['message'] = "format error";
  }
			
  if ($action == 'post' && $session['write'])
  {
    $node = intval(get('node'));
    $json = db_real_escape_string(get('json'));
    $csv = db_real_escape_string(get('csv'));

    $datapairs = array();

    if ($json)
    {
      // preg_replace strips out everything appart from alphanumeric characters, whitespace and -.:,
      $json = preg_replace('/[^\w\s-.:,]/','',$json);
      $datapairs = explode(',', $json);
    }

    if ($csv)
    {
      $values = explode(',', $csv);
      $i = 0;
      foreach ($values as $value)
      {
        $i++; 
        if ($node) $key = $i; else $key = "csv".$i;
        $datapairs[] = $key.":".$value;
      }
    }

    if ($json || $csv)
    {
      $time = time();						// get the time - data recived time
      if (isset($_GET["time"])) $time = intval($_GET["time"]);	// or use sent timestamp if present 

      $inputs = register_inputs($session['userid'],$node,$datapairs,$time);      // register inputs
      process_inputs($session['userid'],$inputs,$time);                          // process inputs to feeds etc
      $output['message'] = "ok";
    }
    else
    {
      $output['message'] = "No csv or json input data present";
    }
  }

  //---------------------------------------------------------------------------------------------------------
  // List inputs
  // http://yoursite/emoncms/input/list.html
  // http://yoursite/emoncms/input/list.json
  //---------------------------------------------------------------------------------------------------------
  if ($action == 'list' && $session['read'])
  {
    $inputs = get_user_inputsbynode($session['userid']);

    if ($format == 'json') $output['content'] = json_encode($inputs);
    if ($format == 'html') $output['content'] = view("input/Views/input_node.php", array('inputs' => $inputs));
  }

  //---------------------------------------------------------------------------------------------------------
  // List inputs by node
  // http://yoursite/emoncms/input/list.html
  // http://yoursite/emoncms/input/list.json
  //---------------------------------------------------------------------------------------------------------
  if ($action == 'node' && $session['read'])
  {
    $inputs = get_user_inputsbynode($session['userid']);

    if ($format == 'json') $output['content'] = json_encode($inputs);
    if ($format == 'html') $output['content'] = view("input/Views/input_node.php", array('inputs' => $inputs));
  }
	
  //---------------------------------------------------------------------------------------------------------
  // Delete an input
  // http://yoursite/emoncms/input/delete?id=1
  //---------------------------------------------------------------------------------------------------------
  if ($action == "delete" && $session['write'])
  { 
    $inputid = intval(get("id"));
    if (input_belongs_to_user($session['userid'], $inputid))
    {
      delete_input($session['userid'],$inputid);
      $output['message'] = _("Input deleted").' '.$inputid;
    } else $output['message'] = "Input ".$inputid." does not exist";
  }

  if ($action == "autoconfigure" && $session['write'])
  { 
    $inputs = get_user_inputs($session['userid']);
    foreach ($inputs as $input)
    {
      auto_configure_inputs($session['userid'],$input[0],$input[1]);
    }
    $output['message'] = "Autoconfigured";
  }

  /*

  Input Process actions

  */

  if ($action == "process" && $session['read'])
  {
    //--------------------------------------------------------------------------
    // Query process
    // http://yoursite/emoncms/process/query?type=1
    // Returns ProcessArg type as int; String description; Array of feedids and names
    //  eg. [2,"Feed",[["1","power"],["3","power-histogram"],["2","power-kwhd"]]]
    //--------------------------------------------------------------------------
    if ($subaction == 'query' && $session['read']) // read access required
    { 
      $type = intval(get("type"));			// get process type

      $arg = preg_replace('/[^\w\s-.]/','',get('arg'));	// filter out all except for alphanumeric white space and dash
      $arg = db_real_escape_string($arg);

      $process = get_process($type);

      $newprocess[0] = $process[1]; // Process arg type
      switch($process[1]) {
      case ProcessArg::VALUE:
        $newprocess[1] = "Value";
        break;
      case ProcessArg::INPUTID:
        $newprocess[1] = "Input";
        $newprocess[2] = get_user_input_names($session['userid']);
        break;
      case ProcessArg::FEEDID:
        $newprocess[1] = "Feed";
        $newprocess[2] = get_user_feed_names($session['userid']);
        break;
      default:
        $newprocess[1] = "ERROR";
      }
      $output['message'] = json_encode($newprocess);
    }

    //---------------------------------------------------------------------------------------------------------
    // Get process list of input
    // http://yoursite/emoncms/input/process/list.json?inputid=1
    //---------------------------------------------------------------------------------------------------------
    if ($subaction == "list" && $session['read'])
    {
      $inputid = intval(get("inputid"));
      if (input_belongs_to_user($session['userid'], $inputid))
      {
        $input_processlist = get_input_processlist_desc($session['userid'],$inputid);

        if ($format == 'json') $output['content'] = json_encode($input_processlist);
        if ($format == 'html') $output['content'] = view("input/Views/process_list.php", array('inputid'=>$inputid, 'input_processlist' => $input_processlist, 'process_list'=>get_process_list()));
      }
      else $output['message'] = "Input ".$inputid." does not exist";
    }

    //---------------------------------------------------------------------------------------------------------
    // Add process
    // http://yoursite/emoncms/input/process/add?inputid=1&type=1&arg=power
    //---------------------------------------------------------------------------------------------------------
    if ($subaction == "add" && $session['write']) // write access required
    { 
      $inputid = intval(get("inputid"));
      if (input_belongs_to_user($session['userid'], $inputid))
      {
      $processid = intval(get('type'));			        // get process type (ProcessArg::)
      $arg = floatval(get('arg'));                              // This is: actual value (i.e x0.01), inputid or feedid

      //arg2 is feed name if arg=-1 (create new feed)
      $arg2 = preg_replace('/[^\w\s-.]/','',get('arg2'));	// filter out all except for alphanumeric white space and dash
      $arg2 = db_real_escape_string($arg2);

      $process = get_process($processid);

      $valid = false; // Flag to determine if argument is valid

      switch ($process[1]) {
      case ProcessArg::VALUE:  // If arg type value
        $arg = floatval($arg);
        $id = $arg;
        if ($arg != '') {
          $valid = true;
        } 
        else {
          $output['message'] = 'ERROR: Argument must be a valid number greater or less than 0.';
        }
        break;
      case ProcessArg::INPUTID:  // If arg type input
        // Check if input exists (returns 0 if invalid)
        $name = get_input_name($arg);
        $id = get_input_id($session['userid'],$name);
        if (($name == '') || ($id == '')) {
          $output['message'] = 'ERROR: Input does not exist!';
        }
        else {
          $valid = true;
        }
        break;
      case ProcessArg::FEEDID:   // If arg type feed
        // First check if feed exists of given feed id and user.
        $name = get_feed_field($arg,'name');
        $id = get_feed_id($session['userid'],$name);
        if (($name == '') || ($id == '')) {
          // If it doesnt then create a feed, $process[3] is the number of datafields in the feed table
          $id = create_feed($session['userid'],$arg2, $process[4]);
          // Check feed was created successfully
          $name = get_feed_field($id,'name');
          if ($name == '') {
            $output['message'] = 'ERROR: Could not create new feed!';
          }
          else {
            $valid = true;
          }
        }
        else {
          $valid = true;
        }
        break;
      }

      if ($valid) {
        add_input_process($session['userid'],$inputid,$processid,$id);
      }

      if ($format == 'json') {
        $output['message'] = json_encode($output['message']);
      }
      elseif ($format == 'html') $output['message'] = "Input process added";

      } else $output['message'] = "Input ".$inputid." does not exist";
    }
    
    //--------------------------------------------------------------------------
    // Delete process 
    // http://yoursite/emoncms/process/delete?inputid=1&processid=1
    //--------------------------------------------------------------------------
    if ($subaction == 'delete' && $session['write']) // write access required
    { 
      $inputid = intval(get("inputid"));
      if (input_belongs_to_user($session['userid'], $inputid))
      {
        $processid = intval(get('processid'));
        if (delete_input_process($session['userid'],$inputid,$processid))
          $output['message'] = "Input process deleted";
        else
          $output['message'] = "Input process does not exist";
      } else $output['message'] = "Input ".$inputid." does not exist";
    }

    //--------------------------------------------------------------------------
    // Move process 
    // http://yoursite/emoncms/process/move?inputid=1&processid=1&moveby=1/-1
    //--------------------------------------------------------------------------
    if ($subaction == 'move' && $session['write']) // write access required
    { 
      $inputid = intval(get("inputid"));
      if (input_belongs_to_user($session['userid'], $inputid))
      {
        $processid = intval(get('processid'));
        $moveby = intval(get('moveby'));
        move_input_process($session['userid'],$inputid,$processid,$moveby);
        $output['message'] = "Input process moved";
      } else $output['message'] = "Input ".$inputid." does not exist";
    }

    if ($subaction == "reset")
    {
      $inputid = intval(get("inputid"));
      if (input_belongs_to_user($session['userid'], $inputid))
      {
        reset_input_process($session['userid'], $inputid );
        $output['message'] = _("Process list has been reset");
      } else $output['message'] = "Input ".$inputid." does not exist";
    }
  }

  return $output;
}

?>
