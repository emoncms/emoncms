<?php

    /*
    
    MQTT input interface script, subscribes to topic emoncms/input
    
    Topics:
    
    emoncms/input           {"apikey": APIKEY, "time": UNIXTIMESTAMP, "node": 10, "data":[100,200,300]}
    ---------------------------------------------------------------------------------------------------
    emoncms/input/10        100,200,300
    emoncms/input/10/1      100
    emoncms/input/10/power  250
    
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
    error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
    $mysqli = @new mysqli($server,$username,$password,$database);
    $redis = new Redis();
    $redis->connect("127.0.0.1");
    
    require("Modules/user/user_model.php");
    $user = new User($mysqli,$redis,null);
    
    include "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis,$feed_settings);

    require "Modules/input/input_model.php"; // 295
    $input = new Input($mysqli,$redis, $feed);

    require "Modules/input/process_model.php"; // 886
    $process = new Process($mysqli,$input,$feed);
  
    require("Lib/phpMQTT.php");
    $mqtt = new phpMQTT("127.0.0.1", 1883, "emoncms-input");
    
    if(!$mqtt->connect()){
	    exit(1);
    }

    $topics['emoncms/#'] = array("qos"=>0, "function"=>"procmsg");
    $mqtt->subscribe($topics,0);
    while($mqtt->proc()){ }
    $mqtt->close();
    
    function procmsg($topic,$value)
    { 
        $time = time();
        print $topic." ".$value."\n";
        
        $userid = 12;
        global $user, $input, $process, $feed;
        
        $inputs = array();
        
        $route = explode("/",$topic);
        
        if ($route[0]=="emoncms")
        {
            if ($route[1]=="input")
            {
                // nodeid defined in topic:  emoncms/input/10
                if (isset($route[2]))
                {
                    $nodeid = $route[2];
                    $dbinputs = $input->get_inputs($userid);
                
                    // input id defined in topic:  emoncms/input/10/1
                    if (isset($route[3]))
                    {
                        $inputs[] = array("userid"=>$userid, "time"=>$time, "nodeid"=>$nodeid, "name"=>$route[3], "value"=>$value);
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
                else
                {
                    // emoncms/input
                    $m = json_decode($value);
                    $time = $m->time;
                    $nodeid = $m->node;
                    $data = $m->csv;
                    
                    $session = $user->apikey_session($m->apikey);
                    if ($session['write'])
                    {
                        $userid = $session['userid'];
                        $dbinputs = $input->get_inputs($userid);

                        $name = 0;
                        foreach ($data as $value) {
                            $inputs[] = array("userid"=>$userid, "time"=>$time, "nodeid"=>$nodeid, "name"=>$name++, "value"=>$value);
                        } 
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
                print "set_timevalue\n";
            } else {
                $inputid = $dbinputs[$nodeid][$name]['id'];
                $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                
                if ($dbinputs[$nodeid][$name]['processList']) $tmp[] = array('value'=>$value,'processList'=>$dbinputs[$nodeid][$name]['processList']);
            }
        }
        
        foreach ($tmp as $i) $process->input($time,$i['value'],$i['processList']);
        print "\n\n";
    }
