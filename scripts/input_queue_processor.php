<?php
    /*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    */

    define('EMONCMS_EXEC', 1);

    $fp = fopen("runlock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

    chdir("/var/www/emoncms");

    require "process_settings.php";
    $mysqli = new mysqli($server,$username,$password,$database);

    $redis = new Redis();
    $redis->connect($redis_server['host'], $redis_server['port']);
    $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix'].':');
    if ( ! empty( $redis_server['auth'] ) ) {
      $redis->auth( $redis_server['auth'] );
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
        usleep($usleep);
    }
