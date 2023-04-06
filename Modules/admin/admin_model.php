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

class Admin {
    private $mysqli;
    private $redis;
    private $settings;
    private $log;
    
    private $emoncms_logfile;
    private $update_logfile;
    private $old_update_logfile;

    public function __construct($mysqli, $redis, $settings)
    {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        $this->settings = $settings;
        $this->log = new EmonLogger(__FILE__);
        
        $this->emoncms_logfile = $settings['log']['location']."/emoncms.log";
        $this->update_logfile = $settings['log']['location']."/update.log";
        $this->old_update_logfile = $settings['log']['location']."/emonpiupdate.log";
    }

    public function emoncms_logfile() {
        return $this->emoncms_logfile;
    }

    public function update_logfile() {
        return $this->update_logfile;
    }

    public function old_update_logfile() {
        return $this->old_update_logfile;
    }
    
    public function get_services_list() {
        return array('emonhub','mqtt_input','emoncms_mqtt','feedwriter','service-runner','emonPiLCD','redis-server','mosquitto','demandshaper');
    }
    
    public function listSerialPorts() {
        $ports = array();
        for ($i=0; $i<5; $i++) {
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
        if (count($ports)==0) {
            $ports[] = "none";
        }
        return $ports;
    }

    public function firmware_available() {
        $localfile = $this->settings['openenergymonitor_dir']."/EmonScripts/firmware_available.json";
        if ($response = @file_get_contents("https://raw.githubusercontent.com/openenergymonitor/EmonScripts/master/firmware_available.json?v=".time())) {
            return json_decode($response);
        }
        else if (file_exists($localfile)) {
            return json_decode(file_get_contents($localfile));
        }
        return array('success'=>false, 'message'=>"Can't get firmware available file");
    }

    /**
     * get running status of service
     *
     * @param string $name
     * @return bool|null true == running | false == stopped | null == not installed
     */
    public function full_system_information() {
        global $emoncms_version;
        // create array of installed services
        $services = array();
        $system = $this->system_information();
        
        foreach($system['services'] as $key=>$value) {
            if (!empty($system['services'][$key])) {    // If the service was found on this system
                // Populate service status fields
                $services[$key] = array(
                    'loadstate' => ucfirst($value['LoadState']),
                    'state' => ucfirst($value['ActiveState']),
                    'text' => ucfirst($value['SubState']),
                    'running' => $value['SubState']==='running',
                    'unitfilestate' => isset($value['UnitFileState'])?$value['UnitFileState']:false
                );
                
                // Set 'cssClass' based on service's configuration and current status
                if ($value['LoadState']==='masked') {          // Check if service is masked (installed, but configured not to run)
                    $services[$key]['cssClass'] = 'masked';
                    $services[$key]['text'] = 'Masked';
                } elseif ($value['LoadState']==='not-found') { // not installed
                    $services[$key]['cssClass'] = 'masked';
                    $services[$key]['text'] = 'Not found or not installed';
                } elseif ($value['SubState']==='running') {    // If not masked, check if service is running
                    $services[$key]['cssClass'] = 'success';
                } else {                                       // Assume service is in danger
                    $services[$key]['cssClass'] = 'danger';
                    $services[$key]['text'] = $value['LoadState'] . " " . $value['ActiveState']  . " " . $value['SubState'];
                }
            }
        }
        
        // sorts conveniently showing active first
        asort($services);
        
        // Hide mqtt_input if not found
        if (isset($services['mqtt_input']) && $services['mqtt_input']['loadstate']=='Not-found') {
            unset($services['mqtt_input']);
        }
        
        
        // add custom messages for feedwriter service
        if(isset($services['feedwriter'])) {
            $message = "";
            if ($services['feedwriter']['running']) {
                $message = ' - sleep ' . $this->settings['feed']['redisbuffer']['sleep'] . 's';
            }
            $services['feedwriter']['text'] .= $message . ' <span id="bufferused"></span>';
        }
        $redis_info = array();
        if($this->settings['redis']['enabled']) {
            $redis_info = $this->redis->info();
            $redis_info['dbSize'] = $this->redis->dbSize();
            $phpRedisPattern = 'Redis Version =>';
            $redis_info['phpRedis'] = substr(shell_exec("php -i | grep '".$phpRedisPattern."'"), strlen($phpRedisPattern));
            $pipRedisPattern = "Version: ";
            $redis_info['pipRedis'] = ""; //substr(shell_exec("pip show redis --disable-pip-version-check | grep '".$pipRedisPattern."'"), strlen($pipRedisPattern));
        }

        return array(
            'system'=>$system,
            'services'=>$services,
            'redis_enabled'=>$this->settings['redis']['enabled'],
            'mqtt_enabled'=>$this->settings['mqtt']['enabled'],
            'emoncms_version'=>$emoncms_version,
            'redis_info'=>$redis_info,
            'feed_settings'=>$this->settings['feed'],
            'component_summary'=>$system['component_summary'],
            'php_modules'=>$this->php_modules($system['php_modules']),
            'mqtt_version'=>$this->mqtt_version(),
            'rpi_info'=> $this->get_rpi_info(),
            'ram_info'=> $this->get_ram($system['mem_info']),
            'disk_info'=> $this->get_mountpoints($system['partitions'])
        );
    }

    /**
     * get running status of service
     *
     * @param string $name
     * @return array | true == running | false == stopped | empty == not installed
     */
    public function getServiceStatus($name) {
        if (!$exec = $this->exec_array('systemctl show '.$name.' | grep State')) {
            return array();
        }
        $status = array();

        foreach ($exec as $line) {
            $parts = explode('=',$line);
            $status[$parts[0]] = $parts[1];
        }
        
        $return = array();
        $keys = array("LoadState","ActiveState","SubState","UnitFileState");
        foreach ($keys as $key) {
            if (isset($status[$key])) {
                $return[$key] = $status[$key];
            }
        }
        return $return;
    }
    
    public function setService($name, $action) {
        $script = __DIR__ . "/../../scripts/service-action.sh";
        return $this->runService($script, "$name $action");
    }

    public function runService($script, $attributes) {
        if (!file_exists($script)) {
            $this->log->error("runService() Script not found '$script' attributes=$attributes");
            return array('success'=>false, 'message'=>"File not found '$script' attributes=$attributes");
        }
        if ($this->redis) { 
            $this->redis->rpush("service-runner","$script $attributes");
            $this->log->info("runService() service-runner trigger sent for '$script $attributes'");
            return array('success'=>true, 'message'=>"service-runner trigger sent for '$script $attributes'"); 
        } else {
            $this->log->warn("runService() Redis not enabled. Trying PHP execution '$script $attributes'");
            $result = $this->exec("$script $attributes");
            $this->log->info("runService() PHP exec returned '$result'");
            return array('success'=>true, 'message'=>"$result"); 
        }
    }

    /**
     * Retrieve server information
     *
     * @return array
     */
    public function system_information() {
        $result = $this->mysqli->query("select now() as datetime, time_format(timediff(now(),convert_tz(now(),@@session.time_zone,'+00:00')),'%H:%i‌​') AS timezone");
        $db = $result->fetch_array();

        @list($system, $host, $kernel) = preg_split('/[\s,]+/', php_uname('a'), 5);

        $services = array();
        foreach ($this->get_services_list() as $service) {
            $services[$service] = $this->getServiceStatus("$service.service");
        }
        $meminfo = false;
        if (@is_readable('/proc/meminfo')) {
          $data = explode("\n", file_get_contents("/proc/meminfo"));
          $meminfo = array();
          foreach ($data as $line) {
              if (strpos($line, ':') !== false) {
                  list($key, $val) = explode(":", $line);
                  $meminfo[$key] = 1024 * floatval( trim( str_replace( ' kB', '', $val ) ) );
              }
          }
        }
        
        $cpuinfo = false;
        if (@is_readable('/usr/bin/lscpu')) {
          $data = $this->exec_array("lscpu");
          foreach ($data as $line) {
              if (strpos($line, ':') !== false) {
                  list($key, $val) = explode(":", $line);
                  $key = trim($key);
                  $val = trim($val);
                  if ($key == "Socket(s)") $cpuinfo .= $val . ' Sockets(s) | ';
                  if ($key == "Core(s) per socket") $cpuinfo .= $val . ' Core(s) | ';
                  if ($key == "Thread(s) per core") $cpuinfo .= $val . ' Threads(s) | ';
                  if ($key == "Model name") $cpuinfo .= $val . ' | ';
                  if ($key == "CPU MHz") $cpuinfo .= $val . 'MHz | ';
                  if ($key == "BogoMIPS") $cpuinfo .= $val . 'MIPS | ';
              }
          }
        }

        // Component summary
        $component_summary = array();
        $components = $this->component_list(false);
        foreach ($components as $component) {
            $component_summary[] = $component["name"]." v".$component["version"];
        }
        $component_summary = implode(" | ",$component_summary);
        
        return array('date' => date('Y-m-d H:i:s T'),
                     'system' => $system,
                     'kernel' => $kernel,
                     'host' => $host,
                     'cpu_info' => $cpuinfo,
                     'machine' => $this->get_machine(),
                     'ip' => server('SERVER_ADDR'),
                     'uptime' => $this->exec('uptime'),
                     'http_server' => $_SERVER['SERVER_SOFTWARE'],
                     'php' => PHP_VERSION,
                     'zend' => (function_exists('zend_version') ? zend_version() : 'n/a'),
                     'run_user' => $this->exec('whoami'),
                     'run_group' => $this->exec('id -Gn'),
                     'script_owner' => (function_exists('get_current_user') ? get_current_user() : 'n/a'),
                     'db_server' => $this->settings['sql']['server'],
                     'db_ip' => gethostbyname($this->settings['sql']['server']),
                     'db_version' => $this->mysqli->server_info,
                     'db_stat' => $this->mysqli->stat(),
                     'db_date' => $db['datetime'] . " (UTC " . $db['timezone'] . ")",

                     'redis_server' => $this->settings['redis']['host'].":".$this->settings['redis']['port'],
                     'redis_ip' => gethostbyname($this->settings['redis']['host']),

                     'services' => $services,

                     'mqtt_server' => $this->settings['mqtt']['host'],
                     'mqtt_ip' => gethostbyname($this->settings['mqtt']['host']),
                     'mqtt_port' => $this->settings['mqtt']['port'],

                     'hostbyaddress' => @gethostbyaddr(gethostbyname($host)),
                     'http_proto' => $_SERVER['SERVER_PROTOCOL'],
                     'http_mode' => $_SERVER['GATEWAY_INTERFACE'],
                     'http_port' => $_SERVER['SERVER_PORT'],
                     'php_modules' => get_loaded_extensions(),
                     'mem_info' => $meminfo,
                     'partitions' => $this->disk_list(),
                     'component_summary' => $component_summary,
                     'git_branch' => $this->exec("git -C " . substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')) . " branch --contains HEAD"),
                     'git_URL' => $this->exec("git -C " . substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')) . " ls-remote --get-url origin"),
                     'git_describe' => $this->exec("git -C " . substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')) . " describe")
                     );
    }

    private function get_machine(){
        $machine_string = "";
        $product = "";
        $board = "";
        $bios = "";

        $res = $this->exec('cat /sys/devices/virtual/dmi/id/board_vendor');
        if (trim($res != "")) $machine_string = trim($res);
        
        $res = $this->exec('cat /sys/devices/virtual/dmi/id/product_name');
        if (trim($res != "")) $product = trim($res); 
        
        $res = $this->exec('cat /sys/devices/virtual/dmi/id/board_name');
        if (trim($res != "")) $board = trim($res); 
        
        $res = $this->exec('cat /sys/devices/virtual/dmi/id/bios_version');
        if (trim($res != "")) $bios = trim($res); 
        
        $res = $this->exec('cat /sys/devices/virtual/dmi/id/bios_date');
        if (trim($res != "")) $bios = trim($bios." ".trim($res));  
        
        if ($product != "") $machine_string .= " ".$product; 
        if ($board != "") $machine_string .= "/".$board; 
        if ($bios != "") $machine_string .= ", BIOS ".$bios;
        if ($machine_string != "") {
            $machine_string = trim(preg_replace("/^\/,?/", "", preg_replace("/ ?(To be filled by O\.E\.M\.|System manufacturer|System Product Name|Not Specified|Default string) ?/i", "", $machine_string)));
        }
        return $machine_string;
    }

    public function components_available() {
        $localfile = $this->settings['openenergymonitor_dir']."/EmonScripts/components_available.json";
        if (file_exists($localfile)) {
            return json_decode(file_get_contents($localfile));
        }
        else if ($response = @file_get_contents("https://raw.githubusercontent.com/openenergymonitor/EmonScripts/stable/components_available.json")) {
            return json_decode($response);
        }
        else {
            return array('success'=>false, 'message'=>"Can't get components available file");
        }
    }

    public function component_list($git_info=true) 
    {
        $emoncms_path = substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/'));
      
        $components = array();
      
        // Emoncms core
        if (file_exists($emoncms_path."/version.json")) {                           // JSON Version informatmion exists
            $json = json_decode(file_get_contents($emoncms_path."/version.json"));  // Get JSON version information
            if (isset($json->version) && $json->version!="") {
                $name = "emoncms";
                $components[$name] = array(
                    "name"=>ucfirst(isset($json->name)?$json->name:$name),
                    "version"=>$json->version,
                    "path"=>$emoncms_path,                                                    // Where it's currently installed
                    "target_location"=>isset($json->location)?$json->location:$emoncms_path,  // Where to install new modules
                    "branches_available"=>isset($json->branches_available)?$json->branches_available:array(),
                    "requires"=>isset($json->requires)?$json->requires:array()
                );
            }
        }
      
        foreach (array("$emoncms_path/Modules",$this->settings['emoncms_dir']."/modules",$this->settings['openenergymonitor_dir']) as $path) {
          
            $directories = glob("$path/*", GLOB_ONLYDIR);                                         // Use glob to get all the folder names only
          
            foreach($directories as $module_fullpath) {                                           // loop through the folders

                if (!is_link($module_fullpath)) {

                    $fullpath_parts = explode("/",$module_fullpath);
                    $name = $fullpath_parts[count($fullpath_parts)-1];
                  
                    if (file_exists($module_fullpath."/module.json")) {                           // JSON Version informatmion exists
                        $json = json_decode(file_get_contents($module_fullpath."/module.json"));  // Get JSON version information
                      
                        if (isset($json->version) && $json->version!="") {
                            $components[$name] = array(
                                "name"=>ucfirst(isset($json->name)?$json->name:$name),
                                "version"=>$json->version,
                                "path"=>$module_fullpath,                                         // Where it's currently installed
                                "target_location"=>isset($json->location)?$json->location:$path,  // Where to install new modules
                                "branches_available"=>isset($json->branches_available)?$json->branches_available:array(),
                                "requires"=>isset($json->requires)?$json->requires:array()
                            );
                        }
                    }
                }
            }
        }

        if ($git_info) {
            foreach ($components as $name=>$component) {
                $path = $components[$name]["path"];
                $components[$name]["describe"] = $this->exec("git -C $path describe");
                $components[$name]["branch"] = str_replace("* ","",$this->exec("git -C $path rev-parse --abbrev-ref HEAD"));
                $components[$name]["local_changes"] = $this->exec("git -C $path diff-index -G. HEAD --");
                $components[$name]["url"] = $this->exec("git -C $path ls-remote --get-url origin");
              
                if (!in_array($components[$name]["branch"],$components[$name]["branches_available"])) {
                    $components[$name]["branches_available"][] = $components[$name]["branch"];
                }
            }             
        }   
      
      
        return $components;
    }

    /**
     * return array of mounted partitions
     *
     * @return array
     */
    public function disk_list()
    {
        $partitions = array();
        // Fetch partition information from df command
        // I would have used disk_free_space() and disk_total_space() here but
        // there appears to be no way to get a list of partitions in PHP?
        $output = array();
        if (!$output = $this->exec_array('df --block-size=1 -x squashfs')) {
            return $partitions;
        }
        foreach($output as $line)
        {
            $columns = array();
            foreach(explode(' ', $line) as $column)
            {
                $column = trim($column);
                if($column != '') $columns[] = $column;
            }

            // Only process 6 column rows
            // (This has the bonus of ignoring the first row which is 7)
            if(count($columns) == 6)
            {
                $filesystem = $columns[0];
                $partition = $columns[5];
                $partitions[$partition]['Temporary']['bool'] = in_array($columns[0], array('tmpfs', 'devtmpfs'));
                $partitions[$partition]['Partition']['text'] = $partition;
                $partitions[$partition]['FileSystem']['text'] = $filesystem;
                if(is_numeric($columns[1]) && is_numeric($columns[2]) && is_numeric($columns[3]))
                {
                    $partitions[$partition]['Size']['value'] = $columns[1];
                    $partitions[$partition]['Free']['value'] = $columns[3];
                    $partitions[$partition]['Used']['value'] = $columns[2];
                }
                else
                {
                    // Fallback if we don't get numerical values
                    $partitions[$partition]['Size']['text'] = $columns[1];
                    $partitions[$partition]['Used']['text'] = $columns[2];
                    $partitions[$partition]['Free']['text'] = $columns[3];
                }

                $partition_name = false;
                $bytes_read = 0;
                $bytes_written = 0;
                $readload = 0;
                $writeload = 0;
                $loadtime = 0;
                
                ob_start();
                @passthru("iostat -o JSON -k $filesystem");
                $output = trim(ob_get_clean());
                $stats = json_decode($output, true);
                if (isset($stats['sysstat']['hosts'][0]['statistics'][0]['disk'][0])) {
                    $disk = $stats['sysstat']['hosts'][0]['statistics'][0]['disk'][0];
                    $partition_name = $disk["disk_device"];
                    $readload = round($disk["kB_read/s"] * 1024); // convert to bytes (used if no redis is available)
                    $writeload = round($disk["kB_wrtn/s"] * 1024); // convert to bytes (used if no redis is available)
                    $bytes_read = round($disk["kB_read"] * 1024); // convert to bytes 
                    $bytes_written = round($disk["kB_wrtn"] * 1024); // convert to bytes
                    $loadtime = -1; // -1 = since reboot
                } else {
                    // ALTERNATIVE: When iostats not available, use hard coded partitions, only works on raspberrypi.
                    // translate partition mount point to mmcblk0pX based name
                    if ($partition=="/boot") $partition_name = "mmcblk0p1";
                    else if ($partition=="/") $partition_name = "mmcblk0p2";
                    else if ($partition=="/var/opt/emoncms") $partition_name = "mmcblk0p3";
                    else if ($partition=="/home/pi/data") $partition_name = "mmcblk0p3";
                    if ($partition_name) {
                        $sectors_read = $this->exec("awk '/$partition_name/ {print $6}' /proc/diskstats");
                        $sectors_written = $this->exec("awk '/$partition_name/ {print $10}' /proc/diskstats");
                        if ($sectors_read==null || $sectors_written==null) $partition_name = false;

                        if ($sectors_read!=null) $bytes_read = $sectors_read * 512;
                        if ($sectors_written!=null) $bytes_written = $sectors_written * 512;
                    }
                }
                if ($this->redis && $partition_name) {
                    // with redis we can calculate average since disk_stats_reset() from BO, else it works with iostats avg kB_wrtn/s since boot
                    $last_bytes_written = 0;
                    if ($this->redis->exists("diskstats:starttime") && $this->redis->exists("diskstats:$partition_name:read") && $this->redis->exists("diskstats:$partition_name:write")) {
                        $last_bytes_read = $this->redis->get("diskstats:$partition_name:read");
                        $last_bytes_written = $this->redis->get("diskstats:$partition_name:write");
                        $last_time = $this->redis->get("diskstats:starttime");
                        $elapsed = time() - $last_time;
                        if ($elapsed > 0) {
                           $readload = ($bytes_read-$last_bytes_read)/$elapsed;
                           $writeload = ($bytes_written-$last_bytes_written)/$elapsed;
                        }
                        $loadtime = $elapsed;
                    } else {
                        $this->redis->set("diskstats:$partition_name:read",$bytes_read);
                        $this->redis->set("diskstats:$partition_name:write",$bytes_written);
                        $this->redis->set("diskstats:starttime",time());
                        $readload = 0;
                        $writeload = 0;
                        $loadtime = 0;
                    }
                }
                $partitions[$partition]['ReadLoad']['value'] = $readload;
                $partitions[$partition]['WriteLoad']['value'] = $writeload;
                $partitions[$partition]['LoadTime']['value'] = $loadtime;
            }
        }
        return $partitions;
    }


    public function disk_stats_reset() {
        if ($this->redis) {
            $prefix = $this->redis->getOption(Redis::OPT_PREFIX);
            $this->redis->del(array_map(
                function ($key) use ($prefix) {
                    return preg_replace( "/^{$prefix}/", '', $key );
                }, $this->redis->keys('diskstats*'))
            );
            return array('success'=>true);
        } else {
            return array('success'=>false, 'message'=>"Redis not enabled");
        }
    }

    /**
     * return an array of all installed php modules
     *
     * @param [type] $_modules
     * @return array
     */
    public function php_modules($_modules) {
        natcasesort($_modules);// sort case insensitive
        $modules = [];// empty list
        foreach($_modules as $ver=>$extension){
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
    public function is_Pi() {
        return !empty($this->exec('ip addr | grep -i "b8:27:eb:\|dc:a6:32:\|28:cd:c1:\|e4:5f:01:"'));
    }

    /**
     * get an array of raspberry pi properites
     *
     * @see: SoC 'hw' now not required - https://github.com/emoncms/emoncms/issues/1364
     * @return array has keys [hw,]rev,sn,model     * @return array has keys hw,rev,sn,model
     */
    public function get_rpi_info() {
        // create empty array with all the required keys
        $rpi_info = array_map(function($n) {return '';},array_flip(explode(',','rev,sn,model,emonpiRelease,cputemp,gputemp,currentfs')));
        // exit with empty array if not a raspberry pi
        if ( !$this->is_Pi()) return $rpi_info;
        // add the rpi details
        $rpi_info['model'] = "Unknown";
        if (@is_readable('/proc/cpuinfo') || true) {
            //load model information
            $rpi_revision = array();
            if (@is_readable(__DIR__."/pi-model.json")) {
                $rpi_revision = json_decode(file_get_contents(__DIR__."/pi-model.json"), true);
                foreach ($rpi_revision as $k => $rev) {
                    if(empty($rev['Code'])) continue;
                    $rpi_revision[$rev['Code']] = $rev;
                    unset($rpi_revision[$k]);
                }
            }
            //get cpu info
            preg_match_all('/^(revision|serial|hardware)\\s*: (.*)/mi', file_get_contents("/proc/cpuinfo"), $matches);
            $matches = array_filter($matches);
            if(!empty($matches)) {
                // $rpi_info['hw'] = "Broadcom ".$matches[2][0];
                $rpi_info['rev'] = $matches[2][1];
                $rpi_info['sn'] = $matches[2][2];
                $rpi_info['model'] = '';
            }
            //build model string
            if(!empty($rpi_revision[$rpi_info['rev']]) || 1)  {
                $empty_model = array_map(function($n) {return '';},array_flip(explode(',','Model,Revision,RAM,Manufacturer,currentfs')));
                $model_info = !empty($rpi_revision[$rpi_info['rev']]) ? $rpi_revision[$rpi_info['rev']] : $empty_model;
                $rpi_info['model'] = "Raspberry Pi ";
                $model = !empty($model_info['Model']) ? $model_info['Model'] : 'N/A';
                if (ctype_digit($model[0])) { //Raspberry Pi >= 2
                    $ver = $model[0];
                    $model = substr($model, 1);
                    $rpi_info['model'] .= $ver." Model ".$model;
                }
                else if (substr($model, 0, 2) == 'CM') { // Raspberry Pi Compute Module
                    $rpi_info['model'] .= " Compute Module";
                    if (ctype_digit($model[2]) && $model[2]>1) $rpi_info['model'] .= " ".$model[2];
                }
                else { //Raspberry Pi
                    $rpi_info['model'] .= " Model ".$model;
                }
                $rpi_info['model'] .= " Rev ".$model_info['Revision']." - ".$model_info['RAM']." (".$model_info['Manufacturer'].")";
            }
            $rpi_info['cputemp'] = number_format((int)$this->exec('cat /sys/class/thermal/thermal_zone0/temp')/1000, '2', '.', '')."&degC";
            $rpi_info['gputemp'] = $this->exec('/opt/vc/bin/vcgencmd measure_temp');
            if(strpos($rpi_info['gputemp'], 'temp=' ) !== false ){
                $rpi_info['gputemp'] = str_replace("temp=","", $rpi_info['gputemp']);
                $rpi_info['gputemp'] = str_replace("'C","°C", $rpi_info['gputemp']);
            }else{
                $rpi_info['gputemp'] = "N/A";
                $rpi_info['gputemp'] .= _(" (to show GPU temp execute this command from the console \"sudo usermod -G video www-data\" )");
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
    public function mqtt_version() {
        $v = '?';
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $v = "n/a";
        } else {
            if (@file_exists('/usr/sbin/mosquitto')) {
                $v = $this->exec('/usr/sbin/mosquitto -h | grep -oP \'(?<=mosquitto\sversion\s)[0-9.]+(?=\s*)\'');
            }
        }
        return $v;
    }

    /**
     * @param array $mem_info
     * @return array
     */
    public function get_ram($mem_info){
        // Ram information
        $sysRam = array_map(function($n) {return '';},array_flip(explode(',','used,raw,percent,table,swap')));

        if ($mem_info) {
            $sysTotal = $mem_info['MemTotal'];
            $sysRamUsed = $mem_info['MemTotal'] - $mem_info['MemFree'] - $mem_info['Buffers'] - $mem_info['Cached'];
            $sysFree = $mem_info['MemTotal']-$sysRamUsed;
            $sysRamPercentRaw = ($sysRamUsed / $mem_info['MemTotal']) * 100;
            $sysRamPercent = sprintf('%.2f',$sysRamPercentRaw);
            $sysRamPercentTable = number_format(round($sysRamPercentRaw, 2), 2, '.', '');

            $sysSwap = array();
            if ($mem_info['SwapTotal'] > 0) {
                $sysSwap['total'] = $this->formatSize($mem_info['SwapTotal']);
                $sysSwap['used'] = $this->formatSize($mem_info['SwapTotal'] - $mem_info['SwapFree']);
                $sysSwap['free'] = $this->formatSize($mem_info['SwapFree']);
                $sysSwap['raw'] = (($mem_info['SwapTotal'] - $mem_info['SwapFree']) / $mem_info['SwapTotal']) * 100;
                $sysSwap['percent'] = sprintf('%.2f',$sysSwap['raw']);
                $sysSwap['table'] = number_format(round($sysSwap['raw'], 2), 2, '.', '');

            }
            $sysRam = array(
                'total'=>$this->formatSize($sysTotal),
                'used'=>$this->formatSize($sysRamUsed),
                'free'=>$this->formatSize($sysFree),
                'raw'=>$sysRamPercentRaw,
                'percent'=>$sysRamPercent,
                'table'=>$sysRamPercentTable,
                'swap'=>$sysSwap
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
    public function get_mountpoints($partitions) {
        // Filesystem Information
        $mounts = array();
        if (count($partitions) > 0) {
            // echo "<tr><td><b>Disk</b></td><td><b>Mount</b></td><td><b>Stats</b></td></tr>\n";
            foreach($partitions as $fs) {
                if (!$fs['Temporary']['bool'] && $fs['FileSystem']['text']!= "none" && $fs['FileSystem']['text']!= "udev") {
                    $diskFree = $fs['Free']['value'];
                    $diskTotal = $fs['Size']['value'];
                    $diskUsed = $fs['Used']['value'];
                    $readLoad = $fs['ReadLoad']['value'];
                    $writeLoad = $fs['WriteLoad']['value'];
                    $loadTime = $fs['LoadTime']['value'];
                    $diskPercentRaw = ($diskUsed / $diskTotal) * 100;
                    $diskPercent = sprintf('%.2f',$diskPercentRaw);
                    $diskPercentTable = number_format(round($diskPercentRaw, 2), 2, '.', '');
                    if (strlen($fs['Partition']['text'])>30) {
                        $mountpoint = substr($fs['Partition']['text'],0,30)."...";
                    } else {
                        $mountpoint = $fs['Partition']['text'];
                    }

                    $readloadstr = "n/a";
                    $writeloadstr = "n/a";
                    $loadstr = "n/a";
                    if ($loadTime) {
                        $readloadstr = $this->formatSize($readLoad)."/s";
                        $writeloadstr = $this->formatSize($writeLoad)."/s";
                    }
                    if ($loadTime == -1){
                        $loadstr = "since boot";
                    } else if ($loadTime > 0) {
                        $days = floor($loadTime / 86400);
                        $hours = floor(($loadTime - ($days*86400))/3600);
                        $mins = floor(($loadTime - ($days*86400) - ($hours*3600))/60);
                        $loadstr = "";
                        if ($days) $loadstr .= $days." days ";
                        if ($hours) $loadstr .= $hours." hours ";
                        $loadstr .= $mins." mins";
                    }

                    $mounts[] = array(
                        'free'=>$this->formatSize($diskFree),
                        'total'=>$this->formatSize($diskTotal),
                        'used'=>$this->formatSize($diskUsed),
                        'readload'=>$readloadstr,
                        'writeload'=>$writeloadstr,
                        'statsloadtime'=>$loadstr,
                        'raw'=>$diskPercentRaw,
                        'percent'=>$diskPercent,
                        'table'=>$diskPercentTable,
                        'mountpoint'=>$mountpoint
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
    public function get_fs_state(){
        $currentfs = "read-only";
        exec('mount', $resexec);
        $matches = null;
        // hardcoded partition to raspberrypi only
        preg_match('/^\/dev\/mmcblk0p2 on \/ .*(\(rw).*/mi', implode("\n",$resexec), $matches);
        if (!empty($matches)) {
            $currentfs = "read-write";
        }
        if (!$this->is_Pi()) $currentfs = '?';
        return $currentfs;
    }

    /**
     * return bytes as suitable unit
     *
     * @param number $bytes
     * @return string
     */
    private function formatSize( $bytes ){
        $types = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
        for( $i = 0; $bytes >= 1024 && $i < ( count( $types ) -1 ); $bytes /= 1024, $i++ );
        return( round( $bytes, 2 ) . " " . $types[$i] );
    }
    
    private function exec($cmd) {
        $output = false;
        if (function_exists("exec")) {
            $output = @exec($cmd);
        }
        return $output;
    }
    
    private function exec_array($cmd) {
        $output = false;
        if (function_exists("exec")) {
            @exec($cmd,$output);
        }
        return $output;
    }
}

