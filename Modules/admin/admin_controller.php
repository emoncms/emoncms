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

    require_once "Modules/admin/log/LogModel.php";
    require_once "Modules/admin/info/ServiceModel.php";
    require_once "Modules/admin/info/SystemInfoModel.php";

    $logModel = new LogModel($settings);
    $services = new ServiceModel($redis, $log, $settings);
    $systemInfo = new SystemInfoModel($mysqli, $redis, $settings);

    // ----------------------------------------------------------------------------------------
    // System commands
    // ----------------------------------------------------------------------------------------

    // !!! SHUT DOWN WHOLE SYSTEM - Designed for use with RaspberryPi !!! 
    if ($route->action == 'shutdown') {
        $route->format = 'text';
        if ($systemInfo->is_Pi()) {
            shell_exec('sudo shutdown -h now 2>&1');
            return "System halt in progress";
        } else {
            return "Shutdown command is only available on Raspberry Pi systems";
        }
    }

    // !!! REBOOT WHOLE SYSTEM - Designed for use with RaspberryPi !!!
    if ($route->action == 'reboot') {
        $route->format = 'text';
        if ($systemInfo->is_Pi()) {
            shell_exec('sudo shutdown -r now 2>&1');
            return "System reboot in progress";
        } else {
            return "Reboot command is only available on Raspberry Pi systems";
        }
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
        return $systemInfo->disk_stats_reset();
    }

    // ----------------------------------------------------------------------------------------
    // System info
    // ----------------------------------------------------------------------------------------

    // System information view
    if ($route->action == 'info') {
        $route->format = 'html';
        return view("Modules/admin/info/system_info_view.php", array());
    }

    // System information JSON test endpoint using class-based provider
    if ($route->action == 'systeminfo') {
        $route->format = 'json';
        $result = $systemInfo->getSystemInfo();
        $result['Services'] = $services->getServices();
        return $result;
    }

    // ----------------------------------------------------------------------------------------
    // Services
    // ----------------------------------------------------------------------------------------

    if ($route->action == 'service') {
        $route->format = 'json';
        // Validate service name
        if (!isset($_GET['name'])) {
            return array('success'=>false, 'message'=>"Missing name parameter");
        }
        $name = $_GET['name'];
        if (!in_array($name,$services->getServicesList())) {
            return array('success'=>false, 'message'=>"Invalid service");
        }

        if ($route->subaction == 'status') return $services->getServiceStatus("$name.service");
        if ($route->subaction == 'start') return $services->setService("$name.service",'start');
        if ($route->subaction == 'stop') return $services->setService("$name.service",'stop');
        if ($route->subaction == 'restart') return $services->setService("$name.service",'restart');
        if ($route->subaction == 'disable') return $services->setService("$name.service",'disable');
        if ($route->subaction == 'enable') return $services->setService("$name.service",'enable');
        return array('success'=>false, 'message'=>"Unknown subaction");
    }

    // ----------------------------------------------------------------------------------------
    // Emoncms log
    // ----------------------------------------------------------------------------------------

    if ($route->action == 'log') {
        $route->format = 'html';

        // Log view
        if ($route->subaction == '') {
            $log_levels = $log->levels();
            return view("Modules/admin/log/emoncms_log_view.php", array(
                'log_enabled' => $logModel->is_enabled(),
                'emoncms_logfile' => $logModel->emoncms_logfile(),
                'log_levels' => $log_levels,
                'log_level' => $logModel->get_log_level(),
                'log_level_label' => $log_levels[$logModel->get_log_level()]
            ));
        }

        // Get log content
        if ($route->subaction == 'get') {
            $route->format = "json";
            return $logModel->get_log_content(25);
        }

        // Download log file
        if ($route->subaction == 'download') {
            $logModel->download();
        }

        return EMPTY_ROUTE;
    }

    // ----------------------------------------------------------------------------------------
    // System update
    // ----------------------------------------------------------------------------------------
    if ($route->action == 'update') {
        require_once "Modules/admin/update/UpdateModel.php";
        $update_model = new UpdateModel($settings, $redis);

        // System update view
        if ($route->subaction == '') {
            $route->format = 'html';
            return view("Modules/admin/update/update_view.php", array(
                'update_log_filename'=> $update_model->update_logfile(),
                'serial_ports'=>$update_model->listSerialPorts(),
                'firmware_available'=>$update_model->firmware_available()
            ));
        }

        $route->format = "json";

        // Full update, Emoncms update and firmware update based on firmware key
        if ($route->subaction == 'start') {
            if (!isset($_POST['type']))         return array('success'=>false, 'message'=>"missing parameter: type");
            if (!isset($_POST['serial_port']))  return array('success'=>false, 'message'=>"missing parameter: serial_port");
            if (!isset($_POST['firmware_key'])) return array('success'=>false, 'message'=>"missing parameter: firmware_key");
            return $update_model->update_start($_POST['type'], $_POST['serial_port'], $_POST['firmware_key']);
        }

        // Standard firmware update using firmware key to select from available firmwares
        if ($route->subaction == 'firmware') {
            if (!isset($_POST['serial_port']))  return array('success'=>false, 'message'=>"missing parameter: serial_port");
            if (!isset($_POST['firmware_key'])) return array('success'=>false, 'message'=>"missing parameter: firmware_key");
            return $update_model->update_firmware($_POST['serial_port'], $_POST['firmware_key']);
        }

        // Custom firmware upload
        if ($route->subaction == 'firmware-upload') {
            if (!isset($_POST['port']))             return array('success'=>false, 'message'=>"missing parameter: port");
            if (!isset($_POST['baud_rate']))        return array('success'=>false, 'message'=>"missing parameter: baud_rate");
            if (!isset($_POST['core']))             return array('success'=>false, 'message'=>"missing parameter: core");
            if (!isset($_POST['autoreset']))        return array('success'=>false, 'message'=>"missing parameter: autoreset");
            if (!isset($_FILES['custom_firmware'])) return array('success'=>false, 'message'=>"missing parameter: custom_firmware");
            return $update_model->upload_custom_firmware($_POST['port'], $_POST['baud_rate'], $_POST['core'], $_POST['autoreset'], $_FILES['custom_firmware']);
        }

        // Get update log content
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

        // Component manager view
        if ($route->subaction == '') {
            $route->format = 'html';
            return view("Modules/admin/components/components_view.php", array(
                "components" => $components_model->component_list()
            ));
        }

        // All component manager actions return JSON
        $route->format = "json";
        if ($route->subaction == 'list')       return $components_model->component_list(true);
        if ($route->subaction == 'available')  return $components_model->components_available();
        if ($route->subaction == 'update')     return $components_model->update_component(get('module', true), get('branch', true));
        if ($route->subaction == 'update-all') return $components_model->update_all_components(get('branch', true));
    }

    // ----------------------------------------------------------------------------------------
    // Serial monitor
    // ----------------------------------------------------------------------------------------
    if ($route->action == 'serial') {
        // Load serial model and pass settings and redis for service command execution and log retrieval
        require_once "Modules/admin/serial/SerialModel.php";
        $serial_model = new SerialModel($settings, $redis);

        // Serial monitor configuration view
        if ($route->subaction == '') {
            $route->format = 'html';
            return view("Modules/admin/serial/serial_config_view.php", array(
                'serial_ports' => $serial_model->listSerialPorts()
            ));
        }

        // Check if serial monitor is running
        if ($route->subaction == 'running') {
            $route->format = "text";
            return $serial_model->serialmonitor_pid();
        }

        // Start serial monitor
        if ($route->subaction == 'start') {
            $route->format = "json";
            if (!isset($_POST['serialport'])) return array('success'=>false, 'message'=>"missing parameter: serialport");
            if (!isset($_POST['baudrate']))   return array('success'=>false, 'message'=>"missing parameter: baudrate");
            return $serial_model->start($_POST['serialport'], (int) $_POST['baudrate']);
        }

        // Stop serial monitor
        if ($route->subaction == 'stop') {
            $route->format = "json";
            return $serial_model->stop();
        }

        // Get serial monitor log
        if ($route->subaction == 'log') {
            $log_content = $serial_model->getLog();
            if ($log_content === false) {
                $route->format = "json";
                return array('success'=>false, 'message'=>"Redis not enabled");
            }
            $route->format = "text";
            return $log_content;
        }

        // Send serial command to serialmonitor service
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
        return view("Modules/admin/users/userlist_view.php", array());
    }

    // Load admin model
    require_once "Modules/admin/users/AdminUserModel.php";
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