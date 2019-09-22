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
    global $mysqli,$session,$route,$updatelogin,$allow_emonpi_admin, $redis, $openenergymonitor_dir, $admin_show_update, $path;
    global $log, $log_location, $log_enabled, $log_level;
    
    $result = EMPTY_ROUTE;// display missing route message by default
    $message = _('406: Route not found');
    
    if(!$session['write']) {
        $result = ''; // empty result shows login page (now redirects once logged in)
        $message = _('Admin re-authentication required');
    }   

    // Allow for special admin session if updatelogin property is set to true in settings.php
    // Its important to use this with care and set updatelogin to false or remove from settings
    // after the update is complete.

    //put $update_logfile here so it can be referenced in other if statements
    //before it was only accesable in the update subaction
    //placed some other variables here as well so they are grouped
    //together for the emonpi action even though they might not be used
    //in the subaction
    $emoncms_logfile = "$log_location/emoncms.log";
    $update_logfile = "$log_location/emonpiupdate.log";
    $backup_logfile = "$log_location/emonpibackup.log";
    $update_flag = "/tmp/emoncms-flag-update";
    $backup_flag = "/tmp/emonpibackup";
    if (file_exists("$openenergymonitor_dir/EmonScripts")) {
        $update_script = "$openenergymonitor_dir/EmonScripts/update/service-runner-update.sh";
    } else {
        $update_script = "$openenergymonitor_dir/emonpi/service-runner-update.sh";
    }
    $backup_file = "$openenergymonitor_dir/data/backup.tar.gz";
    
    $log_levels = array(
        1 =>'INFO',
        2 =>'WARN', // default
        3 =>'ERROR'
    );

    $path_to_config = 'settings.php';
    
    if ($session['admin']) {
        
        if ($route->format == 'html') {
            if ($route->action == 'view') {
                require "Modules/admin/admin_model.php";
                global $path, $emoncms_version, $redis_enabled, $mqtt_enabled, $feed_settings, $shutdownPi;

                // Shutdown / Reboot Code Handler
                if (isset($_POST['shutdownPi'])) {
                    $shutdownPi = htmlspecialchars(stripslashes(trim($_POST['shutdownPi'])));
                }
                if (isset($shutdownPi)) { if ($shutdownPi == 'reboot') { shell_exec('sudo shutdown -r now 2>&1'); } elseif ($shutdownPi == 'halt') { shell_exec('sudo shutdown -h now 2>&1'); } }
                // create array of installed services
                $services = array();
                $system = Admin::system_information();
                foreach($system['services'] as $key=>$value) {
                    if (!is_null($system['services'][$key])) {
                        $services[$key] = array(
                            'state' => ucfirst($value['ActiveState']),
                            'text' => ucfirst($value['SubState']),
                            'cssClass' => $value['SubState']==='running' ? 'success': 'danger',
                            'running' => $value['SubState']==='running'
                        );
                    }
                }
                // add custom messages for feedwriter service
                if(isset($services['feedwriter'])) {
                    $message = '<font color="red">Service is not running</font>';
                    if ($services['feedwriter']['running']) {
                        $message = ' - sleep ' . $feed_settings['redisbuffer']['sleep'] . 's';
                    }
                    $services['feedwriter']['text'] .= $message . ' <span id="bufferused">loading...</span>';
                }
                $redis_info = array();
                if($redis_enabled) {
                    $redis_info = $redis->info();
                    $redis_info['dbSize'] = $redis->dbSize();
                    $phpRedisPattern = 'Redis Version =>';
                    $redis_info['phpRedis'] = substr(shell_exec("php -i | grep '".$phpRedisPattern."'"), strlen($phpRedisPattern));
                    $pipRedisPattern = "Version: ";
                    $redis_info['pipRedis'] = substr(shell_exec("pip show redis --disable-pip-version-check | grep '".$pipRedisPattern."'"), strlen($pipRedisPattern));
                }

                $view_data = array(
                    'system'=>$system,
                    'services'=>$services,
                    'admin_show_update'=>$admin_show_update,
                    'shutdownPi'=>$shutdownPi,
                    'log_enabled'=>$log_enabled,
                    'update_log_filename'=>$update_logfile,
                    'redis_enabled'=>$redis_enabled,
                    'mqtt_enabled'=>$mqtt_enabled,
                    'emoncms_version'=>$emoncms_version,
                    'path'=>$path,
                    'allow_emonpi_admin'=>$allow_emonpi_admin,
                    'emoncms_logfile'=>$emoncms_logfile,
                    'redis_info'=>$redis_info,
                    'feed_settings'=>$feed_settings,
                    'emoncms_modules'=>$system['emoncms_modules'],
                    'php_modules'=>Admin::php_modules($system['php_modules']),
                    'mqtt_version'=>Admin::mqtt_version(),
                    'rpi_info'=> Admin::get_rpi_info(),
                    'ram_info'=> Admin::get_ram($system['mem_info']),
                    'disk_info'=> Admin::get_mountpoints($system['partitions']),
                    'v' => 3,
                    'log_levels' => $log_levels,
                    'log_level'=>$log_level,
                    'log_level_label' => $log_levels[$log_level],
                    'path_to_config'=> $path_to_config
                );
                
                return view("Modules/admin/admin_main_view.php", $view_data);
            }

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
                return view("Modules/admin/update_view.php", array('applychanges'=>$applychanges, 'updates'=>$updates, 'error'=>$error));
            }

            else if ($route->action == 'users' && $session['write'])
            {
                return view("Modules/admin/userlist_view.php", array());
            }

            else if ($route->action == 'setuser' && $session['write'])
            {
                $_SESSION['userid'] = intval(get('id'));
                header("Location: ../user/view");
                // stop any other code from running once http header sent
                exit();
            }
            
            else if ($route->action == 'downloadlog')
            {
              if ($log_enabled) {
                header("Content-Type: application/octet-stream");
                header("Content-Transfer-Encoding: Binary");
                header("Content-disposition: attachment; filename=\"" . basename($emoncms_logfile) . "\"");
                header("Pragma: no-cache");
                header("Expires: 0");
                flush();
                if (file_exists($emoncms_logfile)) {
                  readfile($emoncms_logfile);
                }
                else
                {
                  echo($emoncms_logfile . " does not exist!");
                }
                exit;
              }
            }

            else if ($route->action == 'getlog')
            {
                $route->format = "text";
                if (!$log_enabled) return "Log is disabled";
                if (!file_exists($emoncms_logfile)) return "$emoncms_logfile does not exist";
                
                ob_start();
                // PHP replacement for tail starts here
                function read_file($file, $lines) 
                {
                    //global $fsize;
                    $handle = fopen($file, "r");
                    $linecounter = $lines;
                    $pos = -2;
                    $beginning = false;
                    $text = array();
                    while ($linecounter > 0) {
                        $t = " ";
                        while ($t != "\n") {
                            if (!empty($handle) && fseek($handle, $pos, SEEK_END) == -1) {
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

                $fsize = round(filesize($emoncms_logfile)/1024/1024,2);
                $lines = read_file($emoncms_logfile, 25);
                
                foreach ($lines as $line) {
                  echo $line;
                } //End PHP replacement for Tail
                return trim(ob_get_clean());
            }

            else if (($admin_show_update || $allow_emonpi_admin) && $route->action == 'emonpi') {
                                
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
                    if (!file_exists($update_logfile)) return "$update_logfile does not exist";
                    ob_start();
                    passthru("cat " . $update_logfile);
                    return trim(ob_get_clean());
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
                    if (!$fh) return "ERROR: Can't write the flag $backup_flag.";
                    else $result = "Update flag file $backup_flag created. Update will start on next cron call in " . (60 - (time() % 60)) . "s...";
                    @fclose($fh);
                }
                
                if ($route->subaction == 'getbackuplog' && $session['admin']) {
                    $route->format = "text";
                    ob_start();
                    passthru("cat " . $backup_logfile);
                    return trim(ob_get_clean());
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
                    return passthru('rpi-ro');

                  }
                  if ($argument == 'rw'){
                    return passthru('rpi-rw');
                  }
                }
            }
        }
        else if ($route->format == 'json')
        {
            if ($route->action == 'redisflush' && $session['write'])
            {
                $redis->flushDB();
                return array('used'=>$redis->info()['used_memory_human'], 'dbsize'=>$redis->dbSize());
            }
            
            else if ($route->action == 'numberofusers')
            {
                $route->format = "text";
                $result = $mysqli->query("SELECT COUNT(*) FROM users");
                $row = $result->fetch_array();
                return (int) $row[0];
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
                return $data;
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
            else if ($route->action == 'system' && $session['write'])
            {
                require "Modules/admin/admin_model.php";
                global $path, $emoncms_version, $redis_enabled, $mqtt_enabled, $feed_settings, $shutdownPi;

                // create array of installed services
                $services = array();
                $system = Admin::system_information();
                foreach($system['services'] as $key=>$value) {
                    if (!is_null($system['services'][$key])) {
                        $services[$key] = array(
                            'state' => ucfirst($value['ActiveState']),
                            'text' => ucfirst($value['SubState']),
                            'cssClass' => $value['SubState']==='running' ? 'success': 'danger',
                            'running' => $value['SubState']==='running'
                        );
                    }
                }
                // add custom messages for feedwriter service
                if(isset($services['feedwriter'])) {
                    $message = 'Service is not running';
                    if ($services['feedwriter']['running']) {
                        $message = ' - sleep ' . $feed_settings['redisbuffer']['sleep'] . 's';
                    }
                    $services['feedwriter']['text'] .= $message;
                }

                $view_data = array(
                    'system'=>$system,
                    'services'=>$services,
                    'log_enabled'=>$log_enabled,
                    'redis_enabled'=>$redis_enabled,
                    'mqtt_enabled'=>$mqtt_enabled,
                    'emoncms_version'=>$emoncms_version,
                    'path'=>$path,
                    'emoncms_logfile'=>$emoncms_logfile,
                    'update_log_filename'=> $update_logfile,
                    'redis'=>$redis,
                    'feed_settings'=>$feed_settings,
                    'emoncms_modules'=>$system['emoncms_modules'],
                    'php_modules'=>Admin::php_modules($system['php_modules']),
                    'mqtt_version'=>Admin::mqtt_version(),
                    'rpi_info'=> Admin::get_rpi_info(),
                    'ram_info'=> Admin::get_ram($system['mem_info']),
                    'disk_info'=> Admin::get_mountpoints($system['partitions'])
                );
                
                return $view_data;
            }
            else if ($route->action === 'loglevel' && $session['write']) {
                // current values
                $success = false;
                $log_level_name = $log_levels[$log_level];
                $message = '';

                if ($route->method === 'POST') {
                    if(!empty(post('level'))) {
                        if (is_file($path_to_config) && is_writable($path_to_config)) {
                            $level = intval(post('level'));
                            if(array_key_exists($level, $log_levels)) {
                                // load the settings.php as text file
                                $file = file_get_contents($path_to_config);
                                $matches = array();
                                // replace the value of the $log_level variable
                                preg_match('/^\s+\$log_level = (.*)$/m', $file, $matches);
                                if(!empty($matches)) {
                                    $file = str_replace($matches[1], $level.';', $file);
                                    file_put_contents($path_to_config, $file);
                                    $success = true;
                                    $log_level = $level;
                                    $log_level_name = $log_levels[$level];
                                    $log->error("Log level changed: $level");
                                    $message = _('Changes Saved');
                                } else {
                                    $message = sprintf(_('"$log_level" not found in: %s'), $path_to_config);
                                }
                            } else {
                                $message = sprintf(_('New log level out of range. must be one of %s'), implode(', ', array_keys($log_levels)));
                            }
                        } else {
                            $message = sprintf(_('Not able to write to: %s'), $path_to_config);
                        }
                    } else {
                        $message = _('No new log level supplied');
                    }
                } elseif ($route->method === 'GET') {
                    $success = true;
                }

                return array(
                    'success' => $success,
                    'log-level' => $log_level,
                    'log-level-name' => $log_level_name,
                    'message' => $message
                );
            }
        }
    } else {
        // not $session['admin']

        if ($updatelogin===true) {
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
        } else {
            // user not admin level display login
            $log->error(sprintf('%s|%s',_('Not Admin'), implode('/',array_filter(array($route->controller,$route->action,$route->subaction)))));
            $message = urlencode(_('Admin Authentication Required'));
            
            $referrer = urlencode(base64_encode(filter_var($_SERVER['REQUEST_URI'] , FILTER_SANITIZE_URL)));
            return sprintf(
                '<div class="alert alert-warn mt-3"><h4 class="mb-1">%s</h4>%s. <a href="%s" class="alert-link">%s</a></div>', 
                _('Admin Authentication Required'),
                _('Session timed out or user not Admin'),
                sprintf("%suser/logout?msg=%s&ref=%s",$path, $message, $referrer),
                _('Re-authenticate to see this page')
            );
        }
    }

    return array('content'=>$result,'message'=>$message);
}
