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
    
    if (!isset($username) || $username=="") $error_out .= '<p>missing setting: $username</p>';
    if (!isset($password)) $error_out .= '<p>missing setting: $password</p>';
    if (!isset($server) || $server=="") $error_out .= '<p>missing setting: $server</p>';
    if (!isset($database) || $database=="") $error_out .= '<p>missing setting: $database</p>';
    if ($enable_password_reset && !isset($smtp_email_settings)) $error_out .= '<p>missing setting: $smtp_email_settings</p>';
        
    if (!isset($feed_settings)) $error_out .= "<p>missing setting: feed_settings</p>";
    
    if (!isset($redis_enabled)) $redis_enabled = true;
    
    if ($error_out!="") {
      echo "<div style='width:600px; background-color:#eee; padding:20px; font-family:arial;'>";
      echo "<h3>settings.php file error</h3>";
      echo $error_out;
      echo "<p>To fix check that the settings are set in <i>settings.php</i> or try re-creating your <i>settings.php</i> file from <i>default.settings.php</i> template</p>";
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
