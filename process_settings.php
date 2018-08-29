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

function ini_merge ($config_ini, $custom_ini) {
    foreach ($custom_ini AS $k => $v):
        if (is_array($v)):
            $config_ini[$k] = ini_merge($config_ini[$k], $custom_ini[$k]);
        else:
            $config_ini[$k] = $v;
        endif;
    endforeach;
    return $config_ini;
};

$CONFIG_INI = parse_ini_file("default-settings.ini");
$CUSTOM_INI = parse_ini_file("settings.ini");
$ini_array = ini_merge($CONFIG_INI, $CUSTOM_INI);

//$ini_array = parse_ini_file("default-settings.ini");
//$ini_array = parse_ini_file("settings.ini");
//************************************************
$server   = $ini_array['server'];
$database = $ini_array['database'];
$username = $ini_array['username'];
$password = $ini_array['password'];
$port     = $ini_array['port'];

$dbtest = $ini_array['dbtest'];
$redis_enabled = $ini_array['redis_enabled'];
$redis_server = array( 'host'   => $ini_array['redis_server']['host'],
                       'port'   => $ini_array['redis_server']['port'],
                       'auth'   => $ini_array['redis_server']['auth'],
                       'prefix' => $ini_array['redis_server']['prefix']);
$mqtt_enabled = $ini_array['mqtt_enabled'];          // Activate MQTT by changing to true
$mqtt_server = array( 'host'     => $ini_array['mqtt_server']['host'],
                      'port'     => $ini_array['mqtt_server']['port'],
                      'user'     => $ini_array['mqtt_server']['user'],
                      'password' => $ini_array['mqtt_server']['password'],
                      'basetopic'=> $ini_array['mqtt_server']['basetopic']);
$feed_settings = array(
    'engines_hidden'=>array(
        //Engine::MYSQL         // 0  Mysql traditional
        //Engine::MYSQLMEMORY   // 8  Mysql with MEMORY tables on RAM. All data is lost on shutdown
        //Engine::PHPTIMESERIES // 2
        //,Engine::PHPFINA      // 5
        //,Engine::PHPFIWA      // 6
        //,Engine::CASSANDRA    // 10 Apache Cassandra
    ),
    'redisbuffer'=>array(
        'enabled' => $ini_array['redisbuffer']['enabled']      // If enabled is true, requires redis enabled and feedwriter service running
        ,'sleep' => $ini_array['redisbuffer']['sleep']),       // Number of seconds to wait before write buffer to disk - user selectable option
    'csvdownloadlimit_mb' => $ini_array['csvdownloadlimit_mb'],     // Max csv download size in MB
    'phpfiwa'=>array(
        'datadir' => $ini_array['phpfiwa']['datadir']),
    'phpfina'=>array(
        'datadir' => $ini_array['phpfina']['datadir']),
    'phptimeseries'=>array(
        'datadir' => $ini_array['phptimeseries']['datadir']),
    'cassandra'=>array(
        'keyspace' => $ini_array['cassandra']['datadir'])
);

$max_node_id_limit = $ini_array['max_node_id_limit'];
$default_language = $ini_array['default_language'];
$theme = $ini_array['theme'];
$themecolor = $ini_array['themecolor'];
$favicon = $ini_array['favicon'];
$fullwidth = $ini_array['fullwidth'];
$menucollapses = $ini_array['menucollapses'];
$enable_multi_user = $ini_array['enable_multi_user'];
$enable_rememberme = $ini_array['enable_rememberme'];
$enable_password_reset = $ini_array['enable_password_reset'];
$default_emailto = $ini_array['default_emailto'];
$smtp_email_settings = array(
  'host'=> $ini_array['smtp_email_settings']['host'],
  'port'=> $ini_array['smtp_email_settings']['port'],  // 25, 465, 587
  'from'=> array(
      $ini_array['smtp_email_settings']['from_email'] => $ini_array['smtp_email_settings']['from_name']),
  'encryption'=> $ini_array['smtp_email_settings']['encryption'], // ssl, tls
  'username'=> $ini_array['smtp_email_settings']['username'],
  'password'=>$ini_array['smtp_email_settings']['password']);

$default_controller = $ini_array['default_controller'];
$default_action = $ini_array['default_action'];
$default_controller_auth = $ini_array['default_controller_auth'];
$default_action_auth = $ini_array['default_action_auth'];
$public_profile_enabled = $ini_array['public_profile_enabled'];
$public_profile_controller = $ini_array['public_profile_controller'];
$public_profile_action = $ini_array['public_profile_action'];
$feedviewpath = $ini_array['feedviewpath'];
$log_enabled = $ini_array['log_enabled'];
$log_filename = $ini_array['log_filename'];
$log_level = $ini_array['log_level'];
$allow_emonpi_admin = $ini_array['allow_emonpi_admin'];
$data_sampling = $ini_array['data_sampling'];
$display_errors = $ini_array['display_errors'];
$csv_decimal_places = $ini_array['csv_decimal_places'];
$csv_decimal_place_separator = $ini_array['csv_decimal_place_separator'];
$csv_field_separator = $ini_array['csv_field_separator'];
$allow_config_env_vars = $ini_array['allow_config_env_vars'];
$config_file_version = $ini_array['config_file_version'];
$updatelogin = $ini_array['updatelogin'];
$appname = $ini_array['appname'];

//echo "<h3>Got here</h3>";

//************************************************
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

    if (!isset($feed_settings['csvdownloadlimit_mb'])) $feed_settings['csvdownloadlimit_mb'] = 10; // default
    if (!isset($data_sampling)) $data_sampling = true; // default

    if (!isset($fullwidth)) $fullwidth = false;
    if (!isset($menucollapses)) $menucollapses = true;
    if (!isset($favicon)) $favicon = "favicon.png";
    if (!isset($email_verification)) $email_verification = false;

    if (!isset($csv_decimal_places) || $csv_decimal_places=="") $csv_decimal_places = 2;
    if (!isset($csv_decimal_place_separator) || $csv_decimal_place_separator=="") $csv_decimal_place_separator = '.';
    if (!isset($csv_field_separator) || $csv_field_separator=="") $csv_field_separator = ',';

    if ($csv_decimal_place_separator == $csv_field_separator) $error_out .= '<p>settings incorrect: $csv_decimal_place_separator==$csv_field_separator</p>';
    
    if (!isset($appname)) $appname = 'emoncms';
    
    if (!isset($homedir)) $homedir = "/home/pi";
    if ($homedir!="/home/pi" && !is_dir($homedir)) $error_out .= "<p>homedir is not configured or directory does not exists, check settings: homedir";

    if ($error_out!="") {
      echo "<div style='width:600px; background-color:#eee; padding:20px; font-family:arial;'>";
      echo "<h3>settings.php file error</h3>";
      echo $error_out;
      echo "<p>To fix, check that the settings are set in <i>settings.php</i> or try re-creating your <i>settings.php</i> file from <i>default.settings.php</i> template</p>";
      echo "</div>";
      die;
    }

    if (!isset($default_emailto)) $default_emailto = 'pi@localhost';

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
