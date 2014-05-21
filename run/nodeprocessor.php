<?php
    // This code is released under the GNU Affero General Public License.
    // OpenEnergyMonitor project:
    // http://openenergymonitor.org


    define('EMONCMS_EXEC', 1);

    $fp = fopen("noderunlock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }
    
    chdir("/var/www/emoncms");
    
    require "Modules/log/EmonLogger.php";
    
    require "process_settings.php";
    error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
    
    $mysqli = @new mysqli($server,$username,$password,$database);
    $redis = new Redis();
    $connected = $redis->connect("127.0.0.1");
    
    include "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis,$feed_settings);

    require "Modules/input/input_model.php"; // 295
    $input = new Input($mysqli,$redis, $feed);

    require "Modules/input/process_model.php"; // 886
    $process = new Process($mysqli,$input,$feed);

    include "Modules/node/node_model.php";
    $node = new Node($mysqli,$redis,$process);
  
    require "SAM/php_sam.php";
    $conn = new SAMConnection();
    $conn->connect(SAM_MQTT, array( SAM_HOST => '127.0.0.1', SAM_PORT => '1883'));
    $subUp = $conn->subscribe('topic://noderx') OR die('could not subscribe');

    while($conn)
    {
        $msgUp = $conn->receive($subUp);
        if ($msgUp && $msgUp->body) {
            $nd = json_decode($msgUp->body);
            // print $msgUp->body." ";
            $rtn = $node->set($nd->userid,$nd->nodeid,$nd->time,$nd->data);
            // print $rtn."\n"
        }
    }
