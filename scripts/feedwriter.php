<?php
    // This code is released under the GNU Affero General Public License.
    // OpenEnergyMonitor project: http://openenergymonitor.org

    // Feed writer for buffered mode
    define('EMONCMS_EXEC', 1);

    $fp = fopen("/var/lock/feedwriter.lock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

    chdir(dirname(__FILE__)."/../");
    require "Lib/EmonLogger.php";
    require "process_settings.php";

    $log = new EmonLogger(__FILE__);
    $log->error("Starting feedwriter script");
    
    if (!$settings['redis']['enabled']) { $log->error("Error: setting must be true: redis_enabled"); die; }
    if (!$settings['feed']['redisbuffer']['enabled']) { $log->error("Error: setting must be true: settings['feed']['redisbuffer']['enabled']"); die; }
    if (!$settings['feed']['redisbuffer']['sleep'] || (int)$settings['feed']['redisbuffer']['sleep'] < 1) { $log->error("Error: setting must be > 0 : settings['feed']['redisbuffer']['sleep']"); die; }

    $mysqli = @new mysqli(
        $settings["sql"]["server"],
        $settings["sql"]["username"],
        $settings["sql"]["password"],
        $settings["sql"]["database"],
        $settings["sql"]["port"]
    );
    
    if ($mysqli->connect_error) { $log->error("Can't connect to database:". $mysqli->connect_error);  die('Check log\n'); }

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

    require("Modules/user/user_model.php");
    $user = new User($mysqli,$redis,null);

    require("Modules/feed/feed_model.php");
    $feed = new Feed($mysqli,$redis,$settings['feed']);

    // Remove write locks just in case something halted without releasing
    $feedids = $redis->sMembers("feed:bufferactive");
    foreach ($feedids as $feedid) {
        $feed->EngineClass(Engine::REDISBUFFER)->removeLock($feedid,"write"); 
    }

    $log->info("Buffered feed writer daemon started with sleep " . $settings['feed']['redisbuffer']['sleep'] . "s...");
    while(true)
    {
        $feed->EngineClass(Engine::REDISBUFFER)->process_buffers();
        sleep((int)$settings['feed']['redisbuffer']['sleep']);
    }
