<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function schedule_controller()
{
    global $session,$route,$mysqli,$user;

    $result = false;

    require "Modules/schedule/schedule_model.php";
    $schedule = new Schedule($mysqli,$user->get_timezone($session['userid']));

    if ($route->format == 'html')
    {
        if ($route->action == "view" && $session['write']) $result = view("Modules/schedule/Views/schedule_view.php",array());
        if ($route->action == 'api') {
            require_once "Modules/schedule/schedule_api_obj.php";
            $api = array();
            foreach (schedule_api_obj() as $endpoint) { $endpoint['module'] = "schedule"; $api[] = $endpoint; }
            $result = view("Lib/api_explorer_view.php", array(
                "title"=>tr("Schedule API"),
                "sub"=>tr("Use the schedule API to manage the time of use schedules available to input and feed processing"),
                "api"=>$api, "show_docs_link"=>true, "standalone"=>true,
                "apikeys"=>session_apikeys()
            ));
        }
    }

    if ($route->format == 'json')
    {
        if ($route->action == 'list') {
            if ($session['userid']>0 && $session['userid'] && $session['read']) $result = $schedule->get_list($session['userid']);  
        }
        elseif ($route->action == "create") {
            if ($session['userid']>0 && $session['write']) $result = $schedule->create($session['userid']);
        }
        else {
            $scheduleid = (int) get('id');
            if ($schedule->exist($scheduleid)) // if the feed exists
            {
                $scheduleget = $schedule->get($scheduleid);
                if ($session['read'] && $session['userid']>0 && $scheduleget['userid']==$session['userid'])
                {
                    if ($route->action == "get") $result = $scheduleget;
                    if ($route->action == "expression") $result = $schedule->get_expression($scheduleid);
                    if ($route->action == "test") $result = $schedule->test_expression($scheduleid);
                }
                // if public
                if (isset($session['write']) && $session['write'] && $session['userid']>0 && $scheduleget['userid']==$session['userid']) {
                    if ($route->action == "delete") $result = $schedule->delete($scheduleid );
                    if ($route->action == 'set') $result = $schedule->set_fields($scheduleid,get('fields'));
                }
            }
            else
            {
                $result = array('success'=>false, 'message'=>'Schedule does not exist');
            }
        }           
    }

    return array('content'=>$result);
}