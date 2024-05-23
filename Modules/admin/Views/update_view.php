<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css?v=1">
<div class="admin-container">
    <h3><?php echo _('Update'); ?></h3>

<?php if (PHP_VERSION_ID<70300) { ?>
<div class="alert alert-error"><b>Important:</b> PHP version <?php echo PHP_VERSION; ?> detected. Please update to version 7.3 or newer to keep your installation secure.<br>This emoncms installation is running in compatibility mode and does not include all of the latest security improvements.<br>See guide on updating php on the emoncms github: <a href="https://github.com/emoncms/emoncms/issues/1726">Updating PHP.</a></div>
<?php } ?>

    <?php
    // UPDATES
    // -------------------
    ?>
    <section class="d-md-flex justify-content-between align-items-center pb-md-2 border-top pb-md-0 text-right pb-2 px-1">
        <div class="text-left">
            <h4 class="text-info text-uppercase mb-2"><?php echo _('Full Update'); ?></h4>
            <p><?php echo _('OS, Packages, EmonHub, Emoncms (Does not include firmware update)'); ?></p>
        </div>
        <div class="btn-group">
        <button class="update btn btn-info" type="all" title="<?php echo _('Update All'); ?> - <?php echo _('OS, Packages, EmonHub, Emoncms'); ?>">
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
        <div class="text-left" style="margin-bottom:10px">
            <h4 class="text-info text-uppercase mb-2"><?php echo _('Update Firmware Only'); ?></h4>
            <p><?php echo _('Select your hardware type and firmware version'); ?></p>

            <div class="input-prepend" style="margin-bottom:0px">
                <span class="add-on">Select port:</span>
                <select id="select_serial_port">
                    <?php foreach ($serial_ports as $port) { ?>
                    <option><?php echo $port; ?></option>
                    <?php } ?>
                </select>
            </div>

            <?php
            $hardware_options = array();
            foreach ($firmware_available as $firmware) {
                if (!in_array($firmware->hardware,$hardware_options)) {
                    $hardware_options[] = $firmware->hardware;
                }
            }
            ?>
            <div class="input-prepend" style="margin-bottom:0px">
                <span class="add-on">Hardware:</span>
                <select id="selected_hardware">
                    <option value="none">none</option>
                    <?php foreach ($hardware_options as $hardware) { ?>
                    <option><?php echo $hardware; ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="input-prepend" style="margin-bottom:0px">
                <span class="add-on">Radio format:</span>
                <select id="selected_radio_format">
                   <option value="lowpowerlabs" selected>RFM69 LowPowerLabs</option>
                    <!--<option value="jeelib_native">RFM69 JeeLib Native</option>-->
                    <option value="jeelib_classic">RFM69 JeeLib Classic</option>
                </select>
            </div>
            <br>
            <div class="input-prepend" style="margin-bottom:0px; margin-top:10px">
                <span class="add-on">Firmware:</span>
                <select id="selected_firmware" style="width:552px">
                    <option value="none">none</option>
                </select>
            </div>
            <div id="custom_firmware_bound" style="display: none; color:#333; font-size:14px">
                <hr>
                <!-- option to upload custom firmware -->
                <p>- or - upload custom firmware to <b><span id="custom_firmware_hardware"></span></b> on <b><span id="custom_firmware_port"></span></b>:
                <input type="file" id="custom_firmware" name="custom_firmware" accept=".hex"></p>
            </div>
        </div>

        <button id="update-firmware" class="btn btn-info"><?php echo _('Update Firmware'); ?></button>
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

var firmware_available = <?php echo json_encode($firmware_available); ?>;

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

var updates_log_interval;

// stop updates if interval == 0
function refresherStart(func, interval){
    clearInterval(updates_log_interval);
    updates_log_interval = setInterval(func, interval);
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
    $("#update-logfile-view").slideDown();
}

// auto refresh the updates logfile
$("#getupdatelog").click(function() {
    $this = $(this)
    if ($this.is('.active')) {
        clearInterval(updates_log_interval);
    } else {
        refresherStart(getUpdateLog, 1000);
    }
});
// update all button clicked
$(".update").click(function() {
    refresh_updateLog("");
    var type = $(this).attr("type");
    var serial_port = $("#select_serial_port").val();
    var firmware_key = $("#selected_firmware").val();

    $.ajax({
        type: "POST",
        url: path+"admin/update-start",
        data: "type="+type+"&serial_port="+serial_port+"&firmware_key="+firmware_key,
        async: true,
        dataType: "json",
        success: function(result) {
            if (result.reauth == true) { window.location.reload(true); }
            if (result.success == false)  {
                clearInterval(updates_log_interval);
                refresh_updateLog("<text style='color:red;'>" + result.message + "</text>\n");
            } else {
                refresh_updateLog(result.message);
                refresherStart(getUpdateLog, 1000)
            }
        }
    });
});

$("#selected_hardware").change(function(){
    draw_firmware_select_list();

    // show custom firmware upload if hardware is not none
    if ($("#selected_hardware").val() != "none") {
        $("#custom_firmware_bound").show();
        $("#custom_firmware_hardware").text($("#selected_hardware").val());
        $("#custom_firmware_port").text($("#select_serial_port").val());
    } else {
        $("#custom_firmware_bound").hide();
    }
});

// port change
$("#select_serial_port").change(function(){
    $("#custom_firmware_port").text($("#select_serial_port").val());
});

$("#selected_radio_format").change(function(){
    draw_firmware_select_list();
});

// custom firmware upload
$("#custom_firmware").change(function(){
    // get the file
    var file = this.files[0];

    // check if file is a hex file
    if (file.name.split('.').pop() != "hex") {
        alert("Please select a .hex file");
        return;
    }

    // create form data
    var formData = new FormData();

    // Get the baud rate from the selected firmware
    var firmware_key = $("#selected_firmware").val();
    var firmware = firmware_available[firmware_key];

    // 1. port
    formData.append('port', $("#select_serial_port").val());
    // 2. baud rate
    formData.append('baud_rate', firmware.baud);
    // 3. core
    formData.append('core', firmware.core);
    // 4. autoreset
    formData.append('autoreset', firmware.autoreset);

    // 5. file
    formData.append('custom_firmware', file);
    
    // just submit the file
    $.ajax({
        type: "POST",
        url: path+"admin/upload-custom-firmware",
        data: formData,
        async: true,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(result) {
            if (result.reauth == true) { window.location.reload(true); }
            if (result.success == false)  {
                clearInterval(updates_log_interval);
                refresh_updateLog("<text style='color:red;'>" + result.message + "</text>\n");
            } else {
                refresh_updateLog(result.message);
                refresherStart(getUpdateLog, 1000)
            }
          
        }
    });
});

function draw_firmware_select_list() {
    refresh_updateLog("");
    var hardware = $("#selected_hardware").val();
    var radio_format = $("#selected_radio_format").val();

    if (hardware=="none") {
        $("#selected_firmware").html("<option>none</option>");
        return;
    }

    var out = "";
    for (var firmware_key in firmware_available) {
        var firmware = firmware_available[firmware_key];
        if (firmware.hardware==hardware && firmware.radio_format==radio_format) {
            out += "<option value='"+firmware_key+"'>"+firmware.description+", "+firmware.radio_format+", v"+firmware.version+"</option>";
        }
    }
    $("#selected_firmware").html(out);
}


$("#update-firmware").click(function() {
    refresh_updateLog("");
    var serial_port = $("#select_serial_port").val();
    var firmware_key = $("#selected_firmware").val();

    $.ajax({
        type: "POST",
        url: path+"admin/update-firmware",
        data: "serial_port="+serial_port+"&firmware_key="+firmware_key,
        async: true,
        dataType: "json",
        success: function(result) {
            if (result.reauth == true) { window.location.reload(true); }
            if (result.success == false)  {
                clearInterval(updates_log_interval);
                refresh_updateLog("<text style='color:red;'>" + result.message + "</text>\n");
            } else {
                refresh_updateLog(result.message);
                refresherStart(getUpdateLog, 1000)
            }
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
        var isjson = true;
        try {
            data = JSON.parse(result);
            if (data.reauth == true) { window.location.reload(true); }
            if (data.success == false)  {
                clearInterval(updates_log_interval);
                refresh_updateLog("<text style='color:red;'>"+ data.message+"</text>");
            }
        } catch (e) {
            isjson = false;
        }
        if (isjson == false )     {
            if (result != "") {
                refresh_updateLog(result);
                if (result.indexOf("System update done")!=-1) {
                    clearInterval(updates_log_interval);
                }
            }
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
