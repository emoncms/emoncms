<?php $v=1; ?>
<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css?v=<?php echo $v ?>">

<h3><?php echo _('Update'); ?></h3>

<div class="admin-container">
    
    <?php 
    // UPDATES 
    // -------------------
    ?>
    <section class="d-md-flex justify-content-between align-items-center pb-md-2 border-top pb-md-0 text-right pb-2 px-1">
        <div class="text-left">
            <h4 class="text-info text-uppercase mb-2"><?php echo _('Full Update'); ?></h4>
            <p><?php echo _('OS, Packages, EmonHub, Emoncms & Firmware (If new version)'); ?></p>
        </div>
        <div class="btn-group">
        <button class="update btn btn-info" type="all" title="<?php echo _('Update All'); ?> - <?php echo _('OS, Packages, EmonHub, Emoncms & Firmware (If new version)'); ?>">
            <?php echo _('Full Update'); ?>
        </button>
        </div>
    </section>
    
    <?php 
    // EMONCMS UPDATE
    // -------------------
    ?>
    <aside class="d-md-flex justify-content-between align-items-center pb-md-2 border-top pb-md-0 text-right pb-2 border-top px-1">
        <div class="text-left">
            <h4 class="text-info text-uppercase mb-2"><?php echo _('Update Emoncms Only'); ?></h4>
            <p><?php echo _('Emoncms, Emoncms Modules and Services'); ?></p>
            <p><b>Release info:</b> <a href="https://github.com/emoncms/emoncms/releases"> Emoncms</a></p>
        </div>
        <a class="update btn btn-info" type="emoncms"><?php echo _('Update Emoncms'); ?></a>
    </aside>

    <?php 
    // SYSTEM UPDATE
    // -------------------
    ?>
    <aside class="d-md-flex justify-content-between align-items-center pb-md-2 border-top pb-md-0 text-right pb-2 border-top px-1">
        <div class="text-left">
            <h4 class="text-info text-uppercase mb-2"><?php echo _('Update Firmware Only'); ?></h4>
            <p><?php echo _('Select your hardware type and firmware version'); ?></p>
            <p><b>Release info:</b> <a href="https://github.com/openenergymonitor/emonpi/releases">emonPi</a> | <a href="https://github.com/openenergymonitor/RFM2Pi/releases">RFM69Pi</a></p>
        </div>
        <div class="input-append">
            <select id="select_serial_port">
                <?php foreach ($serial_ports as $port) { ?>
                <option><?php echo $port; ?></option>
                <?php } ?>
            </select>
            <select id="selected_firmware">
                <option value="none">none</option>
                <option value="emonpi">EmonPi</option>
                <option value="rfm69pi">RFM69Pi</option>
                <option value="rfm12pi">RFM12Pi</option>
                <option value="emontxv3cm">EmonTxV3CM</option>
                <option value="custom">Custom</option>
            </select>
            <button class="update btn btn-info" type="firmware"><?php echo _('Update Firmware'); ?></button>
        </div>
    </aside>

    <?php 
    // DATABASE UPDATE
    // -------------------
    ?>
    <aside class="d-md-flex justify-content-between align-items-center pb-md-2 border-top pb-md-0 text-right pb-2 border-top px-1">
        <div class="text-left span6 ml-0">
            <h4 class="text-info text-uppercase mb-2"><?php echo _('Update Database Only'); ?></h4>
            <p><?php echo _('Run this after a manual emoncms update, after installing a new module or to check emoncms database status.'); ?></p>
        </div>
        <a href="<?php echo $path; ?>admin/db" class="btn btn-info"><?php echo _('Update Database'); ?></a>
    </aside>
    
    <?php
    // UPDATE LOG FILE VIEWER
    // -------------------
    //if (is_file($update_log_filename)) { ?>
    <div id="update-logfile-view" class="hide">
    <section class="d-md-flex justify-content-between align-items-center pb-md-2 text-right px-1 border-top">
        <div class="text-left">
            <h3 class="mt-1 mb-0"><?php echo _('Update Log'); ?></h3>
            <p><?php
            // if(is_readable($update_log_filename)) {
                echo sprintf("%s <code>%s</code>",_('View last entries on the logfile:'), $update_log_filename);
            // } else {
                //echo '<div class="alert alert-warn">';
                //echo sprintf('The log file has no read permissions or does not exists. To fix, log-on on shell and do: <pre style="height:3em;overflow:auto">touch %1$s<br>chmod 666 %1$s</pre>',$update_log_filename);
                //echo "</div>";
            // } ?></p>
        </div>
        <div>
            <?php // if(is_readable($update_log_filename)) { ?>
                <button id="getupdatelog" type="button" class="btn btn-info mb-1" data-toggle="button" aria-pressed="false" autocomplete="off">
                    <?php echo _('Auto refresh'); ?>
                </button>
                <a href="<?php echo $path; ?>admin/update-log-download" class="btn btn-info mb-1"><?php echo _('Download Log'); ?></a>
                <button class="btn btn-info mb-1" id="copyupdatelogfile" type="button"><?php echo _('Copy Log to clipboard'); ?></button>
            <?php // } ?>
        </div>
    </section>
    <pre id="update-log-bound" class="log"><div id="update-log"></div></pre>
    </div>
</div>
<div id="snackbar" class=""></div>
<script>

var logFileDetails;
$("#copyupdatelogfile").on('click', function(event) {
    logFileDetails = $("#update-log").text();
    if ( event.ctrlKey ) {
        copyTextToClipboard('LAST ENTRIES ON THE UPDATE LOG FILE\n'+logFileDetails,
        event.target.dataset.success);
    } else {
        copyTextToClipboard('<details><summary>LAST ENTRIES ON THE LOG FILE</summary><br />\n'+ logFileDetails.replace(/\n/g,'<br />\n').replace(/API key '[\s\S]*?'/g,'API key \'xxxxxxxxx\'') + '</details><br />\n',
        event.target.dataset.success);
    }
} );

var updatelogrunning = false;
var updates_log_interval;


// stop updates if interval == 0
function refresherStart(func, interval){
    if (interval > 0) return setInterval(func, interval);
}

// push value to emoncms logfile viewer
function refresh_log(result){
    output_logfile(result, $("#logreply"));
}
// display content in container and scroll to the bottom
function output_logfile(result, $container){
    $container.html(result);
    scrollable = $container.parent('pre')[0];
    if(scrollable) scrollable.scrollTop = scrollable.scrollHeight;
}

// push value to updates logfile viewer
function refresh_updateLog(result){
    output_logfile(result, $("#update-log"));
    if (result!="<?php echo $update_log_filename; ?> does not exist") $("#update-logfile-view").show();
}

// auto refresh the updates logfile
$("#getupdatelog").click(function() {
    $this = $(this)
    if ($this.is('.active')) {
        clearInterval(updates_log_interval);
    } else {
        updates_log_interval = refresherStart(getUpdateLog, 500); 
    }
});
// update all button clicked
$(".update").click(function() {
  var type = $(this).attr("type");
  var serial_port = $("#select_serial_port").val();
  var firmware = $("#selected_firmware").val();
  $.ajax({ type: "POST", url: path+"admin/update-start", data: "type="+type+"&firmware="+firmware+"&serial_port="+serial_port, async: true, success: function(result)
    {
      // update with latest value
      refresh_updateLog(result);
      // autoupdate every 1s
      updates_log_interval = refresherStart(getUpdateLog, 1000)
    }
  });
});

$("#rfm69piupdate").click(function() {
  $.ajax({ type: "POST", url: path+"admin/update-start", data: "argument=rfm69pi", async: true, success: function(result)
    {
      // update with latest value
      refresh_updateLog(result);
      // autoupdate every 1s
      updates_log_interval = refresherStart(getUpdateLog, 1000)
    }
  });
});
// shrink log file viewers
$('[data-dismiss="log"]').click(function(event){
    event.preventDefault();
    $(this).parents('pre').first().addClass('small');
})
getUpdateLog();
function getUpdateLog() {
  $.ajax({ url: path+"admin/update-log", async: true, dataType: "text", success: function(result)
    {
      refresh_updateLog(result);
      if (result.indexOf("System update done")!=-1) {
          clearInterval(updates_log_interval);
      }
    }
  });
}
function copyTextToClipboard(text, message) {
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
    // console.log('Copying text command was ' + msg);
    snackbar(message || 'Copied to clipboard');
  } 
  catch(err) {
    window.prompt("<?php echo _('Copy to clipboard: Ctrl+C, Enter'); ?>", text);
  }
  document.body.removeChild(textArea);
}
function snackbar(text) {
    var snackbar = document.getElementById("snackbar");
    snackbar.innerHTML = text;
    snackbar.className = "show";
    setTimeout(function () {
        snackbar.className = snackbar.className.replace("show", "");
    }, 3000);
}
</script>
