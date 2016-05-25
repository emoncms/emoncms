

<?php global $path, $emoncms_version, $allow_emonpi_admin, $log_enabled, $log_filename, $mysqli, $redis_enabled, $redis, $mqtt_enabled, $feed_settings, $shutdownPi;

  // Retrieve server information
  $system = system_information();
  
  function system_information() {
    global $mysqli, $server, $redis_server, $mqtt_server;
    $result = $mysqli->query("select now() as datetime, time_format(timediff(now(),convert_tz(now(),@@session.time_zone,'+00:00')),'%H:%i‌​') AS timezone");
    $db = $result->fetch_array();

    @list($system, $host, $kernel) = preg_split('/[\s,]+/', php_uname('a'), 5);
    @exec('ps ax | grep feedwriter.php | grep -v grep', $feedwriterproc);

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
                 'php_modules' => get_loaded_extensions());
  }

 ?>
<style>
pre {
    width:100%;
    height:300px;


    margin:0px;
    padding:0px;
    font-size:14px;
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

<h2>Administration</h2>
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
    <tr>
        <td>
            <h3><?php echo _('Logger'); ?></h3>
            <p>
<?php
if(is_writable($log_filename)) {
            echo "View last entries on the logfile: ".$log_filename;
} else {
            echo '<div class="alert alert-warn">';
            echo "The log file has no write permissions or does not exists. To fix, log-on on shell and do:<br><pre>touch $log_filename<br>chmod 666 $log_filename</pre>";
            echo '<small></div>';
}
?>
            </p>
        </td>
        <td class="buttons">
<?php if(is_writable($log_filename)) { ?>

            <br>
            <div class="input-prepend input-append">
                <span class="btn btn-info"><?php echo _('Auto refresh'); ?></span>
                <button class="btn autorefresh-toggle">OFF</button>
            </div>
            
            <?php } ?>
          
        </td>
    </tr>
    <tr><td colspan="2"><pre id="logreply-bound"><div id="logreply"></div></pre></td></tr>
<?php
}
if ($allow_emonpi_admin) {
?>
    <tr>
        <td>
            <h3><?php echo _('Update emonPi'); ?></h3>
            <p>Downloads latest Emoncms changes from Github and updates emonPi firmware. See important notes in <a href="https://github.com/openenergymonitor/emonpi/blob/master/firmware/readme.md">emonPi firmware change log.</a></p>
            <p>Note: If using emonBase (Raspberry Pi + RFM69Pi) the updater can still be used to update Emoncms, RFM69Pi firmware will not be changed.</p> 
        </td>
        <td class="buttons"><br>
            <button id="emonpiupdate" class="btn btn-info"><?php echo _('Update Now'); ?></button><br><br>
        </td>
    </tr>
    <tr><td colspan="2"><pre id="update-log-bound"><div id="update-log"></div></pre></td></tr>
<?php 
}   
?>
    <tr colspan=2>
        <td colspan=2>
            <h3><?php echo _('Server Information'); ?></h3>
            <table class="table table-hover table-condensed">
              <tr><td><b>Emoncms</b></td><td><?php echo _('Version'); ?></td><td><?php echo $emoncms_version; ?></td></tr>
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
              <tr><td><b>MQTT</b></td><td>Version</td><td><?php echo "n/a"; ?></td></tr>
              <tr><td class="subinfo"></td><td>Host</td><td><?php echo $system['mqtt_server']. ":" . $system['mqtt_port'] . ' (' . $system['mqtt_ip'] . ')'; ?></td></tr>
<?php
} // Raspberry Pi Detection and additions.
if ( exec('ifconfig | grep b8:27:eb:') ) {
              if (isset($shutdownPi)) { if ($shutdownPi == 'reboot') { shell_exec('sudo shutdown -r now 2>&1'); } elseif ($shutdownPi == 'halt') { shell_exec('sudo shutdown -h now 2>&1'); } }
              echo "              <tr><td><b>Pi</b></td><td>CPU Temp</td><td>".number_format((int)exec('cat /sys/class/thermal/thermal_zone0/temp')/1000, '2', '.', '')."&degC".chkRebootBtn()."</td></tr>\n";
                define("ramTotal", "ramTotal");
                define("ramUsed", "ramUsed");
                $sysRamTotal = get_server_memory_usage(ramTotal);
                $sysRamUsed = get_server_memory_usage(ramUsed);
                $sysRamRemaining = $sysRamTotal - $sysRamUsed;;
                $sysRamPercent = sprintf('%.2f',($sysRamUsed / $sysRamTotal) * 100);
                $sysRamInvPercent = 100 - $sysRamPercent;

                echo "              <tr><td class=\"subinfo\"></td><td>RAM Use</td><td>";
                echo "<table width=\"100%\"><tr>";
                echo "<td style=\"border-top-left-radius: 10px; border-bottom-left-radius: 10px;\" bgcolor=\"#300a24\" height=\"10px\" width=\"".$sysRamPercent."%\"></td>";
                echo "<td style=\"border-top-right-radius: 10px; border-bottom-right-radius: 10px;\" bgcolor=\"#d0d0d0\" height=\"10px\" width=\"".$sysRamInvPercent."%\"></td></tr></table>\n";
                echo "<b>RAM Total:</b> ".$sysRamTotal."MB <b>RAM Used:</b> ".$sysRamUsed."MB <b>RAM Free:</b> ".$sysRamRemaining."MB <b>Used %</b> ".$sysRamPercent."%</td></tr>\n";
}

// Filesystem Information
if (is_file('/bin/lsblk')){ // Make sure we can actually do this
              echo "              <tr><td><b>Filesystems</b></td><td>Mount Point</td><td>Disk Stats</td></tr>\n";
              $fileSysems = explode("\n", shell_exec('lsblk -n -o MOUNTPOINT | grep /'));
              if (strpos(shell_exec('cat /proc/mounts'), "tmpfs /var/log tmpfs") !== false) { array_push($fileSysems, "/var/log"); }
                foreach($fileSysems as $fs) {
                  if ($fs != "") {
                    $diskFree = disk_free_space($fs);
                    $diskTotal = disk_total_space($fs);
                    $diskUsed = $diskTotal - $diskFree;
                    $diskPercent = sprintf('%.2f',($diskUsed / $diskTotal) * 100);
                    $diskInvPercent = 100 - $diskPercent;

                    echo "              <tr><td class=\"subinfo\"></td><td>".$fs."</td><td>";
                    echo "<table width=\"100%\"><tr>";
                    echo "<td style=\"border-top-left-radius: 10px; border-bottom-left-radius: 10px;\" bgcolor=\"#300a24\" height=\"10px\" width=\"".$diskPercent."%\"></td>";
                    echo "<td style=\"border-top-right-radius: 10px; border-bottom-right-radius: 10px;\" bgcolor=\"#d0d0d0\" height=\"10px\" width=\"".$diskInvPercent."%\"></td></tr></table>\n";
                    echo "<b>Size:</b> ".formatSize($diskTotal)." <b>Used:</b> ".formatSize($diskUsed)." <b>Avail:</b> ".formatSize($diskFree)." <b>Use%</b> ".$diskPercent."%</td></tr>\n";
                  }
} //End Filesystem Info
}?>
              <tr><td><b>PHP</b></td><td>Version</td><td colspan="2"><?php echo $system['php'] . ' (' . "Zend Version" . ' ' . $system['zend'] . ')'; ?></td></tr>
              <tr><td class="subinfo"></td><td>Modules</td><td colspan="2"><?php while (list($key, $val) = each($system['php_modules'])) { echo "$val &nbsp; "; } ?></td></tr>
            </table>
            
        </td>
    </tr>
</table>

<script>
var path = "<?php echo $path; ?>";
var logrunning = false;
var backup_updater = false;
<?php
if ($allow_emonpi_admin) { echo ("backup_updater = setInterval(backup_log_update,1000);"); }
?>

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

var updater;
function updaterStart(func, interval){
  clearInterval(updater);
  updater = null;
  if (interval > 0) updater = setInterval(func, interval);
}

getLog();
function getLog() {
  $.ajax({ url: path+"admin/getlog", async: true, dataType: "text", success: function(result)
    {
      $("#logreply").html(result);
      document.getElementById("logreply-bound").scrollTop = document.getElementById("logreply-bound").scrollHeight;
    }
  });
}

$(".autorefresh-toggle").click(function() {
  if ($(this).html()=="ON") {
      $(this).html("OFF");
      updaterStart(getLog, 0);
  } else {
      $(this).html("ON");
      updaterStart(getLog, 500);
  }
});


$("#emonpiupdate").click(function() {
  $.ajax({ url: path+"admin/emonpi/update", async: true, dataType: "text", success: function(result)
    {
      $("#update-log").html(result);
      document.getElementById("update-log-bound").scrollTop = document.getElementById("update-log-bound").scrollHeight;
      backup_updater = setInterval(backup_log_update,1000);
      
    }
  });
});

$("#redisflush").click(function() {
  $.ajax({ url: path+"admin/redisflush.json", async: true, dataType: "text", success: function(result)
    {
      var data = JSON.parse(result);
      $("#redisused").html(data.dbsize+" keys ("+data.used+")");
    }
  });
});

function backup_log_update() {
  $.ajax({ url: path+"admin/emonpi/getupdatelog", async: true, dataType: "text", success: function(result)
    {
      $("#update-log").html(result);
      document.getElementById("update-log-bound").scrollTop = document.getElementById("update-log-bound").scrollHeight;

      if (result.indexOf("emonPi update done")!=-1) {
          clearInterval(backup_updater);
      }
    }
  });
}
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
</script>
<?php //Disk Size function
function formatSize( $bytes ){
  $types = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
  for( $i = 0; $bytes >= 1024 && $i < ( count( $types ) -1 ); $bytes /= 1024, $i++ );
  return( round( $bytes, 2 ) . " " . $types[$i] );
}

//Memory Information function
function get_server_memory_usage($field){
  $memory_usage = preg_split('#\s+#',shell_exec("free -m | grep Mem:"));
  $memory_bufcache = preg_split('#\s+#',shell_exec("free -m | grep ache:"));
  if ($field == 'ramTotal') { return $memory_usage[1]; }
  if ($field == 'ramUsed') { return $memory_bufcache[2]; }
  if (!isset($field)) { return "Total: ".$memory_usage[1]." MB, In use: ".$memory_bufcache[2]." MB"; }
}
//Shutdown Command Check
function chkRebootBtn(){
  $chkReboot = shell_exec('sudo shutdown -k --no-wall 2>&1'); //Try and run a fake shutdown
  if (stripos($chkReboot, "scheduled ") > 0) {
    shell_exec('sudo shutdown -c --no-wall'); //Cancel the fake shutdown
    return "<button id=\"haltPi\" class=\"btn btn-info btn-small pull-right\">"._('Shutdown')."</button><button id=\"rebootPi\" class=\"btn btn-info btn-small pull-right\">"._('Reboot')."</button>";
  }
  else {
    return "<button id=\"noshut\" class=\"btn btn-info btn-small pull-right\">"._('Shutdown Unsupported')."</button>";
  }
}

?>
