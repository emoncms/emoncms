<?php
    // This code is released under the GNU Affero General Public License.
    // OpenEnergyMonitor project: http://openenergymonitor.org

    // Feed writer for buffered mode
    define('EMONCMS_EXEC', 1);

    $fp = fopen("/var/lock/feedrunlock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

    chdir(dirname(__FILE__)."/../");
    require "Lib/EmonLogger.php";
    require "process_settings.php";

    if (!$redis_enabled) { echo "Error: setting must be true: redis_enabled\n"; die; }
    if (!$feed_settings['redisbuffer']['enabled']) { echo "Error: setting must be true: feed_settings['redisbuffer']['enabled']\n"; die; }
    if (!$feed_settings['redisbuffer']['sleep'] || (int)$feed_settings['redisbuffer']['sleep'] < 1) { echo "Error: setting must be > 0 : feed_settings['redisbuffer']['sleep']\n"; die; }

    $log = new EmonLogger(__FILE__);
    $log->info("Starting feedwriter");

    $mysqli = @new mysqli($server,$username,$password,$database);
    if ( $mysqli->connect_error ) {  $log->error("Can't connect to database:". $mysqli->connect_error);  die('Check log\n'); }

    if ($redis_enabled) {
        $redis = new Redis();
        if (!$redis->connect($redis_server)) { $log->error("Could not connect to redis at $redis_server");  die('Check log\n'); }
    } else {
        $redis = false;
    }

    require("Modules/user/user_model.php");
    $user = new User($mysqli,$redis,null);

    require("Modules/feed/feed_model.php");
    $feed = new Feed($mysqli,$redis,$feed_settings);

    // Remove write locks just in case something halted without releasing
    $feedids = $redis->sMembers("feed:bufferactive");
    foreach ($feedids as $feedid) {
        $feed->EngineClass(Engine::REDISBUFFER)->removeLock($feedid,"write"); 
    }

    echo "Buffered feed writer daemon started with sleep " . $feed_settings['redisbuffer']['sleep'] . "s...\n";
    while(true)
    {
        $feed->EngineClass(Engine::REDISBUFFER)->process_buffers();
        sleep((int)$feed_settings['redisbuffer']['sleep']);
    }
