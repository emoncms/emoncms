<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function device_controller()
{
    global $session,$route,$mysqli,$user,$redis;

    $result = false;

    require_once "Modules/device/device_model.php";
    $device = new Device($mysqli,$redis);

    if ($route->format == 'html')
    {
        if ($route->action == "view" && $session['write']) {
            $devices_templates = $device->get_templates();
            $result = view("Modules/device/Views/device_view.php",array('devices_templates'=>$devices_templates));
        }
        if ($route->action == 'api') $result = view("Modules/device/Views/device_api.php", array());
    }

    if ($route->format == 'json')
    {
        if ($route->action == "mqttauth") {
            $ip = $_SERVER['REMOTE_ADDR'];
            
            $redis->lpush("device_mqttauth",$ip);
            
            //if ($ip=="192.168.0.104") {
                global $mqtt_server;
                $result = $mqtt_server["user"].":".$mqtt_server["password"].":".$mqtt_server["basetopic"];
                $route->format = "text";
            //} else {
            //    $route->format = "text";
            //    $result = "request registered";
            //}
        }
        // Used in conjunction with input name describe to auto create device
        else if ($route->action == "autocreate") {
            if ($session['userid']>0 && $session['write']) $result = $device->autocreate($session['userid'],get('nodeid'),get('type'));
        }
        else if ($route->action == 'list') {
            if ($session['userid']>0 && $session['write']) $result = $device->get_list($session['userid']);
        }
        elseif ($route->action == "create") {
            if ($session['userid']>0 && $session['write']) $result = $device->create($session['userid']);
        }
        elseif ($route->action == "listtemplates") {
            if ($session['userid']>0 && $session['write']) $result = $device->get_templates();
        }
        elseif ($route->action == "listtemplatenames") {
            if ($session['userid']>0 && $session['write']) { 
                $devices_templates = $device->get_templates();
            
              	$devices = array();
	              foreach($devices_templates as $key => $value)
	              {
		              $devices[$key] = ((!isset($value->name) || $value->name == "" ) ? $key : $value->name);
	              }
	              $result = $devices;
            }
        }
        else {
            $deviceid = (int) get('id');
            if ($device->exist($deviceid)) // if the feed exists
            {
                $deviceget = $device->get($deviceid);
                if (isset($session['write']) && $session['write'] && $session['userid']>0 && $deviceget['userid']==$session['userid']) {
                    if ($route->action == "get") $result = $deviceget;
                    if ($route->action == "delete") $result = $device->delete($deviceid);
                    if ($route->action == 'set') $result = $device->set_fields($deviceid, get('fields'));
                    if ($route->action == 'inittemplate') $result = $device->init_template($deviceid);
                }
            }
            else
            {
                $result = array('success'=>false, 'message'=>'Device does not exist');
            }
        }     
    }

    return array('content'=>$result);
}
