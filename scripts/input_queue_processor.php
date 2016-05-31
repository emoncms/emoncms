<?php
    /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
    */

    define('EMONCMS_EXEC', 1);

    $fp = fopen("/var/lock/input_queue_processor.lock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

    chdir(dirname(__FILE__)."/../");
    require "Lib/EmonLogger.php";
    require "process_settings.php";

    if (!$redis_enabled) { echo "Error: setting must be true: redis_enabled\n"; die; }
    
    $log = new EmonLogger(__FILE__);
    $log->info("Starting REDIS Input Queue Processor script");

    $mysqli = @new mysqli($server,$username,$password,$database,$port);
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

    include "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $feed_settings);

    require "Modules/input/input_model.php";
    $input = new Input($mysqli,$redis, $feed);

    require "Modules/process/process_model.php";
    $process = new Process($mysqli,$input,$feed);

    $rn = 0;
    $ltime = time();

    $usleep = 100000;

    while(true)
    {
        if ((time()-$ltime)>=1)
        {
            $ltime = time();

            $buflength = $redis->llen('buffer');

            // A basic throthler to stop the script using up cpu when there is nothing to do.

            // Fine tune sleep
            if ($buflength<2) {
                $usleep += 50;
            } else {
                $usleep -= 50;
            }

            // if there is a big buffer reduce sleep to zero to clear buffer.
            if ($buflength>100) $usleep = 0;

            // if throughput is low then increase sleep significantly
            if ($rn==0) $usleep = 100000;

            // sleep cant be less than zero
            if ($usleep<0) $usleep = 0;

            echo "Buffer length: ".$buflength." ".$usleep." ".$rn."\n";

            $rn = 0;
        }

        // check if there is an item in the queue to process
        $line_str = false;

        if ($redis->llen('buffer')>0)
        {
            // check if there is an item in the queue to process
            $line_str = $redis->lpop('buffer');
        }

        if ($line_str)
        {
            $rn++;

            //echo $line_str."\n";
            $packet = json_decode($line_str);

            $userid = $packet->userid;
            $time = $packet->time;
            $nodeid = $packet->nodeid;
            $data = $packet->data;

            // Load current user input meta data
            // It would be good to avoid repeated calls to this
            $dbinputs = $input->get_inputs($userid);
            
            if (!isset($dbinputs[$nodeid]) && (count($dbinputs) >= $max_node_id_limit )) {
                $log->error("Reached the maximal allowed number of diferent NodeIds, limit is $max_node_id_limit. Node '$nodeid' was ignored.");
            } else {

                $tmp = array();
                foreach ($data as $name => $value)
                {
                    if (!isset($dbinputs[$nodeid][$name])) {
                        $inputid = $input->create_input($userid, $nodeid, $name);
                        $dbinputs[$nodeid][$name] = true;
                        $dbinputs[$nodeid][$name] = array('id'=>$inputid, 'processList'=>'');
                        $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                    } else {
                        $inputid = $dbinputs[$nodeid][$name]['id'];
                        $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);

                        if ($dbinputs[$nodeid][$name]['processList']) $tmp[] = array('value'=>$value,'processList'=>$dbinputs[$nodeid][$name]['processList']);
                    }
                }

                foreach ($tmp as $i) $process->input($time,$i['value'],$i['processList']);
            }
        }
        usleep($usleep);
    }
