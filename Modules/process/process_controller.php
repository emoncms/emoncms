<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.
 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function process_controller()
{
    //return array('content'=>"ok");

    global $mysqli, $redis, $user, $session, $route, $settings;

    // There are no actions in the input module that can be performed with less than write privileges
    if (!$session['read']) return array('content'=>false);

    $result = false;

    require_once "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $settings["feed"]);

    require_once "Modules/input/input_model.php";
    $input = new Input($mysqli,$redis, $feed);

    require_once "Modules/process/process_model.php";
    $process = new Process($mysqli,$input,$feed,$user->get_timezone($session['userid']));

    if ($route->format == 'html')
    {
        if ($route->action == 'api') $result = view("Modules/process/Views/process_api.php", array());
    }

    elseif ($route->format == 'json')
    {
        if ($route->action == "list") {

            $processes = $process->get_process_list();

            // if a context type is specified, filter the processes
            if (isset($_GET['context'])) {
                $context_type = (int) $_GET['context'];
                // can be 0 for input or 1 for virtual feed
                if ($context_type < 0 || $context_type > 1) {
                    return array('content'=>false, 'error'=>'Invalid context type');
                }
                // filter the processes based on the context type
                return $process->filter_valid($processes, $context_type);
            } else {
                return $processes;
            }
        } else if ($route->action == "map") {
            // Return id_num to process function name map
            $processes = $process->get_process_list(); // Load modules modules
        
            // Build map of processids where set
            $process_map = array();
            foreach ($processes as $k=>$v) {
                if (isset($v['id_num'])) {
                    if (isset($process_map[$v['id_num']])) {
                        return array(
                            'success'=>false, 
                            'error'=>'Duplicate process id_num found: '.$v['id_num'],
                            'process'=>$k,
                            'existing_process'=>$process_map[$v['id_num']]
                        );
                    }
                    $process_map[$v['id_num']] = $k;
                }
            }
            return $process_map;
        }
    }

    return array('content'=>$result);
}
