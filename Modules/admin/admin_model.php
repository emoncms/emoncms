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

    public static function get_services_list() {
        return array('emonhub','mqtt_input','emoncms_mqtt','feedwriter','service-runner','emonPiLCD','redis-server','mosquitto','demandshaper');
    }
    
    public static function listSerialPorts() {
        $ports = array();
        for ($i=0; $i<5; $i++) {
            if (file_exists("/dev/ttyAMA$i")) {
                $ports[] = "ttyAMA$i";
            }  
            if (file_exists("/dev/ttyUSB$i")) {
                $ports[] = "ttyUSB$i";
            }
        }
        if (count($ports)==0) {
            $ports[] = "none";
        }
        return $ports;
    }

    public static function firmware_available() {
        global $settings;
        if (file_exists($settings['openenergymonitor_dir']."/EmonScripts/firmware_available.json")) {
            return json_decode(file_get_contents($settings['openenergymonitor_dir']."/EmonScripts/firmware_available.json"));
        }
        return array();
    }

    /**
     * get running status of service
     *
     * @param string $name
     * @return bool|null true == running | false == stopped | null == not installed
     */
    public static function full_system_information() {
        global $redis, $settings, $emoncms_version;
        // create array of installed services
        $services = array();
        $system = Admin::system_information();
        
        foreach($system['services'] as $key=>$value) {
            if (!is_null($system['services'][$key])) {    // If the service was found on this system
                
                // Populate service status fields
            	$services[$key] = array(
                    'state' => ucfirst($value['ActiveState']),
                    'text' => ucfirst($value['SubState']),
                    'running' => $value['SubState']==='running'
                );
            	
            	// Set 'cssClass' based on service's configuration and current status
            	if ($value['LoadState']==='masked') {          // Check if service is masked (installed, but configured not to run)
            		$services[$key]['cssClass'] = 'masked';
            		$services[$key]['text'] = 'Masked';
            	} elseif ($value['SubState']==='running') {    // If not masked, check if service is running
            		$services[$key]['cssClass'] = 'success';
            	} else {                                       // Assume service is in danger
            		$services[$key]['cssClass'] = 'danger';
            	}
            }
        }
        // add custom messages for feedwriter service
        if(isset($services['feedwriter'])) {
            $message = '<font color="red">Service is not running</font>';
            if ($services['feedwriter']['running']) {
                $message = ' - sleep ' . $settings['feed']['redisbuffer']['sleep'] . 's';
            }
            $services['feedwriter']['text'] .= $message . ' <span id="bufferused">loading...</span>';
        }
        $redis_info = array();
        if($settings['redis']['enabled']) {
            $redis_info = $redis->info();
            $redis_info['dbSize'] = $redis->dbSize();
            $phpRedisPattern = 'Redis Version =>';
            $redis_info['phpRedis'] = substr(shell_exec("php -i | grep '".$phpRedisPattern."'"), strlen($phpRedisPattern));
            $pipRedisPattern = "Version: ";
            $redis_info['pipRedis'] = ""; //substr(shell_exec("pip show redis --disable-pip-version-check | grep '".$pipRedisPattern."'"), strlen($pipRedisPattern));
        }

        return array(
            'system'=>$system,
            'services'=>$services,
            'redis_enabled'=>$settings['redis']['enabled'],
            'mqtt_enabled'=>$settings['mqtt']['enabled'],
            'emoncms_version'=>$emoncms_version,
            'redis_info'=>$redis_info,
            'feed_settings'=>$settings['feed'],
            'component_summary'=>$system['component_summary'],
            'php_modules'=>Admin::php_modules($system['php_modules']),
            'mqtt_version'=>Admin::mqtt_version(),
            'rpi_info'=> Admin::get_rpi_info(),
            'ram_info'=> Admin::get_ram($system['mem_info']),
            'disk_info'=> Admin::get_mountpoints($system['partitions']),
            'v' => 3
        );
    }




    /**
     * get running status of service
     *
     * @param string $name
     * @return bool|null true == running | false == stopped | null == not installed
     */
    public static function getServiceStatus($name) {
        @exec('systemctl show '.$name.' | grep State', $exec);
        $status = array();

        foreach ($exec as $line) {
            $parts = explode('=',$line);
            $status[$parts[0]] = $parts[1];
        }
        if (isset($status['LoadState']) && $status['LoadState'] === 'not-found') {
            $return = null;
        } else if (
        		isset($status["ActiveState"]) &&
        		isset($status["SubState"]) &&
        		isset($status["LoadState"])
        		) {
            return array(
                'ActiveState' => $status["ActiveState"],
                'SubState' => $status["SubState"],
                'LoadState' => $status["LoadState"]
            );
        } else {
            $return = null;
        }
        return $return;
    }
    
    public static function setService($name,$action) {
        global $redis;
        if (!$redis) return "could not $action service, redis required";
        $script = "/var/www/emoncms/scripts/service-action.sh $name $action";
        $redis->rpush("service-runner","$script");
        return "service-runner trigger sent for $script";
    }

    /**
     * Retrieve server information
     *
     * @return array
     */
    public static function system_information() {
        global $settings, $mysqli;
        $result = $mysqli->query("select now() as datetime, time_format(timediff(now(),convert_tz(now(),@@session.time_zone,'+00:00')),'%H:%i‌​') AS timezone");
        $db = $result->fetch_array();

        @list($system, $host, $kernel) = preg_split('/[\s,]+/', php_uname('a'), 5);

        $services = array();
        foreach (Admin::get_services_list() as $service) {
            $services[$service] = Admin::getServiceStatus("$service.service");
        }
        //@exec("hostname -I", $ip); $ip = $ip[0];
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
        
        // Component summary
        $component_summary = array();
        $components = Admin::component_list(false);
        foreach ($components as $component) {
            $component_summary[] = $component["name"]." v".$component["version"];
        }
        $component_summary = implode(" | ",$component_summary);
        
        return array('date' => date('Y-m-d H:i:s T'),
                     'system' => $system,
                     'kernel' => $kernel,
                     'host' => $host,
                     'ip' => server('SERVER_ADDR'),
                     'uptime' => @exec('uptime'),
                     'http_server' => $_SERVER['SERVER_SOFTWARE'],
                     'php' => PHP_VERSION,
                     'zend' => (function_exists('zend_version') ? zend_version() : 'n/a'),
                     'db_server' => $settings['sql']['server'],
                     'db_ip' => gethostbyname($settings['sql']['server']),
                     'db_version' => $mysqli->server_info,
                     'db_stat' => $mysqli->stat(),
                     'db_date' => $db['datetime'] . " (UTC " . $db['timezone'] . ")",

                     'redis_server' => $settings['redis']['host'].":".$settings['redis']['port'],
                     'redis_ip' => gethostbyname($settings['redis']['host']),

                     'services' => $services,

                     'mqtt_server' => $settings['mqtt']['host'],
                     'mqtt_ip' => gethostbyname($settings['mqtt']['host']),
                     'mqtt_port' => $settings['mqtt']['port'],

                     'hostbyaddress' => @gethostbyaddr(gethostbyname($host)),
                     'http_proto' => $_SERVER['SERVER_PROTOCOL'],
                     'http_mode' => $_SERVER['GATEWAY_INTERFACE'],
                     'http_port' => $_SERVER['SERVER_PORT'],
                     'php_modules' => get_loaded_extensions(),
                     'mem_info' => $meminfo,
                     'partitions' => Admin::disk_list(),
                     'component_summary' => $component_summary,
                     'git_branch' => @exec("git -C " . substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')) . " branch --contains HEAD"),
                     'git_URL' => @exec("git -C " . substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')) . " ls-remote --get-url origin"),
                     'git_describe' => @exec("git -C " . substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')) . " describe")
                     );
      }

      public static function component_list($git_info=true) 
      {
          global $settings;
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
                      "path"=>$emoncms_path,
                      "location"=>isset($json->location)?$json->location:$emoncms_path,
                      "branches_available"=>isset($json->branches_available)?$json->branches_available:array(),
                      "requires"=>isset($json->requires)?$json->requires:array()
                  );
              }
          }
          
          foreach (array("$emoncms_path/Modules",$settings['emoncms_dir']."/modules",$settings['openenergymonitor_dir']) as $path) {
              
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
                                  "path"=>$module_fullpath,
                                  "location"=>isset($json->location)?$json->location:$path,
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
                  $components[$name]["describe"] = @exec("git -C $path describe");
                  $components[$name]["branch"] = str_replace("* ","",@exec("git -C $path rev-parse --abbrev-ref HEAD"));
                  $components[$name]["local_changes"] = @exec("git -C $path diff-index -G. HEAD --");
                  $components[$name]["url"] = @exec("git -C $path ls-remote --get-url origin");
                  
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
      public static function disk_list()
      {
          $partitions = array();
          // Fetch partition information from df command
          // I would have used disk_free_space() and disk_total_space() here but
          // there appears to be no way to get a list of partitions in PHP?
          $output = array();
          @exec('df --block-size=1 -x squashfs', $output);
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

              $writeload = 0;
              $writeloadtime = "";
              global $redis;
              if ($redis) {
                // translate partition mount point to mmcblk0pX based name
                $partition_name = false;
                if ($partition=="/boot") $partition_name = "mmcblk0p1";
                else if ($partition=="/") $partition_name = "mmcblk0p2";
                else if ($partition=="/var/opt/emoncms") $partition_name = "mmcblk0p3";
                else if ($partition=="/home/pi/data") $partition_name = "mmcblk0p3";

                if ($partition_name) {
                  if ($sectors_written = @exec("awk '/$partition_name/ {print $10}' /proc/diskstats")) {
                    $last_sectors_written = 0;
                    if ($redis->exists("diskstats:$partition_name")) {
                      $last_sectors_written = $redis->get("diskstats:$partition_name");
                      $last_time = $redis->get("diskstats:time");
                      $elapsed = time() - $last_time;
                      $writeload = ($sectors_written-$last_sectors_written)*512/$elapsed;
                      $writeloadtime = $elapsed;
                    } else {
                      $redis->set("diskstats:$partition_name",$sectors_written);
                      $redis->set("diskstats:time",time());
                      $writeload = 0;
                    }

                  }
                }
              }
              $partitions[$partition]['WriteLoad']['value'] = $writeload;
              $partitions[$partition]['WriteLoadTime']['value'] = $writeloadtime;
            }
          }
          return $partitions;
      }

    /**
     * return an array of all installed php modules
     *
     * @param [type] $_modules
     * @return array
     */
    public static function php_modules($_modules) {
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
    public static function is_Pi() {
        return !empty(@exec('ip addr | grep -i "b8:27:eb:\|dc:a6:32:"'));
    }

    /**
     * get an array of raspberry pi properites
     *
     * @see: SoC 'hw' now not required - https://github.com/emoncms/emoncms/issues/1364
     * @return array has keys [hw,]rev,sn,model     * @return array has keys hw,rev,sn,model
     */
    public static function get_rpi_info() {
        // create empty array with all the required keys
        $rpi_info = array_map(function($n) {return '';},array_flip(explode(',','rev,sn,model,emonpiRelease,cputemp,gputemp,currentfs')));
        // exit with empty array if not a raspberry pi
        if ( !Admin::is_Pi()) return $rpi_info;
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
            $rpi_info['cputemp'] = number_format((int)@exec('cat /sys/class/thermal/thermal_zone0/temp')/1000, '2', '.', '')."&degC";
            $rpi_info['gputemp'] = @exec('/opt/vc/bin/vcgencmd measure_temp');
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
        $rpi_info['currentfs'] = Admin::get_fs_state();
        return $rpi_info;
    }
    /**
     * return the current mosquitto server version
     *
     * @return string
     */
    public static function mqtt_version() {
        global $log;
        $v = '?';
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $v = "n/a";
        } else {
            set_error_handler(function($errno, $errstr, $errfile, $errline) use ($log) {
                $log->warn(sprintf("%s:%s - %s", basename($errfile), $errline, $errstr));
            });
            if (file_exists('/usr/sbin/mosquitto')) {
                $v = exec('/usr/sbin/mosquitto -h | grep -oP \'(?<=mosquitto\sversion\s)[0-9.]+(?=\s*)\'');
            }
            restore_error_handler();
        }
        return $v;
    }

    /**
     * @param array $mem_info
     * @return array
     */
    public static function get_ram($mem_info){
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
                $sysSwap['total'] = Admin::formatSize($mem_info['SwapTotal']);
                $sysSwap['used'] = Admin::formatSize($mem_info['SwapTotal'] - $mem_info['SwapFree']);
                $sysSwap['free'] = Admin::formatSize($mem_info['SwapFree']);
                $sysSwap['raw'] = (($mem_info['SwapTotal'] - $mem_info['SwapFree']) / $mem_info['SwapTotal']) * 100;
                $sysSwap['percent'] = sprintf('%.2f',$sysSwap['raw']);
                $sysSwap['table'] = number_format(round($sysSwap['raw'], 2), 2, '.', '');

            }
            $sysRam = array(
                'total'=>Admin::formatSize($sysTotal),
                'used'=>Admin::formatSize($sysRamUsed),
                'free'=>Admin::formatSize($sysFree),
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
    public static function get_mountpoints($partitions) {
        // Filesystem Information
        $mounts = array();
        if (count($partitions) > 0) {
            // echo "<tr><td><b>Disk</b></td><td><b>Mount</b></td><td><b>Stats</b></td></tr>\n";
            foreach($partitions as $fs) {
                if (!$fs['Temporary']['bool'] && $fs['FileSystem']['text']!= "none" && $fs['FileSystem']['text']!= "udev") {
                    $diskFree = $fs['Free']['value'];
                    $diskTotal = $fs['Size']['value'];
                    $diskUsed = $fs['Used']['value'];
                    $writeLoad = $fs['WriteLoad']['value'];
                    $writeLoadTime = $fs['WriteLoadTime']['value'];
                    $diskPercentRaw = ($diskUsed / $diskTotal) * 100;
                    $diskPercent = sprintf('%.2f',$diskPercentRaw);
                    $diskPercentTable = number_format(round($diskPercentRaw, 2), 2, '.', '');
                    if (strlen($fs['Partition']['text'])>30) {
                        $mountpoint = substr($fs['Partition']['text'],0,30)."...";
                    } else {
                        $mountpoint = $fs['Partition']['text'];
                    }

                    $writeloadstr = "n/a";
                    if ($writeLoadTime) {
                        $days = floor($writeLoadTime / 86400);
                        $hours = floor(($writeLoadTime - ($days*86400))/3600);
                        $mins = floor(($writeLoadTime - ($days*86400) - ($hours*3600))/60);

                        $writeloadstr = Admin::formatSize($writeLoad)."/s (";
                        if ($days) $writeloadstr .= $days." days ";
                        if ($hours) $writeloadstr .= $hours." hours ";
                        $writeloadstr .= $mins." mins)";
                    }

                    $mounts[] = array(
                        'free'=>Admin::formatSize($diskFree),
                        'total'=>Admin::formatSize($diskTotal),
                        'used'=>Admin::formatSize($diskUsed),
                        'writeload'=>$writeloadstr,
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
    public static function get_fs_state(){
        $currentfs = "read-only";
        exec('mount', $resexec);
        $matches = null;
        preg_match('/^\/dev\/mmcblk0p2 on \/ .*(\(rw).*/mi', implode("\n",$resexec), $matches);
        if (!empty($matches)) {
            $currentfs = "read-write";
        }
        if (!Admin::is_Pi()) $currentfs = '?';
        return $currentfs;
    }


    /**
     * return bytes as suitable unit
     *
     * @param number $bytes
     * @return string
     */
    private static function formatSize( $bytes ){
        $types = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
        for( $i = 0; $bytes >= 1024 && $i < ( count( $types ) -1 ); $bytes /= 1024, $i++ );
        return( round( $bytes, 2 ) . " " . $types[$i] );
    }

}
