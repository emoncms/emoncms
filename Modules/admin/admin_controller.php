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
    global $mysqli,$session,$route,$updatelogin,$allow_emonpi_admin, $admin_show_update, $log_filename, $log_enabled, $redis, $homedir;
    $result = "<br><div class='alert-error' style='top:0px; left:0px; width:100%; height:100%; text-align:center; padding-top:100px; padding-bottom:100px; border-radius:4px;'><h4>"._('Admin re-authentication required')."</h4></div>";

    // Allow for special admin session if updatelogin property is set to true in settings.php
    // Its important to use this with care and set updatelogin to false or remove from settings
    // after the update is complete.
    
    if ($session['admin']) {
        
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
                $error = !empty($updates[0]['operations']['error']) ? $updates[0]['operations']['error']: '';
                $result = view("Modules/admin/update_view.php", array('applychanges'=>$applychanges, 'updates'=>$updates, 'error'=>$error));
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
            
            else if ($route->action == 'downloadlog')
            {
              if ($log_enabled) {
                header("Content-Type: application/octet-stream");
                header("Content-Transfer-Encoding: Binary");
                header("Content-disposition: attachment; filename=\"" . basename($log_filename) . "\"");
                header("Pragma: no-cache");
                header("Expires: 0");
                flush();
                if (file_exists($log_filename)) {
                  readfile($log_filename);
                }
                else
                {
                  echo($log_filename . " does not exist!");
                }
                exit;
              }
            }
            
            else if ($route->action == 'getlog')
            {
                $route->format = "text";
                if ($log_enabled) {
                    ob_start();
                      // PHP replacement for tail starts here
                      // full path to text file
                      define("TEXT_FILE", $log_filename);
                      // number of lines to read from the end of file
                      define("LINES_COUNT", 25);

                      function read_file($file, $lines) {
                        //global $fsize;
                        $handle = fopen($file, "r");
                        $linecounter = $lines;
                        $pos = -2;
                        $beginning = false;
                        $text = array();
                        while ($linecounter > 0) {
                          $t = " ";
                          while ($t != "\n") {
                            if(!empty($handle) && fseek($handle, $pos, SEEK_END) == -1) {
                            $beginning = true;
                            break;
                            }
                          if(!empty($handle)) $t = fgetc($handle);
                          $pos --;
                          }
                        $linecounter --;
                        if ($beginning) {
                          rewind($handle);
                          }
                        $text[$lines-$linecounter-1] = fgets($handle);
                        if ($beginning) break;
                        }
                      fclose ($handle);
                      return array_reverse($text);
                      }

                      $fsize = round(filesize(TEXT_FILE)/1024/1024,2);
                      $lines = read_file(TEXT_FILE, LINES_COUNT);
                      foreach ($lines as $line) {
                        echo $line;
                      } //End PHP replacement for Tail
                    $result = trim(ob_get_clean());
                } else {
                    $result = "Log is disabled.";
                }
            }

            else if (($admin_show_update || $allow_emonpi_admin) && $route->action == 'emonpi') {
                //put $update_logfile here so it can be referenced in other if statements
                //before it was only accesable in the update subaction
                //placed some other variables here as well so they are grouped
                //together for the emonpi action even though they might not be used
                //in the subaction
                $update_logfile = "$homedir/data/emonpiupdate.log";
                $backup_logfile = "$homedir/data/emonpibackup.log";
                $update_flag = "/tmp/emoncms-flag-update";
                $backup_flag = "/tmp/emonpibackup";
                $update_script = "$homedir/emonpi/service-runner-update.sh";
                $backup_file = "$homedir/data/backup.tar.gz";
                                
                if ($route->subaction == 'update' && $session['write'] && $session['admin']) {
                    $route->format = "text";
                    // Get update argument e.g. 'emonpi' or 'rfm69pi'
                    $firmware="";
                    if (isset($_POST['firmware'])) $firmware = $_POST['firmware'];
                    if (!in_array($firmware,array("emonpi","rfm69pi","rfm12pi","custom"))) return "Invalid firmware type";
                    // Type: all, emoncms, firmware
                    $type="";
                    if (isset($_POST['type'])) $type = $_POST['type'];
                    if (!in_array($type,array("all","emoncms","firmware","emonhub"))) return "Invalid update type";
                    
                    $redis->rpush("service-runner","$update_script $type $firmware>$update_logfile");
                    return "service-runner trigger sent";
                }
                
                if ($route->subaction == 'getupdatelog' && $session['admin']) {
                    $route->format = "text";
                    ob_start();
                    passthru("cat " . $update_logfile);
                    $result = trim(ob_get_clean());
                }
                
                if ($route->subaction == 'downloadupdatelog' && $session['admin'])
                {
                    header("Content-Type: application/octet-stream");
                    header("Content-Transfer-Encoding: Binary");
                    header("Content-disposition: attachment; filename=\"" . basename($update_logfile) . "\"");
                    header("Pragma: no-cache");
                    header("Expires: 0");
                    flush();
                    if (file_exists($update_logfile))
                    {
                      ob_start();
                      readfile($update_logfile);
                      echo(trim(ob_get_clean()));
                    }
                    else
                    {
                      echo($update_logfile . " does not exist!");
                    }
                    exit;
                }
                
                if ($route->subaction == 'backup' && $session['write'] && $session['admin']) {
                    $route->format = "text";
                    
                    $fh = @fopen($backup_flag,"w");
                    if (!$fh) $result = "ERROR: Can't write the flag $backup_flag.";
                    else $result = "Update flag file $backup_flag created. Update will start on next cron call in " . (60 - (time() % 60)) . "s...";
                    @fclose($fh);
                }
                
                if ($route->subaction == 'getbackuplog' && $session['admin']) {
                    $route->format = "text";
                    ob_start();
                    passthru("cat " . $backup_logfile);
                    $result = trim(ob_get_clean());
                }
                
                if ($route->subaction == 'downloadbackuplog' && $session['admin'])
                {
                    header("Content-Type: application/octet-stream");
                    header("Content-Transfer-Encoding: Binary");
                    header("Content-disposition: attachment; filename=\"" . basename($backup_logfile) . "\"");
                    header("Pragma: no-cache");
                    header("Expires: 0");
                    flush();
                    if (file_exists($backup_logfile)) {
                      ob_start();
                      readfile($backup_logfile);
                      echo(trim(ob_get_clean()));
                    }
                    else
                    {
                      echo($backup_logfile . " does not exist!");
                    }
                    exit;
                }
                
                if ($route->subaction == "downloadbackup" && $session['write'] && $session['admin']) {
                    header("Content-type: application/zip");
                    header("Content-Disposition: attachment; filename=\"" . basename($backup_file) . "\"");
                    header("Pragma: no-cache");
                    header("Expires: 0");
                    readfile($backup_file);
                    exit;
                }

                if ($route->subaction == 'fs' && $session['admin'])
                {
                  if (isset($_POST['argument'])) {
                    $argument = $_POST['argument'];
                    }
                  if ($argument == 'ro'){
                    $result = passthru('rpi-ro');

                  }
                  if ($argument == 'rw'){
                    $result = passthru('rpi-rw');
                  }
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
            
            else if ($route->action == 'numberofusers')
            {
                $route->format = "text";
                $result = $mysqli->query("SELECT COUNT(*) FROM users");
                $row = $result->fetch_array();
                $result = (int) $row[0];
            }

            else if ($route->action == 'userlist')
            {

                $limit = "";
                if (isset($_GET['page']) && isset($_GET['perpage'])) {
                    $page = (int) $_GET['page'];
                    $perpage = (int) $_GET['perpage'];
                    $offset = $page * $perpage;
                    $limit = "LIMIT $perpage OFFSET $offset";
                }
                
                $orderby = "id";
                if (isset($_GET['orderby'])) {
                    if ($_GET['orderby']=="id") $orderby = "id";
                    if ($_GET['orderby']=="username") $orderby = "username";
                    if ($_GET['orderby']=="email") $orderby = "email";
                    if ($_GET['orderby']=="email_verified") $orderby = "email_verified";
                }
                
                $order = "DESC";
                if (isset($_GET['order'])) {
                    if ($_GET['order']=="decending") $order = "DESC";
                    if ($_GET['order']=="ascending") $order = "ASC";
                }
                
                $search = false;
                $searchstr = "";
                if (isset($_GET['search'])) {
                    $search = $_GET['search'];
                    $search_out = preg_replace('/[^\p{N}\p{L}_\s\-@.]/u','',$search);
                    if ($search_out!=$search || $search=="") { 
                        $search = false; 
                    }
                    if ($search!==false) $searchstr = "WHERE username LIKE '%$search%' OR email LIKE '%$search%'";
                }
            
                $data = array();
                $result = $mysqli->query("SELECT id,username,email,email_verified FROM users $searchstr ORDER BY $orderby $order ".$limit);
                
                while ($row = $result->fetch_object()) {
                    $data[] = $row;
                    $userid = (int) $row->id;
                    $result1 = $mysqli->query("SELECT * FROM feeds WHERE `userid`='$userid'");
                    $row->feeds = $result1->num_rows;
                    
                }
                $result = $data;
            }

            else if ($route->action == 'setuser' && $session['write'])
            {
                $_SESSION['userid'] = intval(get('id'));
                header("Location: ../user/view");
            }
            
            else if ($route->action == 'setuserfeed' && $session['write'])
            {
                $feedid = (int) get("id");
                $result = $mysqli->query("SELECT userid FROM feeds WHERE id=$feedid");
                $row = $result->fetch_object();
                $userid = $row->userid;
                $_SESSION['userid'] = $userid;
                header("Location: ../user/view");
            }
        }
    }
    else if ($updatelogin===true) {
        $route->format = 'html';
        if ($route->action == 'db')
        {
            $applychanges = false;
            if (isset($_GET['apply']) && $_GET['apply']==true) $applychanges = true;

            require_once "Lib/dbschemasetup.php";
            $updates = array(array(
                'title'=>"Database schema", 'description'=>"",
                'operations'=>db_schema_setup($mysqli,load_db_schema(),$applychanges)
            ));

            return array('content'=>view("Modules/admin/update_view.php", array('applychanges'=>$applychanges, 'updates'=>$updates)));
        }
    }

    return array('content'=>$result);
}
