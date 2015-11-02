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

    $error_out = "";

    if (!isset($config_file_version) || $config_file_version < 6) $error_out .= '<p>settings.php config file has new settings for this version. Copy default.settings.php to settings.php and modify the later.</p>';
    if (!isset($username) || $username=="") $error_out .= '<p>missing setting: $username</p>';
    if (!isset($password)) $error_out .= '<p>missing setting: $password</p>';
    if (!isset($server) || $server=="") $error_out .= '<p>missing setting: $server</p>';
    if (!isset($database) || $database=="") $error_out .= '<p>missing setting: $database</p>';
    if ($enable_password_reset && !isset($smtp_email_settings)) $error_out .= '<p>missing setting: $smtp_email_settings</p>';

    if (!isset($log_enabled)) $error_out .= "<p>missing setting: log_enabled</p>";

    if (!isset($redis_enabled) ) $redis_enabled = false;
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

    if (!isset($mqtt_enabled) ) $mqtt_enabled = false;
    if ($mqtt_enabled && !isset($mqtt_server)) $error_out .= "<p>mqtt server not configured, check setting: mqtt_server</p>";

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
