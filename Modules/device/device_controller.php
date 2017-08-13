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
            $device_templates = $device->get_template_list_short();
            $result = view("Modules/device/Views/device_view.php",array('devices'=>$device_templates));
        }
        if ($route->action == 'api') $result = view("Modules/device/Views/device_api.php", array());
    }

    if ($route->format == 'json')
    {
        // ---------------------------------------------------------------
        // Method for sharing authentication details with a node
        // that does not require copying and pasting passwords and apikeys
        // 1. device requests authentication - reply "request registered"
        // 2. notification asks user whether to allow or deny device
        // 3. user clicks on allow
        // 4. device makes follow up request for authentication
        //    - reply authentication details
        // ---------------------------------------------------------------
        if ($redis && $route->action == "auth") {
            // 1. Register request for authentication details, or provide if allowed
            if ($route->subaction=="request") {
                $ip = $_SERVER['REMOTE_ADDR'];
                
                $allow_ip = $redis->get("device_auth_allow");
                // Only show authentication details to allowed ip address
                if ($allow_ip==$ip) {
                    $redis->del("device_auth_allow");
                    global $mqtt_server;
                    $result = $mqtt_server["user"].":".$mqtt_server["password"].":".$mqtt_server["basetopic"];
                } else {
                    $redis->set("device_auth",json_encode(array("ip"=>$ip)));
                    $result = "request registered";
                }
                $route->format = "text";
            }
            // 2. User checks for device waiting for authentication
            else if ($route->subaction=="check" && $session['write']) {
                if ($device_auth = $redis->get("device_auth")) {
                    $result = json_decode($device_auth);
                } else {
                    $result = "no devices";
                }
            }
            // 3. User allows device to receive authentication details
            else if ($route->subaction=="allow" && $session['write']) {
                 $ip = get("ip");
                 $redis->set("device_auth_allow",$ip);    // Temporary availability of auth for device ip address
                 $redis->expire("device_auth_allow",60);  // Expire after 60 seconds
                 $redis->del("device_auth");
                 $result = true;
            }
        }
        else if ($route->action == 'list') {
            if ($session['userid']>0 && $session['write']) $result = $device->get_list($session['userid']);
        }
        elseif ($route->action == "create") {
        	if ($session['userid']>0 && $session['write']) $result = $device->create($session['userid'],get("nodeid"),get("name"),get("description"),get("type"));
        }
        // Used in conjunction with input name describe to auto create device
        else if ($route->action == "autocreate") {
            if ($session['userid']>0 && $session['write']) $result = $device->autocreate($session['userid'],get('nodeid'),get('type'));
        }
        elseif ($route->action == "template" && $route->subaction != "init") {
            if ($route->subaction == "list") {
                if ($session['userid']>0 && $session['write']) $result = $device->get_template_list();
            }
            elseif ($route->subaction == "listshort") {
                if ($session['userid']>0 && $session['write']) $result = $device->get_template_list_short();
            }
            else if ($route->subaction == "get") {
                if ($session['userid']>0 && $session['write']) $result = $device->get_template(get('device'));
            }
        }
        else {
            $deviceid = (int) get('id');
            if ($device->exist($deviceid)) // if the feed exists
            {
                $deviceget = $device->get($deviceid);
                if (isset($session['write']) && $session['write'] && $session['userid']>0 && $deviceget['userid']==$session['userid']) {
                    if ($route->action == "get") $result = $deviceget;
                    else if ($route->action == "delete") $result = $device->delete($deviceid);
                    else if ($route->action == 'set') $result = $device->set_fields($deviceid, get('fields'));
                    else if ($route->action == 'template' && $route->subaction == 'init') {
                        if (isset($_GET['type'])) {
                            $device->set_fields($deviceid, json_encode(array("type"=>$_GET['type'])));
                        }
                        $result = $device->init_template($deviceid);
                    }
                }
            }
            else {
                $result = array('success'=>false, 'message'=>'Device does not exist');
            }
        }     
    }

    return array('content'=>$result);
}
