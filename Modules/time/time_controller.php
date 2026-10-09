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
    global $session, $route, $user;

    $result = false;

    if ($route->action == 'local' && $session['read'])
    {
        $timezone = $user->get_timezone($session['userid']);
        $now = new DateTime();
        try {
            $now->setTimezone(new DateTimeZone($timezone ?: "UTC"));
        } catch (Exception $e) {}
        $result = 't'.$now->format("H,i,s");
    }

    if ($route->action == 'server')
    {
        $result = 't'.date('H,i,s');
    }

    return array('content'=>$result);
}
