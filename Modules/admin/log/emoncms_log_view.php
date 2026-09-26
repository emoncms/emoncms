<?php
defined('EMONCMS_EXEC') or die('Restricted access');
?>

<?php load_css("Modules/admin/static/admin_styles.css"); ?>
<div class="admin-page">

    <?php
    // LOG FILE VIEWER
    // -------------------
    if ($log_enabled) { ?>
    <div class="page-header">
        <h3><?php echo tr('Emoncms Log'); ?></h3>
        <?php if(is_writable($emoncms_logfile)) { ?>
        <div class="page-actions">
            <button id="getlog" type="button" class="btn btn-default" aria-pressed="false" autocomplete="off"><?php echo tr('Auto refresh'); ?></button>
            <a href="<?php echo $path; ?>admin/log/download" class="btn btn-default"><?php echo tr('Download Log'); ?></a>
            <button class="btn btn-default" id="copylogfile" type="button"><?php echo tr('Copy Log to clipboard'); ?></button>
        </div>
        <?php } ?>
    </div>
    <?php if(is_writable($emoncms_logfile)) { ?>
    <p class="page-lead"><?php echo sprintf("%s <code>%s</code>",tr('View last entries on the logfile:'),$emoncms_logfile); ?></p>
    <?php } else { ?>
    <div class="alert alert-warning">The log file has no write permissions or does not exists. To fix, log-on on shell and do:<pre><?php echo "touch $emoncms_logfile\nchmod 666 $emoncms_logfile"; ?></pre></div>
    <?php } ?>

    <div class="panel log-panel">
        <pre id="logreply-bound" class="log admin-log"><div id="logreply"></div></pre>
        <span id="log-level" class="badge bg-secondary text-uppercase" title="Can be changed in settings file"><?php echo sprintf('Log Level: %s', $log_level_label) ?></span>
    </div>

    <?php
        } else {
            echo '<div class="page-header"><h3>'.tr('Emoncms Log').'</h3></div>';
            echo '<div class="alert alert-warning">'.tr('Logging is disabled in settings.').'</div>';
        }
    ?>
</div>
<div id="snackbar" class=""></div>
<script>


var logFileDetails;
$("#copylogfile").on('click', function(event) {
    logFileDetails = $("#logreply").text();
    if ( event.ctrlKey ) {
        copyTextToClipboard('LAST ENTRIES ON THE LOG FILE\n'+logFileDetails,
        event.target.dataset.success);
    } else {
        copyTextToClipboard('<details><summary>LAST ENTRIES ON THE LOG FILE</summary><br />\n'+ logFileDetails.replace(/\n/g,'<br />\n').replace(/API key '[\s\S]*?'/g,'API key \'xxxxxxxxx\'') + '</details><br />\n',
        event.target.dataset.success);
    }
} );

var logrunning = false;

// setInterval() markers
var emoncms_log_interval;

// stop updates if interval == 0
function refresherStart(func, interval){
    if (interval > 0) return setInterval(func, interval);
}

// push value to emoncms logfile viewer
function refresh_log(result){
    var isjson = true;
    try {
        data = JSON.parse(result);
        if (data.reauth == true) { window.location.reload(true); }
        if (data.success != undefined)  { 
            clearInterval(emoncms_log_interval);
            $container = $("#logreply");
            if (data.success) {
                output_logfile(data.log, $container);
            } else {
                $container.text(data.message);
                $container.css('color', 'red');
            }
            scrollable = $container.parent('pre')[0];
            if(scrollable) scrollable.scrollTop = scrollable.scrollHeight;
        }
    } catch (e) {
        isjson = false;
    }
    if (isjson == false )     {
        output_logfile(result, $("#logreply"));
    }

}
// display content in container and scroll to the bottom
function output_logfile(result, $container){
    $container.text(result);
    scrollable = $container.parent('pre')[0];
    if(scrollable) scrollable.scrollTop = scrollable.scrollHeight;
}

getLog();
// use the api to get the latest value from the logfile
function getLog() {
  $.ajax({ url: path+"admin/log/get", async: true, dataType: "text", success: refresh_log });
}

// auto refresh the updates logfile
$("#getlog").click(function() {
    var active = $(this).toggleClass('active').hasClass('active');
    $(this).attr('aria-pressed', active);
    if (active) {
        emoncms_log_interval = refresherStart(getLog, 1000);
    } else {
        clearInterval(emoncms_log_interval);
    }
});
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
    window.prompt("<?php echo tr('Copy to clipboard: Ctrl+C, Enter'); ?>", text);
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
