<?php

$v = 1;
    /**
     * View specific functions
     *
     */

    /**
     * Shutdown button
     */
    function ShutdownBtn(){
        return '<button id="haltPi" class="btn btn-danger btn-small">'._('Shutdown').'</button>';
    }
    /**
     * Reboot button
     */
    function RebootBtn(){
        return '<button id="rebootPi" class="btn btn-warning btn-small mr-1">'._('Reboot').'</button>';
    }

    /**
     * output a progress bar with the labels and summery below
     *
     * @param number $width
     * @param string $label
     * @param array $summary key/value pairs to show below the progress bar
     * @return string
     */
    function bar($width,$label,$summary) {
        $pattern = <<<eot
        <h5 class="m-0">%s</h5>
        <div class="progress progress-info mb-0">
            <div class="bar" style="width: %s%%"></div>
        </div>
eot;
        $markup = sprintf($pattern, $label, $width);
        $markup .= '<ul class="inline">';
        foreach($summary as $key=>$value) {
            $markup .= "<li class=\"pl-0\"><strong>$key</strong> $value</li>";
        }
        $markup .= '</ul>';
        return $markup;
    }
    /**
     * return html for single admin page title/value row
     * @param string $title shown as the row title
     * @param string $value shown as the row value
     * @param string $title_css list of css classes to add to the title container
     * @param string $value_css list of css classes to add to the value container
     */
    function row($title, $value, $title_css = '', $value_css='') {
        return <<<listItem
        <dt class="col-sm-2 col-4 text-truncate {$title_css}">{$title}</dt>
        <dd class="col-sm-10 col-8 {$value_css}">{$value}</dd>
listItem;
    }
?>
<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css?v=<?php echo $v ?>">


<h2><?php echo _('Administration'); ?></h2>

<div class="admin-container">
    <?php 
    // USERS 
    // -------------------
    ?>
    <div class="d-md-flex justify-content-between align-items-center mb-md-2 pb-md-0 pb-2 text-right">
        <div class="text-left">
            <h3><?php echo _('Users'); ?></h3>
            <p><?php echo _('See a list of registered users') ?></p>
        </div>
        <a href="<?php echo $path; ?>admin/users" class="btn btn-info"><?php echo _('Users'); ?></a>
    </div>

    <?php 
    // UPDATES 
    // -------------------
    ?>
    <?php if ($admin_show_update || $allow_emonpi_admin) { ?>
    <div class="d-md-flex justify-content-between align-items-center mb-md-2 border-top pb-md-0 pb-2 text-right">
        <div class="text-left">
            <h3><?php echo _('Updates'); ?></h3>
            <p><?php echo _('OS, Packages, EmonHub, Emoncms & Firmware (If new version)'); ?></p>
        </div>
        <div class="btn-group">
        <button class="update btn btn-info"><?php echo _('Update All'); ?></button>
        <button class="btn dropdown-toggle btn-info" data-toggle="dropdown">
            <span class="caret text-black"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
                <li><a href="#" title="<?php echo _('Emoncms, Emoncms Modules and Services'); ?>"><?php echo _('Emoncms Only'); ?></a></li>
                <li><a href="#" title=""><?php echo _('EmonHub Only'); ?></a></li>
                <li><a href="#" title="<?php echo _('Select your hardware type and firmware version'); ?>"><?php echo _('Update Firmware Only'); ?></a></li>
                <li><a href="#" title="<?php echo _('Run this after a manual emoncms update, after installing a new module or to check emoncms database status.'); ?>"><?php echo _('MySQL Database Only'); ?></a></li>
                <li class="divider"></li>
                <li><a href="#" class="update" title="<?php echo _('OS, Packages, EmonHub, Emoncms & Firmware (If new version)'); ?>"><?php echo _('Update All'); ?></a></li>
        </ul>
        </div>
    </div>

    <?php 
    // EMONCMS UPDATE
    // -------------------
    ?>
    <div class="d-md-flex justify-content-between align-items-center mb-md-2 border-top pb-md-0 pb-2 text-right border-top pl-3">
        <div class="text-left">
            <h4 class="text-info text-uppercase pt-2"><?php echo _('Emoncms Only'); ?></h4>
            <p><?php echo _('Emoncms, Emoncms Modules and Services'); ?></p>
            <p><b>Release info:</b> <a href="https://github.com/emoncms/emoncms/releases"> Emoncms</a></p>
        </div>
        <a class="update btn btn-default" type="emoncms"><?php echo _('Update Emoncms'); ?></a>
    </div>

    <?php 
    // EMONHUB UPDATE
    // -------------------
    ?>
    <div class="d-md-flex justify-content-between align-items-center mb-md-2 border-top pb-md-0 pb-2 text-right border-top pl-3">
        <div class="text-left">
            <h4 class="text-info text-uppercase pt-2"><?php echo _('EmonHub Only'); ?></h4>
            <p><b>Release info:</b> <a href="https://github.com/openenergymonitor/emonhub/releases"> EmonHub</a></p>
        </div>
        <a class="update btn btn-default" type="emonhub"><?php echo _('Update EmonHub'); ?></a>
    </div>

    <?php 
    // EMONPI UPDATE
    // -------------------
    ?>
    <div class="d-md-flex justify-content-between align-items-center mb-md-2 border-top pb-md-0 pb-2 text-right border-top pl-3">
        <div class="text-left">
            <h4 class="text-info text-uppercase pt-2"><?php echo _('Update Firmware Only'); ?></h4>
            <p><?php echo _('Select your hardware type and firmware version'); ?></p>
            <p><b>Release info:</b> <a href="https://github.com/openenergymonitor/emonpi/releases">emonPi</a> | <a href="https://github.com/openenergymonitor/RFM2Pi/releases">RFM69Pi</a></p>
        </div>
        <div class="input-append">
            <select id="selected_firmware">
                <option value="emonpi">EmonPi</option>
                <option value="rfm69pi">RFM69Pi</option>
                <option value="rfm12pi">RFM12Pi</option>
                <option value="custom">Custom</option>
            </select>
            <button class="update btn btn-default" type="firmware"><?php echo _('Update Firmware'); ?></button>
        </div>
    </div>

    <?php 
    // DATABASE UPDATE
    // -------------------
    ?>
    <div class="d-md-flex justify-content-between align-items-center mb-md-2 border-top pb-md-0 pb-2 text-right border-top pl-3">
        <div class="text-left span6 ml-0">
            <h4 class="text-info text-uppercase pt-2"><?php echo _('MySQL Database Only'); ?></h4>
            <p><?php echo _('Run this after a manual emoncms update, after installing a new module or to check emoncms database status.'); ?></p>
        </div>
        <a href="<?php echo $path; ?>admin/db" class="btn btn-default"><?php echo _('Update Database'); ?></a>
    </div>

    <pre id="update-log-bound" style="display: none;"><div id="update-log"></div></pre>

    <?php } ?>

    <?php
    // LOG FILE VIEWER
    // -------------------
    if ($log_enabled) { ?>
    <div class="d-md-flex justify-content-between align-items-center mb-md-2 border-top pb-md-0 pb-2 text-right">
        <div class="text-left">
            <h3><?php echo _('Emoncms Log'); ?></h3>
            <p><?php
            if(is_writable($log_filename)) {
                echo sprintf("%s <code>%s</code>",_('View last entries on the logfile:'),$log_filename);
            } else {
                echo '<div class="alert alert-warn">';
                echo "The log file has no write permissions or does not exists. To fix, log-on on shell and do:<br><pre>touch $log_filename<br>chmod 666 $log_filename</pre>";
                echo '<small></div>';
            } ?></p>
        </div>
        <div>
            <?php if(is_writable($log_filename)) { ?>
                <button id="getlog" type="button" class="btn btn-info mb-1" data-toggle="button" aria-pressed="false" autocomplete="off"><?php echo _('Auto refresh'); ?></button>
                <a href="<?php echo $path; ?>admin/downloadlog" class="btn btn-info mb-1"><?php echo _('Download Log'); ?></a>
                <button class="btn btn-info mb-1" id="copylogfile" type="button"><?php echo _('Copy Log to clipboard'); ?></button>
            <?php } ?>
        </div>
    </div>
    <pre id="logreply-bound"><div id="logreply"></div></pre>
    <?php } ?>


    <?php 
    // SERVER INFO
    // -------------------
    ?>
    <div class="d-md-flex justify-content-between align-items-center mb-md-2 pb-md-0 pb-2 border-top text-right">
        <div class="text-left">
            <h3><?php echo _('Server Information'); ?></h3>
        </div>
        <button class="btn btn-info" id="copyserverinfo" type="button"><?php echo _('Copy Server Information to clipboard'); ?></button>
    </div>

    <div id="serverinfo-container">
        <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('Services'); ?></h4>
        <dl class="row">
            <?php
            foreach ($services as $key=>$value):
                echo row(
                    sprintf('<span class="badge-%2$s badge"></span> %1$s', $key, $value['cssClass']),
                    sprintf('<strong>%s</strong> %s', $value['state'], $value['text'])
                );
            endforeach;
        ?>
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('Emoncms'); ?></h4>
        <dl class="row">
            <?php echo row(_('Version'),$emoncms_version); ?>
            <?php echo row(_('Modules'), $emoncms_modules); ?>
            <?php
            $git_parts = array(
                row(_('URL'), $system['git_URL'],'','overflow-hidden'),
                row(_('Branch'), $system['git_branch']),
                row(_('Describe'), $system['git_describe'])
            );
            $git_details = sprintf('<dl class="row">%s</dl>',implode('', $git_parts));
        ?>
            <?php echo row(_('Git'), $git_details); ?>
        </dl>


        <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('Server'); ?></h4>
        <dl class="row">
            <?php echo row(_('OS'), $system['system'] . ' ' . $system['kernel']); ?>
            <?php echo row(_('Host'), $system['host'] . ' | ' . $system['hostbyaddress'] . ' | (' . $system['ip'] . ')'); ?>
            <?php echo row(_('Date'), $system['date']); ?>
            <?php echo row(_('Uptime'), $system['uptime']); ?>
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('HTTP'); ?></h4>
        <dl class="row">
            <?php echo row(_('Server'), $system['http_server'] . " " . $system['http_proto'] . " " . $system['http_mode'] . " " . $system['http_port']); ?>
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('MySQL'); ?></h4>
        <dl class="row">
            <?php echo row(_('Version'), $system['db_version']); ?>
            <?php echo row(_('Host'), $system['redis_server'] . ' (' . $system['redis_ip'] . ')'); ?>
            <?php echo row(_('Date'), $system['db_date']); ?>
            <?php echo row(_('Stats'), $system['db_stat']); ?>
        </dl>

        <?php if ($redis_enabled) : ?>
        <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('Redis'); ?></h4>
        <dl class="row">
            <?php echo row(_('Version'), $redis->info()['redis_version']); ?>
            <?php echo row(_('Host'), $system['redis_server']); ?>
            <?php 
            $redis_flush_btn = sprintf('<button id="redisflush" class="btn btn-info btn-small pull-right">%s</button>',_('Flush'));
            $redis_keys = sprintf('%s keys',$redis->dbSize());
            $redis_size = sprintf('(%s)',$redis->info()['used_memory_human']);
            echo row(sprintf('<span class="align-self-center">%s</span>',_('Size')), sprintf('<span id="redisused">%s %s</span>%s',$redis_keys,$redis_size,$redis_flush_btn),'d-flex','d-flex align-items-center justify-content-between'); ?>
            <?php echo row(_('Uptime'), sprintf(_("%s days"), $redis->info()['uptime_in_days'])); ?>
        </dl>
        <?php endif; ?>

        <?php if ($mqtt_enabled) : ?>
        <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('MQTT Server'); ?></h4>
        <dl class="row">
            <?php echo row(_('Version'), sprintf(_('Mosquitto %s'), $mqtt_version)) ?>
            <?php echo row(_('Host'), sprintf('%s:%s (%s)', $system['mqtt_server'], $system['mqtt_port'], $system['mqtt_ip'])); ?>
        </dl>
        <?php endif; ?>

        <?php if (!empty(implode('',$rpi_info))) : ?>
        <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('Pi'); ?></h4>
        <dl class="row">
            <?php echo row(sprintf('<span class="align-self-center">%s</span>',_('Model')), $rpi_info['model'].'<div>'.RebootBtn().ShutdownBtn().'</div>','d-flex','d-flex align-items-center justify-content-between') ?>
            <?php echo row(_('SoC'), $rpi_info['hw']) ?>
            <?php echo row(_('Serial num.'), strtoupper(ltrim($rpi_info['sn'], '0'))) ?>
            <?php echo row(_('Temperature'), sprintf('%s - %s', $rpi_info['cputemp'], $rpi_info['gputemp'])) ?>
            <?php echo row(_('emonpiRelease'), $rpi_info['emonpiRelease']) ?>
            <?php echo row(_('File-system'), $rpi_info['currentfs']) ?>
        </dl>
        <?php endif; ?>

        <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('Memory'); ?></h4>
        <dl class="row">
            <?php 
            echo row(_('RAM'), bar($ram_info['table'], sprintf(_('Used: %s%%'), $ram_info['percent']), array(
                'Total'=>$ram_info['total'],
                'Used'=>$ram_info['used'],
                'Free'=>$ram_info['free']
            )));
            if (!empty($ram_info['swap'])) {
                echo row(_('Swap'), bar($ram_info['swap']['table'], sprintf(_('Used: %s%%'), $ram_info['swap']['percent']), array(
                    'Total'=>$ram_info['swap']['total'],
                    'Used'=>$ram_info['swap']['used'],
                    'Free'=>$ram_info['swap']['free']
                )));
            }
            ?>
            
        </dl>

        <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('Disk'); ?></h4>
        <dl class="row">
            <?php 
            foreach($mount_info as $mount_info) {
                echo row($mount_info['mountpoint'], 
                    bar($mount_info['table'], sprintf(_('Used: %s%%'), $mount_info['percent']), array(
                        'Total'=>$mount_info['total'],
                        'Used'=>$mount_info['used'],
                        'Free'=>$mount_info['free']
                    ))
                );
            }
            ?>
        </dl>

        <div id="clientinfo-container">
            <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('PHP'); ?></h4>
            <dl class="row">
            <?php echo row(_('Version'), $system['php'] . ' (' . "Zend Version" . ' ' . $system['zend'] . ')'); ?>
            <?php echo row(_('Modules'), implode(' | ', $php_modules), '', 'overflow-hidden'); ?>
            </dl>
        </div>
    </div>


    <h3><?php echo _('Client Information'); ?></h3>
    <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('HTTP'); ?></h4>
    <dl class="row">
        <?php echo row(_('Browser'), $_SERVER['HTTP_USER_AGENT']); ?>
        <?php echo row(_('Language'), $_SERVER['HTTP_ACCEPT_LANGUAGE']); ?>
    </dl>
    <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('Window'); ?></h4>
    <dl class="row">
        <?php echo row(_('Size'), '<span id="windowsize"><script>document.write($( window ).width() + " x " + $( window ).height())</script></span>'); ?>
    </dl>
    <h4 class="text-info text-uppercase border-top pt-2"><?php echo _('Screen'); ?></h4>
    <dl class="row">
        <?php echo row(_('Resolution'), "<script>document.write(window.screen.width + ' x ' + window.screen.height);</script>"); ?>
    </dl>

</div><!-- eof .admin-container -->

<div id="snackbar" class=""></div>

<script>
function copyTextToClipboard(text) {
  var textArea = document.createElement("textarea");
  textArea.style.position = 'fixed';
  textArea.style.top = 0;
  textArea.style.left = 0;
  textArea.style.width = '2em';
  textArea.style.height = '2em';
  textArea.style.padding = 0;
  textArea.style.border = 'none';
  textArea.style.outline = 'none';
  textArea.style.boxShadow = 'none';
  textArea.style.background = 'transparent';
  textArea.value = text;
  document.body.appendChild(textArea);
  textArea.select();
  try {
    var successful = document.execCommand('copy');
    var msg = successful ? 'successful' : 'unsuccessful';
    console.log('Copying text command was ' + msg);
    snackbar('Copied to clipboard');
  } 
  catch(err) {
    window.prompt("<?php echo _('Copy to clipboard: Ctrl+C, Enter'); ?>", text);
  }
  document.body.removeChild(textArea);
}
var serverInfoDetails = $('#serverinfo-container').html()
// remove buttons
.replace(/<\/?button.[\s\S]*?button./g,'')
// remove html space
.replace(/&nbsp;/g,'')
// remove comments
.replace(/<!--[\S\s]*-->/g,'')
// replace <h4> with ##
.replace(/<h4 *[^/]*?>/g,"## ")
.replace(/<\/h4>[ \n]/g,"~~\n")
// remove <dl>
.replace(/<dl *[^/]*?>/g,"")
.replace(/<\/dl>[ \n]/g,"\n~~")
// remove <dt>
.replace(/<dt *[^/]*?>/g,' - **')
.replace(/<\/dt>[ \n]*/g,'**')
// remove <dd>
.replace(/<dd *[^/]*?>/g,' | ')
.replace(/<\/dd>/g,"\n")
// remove all other <tags>
.replace(/(<([^>]+)>)/ig,'')
// remove multiple spaces or new lines
.replace(/[\n ]{3,}/g,"\n")
// remove mulitple proceeding spaces
.replace(/[ ]{2,}/g,' ')
// swap placeholder for newline char
.replace(/~{2}/g,"\n \n")
// fix incorrect <li> wrapping
.replace(/- \*\*[ \n]/g,'- **')


var clientInfoDetails = '\n|HTTP|Browser|'+'<?php echo $_SERVER['HTTP_USER_AGENT']; ?>'+'\n|Screen|Resolution|'+ window.screen.width + ' x ' + window.screen.height +'\n|Window|Size|' + $(window).width() + ' x ' + $(window).height();

$("#copyserverinfo").on('click', function(event) {
    if ( event.ctrlKey ) {
        copyTextToClipboard('Server Information\n\n' + 
        serverInfoDetails
        .replace(/\|/g,'\t')
        .replace(/- \*\*/g,'**')
        .replace(/\*\*/g,'')
        .replace(/##/g,'')
        .replace(/\n /g,'')
        .replace(/( \n)/g,'\n') +
        '\nClient Information\n' + 
        clientInfoDetails.replace(/\|/g,'\t'));
    } else {
        copyTextToClipboard('# Server Information\n'+serverInfoDetails.replace(/\n+/g, '\n') + '\n# Client Information\n' + clientInfoDetails);
    }
} );

var logFileDetails;
$("#copylogfile").on('click', function(event) {
    logFileDetails = $("#logreply").text();
    if ( event.ctrlKey ) {
        copyTextToClipboard('LAST ENTRIES ON THE LOG FILE\n'+logFileDetails);
    } else {
        copyTextToClipboard('<details><summary>LAST ENTRIES ON THE LOG FILE</summary><br />\n'+ logFileDetails.replace(/\n/g,'<br />\n').replace(/API key '[\s\S]*?'/g,'API key \'xxxxxxxxx\'') + '</details><br />\n');
    }
} );
$(window).resize(function() {
  $("#windowsize").html( $(window).width() + " x " + $(window).height() );
});
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

$(".update").click(function() {

  var type = $(this).attr("type");
  var firmware = $("#selected_firmware").val();
  
  $.ajax({ type: "POST", url: path+"admin/emonpi/update", data: "type="+type+"&firmware="+firmware, async: true, success: function(result)
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

function snackbar(text) {
    var snackbar = document.getElementById("snackbar");
    snackbar.innerHTML = text;
    snackbar.className = "show";
    setTimeout(function () {
        snackbar.className = snackbar.className.replace("show", "");
    }, 3000);
}

</script>
