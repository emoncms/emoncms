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

class Admin
{
    private $mysqli;
    private $redis;
    private $settings;
    private $log;

    private $emoncms_logfile;
    private $update_model_instance = null;
    private $serial_model_instance = null;

    public function __construct($mysqli, $redis, $settings)
    {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        $this->settings = $settings;
        $this->log = new EmonLogger(__FILE__);

        $this->emoncms_logfile = $settings['log']['location'] . "/emoncms.log";
    }

    public function emoncms_logfile()
    {
        return $this->emoncms_logfile;
    }

    public function update_logfile()
    {
        return $this->update_model()->update_logfile();
    }

    public function old_update_logfile()
    {
        return $this->update_model()->old_update_logfile();
    }

    private function update_model()
    {
        if ($this->update_model_instance === null) {
            require_once "Modules/admin/update/UpdateModel.php";
            $this->update_model_instance = new UpdateModel($this->settings, $this->redis);
        }
        return $this->update_model_instance;
    }

    public function get_services_list()
    {
        return array('emonhub', 'mqtt_input', 'emoncms_mqtt', 'feedwriter', 'service-runner', 'emonPiLCD', 'redis-server', 'mosquitto', 'demandshaper', 'emoncms_sync');
    }

    private function serial_model()
    {
        if ($this->serial_model_instance === null) {
            require_once "Modules/admin/serial/SerialModel.php";
            $this->serial_model_instance = new SerialModel($this->settings, $this->redis);
        }
        return $this->serial_model_instance;
    }

    public function listSerialPorts()
    {
        $ports = array();
        for ($i = 0; $i < 5; $i++) {
            try {
                if (file_exists("/dev/ttyAMA$i")) {
                    $ports[] = "ttyAMA$i";
                }
                if (file_exists("/dev/ttyUSB$i")) {
                    $ports[] = "ttyUSB$i";
                }
                if (file_exists("/dev/ttyS$i")) {
                    $ports[] = "ttyS$i";
                }
            } catch (Exception $e) {
                // no need to do anything here, function will exit with no ports
            }
        }
        if (count($ports) == 0) {
            $ports[] = "none";
        }
        return $ports;
    }

    public function firmware_available()
    {
        return $this->update_model()->firmware_available();
    }

    /**
     * get running status of service
     *
     * @param string $name
     * @return bool|null true == running | false == stopped | null == not installed
     */
    public function full_system_information()
    {
        global $emoncms_version;

        // ========== DATA GATHERING - Database & System Basics ==========
        $result = $this->mysqli->query("select now() as datetime, time_format(timediff(now(),convert_tz(now(),@@session.time_zone,'+00:00')),'%H:%i‌​') AS timezone");
        $db = $result->fetch_array();

        @list($os_name, $host, $kernel) = preg_split('/[\s,]+/', php_uname('a'), 5);
        $script_path = substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/'));

        // ========== SERVICES - Gather and Structure Service Status ==========
        $services = array();
        foreach ($this->get_services_list() as $service) {
            $services[$service] = $this->getServiceStatus("$service.service");
        }

        foreach ($services as $key => $value) {
            if (!empty($value)) {    // If the service was found on this system
                // Populate service status fields
                $services[$key] = array(
                    'loadstate' => ucfirst($value['LoadState']),
                    'state' => ucfirst($value['ActiveState']),
                    'text' => ucfirst($value['SubState']),
                    'running' => $value['SubState'] === 'running',
                    'unitfilestate' => isset($value['UnitFileState']) ? $value['UnitFileState'] : false
                );

                // Set 'cssClass' based on service's configuration and current status
                if ($value['LoadState'] === 'masked') {          // Check if service is masked (installed, but configured not to run)
                    $services[$key]['cssClass'] = 'masked';
                    $services[$key]['text'] = 'Masked';
                } elseif ($value['LoadState'] === 'not-found') { // not installed
                    $services[$key]['cssClass'] = 'masked';
                    $services[$key]['text'] = 'Not found or not installed';
                } elseif ($value['SubState'] === 'running') {    // If not masked, check if service is running
                    $services[$key]['cssClass'] = 'success';
                } else {                                       // Assume service is in danger
                    $services[$key]['cssClass'] = 'danger';
                    $services[$key]['text'] = $value['LoadState'] . " " . $value['ActiveState'] . " " . $value['SubState'];
                }
            }
        }

        asort($services);

        // Hide mqtt_input if not found
        if (isset($services['mqtt_input']) && $services['mqtt_input']['loadstate'] == 'Not-found') {
            unset($services['mqtt_input']);
        }

        // add custom messages for feedwriter service
        if (isset($services['feedwriter'])) {
            $message = "";
            if ($services['feedwriter']['running']) {
                $message = ' - sleep ' . $this->settings['feed']['redisbuffer']['sleep'] . 's';
            }
            $services['feedwriter']['text'] .= $message;
        }

        // ========== SYSTEM INFORMATION SECTION - Initialize ==========
        $markdown_sections = array(
            'System Information' => array()
        );

        // ========== EMONCMS SECTION ==========
        $component_summary = array();
        $components = $this->component_list(false);
        foreach ($components as $component) {
            $component_summary[] = $component["name"] . " v" . $component["version"];
        }

        $markdown_sections['System Information']['Emoncms'] = array(
            'Version' => $emoncms_version,
            'Git URL' => $this->git_remote_url($script_path),
            'Git Branch' => $this->git_branch($script_path),
            'Git Describe' => $this->git_describe($script_path),
            'Components' => implode(" | ", $component_summary)
        );

        // ========== SERVER SECTION ==========
        $cpuinfo = false;
        if (@is_readable('/usr/bin/lscpu')) {
            $data = $this->lscpu() ?: [];
            foreach ($data as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $val) = explode(":", $line);
                    $key = trim($key);
                    $val = trim($val);
                    if ($key == "Socket(s)") {
                        $cpuinfo .= $val . ' Sockets(s) | ';
                    }
                    if ($key == "Core(s) per socket") {
                        $cpuinfo .= $val . ' Core(s) | ';
                    }
                    if ($key == "Thread(s) per core") {
                        $cpuinfo .= $val . ' Threads(s) | ';
                    }
                    if ($key == "Model name") {
                        $cpuinfo .= $val . ' | ';
                    }
                    if ($key == "CPU MHz") {
                        $cpuinfo .= $val . 'MHz | ';
                    }
                    if ($key == "BogoMIPS") {
                        $cpuinfo .= $val . 'MIPS | ';
                    }
                }
            }
        }

        $host_text = $host . ' | ' . @gethostbyaddr(gethostbyname($host)) . ' | (' . server('SERVER_ADDR') . ')';

        $markdown_sections['System Information']['Server'] = array(
            'Machine' => $this->get_machine(),
            'CPU' => $cpuinfo,
            'OS' => $os_name . ' ' . $kernel,
            'Host' => $host_text,
            'Date' => date('Y-m-d H:i:s T'),
            'Uptime' => $this->uptime()
        );

        // ========== MEMORY SECTION ==========
        $meminfo = false;
        if (@is_readable('/proc/meminfo')) {
            $data = explode("\n", file_get_contents("/proc/meminfo"));
            $meminfo = array();
            foreach ($data as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $val) = explode(":", $line);
                    $meminfo[$key] = 1024 * floatval(trim(str_replace(' kB', '', $val)));
                }
            }
        }

        $ram_info = $this->get_ram($meminfo);
        $markdown_sections['System Information']['Memory'] = array(
            'RAM' => array(
                'Used' => $ram_info['percent'] . '%',
                'Total' => $ram_info['total'],
                'Used Value' => $ram_info['used'],
                'Free' => $ram_info['free']
            ),
            'Swap' => array(
                'Used' => isset($ram_info['swap']['percent']) ? $ram_info['swap']['percent'] . '%' : '0%',
                'Total' => isset($ram_info['swap']['total']) ? $ram_info['swap']['total'] : '',
                'Used Value' => isset($ram_info['swap']['used']) ? $ram_info['swap']['used'] : '',
                'Free' => isset($ram_info['swap']['free']) ? $ram_info['swap']['free'] : ''
            )
        );

        // ========== DISK SECTION ==========
        $partitions = $this->disk_list();
        $disk_info = $this->get_mountpoints($partitions);
        $markdown_sections['System Information']['Disk'] = array();

        foreach ($disk_info as $disk) {
            $markdown_sections['System Information']['Disk'][$disk['mountpoint']] = array(
                'Used' => $disk['percent'] . '%',
                'Total' => $disk['total'],
                'Used Value' => $disk['used'],
                'Free' => $disk['free'],
                'Read Load' => $disk['readload'],
                'Write Load' => $disk['writeload'],
                'Load Time' => $disk['statsloadtime']
            );
        }

        // ========== HTTP SECTION ==========
        $markdown_sections['System Information']['HTTP'] = array(
            'Server' => $_SERVER['SERVER_SOFTWARE'] . ' ' . $_SERVER['SERVER_PROTOCOL'] . ' ' . $_SERVER['GATEWAY_INTERFACE'] . ' ' . $_SERVER['SERVER_PORT']
        );

        // ========== MYSQL SECTION ==========
        $markdown_sections['System Information']['MySQL'] = array(
            'Version' => $this->mysqli->server_info,
            'Host' => $this->settings['sql']['server'] . ' (' . gethostbyname($this->settings['sql']['server']) . ')',
            'Date' => $db['datetime'] . " (UTC " . $db['timezone'] . ")",
            'Stats' => $this->mysqli->stat()
        );

        // ========== PHP SECTION ==========
        $php_version = PHP_VERSION;
        $loaded_php_modules = get_loaded_extensions();
        $php_modules = $this->php_modules($loaded_php_modules);

        $markdown_sections['System Information']['PHP'] = array(
            'Version' => $php_version . ' (Zend Version ' . (function_exists('zend_version') ? zend_version() : 'n/a') . ')',
            'Run user' => 'User: ' . $this->whoami() . ' Group: ' . $this->id_groups() . ' Script Owner: ' . (function_exists('get_current_user') ? get_current_user() : 'n/a'),
            'Modules' => array_map(function ($module) use ($php_version) {
                return str_replace('v' . $php_version, '', $module);
            }, $php_modules)
        );

        // ========== REDIS SECTION (Optional) ==========
        if ($this->settings['redis']['enabled']) {
            $redis_info = $this->redis_info();
            $markdown_sections['System Information']['Redis'] = array(
                'Redis Server' => isset($redis_info['redis_version']) ? $redis_info['redis_version'] : '',
                'PHP Redis' => isset($redis_info['phpRedis']) ? $redis_info['phpRedis'] : '',
                'Python Redis' => isset($redis_info['pipRedis']) ? $redis_info['pipRedis'] : '',
                'Host' => $this->settings['redis']['host'] . ":" . $this->settings['redis']['port'],
                'Size' => (isset($redis_info['dbSize']) ? $redis_info['dbSize'] : 0) . ' keys (' . (isset($redis_info['used_memory_human']) ? $redis_info['used_memory_human'] : '') . ')',
                'Uptime' => (isset($redis_info['uptime_in_days']) ? $redis_info['uptime_in_days'] : 0) . ' days'
            );
        }

        // ========== MQTT SECTION (Optional) ==========
        if ($this->settings['mqtt']['enabled']) {
            $mqtt_version = $this->mqtt_version();
            $markdown_sections['System Information']['MQTT Server'] = array(
                'Version' => 'Mosquitto ' . $mqtt_version,
                'Host' => $this->settings['mqtt']['host'] . ':' . $this->settings['mqtt']['port'] . ' (' . gethostbyname($this->settings['mqtt']['host']) . ')'
            );
        }

        // ========== PI SECTION (Optional - Raspberry Pi Only) ==========
        $rpi_info = $this->get_rpi_info();
        $has_pi_data = false;
        foreach ($rpi_info as $value) {
            if (!empty($value)) {
                $has_pi_data = true;
                break;
            }
        }
        if ($has_pi_data) {
            $serial = strtoupper(ltrim(isset($rpi_info['sn']) ? $rpi_info['sn'] : '', '0'));
            $pi_section = array(
                'Model' => isset($rpi_info['model']) ? $rpi_info['model'] : '',
                'Serial num.' => $serial,
                'CPU Temperature' => isset($rpi_info['cputemp']) ? $rpi_info['cputemp'] : '',
                'GPU Temperature' => isset($rpi_info['gputemp']) ? $rpi_info['gputemp'] : ''
            );
            if (!empty($rpi_info['emonpiRelease'])) {
                $pi_section['emonpiRelease'] = $rpi_info['emonpiRelease'];
            }
            $pi_section['File-system'] = isset($rpi_info['currentfs']) ? $rpi_info['currentfs'] : '';
            $markdown_sections['System Information']['Pi'] = $pi_section;
        }

        // ========== CLIENT INFORMATION SECTION ==========
        $client_information = array(
            'Browser' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'IP' => server('REMOTE_ADDR'),
            'Forwarded IP' => server('HTTP_X_FORWARDED_FOR'),
            'Client Hostname' => gethostbyaddr(server('REMOTE_ADDR'))
        );

        // ========== RETURN STRUCTURED DATA ==========
        return array(
            'Services' => $services,
            "System Information" => $markdown_sections['System Information'],
            "Client Information" => $client_information
        );
    }

    /**
     * get running status of service
     *
     * @param string $name
     * @return array | true == running | false == stopped | empty == not installed
     */
    public function getServiceStatus($name)
    {
        // Validate service name
        // remove .service from name
        $service_name = str_replace('.service', '', $name);
        if (file_exists("/.dockerenv")) {
            if (file_exists("/opt/openenergymonitor/emoncms_pre.sh")) {
                $container_services = [
                    "emoncms_mqtt",
                    "feedwriter",
                    "service-runner",
                    "redis-server",
                    "mosquitto",
                    "emoncms_sync"
                ];
                if (in_array($service_name, $container_services)) {
                    return [
                        "LoadState" => "loaded",
                        "ActiveState" => "active",
                        "SubState" => "running",
                        "UnitFileState" => "container",
                    ];
                } else {
                    return [];
                }
            } else {
                return [];
            }
        }
        if (!in_array($service_name, $this->get_services_list())) {
            return array();
        }

        if (!$service_status = $this->service_status($name)) {
            return array();
        }
        $status = array();

        foreach ($service_status as $line) {
            $parts = explode('=', $line, 2);
            $status[$parts[0]] = $parts[1];
        }

        $return = array();
        $keys = array("LoadState", "ActiveState", "SubState", "UnitFileState");
        foreach ($keys as $key) {
            if (isset($status[$key])) {
                $return[$key] = $status[$key];
            }
        }
        return $return;
    }

    public function setService($name, $action)
    {
        // $action = start | stop | restart | enable | disable
        if (!in_array($action, array('start', 'stop', 'restart', 'enable', 'disable'))) {
            return array('success' => false, 'message' => "Invalid action '$action'");
        }

        $script = __DIR__ . "/../../scripts/service-action.sh";
        return $this->runService($script, "$name $action");
    }

    public function runService($script, $attributes)
    {

        if (!file_exists($script)) {
            $this->log->error("runService() Script not found '$script' attributes=$attributes");
            return array('success' => false, 'message' => "File not found '$script' attributes=$attributes");
        }
        if ($this->redis) {
            $this->redis->rpush("service-runner", "$script $attributes");
            $this->log->info("runService() service-runner trigger sent for '$script $attributes'");
            return array('success' => true, 'message' => "service-runner trigger sent for '$script $attributes'");
        } else {
            $this->log->error("runService() Redis not enabled. Cannot execute '$script $attributes' safely.");
            return array('success' => false, 'message' => "Redis is required to run service commands");
        }
    }

    private function get_machine()
    {
        $vendor  = trim((string) $this->dmi_board_vendor());
        $product = trim((string) $this->dmi_product_name());
        $board   = trim((string) $this->dmi_board_name());
        $bios    = trim(trim((string) $this->dmi_bios_version()) . ' ' . trim((string) $this->dmi_bios_date()));

        $machine = $vendor;
        if ($product !== '') $machine .= " $product";
        if ($board !== '')   $machine .= "/$board";
        if ($bios !== '')    $machine .= ", BIOS $bios";

        if ($machine === '') return '';

        $junk = '/ ?(To be filled by O\.E\.M\.|System manufacturer|System Product Name|Not Specified|Default string) ?/i';
        return trim(preg_replace('/^\/,?/', '', preg_replace($junk, '', $machine)));
    }

    public function components_available()
    {
        return $this->components_model()->components_available();
    }

    public function component_list($git_info = true)
    {
        return $this->components_model()->component_list($git_info);
    }

    private $components_model_instance = null;

    private function components_model()
    {
        if ($this->components_model_instance === null) {
            require_once "Modules/admin/components/ComponentsModel.php";
            $this->components_model_instance = new ComponentsModel($this->settings, $this->redis);
        }
        return $this->components_model_instance;
    }

    /**
     * return array of mounted partitions
     *
     * @return array
     */
    public function disk_list()
    {
        $in_docker = file_exists('/.dockerenv');

        if ($in_docker && file_exists('/opt/openenergymonitor/emoncms_pre.sh')) {
            $output = $this->df_data() ?: [];
        } else {
            if (!$output = $this->df()) {
                return [];
            }
        }

        $partitions = [];
        foreach ($output as $line) {
            // Skip header row (7 columns) and blank lines; data rows have 6
            $columns = array_values(array_filter(array_map('trim', explode(' ', $line))));
            if (count($columns) !== 6) {
                continue;
            }

            $filesystem = $columns[0];
            $partition  = $columns[5];

            $partitions[$partition]['Temporary']['bool']  = in_array($filesystem, ['tmpfs', 'devtmpfs']);
            $partitions[$partition]['Partition']['text']  = $partition;
            $partitions[$partition]['FileSystem']['text'] = $filesystem;

            if (is_numeric($columns[1]) && is_numeric($columns[2]) && is_numeric($columns[3])) {
                $partitions[$partition]['Size']['value'] = $columns[1];
                $partitions[$partition]['Used']['value'] = $columns[2];
                $partitions[$partition]['Free']['value'] = $columns[3];
            } else {
                $partitions[$partition]['Size']['text'] = $columns[1];
                $partitions[$partition]['Used']['text'] = $columns[2];
                $partitions[$partition]['Free']['text'] = $columns[3];
            }

            [$partition_name, $bytes_read, $bytes_written, $readload, $writeload, $loadtime]
                = $this->resolve_disk_stats($filesystem, $partition, $in_docker);

            if ($this->redis && $partition_name) {
                [$readload, $writeload, $loadtime] = $this->redis_disk_load($partition_name, $bytes_read, $bytes_written);
            }

            $partitions[$partition]['ReadLoad']['value']  = $readload;
            $partitions[$partition]['WriteLoad']['value'] = $writeload;
            $partitions[$partition]['LoadTime']['value']  = $loadtime;
        }

        return $partitions;
    }

    private function resolve_disk_stats(string $filesystem, string $partition, bool $in_docker): array
    {
        $partition_name = false;
        $bytes_read = $bytes_written = $readload = $writeload = $loadtime = 0;

        if (!$in_docker && $this->is_command_available('iostat')) {
            $stats = $this->iostat($filesystem);
            if (isset($stats['sysstat']['hosts'][0]['statistics'][0]['disk'][0])) {
                $disk          = $stats['sysstat']['hosts'][0]['statistics'][0]['disk'][0];
                $partition_name = $disk['disk_device'];
                $readload       = round($disk['kB_read/s'] * 1024);
                $writeload      = round($disk['kB_wrtn/s'] * 1024);
                $bytes_read     = round($disk['kB_read'] * 1024);
                $bytes_written  = round($disk['kB_wrtn'] * 1024);
                $loadtime = -1;
                return [$partition_name, $bytes_read, $bytes_written, $readload, $writeload, $loadtime];
            }
        }

        // Fallback: map mount points to mmcblk0pX device names (Raspberry Pi only)
        $mount_map = [
            '/boot'            => 'mmcblk0p1',
            '/'                => 'mmcblk0p2',
            '/var/opt/emoncms' => 'mmcblk0p3',
            '/home/pi/data'    => 'mmcblk0p3',
        ];
        $partition_name = $mount_map[$partition] ?? false;

        if ($in_docker) {
            $parts = explode('/', $filesystem);
            $partition_name = end($parts);
        }

        if ($partition_name && @is_readable('/proc/diskstats')) {
            $sectors_read = $sectors_written = null;
            foreach (explode("\n", file_get_contents('/proc/diskstats')) as $dline) {
                $dparts = preg_split('/\s+/', trim($dline));
                if (isset($dparts[2]) && $dparts[2] === $partition_name) {
                    $sectors_read    = isset($dparts[5]) ? (int)$dparts[5] : null;
                    $sectors_written = isset($dparts[9]) ? (int)$dparts[9] : null;
                    break;
                }
            }
            if ($sectors_read === null || $sectors_written === null) {
                $partition_name = false;
            } else {
                $bytes_read    = $sectors_read * 512;
                $bytes_written = $sectors_written * 512;
            }
        } elseif ($partition_name) {
            $partition_name = false;
        }

        return [$partition_name, $bytes_read, $bytes_written, $readload, $writeload, $loadtime];
    }

    private function redis_disk_load(string $partition_name, int $bytes_read, int $bytes_written): array
    {
        if ($this->redis->exists("diskstats:starttime") &&
            $this->redis->exists("diskstats:$partition_name:read") &&
            $this->redis->exists("diskstats:$partition_name:write")) {
            $last_bytes_read    = $this->redis->get("diskstats:$partition_name:read");
            $last_bytes_written = $this->redis->get("diskstats:$partition_name:write");
            $elapsed = time() - $this->redis->get("diskstats:starttime");
            $readload  = $elapsed > 0 ? ($bytes_read - $last_bytes_read) / $elapsed : 0;
            $writeload = $elapsed > 0 ? ($bytes_written - $last_bytes_written) / $elapsed : 0;
            return [$readload, $writeload, $elapsed];
        }

        $this->redis->set("diskstats:$partition_name:read", $bytes_read);
        $this->redis->set("diskstats:$partition_name:write", $bytes_written);
        $this->redis->set("diskstats:starttime", time());
        return [0, 0, 0];
    }


    public function disk_stats_reset()
    {
        if ($this->redis) {
            $prefix = $this->redis->getOption(Redis::OPT_PREFIX);
            $this->redis->del(
                array_map(
                    function ($key) use ($prefix) {
                        return preg_replace("/^{$prefix}/", '', $key);
                    },
                    $this->redis->keys('diskstats*')
                )
            );
            return array('success' => true);
        } else {
            return array('success' => false, 'message' => "Redis not enabled");
        }
    }

    /**
     * return an array of all installed php modules
     *
     * @param [type] $_modules
     * @return array
     */
    public function php_modules($_modules)
    {
        natcasesort($_modules);// sort case insensitive
        $modules = [];// empty list
        foreach ($_modules as $ver => $extension) {
            $module_version = phpversion($extension);// returns false if no version information
            $modules[] = $module_version ? "$extension v$module_version" : $extension; // show version if available
        }
        return $modules;
    }

    /**
     * return true if php is running on raspberry pi
     *
     * @return bool
     */
    public function is_Pi()
    {
        // Check for Raspberry Pi by reading /proc/device-tree/model or /proc/cpuinfo
        if (@file_exists('/proc/device-tree/model')) {
            $model = @file_get_contents('/proc/device-tree/model');
            if (stripos($model, 'Raspberry Pi') !== false) {
                return true;
            }
        }
        if (@is_readable('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo');
            if (preg_match('/^Hardware\s*:\s*BCM\d+/mi', $cpuinfo)) {
                return true;
            }
            if (preg_match('/^Model\s*:\s*Raspberry Pi/mi', $cpuinfo)) {
                return true;
            }
        }
        return false;
    }

    /**
     * get an array of raspberry pi properites
     *
     * @see: SoC 'hw' is no longer used - https://github.com/emoncms/emoncms/issues/1364
     * @return array has keys rev,sn,model
     */
    public function get_rpi_info()
    {
        // create empty array with all the required keys
        $rpi_info = array_map(function ($n) {
            return '';
        }, array_flip(explode(',', 'rev,sn,model,emonpiRelease,cputemp,gputemp,currentfs')));
        // exit with empty array if not a raspberry pi
        if (!$this->is_Pi()) {
            return $rpi_info;
        }
        // add the rpi details
        $rpi_info['model'] = "Unknown";
        if (@is_readable('/proc/cpuinfo')) {
            //load model information
            $rpi_revision = array();
            if (@is_readable(__DIR__ . "/pi-model.json")) {
                $rpi_revision = json_decode(file_get_contents(__DIR__ . "/pi-model.json"), true);
                foreach ($rpi_revision as $k => $rev) {
                    if (empty($rev['Code'])) {
                        continue;
                    }
                    $rpi_revision[$rev['Code']] = $rev;
                    unset($rpi_revision[$k]);
                }
            }
            //get cpu info
            preg_match_all('/^(revision|serial|hardware)\\s*: (.*)/mi', file_get_contents("/proc/cpuinfo"), $matches);

            // If matches are found, map them correctly
            if (!empty($matches[1])) {
                foreach ($matches[1] as $index => $key) {
                    $key = strtolower($key); // Normalize keys to lowercase
                    if ($key === 'revision') {
                        $rpi_info['rev'] = $matches[2][$index];
                    } elseif ($key === 'serial') {
                        $rpi_info['sn'] = $matches[2][$index];
                    } elseif ($key === 'hardware') {
                        $rpi_info['model'] = $matches[2][$index];
                    }
                }
            }

            //build model string
            $empty_model = array_map(function ($n) {
                return '';
            }, array_flip(explode(',', 'Model,Revision,RAM,Manufacturer')));
            $model_info = !empty($rpi_revision[$rpi_info['rev']]) ? $rpi_revision[$rpi_info['rev']] : $empty_model;
            $model = !empty($model_info['Model']) ? $model_info['Model'] : '';
            if (!empty($model)) {
                $rpi_info['model'] = "Raspberry Pi ";
                if (ctype_digit($model[0])) { //Raspberry Pi >= 2
                    $ver = $model[0];
                    $model = substr($model, 1);
                    $rpi_info['model'] .= $ver . " Model " . $model;
                } elseif (substr($model, 0, 2) == 'CM') { // Raspberry Pi Compute Module
                    $rpi_info['model'] .= " Compute Module";
                    if (isset($model[2]) && ctype_digit($model[2]) && $model[2] > 1) {
                        $rpi_info['model'] .= " " . $model[2];
                    }
                } else { //Raspberry Pi
                    $rpi_info['model'] .= " Model " . $model;
                }
                $rpi_info['model'] .= " Rev " . $model_info['Revision'] . " - " . $model_info['RAM'] . " (" . $model_info['Manufacturer'] . ")";
            }
            // Use 'which' to find vcgencmd location, validate against known paths
            $vcgencmd_known_paths = array('/usr/bin/vcgencmd', '/usr/local/bin/vcgencmd', '/opt/vc/bin/vcgencmd');
            $vcgencmd_which = trim((string)$this->which_vcgencmd());
            $vcgencmd_path = in_array($vcgencmd_which, $vcgencmd_known_paths, true) ? $vcgencmd_which : '/opt/vc/bin/vcgencmd';
            $rpi_info['gputemp'] = $this->gpu_temp($vcgencmd_path);

            if (strpos($rpi_info['gputemp'], 'temp=') !== false) {
                $rpi_info['gputemp'] = str_replace("temp=", "", $rpi_info['gputemp']);
                $rpi_info['gputemp'] = str_replace("'C", "°C", $rpi_info['gputemp']);
            } else {
                $rpi_info['gputemp'] = "N/A";
                $rpi_info['gputemp'] .= tr(" (to show GPU temp execute this command from the console \"sudo usermod -G video www-data\" )");
            }
            // release
            if (glob('/boot/emonSD-*')) {
                foreach (glob("/boot/emonSD-*") as $emonpiRelease) {
                    $rpi_info['emonpiRelease'] = str_replace("/boot/", '', $emonpiRelease);
                }
            }
        }
        $rpi_info['currentfs'] = $this->get_fs_state();
        return $rpi_info;
    }

    /**
     * return the current mosquitto server version
     *
     * @return string
     */
    public function mqtt_version()
    {
        $v = '?';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $v = "n/a";
        } else {
            if (@file_exists('/usr/sbin/mosquitto')) {
                if (file_exists("/.dockerenv")) {
                    $v = $this->mosquitto_version_docker();
                } else {
                    $v = $this->mosquitto_version();
                }
            }
        }
        return $v;
    }

    /**
     * return the redis information needed by the admin system info page
     *
     * @return array
     */
    private function redis_info()
    {
        if (!$this->settings['redis']['enabled']) {
            return array();
        }

        $redis_info = $this->redis->info();
        $redis_info['dbSize'] = $this->redis->dbSize();
        $redis_info['phpRedis'] = phpversion('redis') ?: '';
        $redis_info['pipRedis'] = '';

        return array_intersect_key($redis_info, array_flip(array('redis_version', 'pipRedis', 'phpRedis', 'dbSize', 'used_memory_human', 'uptime_in_days')));
    }

    /**
     * @param array $mem_info
     * @return array
     */
    public function get_ram($mem_info)
    {
        // Ram information
        $sysRam = array_map(function ($n) {
            return '';
        }, array_flip(explode(',', 'used,raw,percent,table,swap')));

        if ($mem_info) {
            $sysTotal = $mem_info['MemTotal'];
            $sysRamUsed = $mem_info['MemTotal'] - $mem_info['MemFree'] - $mem_info['Buffers'] - $mem_info['Cached'];
            $sysFree = $mem_info['MemTotal'] - $sysRamUsed;
            $sysRamPercentRaw = ($sysRamUsed / $mem_info['MemTotal']) * 100;
            $sysRamPercent = sprintf('%.2f', $sysRamPercentRaw);
            $sysRamPercentTable = number_format(round($sysRamPercentRaw, 2), 2, '.', '');

            $sysSwap = array();
            if ($mem_info['SwapTotal'] > 0) {
                $sysSwap['total'] = $this->formatSize($mem_info['SwapTotal']);
                $sysSwap['used'] = $this->formatSize($mem_info['SwapTotal'] - $mem_info['SwapFree']);
                $sysSwap['free'] = $this->formatSize($mem_info['SwapFree']);
                $sysSwap['raw'] = (($mem_info['SwapTotal'] - $mem_info['SwapFree']) / $mem_info['SwapTotal']) * 100;
                $sysSwap['percent'] = sprintf('%.2f', $sysSwap['raw']);
                $sysSwap['table'] = number_format(round($sysSwap['raw'], 2), 2, '.', '');
            }
            $sysRam = array(
                'total' => $this->formatSize($sysTotal),
                'used' => $this->formatSize($sysRamUsed),
                'free' => $this->formatSize($sysFree),
                'raw' => $sysRamPercentRaw,
                'percent' => $sysRamPercent,
                'table' => $sysRamPercentTable,
                'swap' => $sysSwap
            );
        }
        return $sysRam;
    }

    /**
     * return array of disk mounts with properties separated from original strings
     *
     * @param array $partitions
     * @return array
     */
    public function get_mountpoints($partitions)
    {
        // Filesystem Information
        $mounts = array();
        if (count($partitions) > 0) {
            // echo "<tr><td><b>Disk</b></td><td><b>Mount</b></td><td><b>Stats</b></td></tr>\n";
            foreach ($partitions as $fs) {
                if (!$fs['Temporary']['bool'] && $fs['FileSystem']['text'] != "none" && $fs['FileSystem']['text'] != "udev") {
                    $diskFree = $fs['Free']['value'];
                    $diskTotal = $fs['Size']['value'];
                    $diskUsed = $fs['Used']['value'];
                    $readLoad = $fs['ReadLoad']['value'];
                    $writeLoad = $fs['WriteLoad']['value'];
                    $loadTime = $fs['LoadTime']['value'];
                    $diskPercentRaw = ($diskUsed / $diskTotal) * 100;
                    $diskPercent = sprintf('%.2f', $diskPercentRaw);
                    $diskPercentTable = number_format(round($diskPercentRaw, 2), 2, '.', '');
                    if (strlen($fs['Partition']['text']) > 30) {
                        $mountpoint = substr($fs['Partition']['text'], 0, 30) . "...";
                    } else {
                        $mountpoint = $fs['Partition']['text'];
                    }

                    $readloadstr = "n/a";
                    $writeloadstr = "n/a";
                    $loadstr = "n/a";
                    if ($loadTime) {
                        $readloadstr = $this->formatSize($readLoad) . "/s";
                        $writeloadstr = $this->formatSize($writeLoad) . "/s";
                    }
                    if ($loadTime == -1) {
                        $loadstr = "since boot";
                    } elseif ($loadTime > 0) {
                        $days = floor($loadTime / 86400);
                        $hours = floor(($loadTime - ($days * 86400)) / 3600);
                        $mins = floor(($loadTime - ($days * 86400) - ($hours * 3600)) / 60);
                        $loadstr = "";
                        if ($days) {
                            $loadstr .= $days . " days ";
                        }
                        if ($hours) {
                            $loadstr .= $hours . " hours ";
                        }
                        $loadstr .= $mins . " mins";
                    }

                    $mounts[] = array(
                        'free' => $this->formatSize($diskFree),
                        'total' => $this->formatSize($diskTotal),
                        'used' => $this->formatSize($diskUsed),
                        'readload' => $readloadstr,
                        'writeload' => $writeloadstr,
                        'statsloadtime' => $loadstr,
                        'raw' => $diskPercentRaw,
                        'percent' => $diskPercent,
                        'table' => $diskPercentTable,
                        'mountpoint' => $mountpoint
                    );
                    // echo "<tr><td class='subinfo'></td><td>".$mountpoint."</td><td><div class='progress progress-info' style='margin-bottom: 0;'><div class='bar' style='width: ".$diskPercentTable."%;'>Used:&nbsp;".$diskPercent."%&nbsp;</div></div>";
                    // echo "<b>Total:</b> ".formatSize($diskTotal)."<b> Used:</b> ".formatSize($diskUsed)."<b> Free:</b> ".formatSize($diskFree)."</td></tr>\n";
                }
            }
        }
        return $mounts;
    }

    /**
     * return read only state of the file system
     *
     * @return string
     */
    public function get_fs_state()
    {
        $currentfs = "read-only";
        $mount_result = $this->mount();
        $matches = null;
        // hardcoded partition to raspberrypi only
        preg_match('/^\/dev\/mmcblk0p2 on \/ .*(\(rw).*/mi', implode("\n", $mount_result), $matches);
        if (!empty($matches)) {
            $currentfs = "read-write";
        }
        if (!$this->is_Pi()) {
            $currentfs = '?';
        }
        return $currentfs;
    }

    /**
     * return bytes as suitable unit
     *
     * @param number $bytes
     * @return string
     */
    private function formatSize($bytes)
    {
        $types = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        for ($i = 0; $bytes >= 1024 && $i < (count($types) - 1); $bytes /= 1024, $i++)
            ;
        return (round($bytes, 2) . " " . $types[$i]);
    }




    private function exec($cmd)
    {
        $output = false;
        if (function_exists("exec")) {
            $output = @exec($cmd);
        }
        return $output;
    }

    private function exec_array($cmd)
    {
        $output = false;
        if (function_exists("exec")) {
            @exec($cmd, $output);
        }
        return $output;
    }

    // --- Named exec helpers: every shell command used by this class is listed here ---

    private function service_status($name) {
        return $this->exec_array('systemctl show ' . $name . ' | grep State');
    }

    private function lscpu() {
        return $this->exec_array('lscpu');
    }

    private function uptime() {
        return $this->exec('uptime');
    }

    private function whoami() {
        return $this->exec('whoami');
    }

    private function id_groups() {
        return $this->exec('id -Gn');
    }

    private function git_describe($path) {
        return $this->exec("git -C " . escapeshellarg($path) . " describe");
    }

    private function git_abbrev_ref($path) {
        return $this->exec("git -C " . escapeshellarg($path) . " rev-parse --abbrev-ref HEAD");
    }

    private function git_branch($path) {
        return $this->exec("git -C " . escapeshellarg($path) . " branch --contains HEAD");
    }

    private function git_local_changes($path) {
        return $this->exec("git -C " . escapeshellarg($path) . " diff-index -G. HEAD --");
    }

    private function git_remote_url($path) {
        return $this->exec("git -C " . escapeshellarg($path) . " ls-remote --get-url origin");
    }

    private function dmi_board_vendor() {
        return $this->exec('cat /sys/devices/virtual/dmi/id/board_vendor 2>/dev/null');
    }

    private function dmi_product_name() {
        return $this->exec('cat /sys/devices/virtual/dmi/id/product_name 2>/dev/null');
    }

    private function dmi_board_name() {
        return $this->exec('cat /sys/devices/virtual/dmi/id/board_name 2>/dev/null');
    }

    private function dmi_bios_version() {
        return $this->exec('cat /sys/devices/virtual/dmi/id/bios_version 2>/dev/null');
    }

    private function dmi_bios_date() {
        return $this->exec('cat /sys/devices/virtual/dmi/id/bios_date 2>/dev/null');
    }

    private function df() {
        return $this->exec_array('df -B 1 -x squashfs');
    }

    private function df_data() {
        return $this->exec_array('df -B 1 /data');
    }

    private function cpu_temp() {
        return $this->exec('cat /sys/class/thermal/thermal_zone0/temp');
    }

    private function which_vcgencmd() {
        return $this->exec('which vcgencmd 2>/dev/null');
    }

    private function gpu_temp($vcgencmd_path) {
        return $this->exec(escapeshellarg($vcgencmd_path) . ' measure_temp');
    }

    private function mosquitto_version_docker() {
        return $this->exec('/usr/sbin/mosquitto -h | grep version');
    }

    private function mosquitto_version() {
        return $this->exec('/usr/sbin/mosquitto -h | grep -oP \'(?<=mosquitto\sversion\s)[0-9.]+(?=\s*)\'');
    }

    private function mount() {
        return $this->exec_array('mount');
    }

    private function which($command) {
        return $this->exec("which " . escapeshellarg($command) . " 2>/dev/null");
    }

    private function iostat($filesystem) {
        ob_start();
        @passthru("iostat -o JSON -k " . escapeshellarg($filesystem) . " 2>/dev/null");
        $output = trim(ob_get_clean());
        return !empty($output) ? json_decode($output, true) : null;
    }

    /**
     * Check if a system command is available
     *
     * @param string $command
     * @return bool
     */
    private function is_command_available($command)
    {
        $result = $this->which($command);
        return !empty(trim($result));
    }

    // --- Public shell commands called from the controller ---

    public function shutdown_system() {
        shell_exec('sudo shutdown -h now 2>&1');
    }

    public function reboot_system() {
        shell_exec('sudo shutdown -r now 2>&1');
    }

    public function set_filesystem_ro() {
        passthru('rpi-ro');
    }

    public function set_filesystem_rw() {
        passthru('rpi-rw');
    }

    public function get_current_git_branch($path)
    {
        return $this->components_model()->get_current_git_branch($path);
    }

    public function serialmonitor_pid() {
        return $this->serial_model()->serialmonitor_pid();
    }
}
