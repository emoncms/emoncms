<?php
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
    
    if (!$mqtt_enabled) { echo "Error: setting must be true: mqtt_enabled\n"; die; }
    
    $log = new EmonLogger(__FILE__);
    $log->info("Starting MQTT Input script");
    
    $mysqli = @new mysqli($server,$username,$password,$database);
    if ($mysqli->connect_error) { $log->error("Can't connect to database:". $mysqli->connect_error);  die('Check log\n'); }

    if ($redis_enabled) {
        $redis = new Redis();
        if (!$redis->connect($redis_server['host'], $redis_server['port'])) { 
            $log->error("Could not connect to redis at ".$redis_server['host'].":".$redis_server['port']);  die('Check log\n'); 
        }
        if (!empty($redis_server['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix']);
        if (!empty($redis_server['auth'])) {
            if (!$redis->auth($redis_server['auth'])) { 
                $log->error("Could not connect to redis at ".$redis_server['host'].", autentication failed"); die('Check log\n');
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
    $process = new Process($mysqli,$input,$feed,$user->get_timezone($mqtt_server['userid']));

    $mqtt_client = new Mosquitto\Client();
    $mqtt_client->setCredentials($mqtt_server['user'],$mqtt_server['password']);
    
    $connected = false;
    $mqtt_client->onConnect('connect');
    $mqtt_client->onDisconnect('disconnect');
    $mqtt_client->onSubscribe('subscribe');
    $mqtt_client->onMessage('message');
    $mqtt_client->connect($mqtt_server['host'], $mqtt_server['port'], 5);

    $topic = $mqtt_server['basetopic']."/#";
    echo "Subscribing to: ".$topic."\n";
    $mqtt_client->subscribe($topic,2);

    while(true){
        $mqtt_client->loop();
        
    }

    function connect($r, $message) {
        global $connected;
        $connected = true;
    	echo "I got code {$r} and message {$message}\n";
    }

    function subscribe() {
	echo "Subscribed to a topic\n";
    }

    function unsubscribe() {
	echo "Unsubscribed from a topic\n";
    }

    function disconnect() {
        global $connected;
        $connected = false;
	echo "Disconnected cleanly\n";
    }

    function message($message)
    { 
        $topic = $message->topic;
        $value = $message->payload;
        
        $time = time();
        echo $topic." ".$value."\n";
        
        global $mqtt_server, $user, $input, $process, $feed;
        
        $userid = $mqtt_server['userid'];
        
        $inputs = array();
        
        $route = explode("/",$topic);

        if ($route[0]==$mqtt_server['basetopic'])
        {
            // nodeid defined in topic:  [bsaetopic]/input/10
            if (isset($route[1]))
            {
                $nodeid = $route[1];
                $dbinputs = $input->get_inputs($userid);
            
                // input id defined in topic:  emoncms/input/10/1
                if (isset($route[2]))
                {
                    $inputs[] = array("userid"=>$userid, "time"=>$time, "nodeid"=>$nodeid, "name"=>$route[2], "value"=>$value);
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
        }
        
        $tmp = array();
        foreach ($inputs as $i)
        {
            $userid = $i['userid'];
            $time = $i['time'];
            $nodeid = $i['nodeid'];
            $name = $i['name'];
            $value = $i['value'];
            
            if (!isset($dbinputs[$nodeid][$name])) {
                $inputid = $input->create_input($userid, $nodeid, $name);
                $dbinputs[$nodeid][$name] = true;
                $dbinputs[$nodeid][$name] = array('id'=>$inputid);
                $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                echo "set_timevalue\n";
            } else {
                $inputid = $dbinputs[$nodeid][$name]['id'];
                $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                
                if ($dbinputs[$nodeid][$name]['processList']) $tmp[] = array('value'=>$value,'processList'=>$dbinputs[$nodeid][$name]['processList']);
            }
        }
        
        foreach ($tmp as $i) $process->input($time,$i['value'],$i['processList']);
      
    }
