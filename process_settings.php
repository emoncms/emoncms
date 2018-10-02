<?php
/*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

require_once('Lib/enum.php');

// Check if settings.php file exists
if (!file_exists(dirname(__FILE__)."/defaults.php")) {
    echo "<div style='width:600px; background-color:#eee; padding:20px; font-family:arial;'>";
    echo "<h3>defaults.php missing</h3>";
    echo 'Please restore default.settings.php to original';
    echo "</div>";
    die;
}

if (!file_exists(dirname(__FILE__)."/settings.php")) {
    echo "<div style='width:600px; background-color:#eee; padding:20px; font-family:arial;'>";
    echo "<h3>settings.php missing</h3>";
    echo 'Copy and modify default.settings.php to settings.php<br>';
    echo 'For more information about configure settings.php file go to <a href="http://emoncms.org">http://emoncms.org</a>';
    echo "</div>";
    die;
}

// Load settings.php
require_once('defaults.php');
require_once('settings.php');

if ($allow_config_env_vars) {
    /*
        Load settings from environment variables

        Environment settings override settings.php and defaults, 
        and allow you to run multiple variants of the same
        installation (e.g. for testing).
    */

    //1 #### Mysql database settings
    if (isset($_ENV["EMONCMS_MYSQL_HOST"]))     $server = $_ENV["EMONCMS_MYSQL_HOST"];
    if (isset($_ENV["EMONCMS_MYSQL_DATABASE"])) $database = $_ENV["EMONCMS_MYSQL_DATABASE"];
    if (isset($_ENV["EMONCMS_MYSQL_USER"]))     $username = $_ENV["EMONCMS_MYSQL_USER"];
    if (isset($_ENV["EMONCMS_MYSQL_PASSWORD"])) $password = $_ENV["EMONCMS_MYSQL_PASSWORD"];
    if (isset($_ENV["EMONCMS_MYSQL_PORT"]))     $port = $_ENV["EMONCMS_MYSQL_PORT"];

    //2 #### redis
    if (isset($_ENV["EMONCMS_REDIS_ENABLED"]))  $redis_enabled = $_ENV["EMONCMS_REDIS_ENABLED"] === 'true';
    if (isset($_ENV["EMONCMS_REDIS_HOST"]))     $redis_server['host'] = $_ENV["EMONCMS_REDIS_HOST"];
    if (isset($_ENV["EMONCMS_REDIS_PORT"]))     $redis_server['port'] = $_ENV["EMONCMS_REDIS_PORT"];
    if (isset($_ENV["EMONCMS_REDIS_AUTH"]))     $redis_server['auth'] = $_ENV["EMONCMS_REDIS_AUTH"];
    if (isset($_ENV["EMONCMS_REDIS_PREFIX"]))   $redis_server['prefix'] = $_ENV["EMONCMS_REDIS_PREFIX"];

    //3 #### MQTT
    if (isset($_ENV["EMONCMS_MQTT_ENABLED"]))   $mqtt_enabled = $_ENV["EMONCMS_MQTT_ENABLED"] === 'true';
    if (isset($_ENV["EMONCMS_MQTT_HOST"]))      $mqtt_server['host'] = $_ENV["EMONCMS_MQTT_HOST"];
    if (isset($_ENV["EMONCMS_MQTT_PORT"]))      $mqtt_server['port'] = $_ENV["EMONCMS_MQTT_PORT"];
    if (isset($_ENV["EMONCMS_MQTT_USER"]))      $mqtt_server['user'] = $_ENV["EMONCMS_MQTT_USER"];
    if (isset($_ENV["EMONCMS_MQTT_PASSWORD"]))  $mqtt_server['password'] = $_ENV["EMONCMS_MQTT_PASSWORD"];
    if (isset($_ENV["EMONCMS_MQTT_BASETOPIC"])) $mqtt_server['basetopic'] = $_ENV["EMONCMS_MQTT_BASETOPIC"];
}

//  Validate settings are complete
$error_out = "";
if (!is_dir($homedir)) $error_out .= "<p>homedir is not configured or directory does not exists, check settings: homedir";

if ($error_out!="") {
    echo "<div style='width:600px; background-color:#eee; padding:20px; font-family:arial;'>";
    echo "<h3>settings.php file error</h3>";
    echo $error_out;
    echo "<p>To fix, check that the settings are set in <i>settings.php</i> or try re-creating your <i>settings.php</i> file from <i>default.settings.php</i> template</p>";
    echo "</div>";
    die;
}

// Set display errors
if ($display_errors) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
}
