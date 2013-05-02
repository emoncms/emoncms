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
  
function resetpassword_controller()
{
    global $user, $path, $session, $route;

    $result = false;

    // Load html,css,js pages to the client
    if ($route->format == 'html')
    {
      if ($route->action == '') $result = $result = view("Modules/resetpassword/Views/resetpassword_view.php", array());
    }
    
    // JSON API
    if ($route->format == 'json')
    {

    }

    return array('content'=>$result);
}