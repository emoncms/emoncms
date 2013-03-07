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
  
// Check if settings.php file exists
if(!file_exists(dirname(__FILE__)."/settings.php"))
{
    echo 'Copy and modify default.settings.php to settings.php';
    die;
}
else
{
    // Load settigs.php
    require_once('settings.php');
    
    // Set display errors
    if (isset($display_errors) && ($display_errors)) {
        error_reporting(E_ALL);      
        ini_set('display_errors', 'on');      
    }    
}
