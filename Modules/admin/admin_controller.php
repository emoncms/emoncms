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

function admin_controller()
{
    global $mysqli,$session,$route,$updatelogin;

    // Allow for special admin session if updatelogin property is set to true in settings.php 
    // Its important to use this with care and set updatelogin to false or remove from settings 
    // after the update is complete.
    if ($updatelogin || $session['admin']) $sessionadmin = true;

    if ($sessionadmin)
    {
      if ($route->action == 'view') $result = view("Modules/admin/admin_main_view.php", array());
      
      if ($route->action == 'modules') $result = view("Modules/admin/admin_modules_view.php", array());
      
      if ($route->action == 'db')
      {
          $applychanges = get('apply');
          if (!$applychanges) $applychanges = false; else $applychanges = true;

          require "Modules/admin/update_class.php";
          require_once "Lib/dbschemasetup.php";

          $update = new Update($mysqli);

          $updates = array();
          $updates[] = array(
            'title'=>"Database schema", 
            'description'=>"", 
            'operations'=>db_schema_setup($mysqli,load_db_schema(),$applychanges)
          );

          // In future versions we could check against db version number as to what updates should be applied
          $updates[] = $update->u0001($applychanges);
          $updates[] = $update->u0002($applychanges);
          $updates[] = $update->u0003($applychanges);

          $result = view("Modules/admin/update_view.php", array('updates'=>$updates));
      }
    }

    return array('content'=>$result);
}
