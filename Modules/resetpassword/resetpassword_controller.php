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
    global $path, $route, $mysqli;

    $result = false;

    // Load html,css,js pages to the client
    if ($route->format == 'html')
    {
      if ($route->action == '') $result = view("Modules/resetpassword/Views/resetpassword_view.php", array());
      /* something like http://.../emoncms/resetpassword/reset/38293729384 */
      else if (($route->action == 'reset') && ($route->subaction != '')) $result = view("Modules/resetpassword/Views/newpassword_view.php", array());
    }
    
    // JSON API
    if ($route->format == 'json')
    {
      if ($route->action == 'resetpassword') {
          require "Modules/resetpassword/resetpassword_model.php"; // 295
          $resetpasswd = new ResetPasswd($mysqli);          
          $result = $resetpasswd->resetpasswd($_GET['email']);
          
          //$result = true; //...check if email exists, send mail, generate token and set it on database in the user password without destroy it
      }
      
    }

    return array('content'=>$result);
}