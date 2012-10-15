<?php
  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org


    ADMIN CONTROLLER ACTIONS		ACCESS

    users				write & admin

  */

  // no direct access
  defined('EMONCMS_EXEC') or die('Restricted access');

  function admin_controller()
  {
    require "Models/feed_model.php";
    global $session, $action,$format;

    $output['content'] = "";
    $output['message'] = "";

    //---------------------------------------------------------------------------------------------------------
    // Gets the user list and user memory use
    // http://yoursite/emoncms/admin/users
    //---------------------------------------------------------------------------------------------------------

    if ($action == '' && $session['write'] && $session['admin'])
    {
      $userlist = get_user_list();
      $total_memuse = 0;
      for ($i=0;$i<count($userlist);$i++) {
        $user = $userlist[$i];
        $stats = get_statistics($user['userid']);
        $user['uphits'] = $stats['uphits'];
        $user['dnhits'] = $stats['dnhits'];
        $user['memuse'] = $stats['memory'];
        $total_memuse += $user['memuse'];
        $userlist[$i] = $user;
      }
        
      usort($userlist, 'user_sort');	// sort by highest memory user first

      $output['content'] = view("admin/admin_view.php", array('userlist'=>$userlist,'total_memuse'=>$total_memuse));
    }
    return $output;
  }


function user_sort($x, $y)
{
	return $y['memuse'] - $x['memuse'];
}

?>