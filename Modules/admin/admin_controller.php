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
    global $mysqli,$session,$route,$updatelogin,$allow_emonpi_update, $log_filename, $log_enabled;
    $result = "<div style='position:absolute; top:0px; left:0px; background-color:rgba(240,240,240,0.5); width:100%; height:100%; text-align:center; padding-top:100px;'><h3>"._('Admin re-authentication required')."</h3></div>";

    // Allow for special admin session if updatelogin property is set to true in settings.php
    // Its important to use this with care and set updatelogin to false or remove from settings
    // after the update is complete.
    if ($updatelogin || $session['admin']) {
        if ($route->action == 'view') $result = view("Modules/admin/admin_main_view.php", array());

        if ($route->action == 'db')
        {
            $applychanges = get('apply');
            if (!$applychanges) $applychanges = false;
            else $applychanges = true;

            require_once "Lib/dbschemasetup.php";

            $updates = array();
            $updates[] = array(
                'title'=>"Database schema",
                'description'=>"",
                'operations'=>db_schema_setup($mysqli,load_db_schema(),$applychanges)
            );

            $result = view("Modules/admin/update_view.php", array('applychanges'=>$applychanges, 'updates'=>$updates));
        }

        else if ($route->action == 'users' && $session['write'] && $session['admin'])
        {
            $result = view("Modules/admin/userlist_view.php", array());
        }

        else if ($route->action == 'userlist' && $session['write'] && $session['admin'])
        {
            $data = array();
            $result = $mysqli->query("SELECT id,username,email FROM users");
            while ($row = $result->fetch_object()) $data[] = $row;
            $result = $data;
        }

        else if ($route->action == 'setuser' && $session['write'] && $session['admin'])
        {
            $_SESSION['userid'] = intval(get('id'));
            header("Location: ../user/view");
        }

        else if ($route->action == 'getlog' && $session['write'] && $session['admin'])
        {
            $route->format = "text";
            if ($log_enabled) {
                ob_start();
                passthru("tail -25 " . $log_filename);
                $result = trim(ob_get_clean());
            } else {
                $result = "Log is disabled.";
            }
        }

        else if ($allow_emonpi_update && $route->action == 'emonpi') {
            if ($route->subaction == 'update' && $session['write'] && $session['admin']) { 
                $route->format = "text";
                $file = "/tmp/emonpiupdate";
                $fh = @fopen($file,"w");
                if (!$fh) $result = "ERROR: Can't write the flag $file.";
                else $result = "Update flag file $file created. Update will start on next cron call in " . (60 - (time() % 60)) . "s...";
                @fclose($fh);
            }
            
            if ($route->subaction == 'getupdatelog') { 
                $route->format = "text";
                ob_start();
                passthru("cat /var/log/emonpiupdate.log");
                $result = trim(ob_get_clean());
            }
        }
    }

    return array('content'=>$result);
}
