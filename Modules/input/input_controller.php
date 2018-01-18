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

function input_controller()
{
    global $mysqli, $redis, $user, $session, $route, $feed_settings, $device, $param;

    // There are no actions in the input module that can be performed with less than write privileges
    if (!isset($session['write'])) return array('content'=>false);
    if (!$session['write']) return array('content'=>false);

    $result = false;

    require_once "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $feed_settings);

    require_once "Modules/input/input_model.php";
    $input = new Input($mysqli,$redis, $feed);

    require_once "Modules/process/process_model.php";
    $process = new Process($mysqli,$input,$feed,$user->get_timezone($session['userid']));

    // Device module not yet included
    // if (!$device) {
    //    require_once "Modules/device/device_model.php";
    //    $device = new Device($mysqli,$redis);
    // }
    
    require_once "Modules/input/input_methods.php";
    $inputMethods = new InputMethods($mysqli,$redis,$user,$input,$feed,$process,$device);
    
    // Change default route to json
    $route->format = "json"; 

    // ------------------------------------------------------------------------
    // input/post
    // ------------------------------------------------------------------------
    if ($route->action == "post") {
        $result = $inputMethods->post($session['userid']);
        if ($result=="ok") {
            if ($param->exists('fulljson')) $result = '{"success": true}';
        } else {
            $result = '{"success": false, "message": "'.str_replace("\"","'",$result).'"}';
            $log = new EmonLogger(__FILE__);
            $log->error($result);
        }
    }
    
    // ------------------------------------------------------------------------
    // input/bulk
    // ------------------------------------------------------------------------
    else if ($route->action == 'bulk') {
        $result = $inputMethods->bulk($session['userid']);
        if ($result=="ok") {
            // result ok returned
        } else {
            $result = '{"success": false, "message": "'.str_replace("\"","'",$result).'"}';
            $log = new EmonLogger(__FILE__);
            $log->error($result);
        }
    }
    
    // --------------------------------------------
    // Fetch inputs by node and node variable names
    // --------------------------------------------
    // input/get                              full list
    // input/get?node=emontx                  {"power1":{"time":0,"value":0},"power2":{"time":0,"value":0},"power3":{"time":0,"value":0}}
    // input/get/emontx                       {"power1":{"time":0,"value":0},"power2":{"time":0,"value":0},"power3":{"time":0,"value":0}}
    // input/get?node=emontx&name=power1      {"time":0,"value":0}
    // input/get/emontx/power1                {"time":0,"value":0}
        
    else if ($route->action == "get") {
        $dbinputs = $input->get_inputs_v2($session['userid']);
        
        if (!$route->subaction && !isset($_GET['node'])) {
            $result = $dbinputs;
        } else {
            // Node
            if ($route->subaction) { $nodeid = $route->subaction; } else { $nodeid = get('node'); }
            $nodeid = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$nodeid);
            
            // If no node variable name specified return all node variables
            if (!$route->subaction2 && !isset($_GET['name'])) {
            
                if (isset($dbinputs[$nodeid])) {
                    $result = $dbinputs[$nodeid];
                } else {
                    $result = "Node does not exist";
                }
            
            } else {
                // Property
                if ($route->subaction2) { $name = $route->subaction2; } else { $name = get('name'); }
                $name = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$name);
                
                if (isset($dbinputs[$nodeid])) {
                    if (isset($dbinputs[$nodeid][$name])) {
                        $result = $dbinputs[$nodeid][$name];
                    } else {
                        $result = "Node variable does not exist";
                    }
                } else {
                    $result = "Node does not exist";
                }
            }
        }
    }

    // --------------------------------------------------------------------------------
    else if ($route->action == "clean") {
        $route->format = 'text';
        $result = $input->clean($session['userid']);
    }
    else if ($route->action == "list") $result = $input->getlist($session['userid']);
    else if ($route->action == "getinputs") $result = $input->get_inputs($session['userid']);

    else if (isset($_GET['inputid']) && $input->belongs_to_user($session['userid'],get("inputid")))
    {
        if ($route->action == 'set') $result = $input->set_fields(get('inputid'),get('fields'));
        else if ($route->action == "delete") $result = $input->delete($session['userid'],get("inputid"));
        else if ($route->action == "process")
        {
            if ($route->subaction == "get") $result = $input->get_processlist(get("inputid"));
            else if ($route->subaction == "set") $result = $input->set_processlist($session['userid'], get('inputid'), post('processlist'),$process->get_process_list());
            else if ($route->subaction == "reset") $result = $input->reset_processlist(get("inputid"));
        }
        
    // Multiple input actions - permissions are checked within model
    } else if (isset($_GET['inputids'])) {
    
        if ($route->action == "delete") {
            $inputids = json_decode(get('inputids'));
            if ($inputids!=null) $result = $input->delete_multiple($session['userid'],$inputids);
        }
    }

    // -------------------------------------------------------------------------
    // HTML Web pages
    // -------------------------------------------------------------------------
    else if ($route->action == 'api') {
        $route->format = "html";
        $result = view("Modules/input/Views/input_api.php", array());
        
    } else if ($route->action == 'view') {
        $route->format = "html";
        $result =  view("Modules/input/Views/input_view.php", array());
    }

    return array('content'=>$result);
}
