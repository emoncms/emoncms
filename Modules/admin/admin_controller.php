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
    //require "Modules/feed/feed_model.php";
    global $session, $route;

    $format = $route['format'];
    $action = $route['action'];

    $output['content'] = "";
    $output['message'] = "";

    //---------------------------------------------------------------------------------------------------------
    // Gets the user list and user memory use
    // http://yoursite/emoncms/admin/users
    //---------------------------------------------------------------------------------------------------------

    if ($action == 'view' && $session['write'] && $session['admin'])
    {
      $output['content'] = view("admin/admin_main_view.php", array());
    }

    if ($action == 'users' && $session['write'] && $session['admin'])
    {
      //$userlist = get_user_list();
      //$output['content'] = view("admin/admin_view.php", array('userlist'=>$userlist));
    }

    if ($action == 'db' && $session['write'] && $session['admin'])
    {
      $out = db_schema_setup(load_db_schema());
      $output['content'] = view("admin/admin_db_view.php", array('out'=>$out));
    }



    return $output;
  }


?>
