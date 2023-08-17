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
    global $settings, $mysqli, $session, $route, $redis, $path, $log;
    
    if (!$session['write']) {
        return array('success'=>false, 'content'=>'', 'reauth'=>true, 'message'=>"Admin re-authentication required");
    }

    require_once "Modules/admin/admin_model.php";
    $admin = new Admin($mysqli, $redis, $settings);
    
    // --------------------------------------------------------------------------------------------
    // Allow for special admin session if updatelogin property is set to true in settings.php
    // Its important to use this with care and set updatelogin to false or remove from settings
    // after the update is complete.
    // --------------------------------------------------------------------------------------------
    if ($route->action == 'db' && ($session['admin'] || $settings['updatelogin']===true)) {
        $route->format = 'html';   
        $applychanges = false;
        if (isset($_GET['apply']) && $_GET['apply']==true) {
            $applychanges = true;
        }
        
        require_once "Lib/dbschemasetup.php";
        $updates = array(array(
            'title'=>"Database schema", 
            'description'=>"",
            'operations'=>db_schema_setup($mysqli,load_db_schema(),$applychanges)
        ));

        $error = !empty($updates[0]['operations']['error']) ? $updates[0]['operations']['error']: '';
        return view("Modules/admin/Views/mysql_update_view.php", array('applychanges'=>$applychanges, 'updates'=>$updates, 'error'=>$error));
    }
    
    // --------------------------------------------------------------------------------------------
    // If not an admin session show notice
    // --------------------------------------------------------------------------------------------
    if (!$session['admin']) {    
        $route->format = 'html';
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
    
    // Everything beyond this point requires an admin session as it will otherwise fail the above check
    
    // ----------------------------------------------------------------------------------------
    // Load html pages
    // ----------------------------------------------------------------------------------------
    
    // System information view
    if ($route->action == 'info') {
        $route->format = 'html';
        return view("Modules/admin/Views/admin_main_view.php", $admin->full_system_information());
    }
    
    // System update view
    if ($route->action == 'update') {
        $route->format = 'html';
        return view("Modules/admin/Views/update_view.php", array(
            'update_log_filename'=> $admin->update_logfile(),
            'serial_ports'=>$admin->listSerialPorts(),
            'firmware_available'=>$admin->firmware_available()
        ));
    }
            
    // System components view
    if ($route->action == 'components') {
        $route->format = 'html';
        return view("Modules/admin/Views/components_view.php", array(
            "components_installed"=>$admin->component_list(), 
            "components_available"=>$admin->components_available(),
            'redis_enabled'=>$settings['redis']['enabled']
        ));
    }
    
    // Firmware view
    if ($route->action == 'serial') {
        $route->format = 'html';
        return view("Modules/admin/Views/serialmonitor_view.php", array(
            'serial_ports'=>$admin->listSerialPorts()
        ));
    }

    // Firmware view
    if ($route->action == 'serconfig') {
        $route->format = 'html';
        return view("Modules/admin/Views/serial_config_view.php", array(
            'serial_ports'=>$admin->listSerialPorts()
        ));
    }
    
    // Emoncms log view
    if ($route->action == 'log') {
        $route->format = 'html';
        
        $log_levels = $log->levels();
        return view("Modules/admin/Views/emoncms_log_view.php", array(
            'log_enabled'=>$settings['log']['enabled'],
            'emoncms_logfile'=>$admin->emoncms_logfile(),
            'log_levels' => $log_levels,
            'log_level'=>$settings['log']['level'],
            'log_level_label' => $log_levels[$settings['log']['level']]     
        ));
    }
    
    // User list view
    if ($route->action == 'users') {
        $route->format = 'html';
        return view("Modules/admin/Views/userlist_view.php", array());
    }
    
    // ----------------------------------------------------------------------------------------
    // System info page actions
    // ----------------------------------------------------------------------------------------
    
    if ($route->action == 'service') {
        $route->format = 'json';
        // Validate service name
        if (!isset($_GET['name'])) {
            return array('success'=>false, 'message'=>"Missing name parameter");
        }
        $name = $_GET['name'];    
        if (!in_array($name,$admin->get_services_list())) {
            return array('success'=>false, 'message'=>"Invalid service");
        }
        
        if ($route->subaction == 'status') return $admin->getServiceStatus("$name.service");
        if ($route->subaction == 'start') return $admin->setService("$name.service",'start');
        if ($route->subaction == 'stop') return $admin->setService("$name.service",'stop');
        if ($route->subaction == 'restart') return $admin->setService("$name.service",'restart');
        if ($route->subaction == 'disable') return $admin->setService("$name.service",'disable');
        if ($route->subaction == 'enable') return $admin->setService("$name.service",'enable');
        return array('success'=>false, 'message'=>"Unknown subaction");
    }
    
    if ($route->action == 'shutdown') {
        $route->format = 'text';
        shell_exec('sudo shutdown -h now 2>&1');
        return "System halt in progress";
    }

    if ($route->action == 'reboot') {
        $route->format = 'text';
        shell_exec('sudo shutdown -r now 2>&1');
        return "System reboot in progress";
    }

    if ($route->action == 'redisflush') {
        $route->format = 'json';
        if ($redis) {
            $redis->flushDB();
            return array('success'=>true, 'used'=>$redis->info()['used_memory_human'], 'dbsize'=>$redis->dbSize());
        } else {
            return array('success'=>false, 'message'=>"Redis not enabled");
        }
    }

    if ($route->action == 'resetdiskstats') {
        $route->format = 'json';
        return $admin->disk_stats_reset();
    }

    if ($route->action == 'fs') {
        if (isset($_POST['argument'])) {
            $argument = $_POST['argument'];
            if ($argument == 'ro'){
                passthru('rpi-ro');
            } else if ($argument == 'rw'){
                passthru('rpi-rw');
            }
        }
        return array('success'=>false, 'message'=>"Missing argument");
    }

    // ----------------------------------------------------------------------------------------
    // System update
    // ----------------------------------------------------------------------------------------       
    if ($route->action == 'update-start') {
        $route->format = "json";
        if (!isset($_POST['type'])) return array('success'=>false,'message'=>"missing parameter: type");
        if (!isset($_POST['serial_port'])) return array('success'=>false, 'message'=>"missing parameter: serial_port");
        if (!isset($_POST['firmware_key'])) return array('success'=>false, 'message'=>"missing parameter: firmware_key");
        
        $type = $_POST['type'];
        if (!in_array($type,array("all","emoncms"))) return array('success'=>false, 'message'=>"Invalid update type");
        
        $serial_port = $_POST['serial_port'];   
        if (!in_array($serial_port,$admin->listSerialPorts())) return array('success'=>false, 'message'=>"Invalid serial port");

        $firmware_key = $_POST['firmware_key'];        
        $firmware_available = $admin->firmware_available();
        if (!isset($firmware_available->$firmware_key) && $firmware_key!="none") return array('success'=>false, 'message'=>"invalid firmware");

        if (file_exists($settings['openenergymonitor_dir']."/EmonScripts")) {
            $update_script = $settings['openenergymonitor_dir']."/EmonScripts/update/service-runner-update.sh";
        } else {
            $update_script = $settings['openenergymonitor_dir']."/emonpi/service-runner-update.sh";
        }
        return $admin->runService($update_script, "$type $firmware_key $serial_port>".$admin->update_logfile());
    }
    
    if ($route->action == 'update-firmware') {
        $route->format = "json";

        if (!isset($_POST['serial_port'])) return array('success'=>false, 'message'=>"missing parameter: serial_port");
        if (!isset($_POST['firmware_key'])) return array('success'=>false, 'message'=>"missing parameter: firmware_key");

        $serial_port = $_POST['serial_port'];
        if (!in_array($serial_port,$admin->listSerialPorts())) return array('success'=>false, 'message'=>"Invalid serial port");
        
        $firmware_key = $_POST['firmware_key'];        
        $firmware_available = $admin->firmware_available();
        if (!isset($firmware_available->$firmware_key)) return array('success'=>false, 'message'=>"Invalid firmware");
        
        $update_script = $settings['openenergymonitor_dir']."/EmonScripts/update/atmega_firmware_upload.sh";
        return $admin->runService($update_script, "$serial_port $firmware_key>".$admin->update_logfile());
    }
    
    if ($route->action == 'update-log') {
        $route->format = "text";
        if (file_exists($admin->update_logfile())) {
            ob_start();
            passthru("cat " . $admin->update_logfile());
            return trim(ob_get_clean());
        }
        else if (file_exists($admin->old_update_logfile())) {
            ob_start();
            passthru("cat " . $admin->old_update_logfile());
            return trim(ob_get_clean());
        }
        else {
            $route->format = "json";
            return array('success'=>false, 'message'=>$admin->update_logfile()." does not exist");
        }
    }
    
    if ($route->action == 'update-log-download') {
        header("Content-Type: application/octet-stream");
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($admin->update_logfile()) . "\"");
        header("Pragma: no-cache");
        header("Expires: 0");
        flush();
        if (file_exists($admin->update_logfile())) {
            ob_start();
            readfile($admin->update_logfile());
            echo(trim(ob_get_clean()));
        } else if (file_exists($admin->old_update_logfile())) {
            ob_start();
            readfile($admin->old_update_logfile());
            echo(trim(ob_get_clean()));
        } else {
            echo($admin->update_logfile() . " does not exist!");
        }
        exit;
    }

    // ----------------------------------------------------------------------------------------
    // Component manager
    // ----------------------------------------------------------------------------------------
    if ($route->action == 'components-installed' && $session['write']) {
        $route->format = "json";
        return $admin->component_list();
    }
    
    if ($route->action == 'components-available' && $session['write']) {
        $route->format = "json";
        return $admin->components_available();
    }
   
    if ($route->action == 'component-update' && $session['write']) {
        $route->format = "json";
        if (!isset($_GET['module'])) return array('success'=>false, 'message'=>"missing parameter: module"); else $module = $_GET['module'];
        if (!isset($_GET['branch'])) return array('success'=>false, 'message'=>"missing parameter: branch"); else $branch = $_GET['branch'];
        $reset = (isset($_GET['reset']) && ($_GET['reset'] == "true") ? true : false);
        return $admin->component_update($module, $branch, $reset);
    }
    
    if ($route->action == 'components-update-all' && $session['write']) {
        $route->format = "json";
        if (!isset($_GET['branch'])) return array('success'=>false, 'message'=>"missing parameter: branch"); else $branch = $_GET['branch'];
        return $admin->component_update_all($branch);
    }

    if ($route->action == 'component-install' && $session['write']) {
        $route->format = "json";
        if (!isset($_GET['module'])) return array('success'=>false, 'message'=>"missing parameter: module"); else $module = $_GET['module'];
        if (!isset($_GET['branch'])) return array('success'=>false, 'message'=>"missing parameter: branch"); else $branch = $_GET['branch'];
        return $admin->component_install($module, $branch);
    }

    if ($route->action == 'component-uninstall' && $session['write']) {
        $route->format = "json";
        if (!isset($_GET['module'])) return array('success'=>false, 'message'=>"missing parameter: module"); else $module = $_GET['module'];
        $reset = (isset($_GET['reset']) && ($_GET['reset'] == "true") ? true : false);
        return $admin->component_uninstall($module, $reset);
    }
    
    // ----------------------------------------------------------------------------------------
    // Firmware
    // ----------------------------------------------------------------------------------------
    if ($route->action == 'serialmonitor') {
        if ($route->subaction == 'running') {
            $route->format = "text";      
            @exec('pidof -x start.sh', $exec);
            $pid = False;
            if (isset($exec[0])) $pid = $exec[0];
            return $pid;
        }
        if ($route->subaction == 'start') {
            $route->format = "json";
            
            if (!isset($_POST['serialport'])) return array('success'=>false, 'message'=>"missing parameter: serialport");
            if (!isset($_POST['baudrate'])) return array('success'=>false, 'message'=>"missing parameter: baudrate");
            $serialport = $_POST['serialport'];
            $baudrate = (int) $_POST['baudrate'];
            if (!in_array($serialport,$admin->listSerialPorts())) return array('success'=>false, 'message'=>"invalid serial port");
            if (!in_array($baudrate,array(9600,38400,115200))) return array('success'=>false, 'message'=>"invalid baud rate");
            
            $script = "/var/www/emoncms/scripts/serialmonitor/start.sh";
            return $admin->runService($script, "$baudrate /dev/$serialport");
        }
        if ($route->subaction == 'stop') {
            $route->format = "json";
            if (!$redis) return array('success'=>false, 'message'=>"Redis not enabled");
            $redis->rpush("serialmonitor","exit");
            return array('success'=>true,  'message'=>"serialmonitor stop command sent"); 
        }
        if ($route->subaction == 'log') {
            if (!$redis) {
                $route->format = "json";
                return array('success'=>false, 'message'=>"Redis not enabled");
            }
            $route->format = "text";
            $out = "";
            while($redis->llen('serialmonitor-log')) {
                $out .= $redis->lpop('serialmonitor-log')."\n";
            }
            return $out;
        }
        if ($route->subaction == 'cmd') {
            $route->format = "json";
            if (!$redis) return array('success'=>false, 'message'=>"Redis not enabled");
            $cmd = "";
            if (isset($_GET['cmd'])) $cmd = $_GET['cmd'];
            if (isset($_POST['cmd'])) $cmd = $_POST['cmd'];
            if ($cmd!="") {
                $redis->rpush("serialmonitor",$cmd);
                return array('success'=>true, 'message'=>"serialmonitor cmd sent: $cmd");
            } else {
                return array('success'=>false, 'message'=>"no command");
            }
                
        }
    }
    
    // ----------------------------------------------------------------------------------------
    // Emoncms log
    // ----------------------------------------------------------------------------------------
    if ($route->action == 'downloadlog') {
        if ($settings['log']['enabled']) {
            header("Content-Type: application/octet-stream");
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . basename($admin->emoncms_logfile()) . "\"");
            header("Pragma: no-cache");
            header("Expires: 0");
            flush();
            if (file_exists($admin->emoncms_logfile())) {
                readfile($admin->emoncms_logfile());
            } else {
                echo($admin->emoncms_logfile() . " does not exist!");
            }
            exit;
        }
        return false;
    }
    
    if ($route->action == 'getlog') {
        if (!$settings['log']['enabled']) {
            $route->format = "json";
            return array('success'=>false, 'message'=>"Log is disabled");
        }
        else if (!file_exists($admin->emoncms_logfile())) { 
            $route->format = "json";
            return array('success'=>false, 'message'=>$admin->emoncms_logfile() . " does not exist");
        }

        $route->format = "text";
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

        $fsize = round(filesize($admin->emoncms_logfile())/1024/1024,2);
        $lines = read_file($admin->emoncms_logfile(), 25);
        
        foreach ($lines as $line) {
          echo $line;
        } //End PHP replacement for Tail
        return trim(ob_get_clean());
    }

    // ----------------------------------------------------------------------------------------
    // Users
    // ----------------------------------------------------------------------------------------
    if ($route->action == 'setuser') {
        $_SESSION['userid'] = intval(get('id'));
        header("Location: ../user/view");
        // stop any other code from running once http header sent
        exit();
    }
    
    if ($route->action == 'numberofusers') {
        $route->format = "text";
        $result = $mysqli->query("SELECT COUNT(*) FROM users");
        $row = $result->fetch_array();
        return (int) $row[0];
    }

    if ($route->action == 'userlist') {
        $route->format = 'json';
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
    
    if ($route->action == 'setuserfeed' && $session['write']) {
        $route->format = 'json';
        $feedid = (int) get("id");
        $result = $mysqli->query("SELECT userid FROM feeds WHERE id=$feedid");
        $row = $result->fetch_object();
        $userid = $row->userid;
        $_SESSION['userid'] = $userid;
        header("Location: ../user/view");
        return false;
    }

    return EMPTY_ROUTE;
}
