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

function time_controller()
{
    global $mysqli,$session, $route;

    $result = false;

    if (!isset($session['read'])) return array('content'=>false);

    if ($route->action == 'local' && $session['read'])
    {
        $userid = $session['userid'];
        $result = $mysqli->query("SELECT timezone FROM users WHERE id = '$userid';");
        $row = $result->fetch_object();
        
        $now = new DateTime();
        $now->setTimezone(new DateTimeZone($row->timezone));
        $result = 't'.$now->format("H,i,s");
    }

    if ($route->action == 'server' && $session['read'])
    {
        $result = 't'.date('H,i,s');
    }

    return array('content'=>$result);
}