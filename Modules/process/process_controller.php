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

    global $mysqli, $redis, $user, $session, $route, $feed_settings;

    // There are no actions in the input module that can be performed with less than write privileges
    if (!$session['write']) return array('content'=>false);

    $result = false;

    require_once "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $feed_settings);

    require_once "Modules/input/input_model.php"; 
    $input = new Input($mysqli,$redis, $feed);

    require_once "Modules/process/process_model.php";
    $process = new Process($mysqli,$input,$feed,$user->get_timezone($session['userid']));
    
    if ($route->format == 'html')
    {
        if ($route->action == 'api') $result = view("Modules/process/Views/process_api.php", array());
    }

    else if ($route->format == 'json')
    {
        if ($route->action == "list") $result = $process->get_process_list();
    }

    return array('content'=>$result);
}
