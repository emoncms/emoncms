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
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";
$log = new EmonLogger(__FILE__);

// Connect to mysql
$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);

if ($mysqli->connect_error) { 
    $log->error("Cannot connect to MYSQL database:". $mysqli->connect_error);
    die('Check log\n');
}

// Connect to redis
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

// Default userid 
$userid = 1;

require("Modules/user/user_model.php");
$user = new User($mysqli,$redis,null);

require_once "Modules/feed/feed_model.php";
$feed = new Feed($mysqli,$redis,$settings['feed']);
