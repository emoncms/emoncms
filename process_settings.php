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

if (!defined('DS')) {
    define('DS', '/');
}

define('ROOT', dirname(__FILE__) . DS);
define('CORE', ROOT . 'Core' . DS);

require_once CORE . 'Utility' . DS  . 'Configure.php';
require_once CORE . 'Lib' . DS . 'Enum.php';

if (!file_exists(ROOT .  'settings.php')) 
{
    echo '<div style="width:600px; background-color:#eee; padding:20px; font-family:arial;">';
    echo '<h3>settings.php file error</h3>';
    echo 'Copy and modify default.settings.php to settings.php<br>';
    echo 'For more information about configure settings.php file go to <a href="http://emoncms.org">http://emoncms.org</a>';
    echo '</div>';
    exit;
}

require_once ROOT .  'settings.php';
Configure::write('EmonCMS.version', '8.0');

if (Configure::read('debug'))
{
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
}

$check = array(
    'DB_CONFIG.default.username',
    'DB_CONFIG.default.server',
    'DB_CONFIG.default.database',
    // 'smtp_email_settings' => !$enable_password_reset || empty($smtp_email_settings),
    // 'feed_settings',
);

$error_out = '';
foreach ($check as $k => $v) 
{
    if ($v === false || (is_string($v) && !Configure::check($v))) 
    {
         $error_out .= sprintf('<p>missing setting: $%s</p>' . "\n", is_string($k) ? $k : $v);
    }
}

if (!empty($error_out)) 
{
    echo implode("\n", array(
        '<div style="width:600px; background-color:#eee; padding:20px; font-family:arial;">',
        '<h3>settings.php file error</h3>',
        $error_out,
       '<p>To fix check that the settings are set in <i>settings.php</i> or try re-creating your <i>settings.php</i> file from <i>default.settings.php</i> template</p>',
       '</div>',
    ));
    exit;
}