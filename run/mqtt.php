<?php

    $mqttsettings = array(
        'userid' => 1,
        'basetopic' => "emonhub/rx"
    );

    /*
    
    MQTT input interface script, subscribes to topic emoncms/input
    
    Topics:

    emoncms/input/10            100,200,300
    emoncms/input/10/1          100
    emoncms/input/10/power      250
    emoncms/input/house/power   2500
    
    * userid has to be set in script, no method of setting timestamp
    
    Emoncms then processes these inputs in the same way as they would be
    if sent to the HTTP Api.
    
    */

    // This code is released under the GNU Affero General Public License.
    // OpenEnergyMonitor project:
    // http://openenergymonitor.org
    
    define('EMONCMS_EXEC', 1);

    $fp = fopen("runlock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }
    
    chdir("/var/www/emoncms");
    
    require "Modules/log/EmonLogger.php";
    
    require "process_settings.php";
    $mysqli = @new mysqli($server,$username,$password,$database);
    $redis = new Redis();
    $redis->connect("127.0.0.1");
    
    require("Lib/phpMQTT.php");
    $mqtt = new phpMQTT("127.0.0.1", 1883, "Emoncms input subscriber");
    
    require("Modules/user/user_model.php");
    $user = new User($mysqli,$redis,null);
    
    include "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis,$feed_settings);

    require "Modules/input/input_model.php"; // 295
    $input = new Input($mysqli,$redis, $feed);

    require "Modules/input/process_model.php"; // 886
    $process = new Process($mysqli,$input,$feed);
  
    if(!$mqtt->connect()){
	    exit(1);
    }

    $topic = $mqttsettings['basetopic']."/#";
    print "Subscribing to: ".$topic."\n";
    
    $topics[$topic] = array("qos"=>0, "function"=>"procmsg");
    $mqtt->subscribe($topics,0);
    while($mqtt->proc()){ }
    $mqtt->close();
    
    function procmsg($topic,$values)
    { 
        global $mqttsettings, $user, $input, $process, $feed;
        $userid = $mqttsettings['userid'];
        
        $time = time();
        print $topic." ".$values."\n";
        $topic_parts = explode("/",$topic);
        
        
        if (count($topic_parts)==4 && $topic_parts[0]=="emonhub" && $topic_parts[1]=="rx" && $topic_parts[3]=="values")
        {
            $nodeid = (int) $topic_parts[2];
            $values = explode(",",$values);
            
            $name = 0;
            $tmp = array();
            $dbinputs = $input->get_inputs($userid);
            
            foreach ($values as $value)
            {
                if (!isset($dbinputs[$nodeid][$name])) {
                    $inputid = $input->create_input($userid, $nodeid, $name);
                    $dbinputs[$nodeid][$name] = true;
                    $dbinputs[$nodeid][$name] = array('id'=>$inputid);
                    $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                } else {
                    $inputid = $dbinputs[$nodeid][$name]['id'];
                    $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                    
                    if ($dbinputs[$nodeid][$name]['processList']) $tmp[] = array('value'=>$value,'processList'=>$dbinputs[$nodeid][$name]['processList']);
                }
                $name ++;
            }
            
            foreach ($tmp as $i) $process->input($time,$i['value'],$i['processList']);
        }
    }

