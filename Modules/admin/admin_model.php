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
    /**
     * get running status of service
     *
     * @param string $name
     * @return mixed true == running | false == stopped | null == not installed
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
        } else if (isset($status["ActiveState"]) && isset($status["SubState"])) {
            return array(
                'ActiveState' => $status["ActiveState"],
                'SubState' => $status["SubState"]
            );
        } else {
        $return = null;
        }
        return $return;
    }
    
    // Retrieve server information
    public static function system_information() {
        global $settings, $mysqli;
        $result = $mysqli->query("select now() as datetime, time_format(timediff(now(),convert_tz(now(),@@session.time_zone,'+00:00')),'%H:%i‌​') AS timezone");
        $db = $result->fetch_array();
    
        @list($system, $host, $kernel) = preg_split('/[\s,]+/', php_uname('a'), 5);
    
        $services = array();
        $services['emonhub'] = Admin::getServiceStatus('emonhub.service');
        $services['mqtt_input'] = Admin::getServiceStatus('mqtt_input.service'); // depreciated, replaced with emoncms_mqtt
        $services['emoncms_mqtt'] = Admin::getServiceStatus('emoncms_mqtt.service');
        $services['feedwriter'] = Admin::getServiceStatus('feedwriter.service');
        $services['service-runner'] = Admin::getServiceStatus('service-runner.service');
        $services['emonPiLCD'] = Admin::getServiceStatus('emonPiLCD.service');
        $services['redis-server'] = Admin::getServiceStatus('redis-server.service');
        $services['mosquitto'] = Admin::getServiceStatus('mosquitto.service');
    
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
        $emoncms_modules = "";
        $emoncmsModulesPath = substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')).'/Modules';  // Set the Modules path
        $emoncmsModuleFolders = glob("$emoncmsModulesPath/*", GLOB_ONLYDIR);                // Use glob to get all the folder names only
        foreach($emoncmsModuleFolders as $emoncmsModuleFolder) {                            // loop through the folders
            if ($emoncms_modules != "")  $emoncms_modules .= " | ";
            if (file_exists($emoncmsModuleFolder."/module.json")) {                         // JSON Version informatmion exists
              $json = json_decode(file_get_contents($emoncmsModuleFolder."/module.json"));  // Get JSON version information
              $jsonAppName = $json->{'name'};
              $jsonVersion = $json->{'version'};
              if ($jsonAppName) {
                $emoncmsModuleFolder = $jsonAppName;
              }
              if ($jsonVersion) {
                $emoncmsModuleFolder = $emoncmsModuleFolder." v".$jsonVersion;
              }
            }
            $emoncms_modules .=  str_replace($emoncmsModulesPath."/", '', $emoncmsModuleFolder);
        }
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
                     'emoncms_modules' => $emoncms_modules,
                     'git_branch' => @exec("git -C " . substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')) . " branch --contains HEAD"),
                     'git_URL' => @exec("git -C " . substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')) . " ls-remote --get-url origin"),
                     'git_describe' => @exec("git -C " . substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')) . " describe")
                     );
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
              $partition = $columns[5];
              $partitions[$partition]['Temporary']['bool'] = in_array($columns[0], array('tmpfs', 'devtmpfs'));
              $partitions[$partition]['Partition']['text'] = $partition;
              $partitions[$partition]['FileSystem']['text'] = $columns[0];
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
            }
          }
          return $partitions;
      }
      

    /**
     * return an array of all installed php modules
     *
     * @param [type] $_modules
     * @return void
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
     * @return boolean
     */
    public static function is_Pi() {
        return @exec('ifconfig | grep b8:27:eb:');
    }

    /**
     * get an array of raspberry pi properites
     *
     * @return array has keys hw,rev,sn,model
     */
    public static function get_rpi_info() {
        // create empty array with all the required keys
        $rpi_info = array_map(function($n) {return '';},array_flip(explode(',','hw,rev,sn,model,emonpiRelease,cputemp,gputemp,currentfs')));
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
                $rpi_info['hw'] = "Broadcom ".$matches[2][0];
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
        $v = '?';
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $v = "n/a";
        } else {
            if (file_exists('/usr/sbin/mosquitto')) {
                $v = exec('/usr/sbin/mosquitto -h | grep -oP \'(?<=mosquitto\sversion\s)[0-9.]+(?=\s*)\'');
            }
        }
        return $v;
    }

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
     * @return void
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
                    $diskPercentRaw = ($diskUsed / $diskTotal) * 100;
                    $diskPercent = sprintf('%.2f',$diskPercentRaw);
                    $diskPercentTable = number_format(round($diskPercentRaw, 2), 2, '.', '');
                    if (strlen($fs['Partition']['text'])>30) {
                        $mountpoint = substr($fs['Partition']['text'],0,30)."...";
                    } else {
                        $mountpoint = $fs['Partition']['text'];
                    }
                    $mounts[] = array(
                        'free'=>Admin::formatSize($diskFree),
                        'total'=>Admin::formatSize($diskTotal),
                        'used'=>Admin::formatSize($diskUsed),
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
     * @return void
     */
    public static function get_fs_state(){
        $currentfs = "<b>read-only</b>"; 
        exec('mount', $resexec);
        $matches = null;
        preg_match('/^\/dev\/mmcblk0p2 on \/ .*(\(rw).*/mi', implode("\n",$resexec), $matches);
        if (!empty($matches)) {
            $currentfs = "<b>read-write</b>"; 
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
?>
