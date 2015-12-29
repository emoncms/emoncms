<?php global $path, $emoncms_version, $allow_emonpi_update, $log_enabled, $log_filename, $mysqli, $redis_enabled, $redis, $mqtt_enabled, $feed_settings;

  // Retrieve server information
  $system = system_information();
  
  function system_information() {
    global $mysqli, $server, $redis_server, $mqtt_server;
    $result = $mysqli->query("select now() as datetime, time_format(timediff(now(),convert_tz(now(),@@session.time_zone,'+00:00')),'%H:%i‌​') AS timezone");
    $db = $result->fetch_array();

    @list($system, $host, $kernel) = preg_split('/[\s,]+/', @exec('uname -a'), 5);
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

                 'mqtt_server' => $mqtt_server,       
                 'mqtt_ip' => gethostbyname($mqtt_server),

                 'hostbyaddress' => gethostbyaddr(gethostbyname($host)),
                 'http_proto' => $_SERVER['SERVER_PROTOCOL'],
                 'http_mode' => $_SERVER['GATEWAY_INTERFACE'],
                 'http_port' => $_SERVER['SERVER_PORT'],
                 'php_modules' => get_loaded_extensions());
  }

 ?>
<style>
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
            <div id="logreply" style="display:none"></div>
        </td>
        <td class="buttons">
<?php if(is_writable($log_filename)) { ?>
            <br><button id="getlog" class="btn btn-info"><?php echo _('Last Log'); ?></button>
<?php } ?>          
        </td>
    </tr>
<?php
}
if ($allow_emonpi_update) {
?>
    <tr>
        <td>
            <h3><?php echo _('Update emonPi'); ?></h3>
            <p>Downloads latest Emoncms changes from Github and updates emonPi firmware. See important notes in <a href="https://github.com/openenergymonitor/emonpi/blob/master/firmware/CHANGE%20LOG.md">emonPi firmware change log.</a> When update is running hit 'Refresh Log' repeatedly to display update progress log</p>
            <p>Note: If using emonBase (Raspberry Pi + RFM69Pi) the updater can still be used to update Emoncms, RFM69Pi firmware will not be changed.</p> 
            <div id="emonpireply" style="display:none"></div>
        </td>
        <td class="buttons"><br>
            <button id="emonpiupdate" class="btn btn-info"><?php echo _('Update Now'); ?></button><br><br>
            <button id="emonpiupdatelog" class="btn btn-info"><?php echo _('Refresh Log'); ?></button>
        </td>
    </tr>
<?php 
}   
?>

    <tr>
        <td>
            <h3><?php echo _('Create backup'); ?></h3>
            <p>Create a compressed archive containing the emoncms mysql database, phpfina, phptimeseries data files, emonhub.conf and emoncms.conf.<br>This can be used to migrate data to another emonpi or emonbase.<br>Depending on your data size it may take a while to prepare the backup file. Once ready a link will appear here from which the backup can then be downloaded. Refresh the page to see the link.</p>
            <div id="emonpi-backup-reply" style="display:none"></div>
        </td>
        <td class="buttons"><br>
            <button id="emonpi-backup" class="btn btn-info"><?php echo _('Create backup'); ?></button>
            <?php 
            if (file_exists("/home/pi/data/backup.tar.gz") && !file_exists("/tmp/backuplock")) {
                echo '<br><br><b>Download ready:</b><br><a href="'.$path.'/admin/emonpi/downloadbackup">backup.tar.gz</a>';
            }
            ?>
        </td>
    </tr>

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
              <tr><td class="subinfo"></td><td>Host</td><td><?php echo $system['mqtt_server'] . ' (' . $system['mqtt_ip'] . ')'; ?></td></tr>
<?php
}
?>
              <tr><td><b>PHP</b></td><td>Version</td><td colspan="2"><?php echo $system['php'] . ' (' . "Zend Version" . ' ' . $system['zend'] . ')'; ?></td></tr>
              <tr><td class="subinfo"></td><td>Modules</td><td colspan="2"><?php while (list($key, $val) = each($system['php_modules'])) { echo "$val &nbsp; "; } ?></td></tr>
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

var updater;
function updaterStart(func, interval){
  clearInterval(updater);
  updater = null;
  if (interval > 0) updater = setInterval(func, interval);
}

function getLog() {
  $.ajax({ url: path+"admin/getlog", async: true, dataType: "text", success: function(result)
    {
      $("#logreply").html('<pre class="alert alert-info"><small>'+result+'<small></pre>');
    }
  });
}

$("#getlog").click(function() {
  logrunning = !logrunning;
  if (logrunning) { updaterStart(getLog, 500); $("#logreply").show(); }
  else { updaterStart(getLog, 0); $("#logreply").hide(); }
});


$("#emonpiupdate").click(function() {
  $.ajax({ url: path+"admin/emonpi/update", async: true, dataType: "text", success: function(result)
    {
      $("#emonpireply").html('<pre class="alert alert-info"><small>'+result+'<small></pre>');
      $("#emonpireply").show();
    }
  });
});

$("#emonpi-backup").click(function() {
  $.ajax({ url: path+"admin/emonpi/backup", async: true, dataType: "text", success: function(result)
    {
      $("#emonpi-backup-reply").html('<pre class="alert alert-info"><small>'+result+'<small></pre>');
      $("#emonpi-backup-reply").show();
    }
  });
});

$("#emonpiupdatelog").click(function() {
  $.ajax({ url: path+"admin/emonpi/getupdatelog", async: true, dataType: "text", success: function(result)
    {
      $("#emonpireply").html('<pre class="alert alert-info"><small>'+result+'<small></pre>');
      $("#emonpireply").show();
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
</script>
