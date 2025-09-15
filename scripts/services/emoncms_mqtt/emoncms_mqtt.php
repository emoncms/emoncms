<?php
    /*

    **MQTT input interface script**

    SERVICE INSTALL INSTRUCTIONS:
    https://github.com/emoncms/emoncms/blob/master/docs/RaspberryPi/MQTT.md

    EXAMPLES:

    // Existing examples:
    create an input from emonTx node called power with value 10:
        [basetopic]/emontx/power 10

    create an input from node 10 called power with value 10 :
        [basetopic]/10/power 10

    create input from emontx with key 0 of value 10
        [basetopic]/emontx 10

    create input from emontx with key 0 of value 10, key 1 of value 11 and key 2 of value 11
        [basetopic]/emontx 10,11,12

    * [basetopic] and user ID of target Emoncms account can be set in settings.php

    // Additional supported formats:

    // 1. JSON object with key-value pairs:
        [basetopic]/emontx {"power":10,"vrms":230.1}

    // 2. JSON object with time (as number or string):
        [basetopic]/emontx {"power":10,"vrms":230.1,"time":1720080000}
        [basetopic]/emontx {"power":10,"time":"2025-07-04T12:00:00Z"}

    // 3. JSON object with nested {name, value} objects:
        [basetopic]/emontx {"power":{"name":"ct1","value":10},"vrms":{"value":230.1}}

    // 4. Device auto-configuration (if 'describe' key is present):
        [basetopic]/emontx {"describe":"..."}

    Emoncms then processes these inputs in the same way as they would be
    if sent to the HTTP Api.

    */

    // This code is released under the GNU Affero General Public License.
    // OpenEnergyMonitor project:
    // http://openenergymonitor.org

    define('EMONCMS_EXEC', 1);

    $fp = fopen("/var/lock/emoncms_mqtt.lock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

    chdir(dirname(__FILE__)."/../../../");
    require "Lib/EmonLogger.php";
    require "core.php";
    require "process_settings.php";

    set_error_handler('exceptions_error_handler');

    $log = new EmonLogger(__FILE__);
    $log->warn("Starting MQTT Input script");

    if (!$settings["mqtt"]["enabled"]) {
        //echo "Error MQTT input script: MQTT must be enabled in settings.php\n";
        $log->error("MQTT must be enabled in settings.php");
        die;
    }

    require("Modules/user/user_model.php");
    require_once "Modules/feed/feed_model.php";
    require_once "Modules/input/input_model.php";
    require_once "Modules/process/process_model.php";

    $device_module_exists = false;
    if (file_exists("Modules/device/device_model.php")) {
        require_once "Modules/device/device_model.php";
        $device_module_exists = true;
    }

    $connected = false;
    $subscribed = 0;
    $last_retry = 0;
    $last_heartbeat = time();
    $count = 0;
    $pub_count = 0; // used to reduce load relating to checking for messages to be published

    // Connect to redis if enabled
    if ($settings['redis']['enabled']) {
        $redis = new Redis();
        if (!$redis->connect($settings['redis']['host'], $settings['redis']['port'])) {
            $log->error("Cannot connect to redis at ".$settings['redis']['host'].":".$settings['redis']['port']);  die('Check log\n');
        }
        if (!empty($settings['redis']['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $settings['redis']['prefix']);
        if (!empty($settings['redis']['auth'])) {
            if (!$redis->auth($settings['redis']['auth'])) {
                $log->error("Cannot connect to redis at ".$settings['redis']['host'].", autentication failed"); die('Check log\n');
            }
        }
    } else {
        $redis = false;
    }

    /*
        new Mosquitto\Client($id,$cleanSession)
        $id (string) – The client ID. If omitted or null, one will be generated at random.
        $cleanSession (boolean) – Set to true to instruct the broker to clean all messages and subscriptions on disconnect. Must be true if the $id parameter is null.
    */
    $mqtt_client = new Mosquitto\Client($settings['mqtt']['client_id'],true);
    $mqtt_client->onConnect('connect');
    $mqtt_client->onDisconnect('disconnect');
    $mqtt_client->onSubscribe('subscribe');
    $mqtt_client->onMessage('message');

    $mysqli_connected = false;

    // Option 1: extend on this:
    while(true) {
        // Ensure we are connected to mysql
        if ($mysqli_connected === false) {
            // Try to connect to mysql
            $mysqli = @new mysqli(
                $settings["sql"]["server"],
                $settings["sql"]["username"],
                $settings["sql"]["password"],
                $settings["sql"]["database"],
                $settings["sql"]["port"]
            );

            if ($mysqli->connect_error) {
                $log->error("Cannot connect to MYSQL database:". $mysqli->connect_error);
                sleep(5.0);
                continue;
            } else {
                $mysqli_connected = true;
            }

            // Recreate all model objects with new mysqli connection
            $user = new User($mysqli, $redis, null);
            $feed = new Feed($mysqli, $redis, $settings['feed']);
            $input = new Input($mysqli, $redis, $feed);

            $timezone = 'UTC';
            if (!$settings["mqtt"]["multiuser"]) {
                $timezone = $user->get_timezone($settings["mqtt"]["userid"]);
            }

            $process = new Process($mysqli, $input, $feed, $timezone);
            if ($device_module_exists !== false) {
                $device = new Device($mysqli, $redis);
            }
            $log->info("Successfully reconnected to MySQL and reloaded all objects");
        }
        
        try {
            $mqtt_client->loop();
        } catch (Exception $e) {
            if ($connected) $log->error($e);
        }

        if (!$connected && (time()-$last_retry)>5.0) {
            $subscribed = 0;
            $last_retry = time();
            try {
                // SUBSCRIBE
                $log->warn("Not connected, retrying connection");
                $mqtt_client->setCredentials($settings['mqtt']['user'],$settings['mqtt']['password']);
                if(isset($settings['mqtt']['capath']) && $settings['mqtt']['capath'] !== null) {
                    $log->warn("mqtt: using ssl");
                    if(isset($settings['mqtt']['certpath']) && $settings['mqtt']['certpath'] !== null && isset($settings['mqtt']['keypath']) && $settings['mqtt']['keypath'] !== null) {
                        if(isset($settings['mqtt']['keypw']) && $settings['mqtt']['keypw'] !== null) {
                            // To run setTlsCertificates with capath, certpath, keypath and keypw if they are provided
                            $mqtt_client->setTlsCertificates($settings['mqtt']['capath'],
                                                             $settings['mqtt']['certpath'],
                                                             $settings['mqtt']['keypath'],
                                                             $settings['mqtt']['keypw']);
                        } else {
                            // To run setTlsCertificates with capath, certpath and keypath if they are provided and not keypw
                            $mqtt_client->setTlsCertificates($settings['mqtt']['capath'],
                                                             $settings['mqtt']['certpath'],
                                                             $settings['mqtt']['keypath']);
                        }
                    } else {
                        // To run setTlsCertificates with capath only, if nothing else is provided
                        $mqtt_client->setTlsCertificates($settings['mqtt']['capath']);
                    }
                }
                $mqtt_client->connect($settings['mqtt']['host'], $settings['mqtt']['port'], 5);
                // moved subscribe to onConnect callback

            } catch (Exception $e) {
                $log->error($e);
                $subscribed = 0;
            }
        }

        // PUBLISH
        // loop through all queued items in redis
        if ($redis !== false && $connected && $pub_count>10) {
            $pub_count = 0;
            $publish_to_mqtt = $redis->hgetall("publish_to_mqtt");
            foreach ($publish_to_mqtt as $topic=>$value) {
                $redis->hdel("publish_to_mqtt",$topic);
                $mqtt_client->publish($topic, $value);
            }
            // Queue option
            $queue_topic = 'mqtt-pub-queue';
            for ($i=0; $i<$redis->llen($queue_topic); $i++) {
                if ($connected && $data = filter_var_array(json_decode($redis->lpop($queue_topic), true))) {
                    $mqtt_client->publish($data['topic'], json_encode(array("time"=>$data['time'],"value"=>$data['value'])));
                }
            }
        }
        $pub_count++;

        if ((time() - $last_heartbeat) > 300) {
            $last_heartbeat = time();
            $log->info("$count Messages processed in last 5 minutes");
            if (isset($settings['mqtt']['pub_count']) && $settings['mqtt']['pub_count']) {
                $topic = $settings['mqtt']['basetopic'] . "/emoncms/mqtt_msg_count_5min";
                $mqtt_client->publish($topic, $count);
            }
            $count = 0;
            
            // Check mysql connection and recreate objects if needed
            try {
                $result = $mysqli->query("SELECT 1");
                if (!$result) {
                    throw new Exception("Connection lost");
                }
                $result->close();
            } catch (Exception $e) {
                $log->warn("MySQL connection lost, attempting to reconnect and reload objects");
                $mysqli->close();
                $mysqli_connected = false;
            }
        }

        usleep(10000);
    }

    function connect($r, $message) {
        global $log, $connected, $settings, $mqtt_client, $subscribed;
        //echo "Connected to MQTT server with code {$r} and message {$message}\n";
        $log->warn("Connecting to MQTT server: {$message}: code: {$r}");
        if( $r==0 ) {
            // if CONACK is zero
            $connected = true;
            if ($subscribed==0) {
                $topic = $settings['mqtt']['basetopic']."/#";
                $subscribed = $mqtt_client->subscribe($topic,2);
                $log->info("Subscribed to: ".$topic." ID - ".$subscribed);
            }
        } else {
            $subscribed = 0;
            $log->error('unexpected connection problem mqtt server:'.$message);
        }
    }

    function subscribe() {
        global $log, $topic;
        //echo "Subscribed to topic: ".$topic."\n";
        $log->info("Callback subscribed to topic: ".$topic);
    }

    function unsubscribe() {
        global $log, $topic, $subscribed;
        //echo "Unsubscribed from topic:".$topic."\n";
        $subscribed = 0;
        $log->error("Unsubscribed from topic: ".$topic);
    }

    function disconnect() {
        global $connected, $log, $subscribed;
        $subscribed = 0;
        $connected = false;
        //echo "Disconnected cleanly\n";
        $log->warn("MQTT has disconnected - cleanly");
    }

    function message($message)
    {
        try {
            $jsoninput = false;
            $topic = $message->topic;
            $value = $message->payload;
            $time = time();

            global $settings, $user, $input, $process, $device, $log, $count;

            //remove characters that emoncms topics cannot handle
            $topic = str_replace(":","",$topic);

            //Check and see if the input is a valid JSON and when decoded is an array. A single number is valid JSON.
            $jsondata = json_decode($value,true,3);
            if ((json_last_error() === JSON_ERROR_NONE) && is_array($jsondata)) {
                // JSON is valid - is it an array
                $jsoninput = true;
                $log->info("MQTT Valid JSON found ");
                //Create temporary array and change all keys to lower case to look for a 'time' key
                $jsondataLC = array_change_key_case($jsondata);

                // If JSON, check to see if there is a time value else set to time now.
                if (array_key_exists('time',$jsondataLC)){
                    $inputtime = $jsondataLC['time'];

                    // validate time
                    if (is_numeric($inputtime)){
                        $log->info("Valid time in seconds used ".$inputtime);
                        $time = (int) $inputtime;
                        unset($jsondata["time"]);
                    } elseif (is_string($inputtime)){
                        if (($timestamp = strtotime($inputtime)) === false) {
                            //If time string is not valid, use system time.
                            $log->warn("Time string not valid ".$inputtime);
                            $time = time();
                        } else {
                            $log->info("Valid time string used ".$inputtime);
                            $time = $timestamp;
                            unset($jsondata["time"]);
                        }
                    } else {
                        $log->warn("Time value not valid ".json_encode($inputtime));
                        $time = time();
                        unset($jsondata["time"]);
                    }
                } else {
                    $log->info("No time element found in JSON - System time used");
                    $time = time();
                }
            } else {
                $jsoninput = false;
                $time = time();
            }

            $log->info($topic." ".($jsoninput ? json_encode($jsondata) : $value));
            $count ++;

            $inputs = array();

            // 1. Filter out basetopic
            $topic = str_replace($settings['mqtt']['basetopic']."/","",$topic);
            // 2. Split by /
            $route = explode("/",$topic);
            $route_len = count($route);

            if ($route_len>=1) {

                if ($settings["mqtt"]["multiuser"]) {
                    // Userid is first entry
                    $userid = (int) $route[0];
                    // Node id is second entry
                    $nodeid = $route[1];
                    // minimum route length is 3 /emon/userid/nodeid
                    $min_route_len = 3;
                } else {
                    // Userid from settings
                    $userid = $settings["mqtt"]["userid"];
                    // Node id is first entry
                    $nodeid = $route[0];
                    // minimum route length is 2 /emon/nodeid
                    $min_route_len = 2;
                }
                // Filter nodeid, pre input create, to avoid duplicate inputs
                $nodeid = preg_replace('/[^\p{N}\p{L}_\s\-.]/u','',$nodeid);

                $dbinputs = $input->get_inputs($userid);

                if ($jsoninput) {
                    $input_name = "";
                    if ($route_len>=$min_route_len) {
                        // Input name is all the remaining parts connected together with _ and
                        // added to front of input name.
                        $input_name_parts = array();
                        for ($i=$min_route_len-1; $i<$route_len; $i++) $input_name_parts[] = $route[$i];
                        $input_name = implode("_",$input_name_parts)."_";
                    }
                    foreach ($jsondata as $key=>$value) {
                        // Unbox { name: xxx, value: xxx }
                        if (is_array($value) && array_key_exists("value", $value)) {
                            if (array_key_exists("name", $value) && $value["name"])
                                $key .= '_'.$value["name"];
                            $value = $value["value"];
                        }

                        if (is_scalar($value)) {
                            $inputs[] = array("userid"=>$userid, "time"=>$time, "nodeid"=>$nodeid, "name"=>$input_name.$key, "value"=>$value);
                        } else {
                            $log->warn("Unable to unpack JSON, not recording ".$key." : ".json_encode($value));
                            continue;
                        }
                    }
                } elseif ($route_len>=$min_route_len) {
                    // Input name is all the remaining parts connected together
                    $input_name_parts = array();
                    for ($i=$min_route_len-1; $i<$route_len; $i++) $input_name_parts[] = $route[$i];
                    $input_name = implode("_",$input_name_parts);

                    $inputs[] = array("userid"=>$userid, "time"=>$time, "nodeid"=>$nodeid, "name"=>$input_name, "value"=>$value);
                }
                else
                {
                    $values = explode(",",$value);
                    $name = 0;
                    foreach ($values as $value) {
                        $inputs[] = array("userid"=>$userid, "time"=>$time, "nodeid"=>$nodeid, "name"=>$name++, "value"=>$value);
                    }
                }
            } else {
                $log->error("No matching MQTT topics! None or null inputs will be recorded!");
            }

            if (!isset($dbinputs[$nodeid])) {
                $dbinputs[$nodeid] = array();
                if ($device && method_exists($device,"create")) $device->create($userid,$nodeid,null,null,null);
            }

            $tmp = array();
            foreach ($inputs as $i)
            {
                $userid = $i['userid'];
                $time = $i['time'];
                $nodeid = $i['nodeid'];
                $name = $i['name'];
                $value = $i['value'];

                if (!is_numeric($value)) $value = null;

                if ($settings["mqtt"]["multiuser"]) {
                    $process->timezone = $user->get_timezone($userid);
                }
                // Filter name, pre input create, to avoid duplicate inputs
                $name = preg_replace('/[^\p{N}\p{L}_\s\-.]/u','',$name);

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
                        $dbinputs[$nodeid][$name] = array('id'=>$inputid);
                        $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                    } else {
                        $inputid = $dbinputs[$nodeid][$name]['id'];
                        $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);

                        if ($dbinputs[$nodeid][$name]['processList']) {
                            $tmp[] = array(
                                'value'=>$value,
                                'processList'=>$dbinputs[$nodeid][$name]['processList'],
                                'opt'=>array(
                                    'sourcetype' => ProcessOriginType::INPUT,
                                    'sourceid'=>$inputid
                                )
                            );
                        }
                    }
                }
            }

            foreach ($tmp as $i) $process->input($time,$i['value'],$i['processList'],$i['opt']);

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
