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
        if (in_array($route->action,array('update-log','getlog','serialmonitor'))) {
            $route->format = 'text';
            return "Admin re-authentication required";
        }
        return array('content'=>'','message'=>'Admin re-authentication required'); 
    }
    
    $emoncms_logfile = $settings['log']['location']."/emoncms.log";
    $update_logfile = $settings['log']['location']."/update.log";
    $old_update_logfile = $settings['log']['location']."/emonpiupdate.log";    
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
        return view("Modules/admin/mysql_update_view.php", array('applychanges'=>$applychanges, 'updates'=>$updates, 'error'=>$error));
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
        require "Modules/admin/admin_model.php";
        return view("Modules/admin/admin_main_view.php",Admin::full_system_information());
    }
    
    // System update view
    if ($route->action == 'update') {
        $route->format = 'html';
        require "Modules/admin/admin_model.php";
        return view("Modules/admin/update_view.php", array(
            'update_log_filename'=> $update_logfile,
            'serial_ports'=>Admin::listSerialPorts(),
            'firmware_available'=>Admin::firmware_available()
        ));
    }
            
    // System components view
    if ($route->action == 'components') {
        $route->format = 'html';
        require "Modules/admin/admin_model.php";
        return view("Modules/admin/components_view.php", array("components"=>Admin::component_list()));
    } 
    
    // Firmware view
    if ($route->action == 'serial') {
        $route->format = 'html';
        require "Modules/admin/admin_model.php";
        return view("Modules/admin/firmware_view.php", array(
            'serial_ports'=>Admin::listSerialPorts()
        ));
    }
    
    // Emoncms log view
    if ($route->action == 'emoncmslog') {
        $route->format = 'html';
        
        $log_levels = array(
            1 =>'INFO',
            2 =>'WARN', // default
            3 =>'ERROR'
        );  
        
        return view("Modules/admin/emoncms_log_view.php", array(
            'log_enabled'=>$settings['log']['enabled'],
            'emoncms_logfile'=>$emoncms_logfile,
            'log_levels' => $log_levels,
            'log_level'=>$settings['log']['level'],
            'log_level_label' => $log_levels[$settings['log']['level']]     
        ));
    }
    
    // User list view
    if ($route->action == 'users') {
        $route->format = 'html';
        return view("Modules/admin/userlist_view.php", array());
    }
    
    // ----------------------------------------------------------------------------------------
    // System info page actions
    // ----------------------------------------------------------------------------------------
    
    if ($route->action == 'service') {
        $route->format = 'json';
        // Validate service name
        if (!isset($_GET['name'])) {
            return "missing name parameter";
        }
        $name = $_GET['name'];
        require "Modules/admin/admin_model.php";     
        if (!in_array($name,Admin::get_services_list())) {
            return "invalid service";
        }
        
        if ($route->subaction == 'status') return Admin::getServiceStatus("$name.service");
        if ($route->subaction == 'start') return Admin::setService("$name.service",'start');
        if ($route->subaction == 'stop') return Admin::setService("$name.service",'stop');
        if ($route->subaction == 'restart') return Admin::setService("$name.service",'restart');
        if ($route->subaction == 'disable') return Admin::setService("$name.service",'disable');
        if ($route->subaction == 'enable') return Admin::setService("$name.service",'enable');
        return false;
    }
    
    if ($route->action == 'shutdown') {
        $route->format = 'text';
        shell_exec('sudo shutdown -h now 2>&1');
        return "system halt in progress";
    }

    if ($route->action == 'reboot') {
        $route->format = 'text';
        shell_exec('sudo shutdown -r now 2>&1');
        return "system reboot in progress";
    }

    if ($route->action == 'redisflush') {
        $route->format = 'json';
        if ($redis) {
            $redis->flushDB();
            return array('used'=>$redis->info()['used_memory_human'], 'dbsize'=>$redis->dbSize());
        } else {
            return false;
        }
    }

    if ($route->action == 'resetwriteload') {
        $route->format = 'json';
        if ($redis) {
            $redis->del("diskstats:mmcblk0p1");
            $redis->del("diskstats:mmcblk0p2");
            $redis->del("diskstats:mmcblk0p3");
            $redis->del("diskstats:time");
        }
        return true;
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
        return false;
    }

    // ----------------------------------------------------------------------------------------
    // System update
    // ----------------------------------------------------------------------------------------       
    if ($route->action == 'update-start') {
        $route->format = "text";
        if (!$redis) return "redis not running";
        if (!isset($_POST['type'])) return "missing parameter: type";
        if (!isset($_POST['serial_port'])) return "missing parameter: serial_port";
        if (!isset($_POST['firmware_key'])) return "missing parameter: firmware_key";
        
        $type = $_POST['type'];
        if (!in_array($type,array("all","emoncms"))) return "Invalid update type";
        
        $serial_port = $_POST['serial_port'];
        require "Modules/admin/admin_model.php";     
        if (!in_array($serial_port,Admin::listSerialPorts())) return "Invalid serial port";

        $firmware_key = $_POST['firmware_key'];        
        $firmware_available = Admin::firmware_available();
        if (!isset($firmware_available->$firmware_key) && $firmware_key!="none") return "invalid firmware";

        if (file_exists($settings['openenergymonitor_dir']."/EmonScripts")) {
            $update_script = $settings['openenergymonitor_dir']."/EmonScripts/update/service-runner-update.sh";
        } else {
            $update_script = $settings['openenergymonitor_dir']."/emonpi/service-runner-update.sh";
        }        
        $redis->rpush("service-runner","$update_script $type $firmware_key $serial_port>$update_logfile");
        return "service-runner trigger sent";
    }
    
    if ($route->action == 'update-firmware') {
        $route->format = "text";
        if (!$redis) return "redis not running";
        if (!isset($_POST['serial_port'])) return "missing parameter: serial_port";
        if (!isset($_POST['firmware_key'])) return "missing parameter: firmware_key";

        $serial_port = $_POST['serial_port'];
        require "Modules/admin/admin_model.php";     
        if (!in_array($serial_port,Admin::listSerialPorts())) return "Invalid serial port";
        
        $firmware_key = $_POST['firmware_key'];        
        $firmware_available = Admin::firmware_available();
        if (!isset($firmware_available->$firmware_key)) return "invalid firmware";
        
        $update_script = $settings['openenergymonitor_dir']."/EmonScripts/update/atmega_firmware_upload.sh"; 
        $redis->rpush("service-runner","$update_script $serial_port $firmware_key>$update_logfile");
        return "service-runner trigger sent";
    }
    
    if ($route->action == 'update-log') {
        $route->format = "text";
        if (file_exists($update_logfile)) {
            ob_start();
            passthru("cat " . $update_logfile);
            return trim(ob_get_clean());
        }
        else if (file_exists($old_update_logfile)) {
            ob_start();
            passthru("cat " . $old_update_logfile);
            return trim(ob_get_clean());
        }
        else {
            return "$update_logfile does not exist";
        }
    }
    
    if ($route->action == 'update-log-download') {
        header("Content-Type: application/octet-stream");
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($update_logfile) . "\"");
        header("Pragma: no-cache");
        header("Expires: 0");
        flush();
        if (file_exists($update_logfile)) {
            ob_start();
            readfile($update_logfile);
            echo(trim(ob_get_clean()));
        } else if (file_exists($old_update_logfile)) {
            ob_start();
            readfile($old_update_logfile);
            echo(trim(ob_get_clean()));
        } else {
            echo($update_logfile . " does not exist!");
        }
        exit;
    }
    
    // ----------------------------------------------------------------------------------------
    // Component manager
    // ----------------------------------------------------------------------------------------
    if ($route->action == 'components-installed' && $session['write']) {
        $route->format = "json";
        require "Modules/admin/admin_model.php";
        return Admin::component_list(true);
    }
    
    if ($route->action == 'components-available' && $session['write']) {
        $route->format = "json";
        if (file_exists("/opt/openenergymonitor/EmonScripts/components_available.json")) {
            return json_decode(file_get_contents("/opt/openenergymonitor/EmonScripts/components_available.json"));
        } else {
            return false;
        }
    }
   
    if ($route->action == 'component-update' && $session['write']) {
        $route->format = "text";

        require "Modules/admin/admin_model.php";                
        $components = Admin::component_list(false);
        
        if (!isset($_GET['module'])) return "missing module parameter"; else $module = $_GET['module'];
        if (!isset($_GET['branch'])) return "missing branch parameter"; else $branch = $_GET['branch'];
        
        if (!isset($components[$module])) return "invalid module";
        $module_path = $components[$module]["path"];     
        
        // if branch is not in available branches, check that it is not the current branch
        if (!in_array($branch,$components[$module]["branches_available"])) {
            $current_branch = @exec("git -C $module_path rev-parse --abbrev-ref HEAD");
            if ($branch!=$current_branch) return "invalid branch";
        }
        
        $script = "/opt/openenergymonitor/EmonScripts/update/update_component.sh";
        $redis->rpush("service-runner","$script $module_path $branch>$update_logfile");
        return "cmd sent";
    }
    
    if ($route->action == 'components-update-all' && $session['write']) {
        $route->format = "text";
        if (!isset($_GET['branch'])) return "missing branch parameter"; else $branch = $_GET['branch'];
        
        // Validate branch
        require "Modules/admin/admin_model.php";
        $available_branches = array();
        foreach (Admin::component_list(false) as $c) {
            foreach ($c["branches_available"] as $b) {
                if (!in_array($b,$available_branches)) $available_branches[] = $b;
            }
        }
        if (!in_array($branch,$available_branches)) return "invalid branch";
        
        $script = "/opt/openenergymonitor/EmonScripts/update/update_all_components.sh";
        $redis->rpush("service-runner","$script $branch>$update_logfile");
        return "cmd sent";
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
            $route->format = "text";
            
            if (!isset($_POST['serialport'])) return "missing parameter: serialport";
            if (!isset($_POST['baudrate'])) return "missing parameter: baudrate";
            
            $serialport = $_POST['serialport'];
            $baudrate = (int) $_POST['baudrate'];
            
            require "Modules/admin/admin_model.php";
            if (!in_array($serialport,Admin::listSerialPorts())) return "invalid serial port";
            if (!in_array($baudrate,array(9600,38400,115200))) return "invalid baud rate";
            
            $script = "/var/www/emoncms/scripts/serialmonitor/start.sh";
            $redis->rpush("service-runner","$script $baudrate /dev/$serialport");
            return "service-runner serialmonitor start"; 
        }
        if ($route->subaction == 'stop') {
            $route->format = "text";  
            $redis->rpush("serialmonitor","exit");
            return "serialmonitor stop command sent";
        }
        if ($route->subaction == 'log') {
            $route->format = "text";
            $out = "";
            while($redis->llen('serialmonitor-log')) {
                $out .= $redis->lpop('serialmonitor-log')."\n";
            }
            return $out;
        }
        if ($route->subaction == 'cmd') {
            $route->format = "text";
            $cmd = "";
            if (isset($_GET['cmd'])) $cmd = $_GET['cmd'];
            if (isset($_POST['cmd'])) $cmd = $_POST['cmd'];
            if ($cmd!="") {
                $redis->rpush("serialmonitor",$cmd);
                return "serialmonitor cmd sent: $cmd";
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
            header("Content-disposition: attachment; filename=\"" . basename($emoncms_logfile) . "\"");
            header("Pragma: no-cache");
            header("Expires: 0");
            flush();
            if (file_exists($emoncms_logfile)) {
                readfile($emoncms_logfile);
            } else {
                echo($emoncms_logfile . " does not exist!");
            }
            exit;
        }
        return false;
    }
    
    if ($route->action == 'getlog') {
        $route->format = "text";
        if (!$settings['log']['enabled']) return "Log is disabled";
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
