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
    global $session, $route;

    $format = $route['format'];
    $action = $route['action'];

    $output['content'] = "";
    $output['message'] = "";

    if ($action == 'local' && $session['read'])
    {
      $timeoffset = get_user_timeoffset($session['userid']);
      $output['content'] = 't'.(date('H')+$timeoffset).date(',i,s');
    }

    if ($action == 'server' && $session['read'])
    {
      $output['content'] = 't'.date('H,i,s');
    }

    if ($action == 'set' && $session['read'])
    {
      $offset = intval($_GET['offset']);
      
      if ($offset>0)
      {
        $output['message'] = "Local time set to server time +".$offset." hours";
      }
      elseif ($offset==0)
      {
        $output['message'] = "Local time set to server time";
      }
      else
      {
        $output['message'] = "Local time set to server time ".$offset." hours";
      }

      set_user_timeoffset($session['userid'],$offset);
    }

    return $output;
  }

?>
