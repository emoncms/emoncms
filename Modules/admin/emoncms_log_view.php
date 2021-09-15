<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css?v=<?php echo $v ?>">

<div class="admin-container" style="margin-top:10px">

    <?php
    // LOG FILE VIEWER
    // -------------------
    if ($log_enabled) { ?>
    <section class="d-md-flex justify-content-between align-items-center pb-md-2 text-right px-1">
        <div class="text-left">
            <h3 class="mt-1 mb-0"><?php echo _('Emoncms Log'); ?></h3>
            <p><?php
            if(is_writable($emoncms_logfile)) {
                echo sprintf("%s <code>%s</code>",_('View last entries on the logfile:'),$emoncms_logfile);
            } else {
                echo '<div class="alert alert-warn">';
                echo "The log file has no write permissions or does not exists. To fix, log-on on shell and do:<br><pre>touch $emoncms_logfile<br>chmod 666 $emoncms_logfile</pre>";
                echo '<small></div>';
            } ?></p>
        </div>
        <div>
            <?php if(is_writable($emoncms_logfile)) { ?>
                <button id="getlog" type="button" class="btn btn-info mb-1" data-toggle="button" aria-pressed="false" autocomplete="off">
                    <?php echo _('Auto refresh'); ?>
                </button>
                <a href="<?php echo $path; ?>admin/downloadlog" class="btn btn-info mb-1"><?php echo _('Download Log'); ?></a>
                <button class="btn btn-info mb-1" id="copylogfile" type="button"><?php echo _('Copy Log to clipboard'); ?></button>
            <?php } ?>
        </div>
    </section>
    
    <section>
        <pre id="logreply-bound" class="log" style="height:520px"><div id="logreply"></div></pre>
        <?php if(isset($path_to_config) && is_writable($path_to_config)) { ?>
        <div id="log-level" class="dropup btn-group">
            <a class="btn btn-small dropdown-toggle btn-inverse text-uppercase" data-toggle="dropdown" href="#" title="<?php echo _('Change the logging level') ?>">
            <span class="log-level-name"><?php echo sprintf('Log Level: %s', $log_level_label) ?></span>
            <span class="caret"></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                <?php foreach ($log_levels as $key=>$value) {
                    $active = $key === $log_level ? ' active':'';
                    printf('<li><a href="#" data-key="%s" class="btn %s">%s</a></li>', $key, $active, $value);
                }?>

            </ul>
        </div>
        <?php } else { ?>
            <span id="log-level" class="btn-small dropdown-toggle btn-inverse text-uppercase">
                <?php echo sprintf('Log Level: %s', $log_level_label) ?>
            </span>
        <?php } ?>
    </section>
    
    <?php } ?>
    
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

    if (result=="Admin re-authentication required") {
        window.location = "/";
    }

    output_logfile(result, $("#logreply"));
}
// display content in container and scroll to the bottom
function output_logfile(result, $container){
    $container.html(result);
    scrollable = $container.parent('pre')[0];
    if(scrollable) scrollable.scrollTop = scrollable.scrollHeight;
}

getLog();
// use the api to get the latest value from the logfile
function getLog() {
  $.ajax({ url: path+"admin/getlog", async: true, dataType: "text", success: refresh_log });
}

// auto refresh the updates logfile
$("#getlog").click(function() {
    $this = $(this)
    if ($this.is('.active')) {
        clearInterval(emoncms_log_interval);
    } else {
        emoncms_log_interval = refresherStart(getLog, 500); 
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
