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
    global $mysqli,$session,$route,$updatelogin,$allow_emonpi_admin, $log_filename, $log_enabled, $redis;
    $result = "<br><div class='alert-error' style='top:0px; left:0px; width:100%; height:100%; text-align:center; padding-top:100px; padding-bottom:100px; border-radius:4px;'><h4>"._('Admin re-authentication required')."</h4></div>";

    // Allow for special admin session if updatelogin property is set to true in settings.php
    // Its important to use this with care and set updatelogin to false or remove from settings
    // after the update is complete.
    if ($updatelogin || $session['admin']) {
        
        if ($route->format == 'html') {
            if ($route->action == 'view') $result = view("Modules/admin/admin_main_view.php", array());

            else if ($route->action == 'db')
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

            else if ($route->action == 'users' && $session['write'])
            {
                $result = view("Modules/admin/userlist_view.php", array());
            }

            else if ($route->action == 'setuser' && $session['write'])
            {
                $_SESSION['userid'] = intval(get('id'));
                header("Location: ../user/view");
            }

            else if ($route->action == 'getlog')
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

            else if ($allow_emonpi_admin && $route->action == 'emonpi') {
                if ($route->subaction == 'update' && $session['write'] && $session['admin']) { 
                    $route->format = "text";
                    
                    $update_flag = "/tmp/emoncms-flag-update";
                    $update_script = "/home/pi/emonpi/service-runner-update.sh";
                    $update_logfile = "/home/pi/data/emonpiupdate.log";
                    
                    $fh = @fopen($update_flag,"w");
                    if (!$fh) {
                        $result = "ERROR: Can't write the flag $update_flag.";
                    } else {
                        fwrite($fh,"$update_script>$update_logfile");
                        $result = "Update flag set";
                    }
                    @fclose($fh);
                }
                
                if ($route->subaction == 'getupdatelog') { 
                    $route->format = "text";
                    ob_start();
                    passthru("cat /home/pi/data/emonpiupdate.log");
                    $result = trim(ob_get_clean());
                }
                
                if ($route->subaction == 'backup' && $session['write'] && $session['admin']) { 
                    $route->format = "text";
                    $file = "/tmp/emonpibackup";
                    $fh = @fopen($file,"w");
                    if (!$fh) $result = "ERROR: Can't write the flag $file.";
                    else $result = "Update flag file $file created. Update will start on next cron call in " . (60 - (time() % 60)) . "s...";
                    @fclose($fh);
                }
                
                if ($route->subaction == 'getbackuplog') { 
                    $route->format = "text";
                    ob_start();
                    passthru("cat /home/pi/data/emonpibackup.log");
                    $result = trim(ob_get_clean());
                }
                
                if ($route->subaction == "downloadbackup" && $session['write'] && $session['admin']) {
                    header("Content-type: application/zip"); 
                    header("Content-Disposition: attachment; filename=backup.tar.gz"); 
                    header("Pragma: no-cache"); 
                    header("Expires: 0"); 
                    readfile("/home/pi/data/backup.tar.gz");
                    exit;
                }
            }
        }
        else if ($route->format == 'json')
        {
            if ($route->action == 'redisflush' && $session['write'])
            {
                $redis->flushDB();
                $result = array('used'=>$redis->info()['used_memory_human'], 'dbsize'=>$redis->dbSize());
            }
            else if ($route->action == 'userlist' && $session['write'])
            {
                $data = array();
                $result = $mysqli->query("SELECT id,username,email FROM users");
                while ($row = $result->fetch_object()) $data[] = $row;
                $result = $data;
            }
        }
    }

    return array('content'=>$result);
}
