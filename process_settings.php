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
if(file_exists(dirname(__FILE__)."/settings.php"))
{
    // Load settings.php
    require_once('settings.php');

    if (!isset($allow_config_env_vars)) $allow_config_env_vars = false;
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
        // create the array if it's not already been done
        if (!isset($redis_server)) $redis_server = array();

        if (isset($_ENV["EMONCMS_REDIS_ENABLED"]))  $redis_enabled = $_ENV["EMONCMS_REDIS_ENABLED"] === 'true';
        if (isset($_ENV["EMONCMS_REDIS_HOST"]))     $redis_server['host'] = $_ENV["EMONCMS_REDIS_HOST"];
        if (isset($_ENV["EMONCMS_REDIS_PORT"]))     $redis_server['port'] = $_ENV["EMONCMS_REDIS_PORT"];
        if (isset($_ENV["EMONCMS_REDIS_AUTH"]))     $redis_server['auth'] = $_ENV["EMONCMS_REDIS_AUTH"];
        if (isset($_ENV["EMONCMS_REDIS_PREFIX"]))   $redis_server['prefix'] = $_ENV["EMONCMS_REDIS_PREFIX"];

        //3 #### MQTT
        // create the array if it's not already been done
        if (!isset($mqtt_server)) $mqtt_server = array();
        if (isset($_ENV["EMONCMS_MQTT_ENABLED"]))  $mqtt_enabled = $_ENV["EMONCMS_MQTT_ENABLED"] === 'true';

        if (isset($_ENV["EMONCMS_MQTT_HOST"]))     $redis_server['host'] = $_ENV["EMONCMS_MQTT_HOST"];
        if (isset($_ENV["EMONCMS_MQTT_PORT"]))     $redis_server['port'] = $_ENV["EMONCMS_MQTT_PORT"];
        if (isset($_ENV["EMONCMS_MQTT_USER"]))     $redis_server['user'] = $_ENV["EMONCMS_MQTT_USER"];
        if (isset($_ENV["EMONCMS_MQTT_PASSWORD"]))     $redis_server['password'] = $_ENV["EMONCMS_MQTT_PASSWORD"];
        if (isset($_ENV["EMONCMS_MQTT_BASETOPIC"]))     $redis_server['basetopic'] = $_ENV["EMONCMS_MQTT_BASETOPIC"];
    }
    
    //  Validate settings are complete

    $error_out = "";

    if (!isset($config_file_version) || $config_file_version < 9) $error_out .= '<p>settings.php config file has new settings for this version. Copy default.settings.php to settings.php and modify the later.</p>';
    if (!isset($username) || $username=="") $error_out .= '<p>missing setting: $username</p>';
    if (!isset($password)) $error_out .= '<p>missing setting: $password</p>';
    if (!isset($server) || $server=="") $error_out .= '<p>missing setting: $server</p>';
    if (!isset($database) || $database=="") $error_out .= '<p>missing setting: $database</p>';
    if ($enable_password_reset && !isset($smtp_email_settings)) $error_out .= '<p>missing setting: $smtp_email_settings</p>';

    if (!isset($log_enabled)) $error_out .= "<p>missing setting: log_enabled</p>";
    if (!isset($log_level)) $log_level=2;  //default to warning log level

    if (!isset($redis_enabled)) $redis_enabled = false;
    if ($redis_enabled) {
        if (!class_exists('Redis')) $error_out .= "<p>redis enabled but not installed, check setting: redis_enabled</p>";
        if (!isset($redis_server['host'])) $error_out .= "<p>redis server not configured, check setting: redis_server.host</p>";
        if (!isset($redis_server['port'])) $error_out .= "<p>redis server not configured, check setting: redis_server.port</p>";
        if (!isset($redis_server['auth'])) $error_out .= "<p>redis server not configured, check setting: redis_server.auth</p>";
        if (!isset($redis_server['prefix'])) $error_out .= "<p>redis server not configured, check setting: redis_server.prefix</p>";
        if (!empty($redis_server['prefix'])) $redis_server['prefix'] = $redis_server['prefix'] . ":";
        if (!isset($feed_settings['redisbuffer']['enabled'])) $feed_settings['redisbuffer'] = array('enabled'=>false);
        if (!$feed_settings['redisbuffer']['sleep']) $feed_settings['redisbuffer']['sleep'] = 60;
        if ((int)$feed_settings['redisbuffer']['sleep'] < 1) $error_out .= "<p>buffered writing sleep interval must be > 0, check settings: settings['redisbuffer']['sleep']";
    } else {
        if ($feed_settings['redisbuffer']['enabled']) $error_out .= "<p>buffered writing requires redis but its disabled, check settings: settings['redisbuffer']['enabled'], redis_enabled";
    }

    if (!isset($mqtt_enabled)) $mqtt_enabled = false;
    if ($mqtt_enabled) {
        if (!isset($mqtt_server['host'])) $error_out .= "<p>mqtt server not configured, check setting: mqtt_server</p>";
        if (!isset($mqtt_server['port'])) $mqtt_server['port'] = 1883;
        if (!isset($mqtt_server['user'])) $mqtt_server['user'] = null;
        if (!isset($mqtt_server['password'])) $mqtt_server['password'] = null;
        if (!isset($mqtt_server['basetopic'])) $mqtt_server['basetopic'] = "nodes";
    }

    if (!isset($feed_settings)) $feed_settings = array();
    if (!isset($feed_settings['phpfiwa'])) $error_out .= "<p>feed setting for phpfiwa is not configured, check settings: settings['phpfiwa']";
    if (!isset($feed_settings['phpfina'])) $error_out .= "<p>feed setting for phpfina is not configured, check settings: settings['phpfina']";
    if (!isset($feed_settings['phptimeseries'])) $error_out .= "<p>feed setting for phptimeseries is not configured, check settings: settings['phptimeseries']";
    if (!isset($feed_settings['redisbuffer'])) $error_out .= "<p>feed setting for redisbuffer is not configured, check settings: settings['redisbuffer']";
    if (!isset($feed_settings['engines_hidden'])) $error_out .= "<p>feed setting for engines_hidden is not configured, check settings: settings['engines_hidden']";
    if (!isset($homedir)) $homedir = "/home/pi";

    if (!isset($feed_settings['csvdownloadlimit_mb'])) $feed_settings['csvdownloadlimit_mb'] = 10; // default
    if (!isset($data_sampling)) $data_sampling = true; // default

    if (!isset($fullwidth)) $fullwidth = false;
    if (!isset($menucollapses)) $menucollapses = true;
    if (!isset($favicon)) $favicon = "favicon.png";

    if (!isset($csv_decimal_places) || $csv_decimal_places=="") $csv_decimal_places = 2;
    if (!isset($csv_decimal_place_separator) || $csv_decimal_place_separator=="") $csv_decimal_place_separator = '.';
    if (!isset($csv_field_separator) || $csv_field_separator=="") $csv_field_separator = ',';

    if ($csv_decimal_place_separator == $csv_field_separator) $error_out .= '<p>settings incorrect: $csv_decimal_place_separator==$csv_field_separator</p>';

    if ($error_out!="") {
      echo "<div style='width:600px; background-color:#eee; padding:20px; font-family:arial;'>";
      echo "<h3>settings.php file error</h3>";
      echo $error_out;
      echo "<p>To fix, check that the settings are set in <i>settings.php</i> or try re-creating your <i>settings.php</i> file from <i>default.settings.php</i> template</p>";
      echo "</div>";
      die;
    }


    // Set display errors
    if (isset($display_errors) && ($display_errors)) {
        error_reporting(E_ALL);
        ini_set('display_errors', 'on');
    }
}
else
{
    echo "<div style='width:600px; background-color:#eee; padding:20px; font-family:arial;'>";
    echo "<h3>settings.php file error</h3>";
    echo 'Copy and modify default.settings.php to settings.php<br>';
    echo 'For more information about configure settings.php file go to <a href="http://emoncms.org">http://emoncms.org</a>';
    echo "</div>";
    die;
}
