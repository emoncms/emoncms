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

// This function takes two arrays of settings and merges them, using
// the value from $overrides where it differs from the one in $defaults.
function ini_merge ($defaults, $overrides) {
    foreach ($overrides as $k => $v) {
        if (is_array($v)) {
            $defaults[$k] = ini_merge($defaults[$k], $overrides[$k]);
        } else {
            $defaults[$k] = $v;
        }
    }

    return $defaults;
};

// This function iterates over all the config file entries, replacing values
// of the format {{VAR_NAME}} with the environment variable 'VAR_NAME'.
//
// This can be useful in containerised setups, or testing environments.
function ini_check_envvars($config) {
    global $error_out;

    foreach ($config as $section => $options) {
        foreach ($options as $key => $value) {
            // Find {{ }} vars and replace what's within them with the
            // named environment var
            if (strpos($value, '{{') !== false && strpos($value, '}}') !== false) {
                preg_match_all( '/{{([^}]*)}}/', $value, $matches);
                foreach ($matches[1] as $match) {
                    if (!isset($_ENV[$match])) {
                        $error_out .= "<p>Error: environment var '${match}' not defined in config section [${section}], setting '${key}'</p>";
                    } else {
                        $newval = str_replace('{{'.$match.'}}', $_ENV[$match], $value);

                        // Convert booleans from strings
                        if ($newval === 'true') {
                            $newval = true;
                        } else if ($newval === 'false') {
                            $newval = false;

                        // Convert numbers from strings
                        } else if (is_numeric($newval)) {
                            $newval = $newval + 0;
                        }

                        // Set the new value
                        $config[$section][$key] = $newval;
                    }
                }
            }
        }
    }

    return $config;
}

$error_out = "";

$CONFIG_INI = parse_ini_file("default-settings.ini", true);
$CUSTOM_INI = parse_ini_file("settings.ini", true);
$ini_array = ini_merge($CONFIG_INI, $CUSTOM_INI);

$ini_array = ini_check_envvars($ini_array);

// [database]
$server   = $ini_array['database']['server'];
$database = $ini_array['database']['database'];
$username = $ini_array['database']['username'];
$password = $ini_array['database']['password'];
$port     = $ini_array['database']['port'];
$dbtest   = $ini_array['database']['dbtest'];

// [redis]
$redis_enabled = !empty($ini_array['redis']) ?
                        $ini_array['redis']['enabled'] :
                        false;
if ($redis_enabled) {
    $redis_server = array( 'host'   => $ini_array['redis']['host'],
                           'port'   => $ini_array['redis']['port'],
                           'auth'   => $ini_array['redis']['auth'],
                           'prefix' => $ini_array['redis']['prefix']);
}

// [mqtt]
$mqtt_enabled = !empty($ini_array['mqtt']) ?
                        $ini_array['mqtt']['enabled'] :
                        false;

$mqtt_server = array( 'host'     => $ini_array['mqtt']['host'],
                      'port'     => $ini_array['mqtt']['port'],
                      'user'     => $ini_array['mqtt']['user'],
                      'password' => $ini_array['mqtt']['password'],
                      'basetopic'=> $ini_array['mqtt']['basetopic']);

// [feed]
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
        'enabled' => $ini_array['feed']['redisbuffer_enabled'],      // If enabled is true, requires redis enabled and feedwriter service running
        'sleep' => $ini_array['feed']['redisbuffer_sleep']        // Number of seconds to wait before write buffer to disk - user selectable option
    ),
    'phpfiwa'=>array(
        'datadir' => $ini_array['feed']['phpfiwa_datadir']),
    'phpfina'=>array(
        'datadir' => $ini_array['feed']['phpfina_datadir']),
    'phptimeseries'=>array(
        'datadir' => $ini_array['feed']['phptimeseries_datadir']),
    'cassandra'=>array(
        'keyspace' => $ini_array['feed']['cassandra_keyspace'])
);

$max_node_id_limit = $ini_array['feed']['max_node_id_limit'];

// [interface]
$default_language = $ini_array['interface']['default_language'];
$theme = $ini_array['interface']['theme'];
$themecolor = $ini_array['interface']['themecolor'];
$favicon = $ini_array['interface']['favicon'];
$fullwidth = $ini_array['interface']['fullwidth'];
$menucollapses = $ini_array['interface']['menucollapses'];
$enable_multi_user = $ini_array['interface']['enable_multi_user'];
$enable_rememberme = $ini_array['interface']['enable_rememberme'];
$enable_password_reset = $ini_array['interface']['enable_password_reset'];
$default_emailto = $ini_array['interface']['default_emailto'];
$default_controller = $ini_array['interface']['default_controller'];
$default_action = $ini_array['interface']['default_action'];
$default_controller_auth = $ini_array['interface']['default_controller_auth'];
$default_action_auth = $ini_array['interface']['default_action_auth'];
$feedviewpath = $ini_array['interface']['feedviewpath'];

// [public_profile]
$public_profile_enabled = $ini_array['public_profile']['enabled'];
$public_profile_controller = $ini_array['public_profile']['controller'];
$public_profile_action = $ini_array['public_profile']['action'];

// [smtp]
$smtp_email_settings = array(
  'host'=> $ini_array['smtp']['host'],
  'port'=> $ini_array['smtp']['port'],  // 25, 465, 587
  'from'=> array(
      $ini_array['smtp']['from_email'] => $ini_array['smtp']['from_name']),
  'encryption'=> $ini_array['smtp']['encryption'], // ssl, tls
  'username'=> $ini_array['smtp']['username'],
  'password'=>$ini_array['smtp']['password']
);

// [csv]
$feed_settings['csvdownloadlimit_mb'] = $ini_array['csv']['downloadlimit_mb'];
$csv_decimal_places = $ini_array['csv']['decimal_places'];
$csv_decimal_place_separator = $ini_array['csv']['decimal_place_separator'];
$csv_field_separator = $ini_array['csv']['field_separator'];

// [log]
$log_enabled = $ini_array['log']['enabled'];
$log_filename = $ini_array['log']['filename'];
$log_level = $ini_array['log']['level'];

// [other]
$allow_emonpi_admin = $ini_array['other']['allow_emonpi_admin'];
$data_sampling = $ini_array['other']['data_sampling'];
$display_errors = $ini_array['other']['display_errors'];
$config_file_version = $ini_array['other']['config_file_version'];
$updatelogin = $ini_array['other']['updatelogin'];
$appname = $ini_array['other']['appname'];

//echo "<h3>Got here</h3>";

//************************************************
// Check if settings.php file exists
if(file_exists(dirname(__FILE__)."/settings.php"))
{
    // Load settings.php
    require_once('settings.php');

    //  Validate settings are complete
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

if ($error_out!="") {
  echo "<div style='width:600px; background-color:#eee; padding:20px; font-family:arial;'>";
  echo "<h3>settings.php file error</h3>";
  echo $error_out;
  echo "<p>To fix, check that the settings are set in <i>settings.php</i> or try re-creating your <i>settings.php</i> file from <i>default.settings.php</i> template</p>";
  echo "</div>";
  die;
}