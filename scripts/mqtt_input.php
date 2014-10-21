<?php

    $userid = 12;

    /*
    
    MQTT input interface script, subscribes to topic emoncms/input
    
    Messages of the format:
    
    {
        "apikey": KEY, -- not implemented yet
        "node": 10,
        "time": UNIXTIMESTAMP,
        "csv": [3120, 1200, 24.5, 22.3]
    }
    
    are published to this topic by an external application.
    
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
  
    require "SAM/php_sam.php";
    $conn = new SAMConnection();
    $conn->connect(SAM_MQTT, array( SAM_HOST => '127.0.0.1', SAM_PORT => '1883'));
    $subUp = $conn->subscribe('topic://emoncms/input') OR die('could not subscribe');

    while($conn)
    {
        $msgUp = $conn->receive($subUp);
        if ($msgUp && $msgUp->body) 
        {    
            $m = json_decode($msgUp->body);

            $time = $m->time;
            $nodeid = $m->node;
            $data = $m->csv;

            $dbinputs = $input->get_inputs($userid);

            $tmp = array();

            foreach ($data as $name => $value)
            {
                if ($input->check_node_id_valid($nodeid))
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
                }
            }

            foreach ($tmp as $i) $process->input($time,$i['value'],$i['processList']);
        }
    }

