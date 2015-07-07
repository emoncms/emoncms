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
        if ($route->action == 'api') $result = view("Modules/schedule/Views/schedule_api.php", array());
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
                // if public or belongs to user
                if ($session['read'] && ($scheduleget['public'] || ($session['userid']>0 && $scheduleget['userid']==$session['userid'])))
                {
                    if ($route->action == "get") $result = $scheduleget;
                    if ($route->action == "expression") $result = $schedule->get_expression($scheduleid);
                    if ($route->action == "test") $result = $schedule->test_expression($scheduleid);
                }
                // if public
                if (isset($session['write']) && $session['write'] && $session['userid']>0 && $scheduleget['userid']==$session['userid']) {
                    if ($route->action == "delete") $result = $schedule->delete($scheduleid );
                    if ($route->action == 'set') $result = $schedule->set_fields($scheduleid ,get('fields'));
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