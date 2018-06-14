<?php

    // TBD: support user target in message schema
    $mqttsettings = array(
        'userid' => 1
    );


    /*
    
    **MQTT input interface script**
    
    SERVICE INSTALL INSTRUCTIONS:
    https://github.com/emoncms/blob/master/docs/RaspberryPi/MQTT.md
    
    EXAMPLES:
    
    create an input from emonTx node called power with value 10:
        [basetopic]/emontx/power 10
    
    create an input from node 10 called power with value 10 :
        [basetopic]/10/power 10
        
    create input from emontx with key 0 of value 10
        [basetopic]/emontx 10
        
    create input from emontx with key 0 of value 10, key 1 of value 11 and key 2 of value 11
        [basetopic]/emontx 10,11,12

    * [basetopic] and user ID of target Emoncms account can be set in settings.php
    
    Emoncms then processes these inputs in the same way as they would be
    if sent to the HTTP Api.
    
    */

    // This code is released under the GNU Affero General Public License.
    // OpenEnergyMonitor project:
    // http://openenergymonitor.org
    
    define('EMONCMS_EXEC', 1);

    $fp = fopen("/var/lock/phpmqtt_input.lock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }
    
    chdir(dirname(__FILE__)."/../");
    require "Lib/EmonLogger.php";
    require "process_settings.php";
    
    set_error_handler('exceptions_error_handler');
    
    $log = new EmonLogger(__FILE__);
    $log->info("Starting MQTT Input script");
    
    if (!$mqtt_enabled) {
        //echo "Error MQTT input script: MQTT must be enabled in settings.php\n";
        $log->error("MQTT must be enabled in settings.php");
        die;
    }
    
    $mysqli = new mysqli($server,$username,$password,$database,$port);
    if ($mysqli->connect_error) { $log->error("Cannot connect to MYSQL database:". $mysqli->connect_error);  die('Check log\n'); }

    // Enable for testing
    // $mysqli->query("SET interactive_timeout=60;");
    // $mysqli->query("SET wait_timeout=60;");

    if ($redis_enabled) {
        $redis = new Redis();
        if (!$redis->connect($redis_server['host'], $redis_server['port'])) {
            $log->error("Cannot connect to redis at ".$redis_server['host'].":".$redis_server['port']);  die('Check log\n');
        }
        if (!empty($redis_server['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix']);
        if (!empty($redis_server['auth'])) {
            if (!$redis->auth($redis_server['auth'])) {
                $log->error("Cannot connect to redis at ".$redis_server['host'].", autentication failed"); die('Check log\n');
            }
        }
    } else {
        $redis = false;
    }
    
    require("Modules/user/user_model.php");
    $user = new User($mysqli,$redis,null);

    require_once "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $feed_settings);

    require_once "Modules/input/input_model.php";
    $input = new Input($mysqli,$redis, $feed);

    require_once "Modules/process/process_model.php";
    $process = new Process($mysqli,$input,$feed,$user->get_timezone($mqttsettings['userid']));

    $device = false;
    if (file_exists("Modules/device/device_model.php")) {
        require_once "Modules/device/device_model.php";
        $device = new Device($mysqli,$redis);
    }
    
    $mqtt_client = new Mosquitto\Client();
    
    $connected = false;
    $last_retry = 0;
    $last_heartbeat = time();
    $count = 0;
    
    $mqtt_client->onConnect('connect');
    $mqtt_client->onDisconnect('disconnect');
    $mqtt_client->onSubscribe('subscribe');
    $mqtt_client->onMessage('message');

    // Option 1: extend on this:
     while(true){
        try { 
            $mqtt_client->loop(); 
        } catch (Exception $e) {
            if ($connected) $log->error($e);
        }
        
        if (!$connected && (time()-$last_retry)>5.0) {
            $last_retry = time();
            try {
                $mqtt_client->setCredentials($mqtt_server['user'],$mqtt_server['password']);
                $mqtt_client->connect($mqtt_server['host'], $mqtt_server['port'], 60);
                $topic = $mqtt_server['basetopic']."/#";
                //echo "Subscribing to: ".$topic."\n";
                $log->info("Subscribing to: ".$topic);
                $mqtt_client->subscribe($topic,2);
            } catch (Exception $e) {
                $log->error($e);
            }
            //echo "Not connected, retrying connection\n";
            $log->warn("Not connected, retrying connection");
        }
        
        if ((time()-$last_heartbeat)>300) {
            $last_heartbeat = time();
            $log->info("$count Messages processed in last 5 minutes");
            $count = 0;
            
            // Keep mysql connection open with periodic ping
            if (!$mysqli->ping()) {
                $log->warn("mysql ping false");
                die;
            }
        }
        
        usleep(1000);
    }
    

    function connect($r, $message) {
        global $log, $connected;
        $connected = true;
        //echo "Connected to MQTT server with code {$r} and message {$message}\n";
        $log->warn("Connecting to MQTT server: {$message}: code: {$r}");
    }

    function subscribe() {
        global $log, $topic;
        //echo "Subscribed to topic: ".$topic."\n";
        $log->info("Subscribed to topic: ".$topic);
    }

    function unsubscribe() {
        global $log, $topic;
        //echo "Unsubscribed from topic:".$topic."\n";
        $log->error("Unsubscribed from topic: ".$topic);
    }

    function disconnect() {
        global $connected, $log;
        $connected = false;
        //echo "Disconnected cleanly\n";
        $log->info("Disconnected cleanly");
    }

    function message($message)
    {
        try {
            $jsoninput = false;
            $topic = $message->topic;
            $value = $message->payload;
            
            global $mqtt_server, $user, $input, $process, $device, $log, $count;

            //Check and see if the input is a valid JSON and when decoded is an array. A single number is valid JSON.
            $jsondata = json_decode($value,true,2);
            if ((json_last_error() === JSON_ERROR_NONE) && is_array($jsondata)) {
                // JSON is valid - is it an array
                $jsoninput = true;
                $log->info("MQTT Valid JSON found ");
                //Create temporary array and change all keys to lower case to look for a 'time' key
                $jsondataLC = array_change_key_case($jsondata);

                #If JSON check to see if there is a time value else set to time now.
                if (array_key_exists('time',$jsondataLC)){
                    $time = $jsondataLC['time'];
                    if (is_string($time)){
                        if (($timestamp = strtotime($time)) === false) {
                            //If time string is not valid, use system time.
                            $time = time();
                            $log->warn("Time string not valid ".$time);
                        } else {
                            $log->info("Valid time string used ".$time);
                            $time = $timestamp;
                        }
                    } else {
                        $log->info("Valid time in seconds used ".$time);
                        //Do nothings as it has been assigned to $time as a value
                    }
                } else {
                    $log->info("No time element found in JSON - System time used");
                    $time = time();
                }
            } else {
                $jsoninput = false;
                $log->info("No JSON found - System time used");
                $time = time();
            }

            $log->info($topic." ".$value);
            $count ++;
            
            #Emoncms user ID TBD: incorporate on message via authentication mechanism
            global $mqttsettings;
            $userid = $mqttsettings['userid'];
            
            $inputs = array();
            
            $route = explode("/",$topic);
            $basetopic = explode("/",$mqtt_server['basetopic']);

            /*Iterate over base topic to determine correct sub-topic*/
            $st=-1;
            foreach ($basetopic as $subtopic) {
                if(isset($route[$st+1])) {
                    if($basetopic[$st+1]==$route[$st+1]) {
                        $st = $st + 1;
                    } else {
                        break;
                    }
                } else {
                    $log->error("MQTT base topic is longer than input topics! Will not produce any inputs! Base topic is ".$mqtt_server['basetopic'].". Topic is ".$topic.".");
                }
            }
     
            if ($st>=0)
            {
                if (isset($route[$st+1]))
                {
                    $nodeid = $route[$st+1];
                    $dbinputs = $input->get_inputs($userid);

                    if ($jsoninput) {
                        foreach ($jsondata as $key=>$value) {
                            $inputs[] = array("userid"=>$userid, "time"=>$time, "nodeid"=>$nodeid, "name"=>$key, "value"=>$value);
                        }
                    } else if (isset($route[$st+2])) {
                        $inputs[] = array("userid"=>$userid, "time"=>$time, "nodeid"=>$nodeid, "name"=>$route[$st+2], "value"=>$value);
                    }
                    else
                    {
                        $values = explode(",",$value);
                        $name = 0;
                        foreach ($values as $value) {
                            $inputs[] = array("userid"=>$userid, "time"=>$time, "nodeid"=>$nodeid, "name"=>$name++, "value"=>$value);
                        }
                    }
                }
            } else {
                $log->error("No matching MQTT topics! None or null inputs will be recorded!");  
            }
            
            // Enabled in device-support branch
            // if (!isset($dbinputs[$nodeid])) {
            //     $dbinputs[$nodeid] = array();
            //     if ($device && method_exists($device,"create")) $device->create($userid,$nodeid);
            // }

            $tmp = array();
            foreach ($inputs as $i)
            {
                $userid = $i['userid'];
                $time = $i['time'];
                $nodeid = $i['nodeid'];
                $name = $i['name'];
                $value = $i['value'];
                
                // Automatic device configuration using device module if 'describe' keyword found
                if (strtolower($name)=="describe") {
                    if ($device && method_exists($device,"autocreate")) {
                        $result = $device->autocreate($userid,$nodeid,$value);
                        $log->info(json_encode($result));
                    }
                }
                else 
                {
                    if (!isset($dbinputs[$nodeid][$name])) {
                        $inputid = $input->create_input($userid, $nodeid, $name);
                        if (!$inputid) {
                            $log->warn("error creating input"); die;
                        }
                        $dbinputs[$nodeid][$name] = true;
                        $dbinputs[$nodeid][$name] = array('id'=>$inputid);
                        $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                    } else {
                        $inputid = $dbinputs[$nodeid][$name]['id'];
                        $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                        
                        if ($dbinputs[$nodeid][$name]['processList']) $tmp[] = array('value'=>$value,'processList'=>$dbinputs[$nodeid][$name]['processList']);
                    }
                }
            }
            
            foreach ($tmp as $i) $process->input($time,$i['value'],$i['processList']);
            
        } catch (Exception $e) {
            $log->error($e);
        }
    }
    
    
    function exceptions_error_handler($severity, $message, $filename, $lineno) {
        if (error_reporting() == 0) {
            return;
        }
        if (error_reporting() & $severity) {
            throw new ErrorException($message, 0, $severity, $filename, $lineno);
        }
    }
