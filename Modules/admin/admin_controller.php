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
    global $settings, $mysqli, $session, $route, $redis, $path, $log, $user;

    if (!$session['write']) {
        return array('success'=>false, 'content'=>'', 'reauth'=>true, 'message'=>"Admin re-authentication required");
    }

    require_once "Modules/admin/admin_model.php";
    $admin = new Admin($settings);

    // --------------------------------------------------------------------------------------------
    // Allow for special admin session if updatelogin property is set to true in settings.php
    // Its important to use this with care and set updatelogin to false or remove from settings
    // after the update is complete.
    // --------------------------------------------------------------------------------------------
    if ($route->action == 'db' && ($session['admin'] || $settings['updatelogin']===true)) {
        $route->format = 'html';
        if (!$session['admin'] && $settings['updatelogin']===true) {
            $log->warn("DB schema update accessed via updatelogin bypass (not an admin session). Set updatelogin to false in settings after update is complete.");
        }
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
    if (!isset($session['admin']) || !$session['admin']) {
        $route->format = 'html';
        // user not admin level display login
        $log->error(sprintf('%s|%s',tr('Not Admin'), implode('/',array_filter(array($route->controller,$route->action,$route->subaction)))));
        $message = urlencode(tr('Admin Authentication Required'));

        $referrer = urlencode(base64_encode(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL)));
        return sprintf(
            '<div class="alert alert-warn mt-3"><h4 class="mb-1">%s</h4>%s. <a href="%s" class="alert-link">%s</a></div>',
            tr('Admin Authentication Required'),
            tr('Session timed out or user not Admin'),
            sprintf("%suser/logout?msg=%s&ref=%s",$path, $message, $referrer),
            tr('Re-authenticate to see this page')
        );
    }

    // Everything beyond this point requires an admin session as it will otherwise fail the above check

    // ----------------------------------------------------------------------------------------
    // Load html pages
    // ----------------------------------------------------------------------------------------

    // System information view
    if ($route->action == 'info') {
        $route->format = 'html';
        return view("Modules/admin/info/system_info_view.php", array());
    }

    // System information JSON test endpoint using class-based provider
    if ($route->action == 'systeminfo') {
        
        require_once "Modules/admin/info/SystemInfo.php";
        $route->format = 'json';
        $systemInfo = new SystemInfo($mysqli, $redis, $settings);
        $result = $systemInfo->getSystemInfo();

        require_once "Modules/admin/info/Services.php";
        $services = new Services($redis, $log, $settings);
        $result['Services'] = $services->getServices();

        return $result;
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
        $admin->shutdown_system();
        return "System halt in progress";
    }

    if ($route->action == 'reboot') {
        $route->format = 'text';
        $admin->reboot_system();
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
                $admin->set_filesystem_ro();
            } elseif ($argument == 'rw'){
                $admin->set_filesystem_rw();
            }
        }
        return array('success'=>false, 'message'=>"Missing argument");
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
        } elseif (!file_exists($admin->emoncms_logfile())) {
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
                    $pos--;
                }
                $linecounter--;
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
    // System update
    // ----------------------------------------------------------------------------------------
    if ($route->action == 'update') {
        require_once "Modules/admin/update/UpdateModel.php";
        $update_model = new UpdateModel($settings, $redis);

        if ($route->subaction == '') {
            $route->format = 'html';
            return view("Modules/admin/update/update_view.php", array(
                'update_log_filename'=> $update_model->update_logfile(),
                'serial_ports'=>$update_model->listSerialPorts(),
                'firmware_available'=>$update_model->firmware_available()
            ));
        }

        $route->format = "json";

        if ($route->subaction == 'start') {
            if (!isset($_POST['type']))         return array('success'=>false, 'message'=>"missing parameter: type");
            if (!isset($_POST['serial_port']))  return array('success'=>false, 'message'=>"missing parameter: serial_port");
            if (!isset($_POST['firmware_key'])) return array('success'=>false, 'message'=>"missing parameter: firmware_key");
            return $update_model->update_start($_POST['type'], $_POST['serial_port'], $_POST['firmware_key']);
        }

        if ($route->subaction == 'firmware') {
            if (!isset($_POST['serial_port']))  return array('success'=>false, 'message'=>"missing parameter: serial_port");
            if (!isset($_POST['firmware_key'])) return array('success'=>false, 'message'=>"missing parameter: firmware_key");
            return $update_model->update_firmware($_POST['serial_port'], $_POST['firmware_key']);
        }

        if ($route->subaction == 'firmware-upload') {
            if (!isset($_POST['port']))             return array('success'=>false, 'message'=>"missing parameter: port");
            if (!isset($_POST['baud_rate']))        return array('success'=>false, 'message'=>"missing parameter: baud_rate");
            if (!isset($_POST['core']))             return array('success'=>false, 'message'=>"missing parameter: core");
            if (!isset($_POST['autoreset']))        return array('success'=>false, 'message'=>"missing parameter: autoreset");
            if (!isset($_FILES['custom_firmware'])) return array('success'=>false, 'message'=>"missing parameter: custom_firmware");
            return $update_model->upload_custom_firmware($_POST['port'], $_POST['baud_rate'], $_POST['core'], $_POST['autoreset'], $_FILES['custom_firmware']);
        }

        if ($route->subaction == 'log') {
            $log_content = $update_model->get_update_log();
            if ($log_content === false) {
                $route->format = "json";
                return array('success'=>false, 'message'=>$update_model->update_logfile()." does not exist");
            }
            $route->format = "text";
            return $log_content;
        }

        if ($route->subaction == 'log-download') {
            $update_model->download_update_log();
        }
    }

    // ----------------------------------------------------------------------------------------
    // Component manager
    // ----------------------------------------------------------------------------------------
    if ($route->action == 'component') {
        require_once "Modules/admin/components/ComponentsModel.php";
        $components_model = new ComponentsModel($settings, $redis);

        if ($route->subaction == '') {
            $route->format = 'html';
            return view("Modules/admin/components/components_view.php", array(
                "components" => $components_model->component_list()
            ));
        }

        if ($session['write']) {
        $route->format = "json";

        if ($route->subaction == 'list')       return $components_model->component_list(true);
        if ($route->subaction == 'available')  return $components_model->components_available();
        if ($route->subaction == 'update')     return $components_model->update_component(get('module', true), get('branch', true));
        if ($route->subaction == 'update-all') return $components_model->update_all_components(get('branch', true));
        }
    }

    // ----------------------------------------------------------------------------------------
    // Serial monitor
    // ----------------------------------------------------------------------------------------
    if ($route->action == 'serial') {
        require_once "Modules/admin/serial/SerialModel.php";
        $serial_model = new SerialModel($settings, $redis);

        if ($route->subaction == 'config') {
            $route->format = 'html';
            return view("Modules/admin/serial/serial_config_view.php", array(
                'serial_ports' => $serial_model->listSerialPorts()
            ));
        }

        if ($route->subaction == 'running') {
            $route->format = "text";
            return $serial_model->serialmonitor_pid();
        }
        if ($route->subaction == 'start') {
            $route->format = "json";
            if (!isset($_POST['serialport'])) return array('success'=>false, 'message'=>"missing parameter: serialport");
            if (!isset($_POST['baudrate']))   return array('success'=>false, 'message'=>"missing parameter: baudrate");
            return $serial_model->start($_POST['serialport'], (int) $_POST['baudrate']);
        }
        if ($route->subaction == 'stop') {
            $route->format = "json";
            return $serial_model->stop();
        }
        if ($route->subaction == 'log') {
            $log_content = $serial_model->getLog();
            if ($log_content === false) {
                $route->format = "json";
                return array('success'=>false, 'message'=>"Redis not enabled");
            }
            $route->format = "text";
            return $log_content;
        }
        if ($route->subaction == 'cmd') {
            $route->format = "json";
            $cmd = "";
            if (isset($_GET['cmd']))  $cmd = $_GET['cmd'];
            if (isset($_POST['cmd'])) $cmd = $_POST['cmd'];
            return $serial_model->sendCmd($cmd);
        }
    }

    // ----------------------------------------------------------------------------------------
    // Users
    // ----------------------------------------------------------------------------------------

    // Admin user list view
    if ($route->action == 'users') {
        $route->format = 'html';
        return view("Modules/admin/Views/userlist_view.php", array());
    }

    // Load admin model
    require_once "Modules/admin/AdminUserModel.php";
    $admin_model = new AdminUserModel($mysqli, $user);

    // Switch to another user by id (admin only)
    if ($route->action == 'setuser') {
        $admin_model->setUser(get('id',true));
    }

    // Switch to another user by feedid (admin only)
    if ($route->action == 'setuserfeed') {
        $admin_model->setUserFeed(get('feedid', true));
    }

    // Get total number of users (admin only)
    if ($route->action == 'numberofusers') {
        $route->format = 'text';
        return $admin_model->numberOfUsers();
    }

    // Get paginated list of users (admin only)
    if ($route->action == 'userlist') {
        $route->format = 'json';
        return $admin_model->userList(
            get('page'),
            get('perpage'),
            get('orderby'),
            get('order'),
            get('search')
        );
    }


    return EMPTY_ROUTE;
}
