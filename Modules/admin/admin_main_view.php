

<?php global $path, $emoncms_version, $allow_emonpi_admin, $log_enabled, $log_filename, $mysqli, $redis_enabled, $redis, $mqtt_enabled, $feed_settings, $shutdownPi;

  // Retrieve server information
  $system = system_information();

  function system_information() {
    global $mysqli, $server, $redis_server, $mqtt_server;
    $result = $mysqli->query("select now() as datetime, time_format(timediff(now(),convert_tz(now(),@@session.time_zone,'+00:00')),'%H:%i‌​') AS timezone");
    $db = $result->fetch_array();

    @list($system, $host, $kernel) = preg_split('/[\s,]+/', php_uname('a'), 5);
    @exec('ps ax | grep feedwriter.php | grep -v grep', $feedwriterproc);
    
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
    $emoncmsModuleFolders = glob("$emoncmsModulesPath/*", GLOB_ONLYDIR);  // Use glob to get all the folder names only
    foreach($emoncmsModuleFolders as $emoncmsModuleFolder) {  // loop through the folders
        if ($emoncms_modules != "")  $emoncms_modules .= "&nbsp;&nbsp;&nbsp;";
        $emoncms_modules .=  str_replace($emoncmsModulesPath."/", '', $emoncmsModuleFolder);
    }

    return array('date' => date('Y-m-d H:i:s T'),
                 'system' => $system,
                 'kernel' => $kernel,
                 'host' => $host,
                 'ip' => gethostbyname($host),
                 'uptime' => @exec('uptime'),
                 'http_server' => $_SERVER['SERVER_SOFTWARE'],
                 'php' => PHP_VERSION,
                 'zend' => (function_exists('zend_version') ? zend_version() : 'n/a'),
                 'db_server' => $server,
                 'db_ip' => gethostbyname($server),
                 'db_version' => 'MySQL ' . $mysqli->server_info,
                 'db_stat' => $mysqli->stat(),
                 'db_date' => $db['datetime'] . " (UTC " . $db['timezone'] . ")",

                 'redis_server' => $redis_server['host'].":".$redis_server['port'],
                 'redis_ip' => gethostbyname($redis_server['host']),
                 'feedwriter' => !empty($feedwriterproc),

                 'mqtt_server' => $mqtt_server['host'],
                 'mqtt_ip' => gethostbyname($mqtt_server['host']),
                 'mqtt_port' => $mqtt_server['port'],

                 'hostbyaddress' => @gethostbyaddr(gethostbyname($host)),
                 'http_proto' => $_SERVER['SERVER_PROTOCOL'],
                 'http_mode' => $_SERVER['GATEWAY_INTERFACE'],
                 'http_port' => $_SERVER['SERVER_PORT'],
                 'php_modules' => get_loaded_extensions(),
                 'mem_info' => $meminfo,
                 'partitions' => disk_list(),
                 'emoncms_modules' => $emoncms_modules
                 );
  }

  function formatSize( $bytes ){
    $types = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
    for( $i = 0; $bytes >= 1024 && $i < ( count( $types ) -1 ); $bytes /= 1024, $i++ );
    return( round( $bytes, 2 ) . " " . $types[$i] );
  }

  // Shutdown / Reboot Code Handler
  if (isset($_POST['shutdownPi'])) {
      $shutdownPi = htmlspecialchars(stripslashes(trim($_POST['shutdownPi'])));
  }
  if (isset($shutdownPi)) { if ($shutdownPi == 'reboot') { shell_exec('sudo shutdown -r now 2>&1'); } elseif ($shutdownPi == 'halt') { shell_exec('sudo shutdown -h now 2>&1'); } }
  
  //Shutdown Command Check
  function chkRebootBtn(){
    $chkReboot = shell_exec('sudo shutdown -k --no-wall 2>&1'); //Try and run a fake shutdown
    if (stripos($chkReboot, "scheduled ") > 0) {
      shell_exec('sudo shutdown -c --no-wall'); //Cancel the fake shutdown
      return "<button id=\"haltPi\" class=\"btn btn-danger btn-small pull-right\">"._('Shutdown')."</button><button id=\"rebootPi\" class=\"btn btn-warning btn-small pull-right\">"._('Reboot')."</button>";
    }
    else {
      return "<button id=\"noshut\" class=\"btn btn-info btn-small pull-right\">"._('Shutdown Unsupported')."</button>";
    }
  }
  
  function disk_list()
  {
      $partitions = array();
      // Fetch partition information from df command
      // I would have used disk_free_space() and disk_total_space() here but
      // there appears to be no way to get a list of partitions in PHP?
      $output = array();
      @exec('df --block-size=1', $output);
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
 
 ?>
<style>
pre {
    width:100%;
    height:300px;

    margin:0px;
    padding:0px;
    color:#fff;
    background-color:#300a24;
    overflow: scroll;
    overflow-x: hidden;
}
#export-log {
    padding-left:20px;
    padding-top:20px;
}
#import-log {
    padding-left:20px;
    padding-top:20px;
}

table tr td.buttons { text-align: right;}
table tr td.subinfo { border-color:transparent;}
</style>

<h2><?php echo _('Administration'); ?></h2>
<table class="table table-hover">
    <tr>
        <td>
            <h3><?php echo _('Users'); ?></h3>
            <p><?php echo _('Administer user accounts'); ?></p>
        </td>
        <td class="buttons"><br>
            <a href="<?php echo $path; ?>admin/users" class="btn btn-info"><?php echo _('Users'); ?></a>
        </td>
    </tr>
    <tr>
        <td>
            <h3><?php echo _('Update database'); ?></h3>
            <p><?php echo _('Run this after updating emoncms, after installing a new module or to check emoncms database status.'); ?></p>
        </td>
        <td class="buttons"><br>
            <a href="<?php echo $path; ?>admin/db" class="btn btn-info"><?php echo _('Update & check'); ?></a>
        </td>
    </tr>
<?php
if ($log_enabled) {
?>
    <tr colspan="2" >
        <td colspan="2" >
            <table class="table table-condensed" style="background-color: transparent">
            <tr>
                <td style="border-top: 0px">
                    <h3><?php echo _('Logger'); ?></h3>
                    <p>
<?php
if(is_writable($log_filename)) {
                    echo "View last entries on the logfile: ".$log_filename;
} else {
                    echo '<div class="alert alert-warn">';
                    echo "The log file has no write permissions or does not exists. To fix, log-on on shell and do:<br><pre>touch $log_filename<br>chmod 666 $log_filename</pre>";
                    echo '<small></div>';
} ?>
                    </p>
                </td>
                <td class="buttons" style="border-top: 0px">
<?php if(is_writable($log_filename)) { ?>
                    <br>
                    <button id="getlog" type="button" class="btn btn-info" data-toggle="button" aria-pressed="false" autocomplete="off"><?php echo _('Auto refresh'); ?></button>
                    <a href="<?php echo $path; ?>admin/downloadlog" class="btn btn-info"><?php echo _('Download Log'); ?></a>
<?php } ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" ><pre id="logreply-bound"><div id="logreply"></div></pre></td>
            </tr>
            </table>
        </td>
    </tr>
<?php
}

if ($allow_emonpi_admin) {
?>
    <tr>
        <td colspan="2" style="margin:0px; padding:0px;">
            <table class="table table-condensed" style="background-color: transparent">
            <tr>
                <td style="border-top: 0px">
                    <h3><?php echo _('Update'); ?></h3>
                    <p><b>emonPi Update:</b> updates emonPi firmware & Emoncms</p>
                    <p><b>emonBase Update:</b> updates emonBase (RFM69Pi firmware) & Emoncms</p>
                    <p><b>Change Logs:</b> <a href="https://github.com/emoncms/emoncms/releases"> Emoncms</a> | <a href="https://github.com/openenergymonitor/emonpi/releases">emonPi</a> | <a href="https://github.com/openenergymonitor/RFM2Pi/releases">RFM69Pi</a></p>
                    <p><i>Caution: ensure RFM69Pi is populated with RFM69CW module not RFM12B before running RFM69Pi update: <a href="https://learn.openenergymonitor.org/electricity-monitoring/networking/which-radio-module">Identifying different RF Modules</a>.</i></p>
                </td>
                <td class="buttons" style="border-top: 0px"><br>
                    <button id="emonpiupdate" class="btn btn-warning"><?php echo _('emonPi Update'); ?></button>
                    <button id="rfm69piupdate" class="btn btn-danger"><?php echo _('emonBase Update'); ?></button><br></br>
                    <a href="<?php echo $path; ?>admin/emonpi/downloadupdatelog" class="btn btn-info"><?php echo _('Download Log'); ?></a><br><br>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-top: 0px"><pre id="update-log-bound" style="display: none;"><div id="update-log"></div></pre></td>
            </tr>
            </table>
        </td>
    </tr>
<?php
}
?>

    <tr colspan=2>
        <td colspan=2>
            <h3><?php echo _('Server Information'); ?></h3>
            <table class="table table-hover table-condensed">
              <tr><td><b>Emoncms</b></td><td><?php echo _('Version'); ?></td><td><?php echo $emoncms_version; ?></td></tr>
              <tr><td class="subinfo"></td><td>Modules</td><td><?php echo $system['emoncms_modules']; ?></td></tr>
<?php
if ($feed_settings['redisbuffer']['enabled']) {
?>
              <tr><td class="subinfo"></td><td>Buffer</td><td><span id="bufferused">loading...</span></td></tr>
              <tr><td class="subinfo"></td><td>Writer</td><td><?php echo ($system['feedwriter'] ? "Daemon is running with sleep ".$feed_settings['redisbuffer']['sleep'] . "s" : "<font color='red'>Daemon is not running, start it at ~/scripts/feedwriter</font>"); ?></td></tr>
<?php
}
?>
              <tr><td><b>Server</b></td><td>OS</td><td><?php echo $system['system'] . ' ' . $system['kernel']; ?></td></tr>
              <tr><td class="subinfo"></td><td>Host</td><td><?php echo $system['host'] . ' ' . $system['hostbyaddress'] . ' (' . $system['ip'] . ')'; ?></td></tr>
              <tr><td class="subinfo"></td><td>Date</td><td><?php echo $system['date']; ?></td></tr>
              <tr><td class="subinfo"></td><td>Uptime</td><td><?php echo $system['uptime']; ?></td></tr>

              <tr><td><b>HTTP</b></td><td>Server</td><td colspan="2"><?php echo $system['http_server'] . " " . $system['http_proto'] . " " . $system['http_mode'] . " " . $system['http_port']; ?></td></tr>

              <tr><td><b>Database</b></td><td>Version</td><td><?php echo $system['db_version']; ?></td></tr>
              <tr><td class="subinfo"></td><td>Host</td><td><?php echo $system['db_server'] . ' (' . $system['db_ip'] . ')'; ?></td></tr>
              <tr><td class="subinfo"></td><td>Date</td><td><?php echo $system['db_date']; ?></td></tr>
              <tr><td class="subinfo"></td><td>Stats</td><td><?php echo $system['db_stat']; ?></td></tr>
<?php
if ($redis_enabled) {
?>
              <tr><td><b>Redis</b></td><td>Version</td><td><?php echo $redis->info()['redis_version']; ?></td></tr>
              <tr><td class="subinfo"></td><td>Host</td><td><?php echo $system['redis_server'] . ' (' . $system['redis_ip'] . ')'; ?></td></tr>
              <tr><td class="subinfo"></td><td>Size</td><td><span id="redisused"><?php echo $redis->dbSize() . " keys  (" . $redis->info()['used_memory_human'].")";?></span><button id="redisflush" class="btn btn-info btn-small pull-right"><?php echo _('Flush'); ?></button></td></tr>
              <tr><td class="subinfo"></td><td>Uptime</td><td><?php echo $redis->info()['uptime_in_days'] . " days"; ?></td></tr>
<?php
}
if ($mqtt_enabled) {
?>
              <tr><td><b>MQTT</b></td><td>Version</td><td><?php if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') { echo "n/a"; } else { if (file_exists('/usr/sbin/mosquitto')) { echo exec('/usr/sbin/mosquitto -h | grep -oP \'(?<=mosquitto\sversion\s)[0-9.]+(?=\s*\(build)\''); } } ?></td></tr>
              <tr><td class="subinfo"></td><td>Host</td><td><?php echo $system['mqtt_server']. ":" . $system['mqtt_port'] . ' (' . $system['mqtt_ip'] . ')'; ?></td></tr>
<?php
}

// Raspberry Pi
if ( @exec('ifconfig | grep b8:27:eb:') ) {
              echo "<tr><td><b>Pi</b></td><td>CPU Temp</td><td>".number_format((int)@exec('cat /sys/class/thermal/thermal_zone0/temp')/1000, '2', '.', '')."&degC".chkRebootBtn()."</td></tr>\n";
              foreach (glob("/boot/emonSD-*") as $emonpiRelease) {
                $emonpiRelease = str_replace("/boot/", '', $emonpiRelease);
              }
                echo "<tr><td class=\"subinfo\"></td><td>Release</td><td>".$emonpiRelease."</td></tr>\n";
                echo "<tr><td class=\"subinfo\"></td><td>File-system</td><td>Set root file-system temporarily to read-write, (default read-only)<button id=\"fs-rw\" class=\"btn btn-danger btn-small pull-right\">"._('Read-Write')."</button> <button id=\"fs-ro\" class=\"btn btn-info btn-small pull-right\">"._('Read-Only')."</button></td></tr>\n";
}

// Ram information
if ($system['mem_info']) {
              $sysRamUsed = $system['mem_info']['MemTotal'] - $system['mem_info']['MemFree'] - $system['mem_info']['Buffers'] - $system['mem_info']['Cached'];
              $sysRamPercent = sprintf('%.2f',($sysRamUsed / $system['mem_info']['MemTotal']) * 100);
              echo "<tr><td><b>Memory</b></td><td>RAM</td><td><div class='progress progress-info' style='margin-bottom: 0;'><div class='bar' style='width: ".$sysRamPercent."%;'>Used&nbsp;".$sysRamPercent."%</div></div>";
              echo "<b>Total:</b> ".formatSize($system['mem_info']['MemTotal'])."<b> Used:</b> ".formatSize($sysRamUsed)."<b> Free:</b> ".formatSize($system['mem_info']['MemTotal'] - $sysRamUsed)."</td></tr>\n";
              
              if ($system['mem_info']['SwapTotal'] > 0) {
                $sysSwapUsed = $system['mem_info']['SwapTotal'] - $system['mem_info']['SwapFree'];
                $sysSwapPercent = sprintf('%.2f',($sysSwapUsed / $system['mem_info']['SwapTotal']) * 100);
                echo "<tr><td class='subinfo'></td><td>Swap</td><td><div class='progress progress-info' style='margin-bottom: 0;'><div class='bar' style='width: ".$sysSwapPercent."%;'>Used&nbsp;".$sysSwapPercent."%</div></div>";
                echo "<b>Total:</b> ".formatSize($system['mem_info']['SwapTotal'])."<b> Used:</b> ".formatSize($sysSwapUsed)."<b> Free:</b> ".formatSize($system['mem_info']['SwapFree'])."</td></tr>\n";
              }
}
// Filesystem Information
                if (count($system['partitions']) > 0) {
                    echo "<tr><td><b>Disk</b></td><td><b>Mount</b></td><td><b>Stats</b></td></tr>\n";
                    foreach($system['partitions'] as $fs) {
                      if (!$fs['Temporary']['bool'] && $fs['FileSystem']['text']!= "none" && $fs['FileSystem']['text']!= "udev") {
                        $diskFree = $fs['Free']['value'];
                        $diskTotal = $fs['Size']['value'];;
                        $diskUsed = $fs['Used']['value'];;
                        $diskPercent = sprintf('%.2f',($diskUsed / $diskTotal) * 100);
                        
                        echo "<tr><td class='subinfo'></td><td>".$fs['Partition']['text']."</td><td><div class='progress progress-info' style='margin-bottom: 0;'><div class='bar' style='width: ".$diskPercent."%;'>Used&nbsp;".$diskPercent."%</div></div>";
                        echo "<b>Total:</b> ".formatSize($diskTotal)."<b> Used:</b> ".formatSize($diskUsed)."<b> Free:</b> ".formatSize($diskFree)."</td></tr>\n";
                        
                      }
                    }
                }

?>
              <tr><td><b>PHP</b></td><td>Version</td><td colspan="2"><?php echo $system['php'] . ' (' . "Zend Version" . ' ' . $system['zend'] . ')'; ?></td></tr>
              <tr><td class="subinfo"></td><td>Modules</td><td colspan="2"><?php natcasesort($system['php_modules']); while ( list($key, $val) = each($system['php_modules']) ) { $ver = phpversion($val); echo $val; if (!empty($ver) && is_numeric($ver[0])) { $first = explode(" ", $ver); echo " v" .$first[0]; } echo " &nbsp; "; } ?></td></tr>
            </table>
            
        </td>
    </tr>
</table>

<script>
var path = "<?php echo $path; ?>";
var logrunning = false;

<?php if ($feed_settings['redisbuffer']['enabled']) { ?>
  getBufferSize();
<?php } ?>
function getBufferSize() {
  $.ajax({ url: path+"feed/buffersize.json", async: true, dataType: "json", success: function(result)
    {
      var data = JSON.parse(result);
      $("#bufferused").html( data + " feed points pending write");
    }
  });
}

var refresher_log;
function refresherStart(func, interval){
  clearInterval(refresher_log);
  refresher_log = null;
  if (interval > 0) refresher_log = setInterval(func, interval);
}

getLog();
function getLog() {
  $.ajax({ url: path+"admin/getlog", async: true, dataType: "text", success: function(result)
    {
      $("#logreply").html(result);
      $("#logreply-bound").scrollTop = $("#logreply-bound").scrollHeight;
    }
  });
}

$("#getlog").click(function() {
  logrunning = !logrunning;
  if (logrunning) { refresherStart(getLog, 500); }
  else { refresherStart(getLog, 0);  }
});

var refresher_update;

$("#emonpiupdate").click(function() {
  $.ajax({ type: "POST", url: path+"admin/emonpi/update", data: "argument=emonpi", async: true, success: function(result)
    {
      $("#update-log").html(result);
      $("#update-log-bound").scrollTop = $("#update-log-bound").scrollHeight;
      $("#update-log-bound").show()
      clearInterval(refresher_update);
      refresher_update = null;
      refresher_update = setInterval(getUpdateLog,1000);
    }
  });
});

$("#rfm69piupdate").click(function() {
  $.ajax({ type: "POST", url: path+"admin/emonpi/update", data: "argument=rfm69pi", async: true, success: function(result)
    {
      $("#update-log").html(result);
      $("#update-log-bound").scrollTop = $("#update-log-bound").scrollHeight;
      $("#update-log-bound").show()
      clearInterval(refresher_update);
      refresher_update = null;
      refresher_update = setInterval(getUpdateLog,1000);
    }
  });
});

function getUpdateLog() {
  $.ajax({ url: path+"admin/emonpi/getupdatelog", async: true, dataType: "text", success: function(result)
    {
      $("#update-log").html(result);
      $("#update-log-bound").scrollTop = $("#update-log-bound").scrollHeight;
      if (result.indexOf("emonPi update done")!=-1) {
          clearInterval(refresher_update);
      }
    }
  });
}

$("#redisflush").click(function() {
  $.ajax({ url: path+"admin/redisflush.json", async: true, dataType: "text", success: function(result)
    {
      var data = JSON.parse(result);
      $("#redisused").html(data.dbsize+" keys ("+data.used+")");
    }
  });
});

$("#haltPi").click(function() {
  if(confirm('Please confirm you wish to shutdown your Pi, please wait 30 secs before disconnecting the power...')) {
    $.post( location.href, { shutdownPi: "halt" } );
  }
});

$("#rebootPi").click(function() {
  if(confirm('Please confirm you wish to reboot your Pi, this will take approximately 30 secs to complete...')) {
    $.post( location.href, { shutdownPi: "reboot" } );
  }
});

$("#noshut").click(function() {
  alert('Please modify /etc/sudoers to allow your webserver to run the shutdown command.')
});

$("#fs-rw").click(function() {
  if(confirm('Setting file-system to Read-Write, remember to restore Read-Only when your done..')) {
    $.ajax({ type: "POST", url: path+"admin/emonpi/fs", data: "argument=rw", async: true, success: function(result)
      {
        // console.log(data);
      }
    });
  }
});

$("#fs-ro").click(function() {
  if(confirm('Settings filesystem back to Read Only')) {
    $.ajax({ type: "POST", url: path+"admin/emonpi/fs", data: "argument=ro", async: true, success: function(result)
      {
      // console.log(data);
      }
    });
  }
});

</script>
