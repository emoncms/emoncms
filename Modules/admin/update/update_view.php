<?php 
defined('EMONCMS_EXEC') or die('Restricted access');
global $settings; 
?>
<?php load_css("Modules/admin/static/admin_styles.css"); ?>
<div class="admin-page">
    <div class="page-header">
        <h3><?php echo tr('Update'); ?></h3>
    </div>

<?php if (PHP_VERSION_ID<70300) { ?>
<div class="alert alert-danger"><b>Important:</b> PHP version <?php echo PHP_VERSION; ?> detected. Please update to version 7.3 or newer to keep your installation secure.<br>This emoncms installation is running in compatibility mode and does not include all of the latest security improvements.<br>See guide on updating php on the emoncms github: <a href="https://github.com/emoncms/emoncms/issues/1726">Updating PHP.</a></div>
<?php } ?>

    <div class="panel">
        <div class="panel-header panel-header-static">
            <span class="panel-accent"></span>
            <span class="panel-name"><?php echo tr('Updates'); ?></span>
        </div>
        <div class="panel-row">
            <div class="row-key"><?php echo tr('Full Update'); ?></div>
            <div class="row-value"><?php echo tr('OS, Packages, EmonHub, Emoncms (Does not include firmware update)'); ?></div>
            <button class="update btn btn-primary" type="all" title="<?php echo tr('Update All'); ?> - <?php echo tr('OS, Packages, EmonHub, Emoncms'); ?>"><?php echo tr('Full Update'); ?></button>
        </div>
        <div class="panel-row">
            <div class="row-key"><?php echo tr('Update Emoncms Only'); ?></div>
            <div class="row-value"><?php echo tr('Emoncms, Emoncms Modules and Services'); ?>. <a href="https://github.com/emoncms/emoncms/releases">Release info</a></div>
            <button class="update btn btn-primary" type="emoncms"><?php echo tr('Update Emoncms'); ?></button>
        </div>
        <div class="panel-row">
            <div class="row-key"><?php echo tr('Update Database Only'); ?></div>
            <div class="row-value"><?php echo tr('Run this after a manual emoncms update, after installing a new module or to check emoncms database status.'); ?></div>
            <a href="<?php echo $path; ?>admin/db" class="btn btn-primary"><?php echo tr('Update Database'); ?></a>
        </div>
    </div>

    <?php
    $hardware_options = array();
    foreach ($firmware_available as $firmware) {
        if (!in_array($firmware->hardware,$hardware_options)) {
            $hardware_options[] = $firmware->hardware;
        }
    }
    ?>
    <div class="panel">
        <div class="panel-header panel-header-static">
            <span class="panel-accent"></span>
            <span class="panel-name"><?php echo tr('Update Firmware Only'); ?></span>
        </div>
        <div class="panel-body admin-form">
            <p class="text-muted"><?php echo tr('Select your hardware type and firmware version'); ?></p>

            <div class="admin-field">
                <label class="form-label" for="select_serial_port"><?php echo tr('Serial port'); ?></label>
                <select class="form-select input-165" id="select_serial_port">
                    <?php foreach ($serial_ports as $port) { ?>
                    <option><?php echo $port; ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="admin-field">
                <label class="form-label" for="selected_hardware"><?php echo tr('Hardware'); ?></label>
                <select class="form-select input-220" id="selected_hardware">
                    <option value="none">none</option>
                    <?php foreach ($hardware_options as $hardware) { ?>
                    <option><?php echo $hardware; ?></option>
                    <?php } ?>
                </select>
            </div>

            <div id="radio_format_bound" class="admin-field">
                <label class="form-label" for="selected_radio_format"><?php echo tr('Radio format'); ?></label>
                <select class="form-select input-220" id="selected_radio_format">
                    <option value="lowpowerlabs" selected>RFM69 LowPowerLabs</option>
                    <!--<option value="jeelib_native">RFM69 JeeLib Native</option>-->
                    <option value="jeelib_classic">RFM69 JeeLib Classic</option>
                </select>
            </div>

            <div class="admin-field">
                <label class="d-inline-block me-3">
                    <input type="radio" name="firmware_source" value="standard" checked> <?php echo tr('Standard firmware'); ?>
                </label>
                <label class="d-inline-block">
                    <input type="radio" name="firmware_source" value="custom" id="firmware_source_custom" disabled> <?php echo tr('Custom firmware file'); ?>
                </label>
            </div>

            <div id="standard_firmware_bound" class="admin-field">
                <label class="form-label" for="selected_firmware"><?php echo tr('Firmware'); ?></label>
                <select class="form-select input-545" id="selected_firmware">
                    <option value="none">none</option>
                </select>
            </div>

            <div id="custom_firmware_bound" class="admin-field" style="display:none">
                <label class="form-label"><?php echo tr('Firmware file'); ?></label>
                <div>
                    <label for="custom_firmware" class="btn btn-default" tabindex="0"><?php echo tr('Choose file'); ?>&hellip;</label>
                    <span id="custom_firmware_name"><?php echo tr('No file selected'); ?></span>
                </div>
                <!-- the native file input is visually hidden, the label above opens it -->
                <input type="file" id="custom_firmware" name="custom_firmware" accept=".hex,.bin" class="visually-hidden">
            </div>

            <p id="firmware_summary" class="firmware-summary"></p>

            <button id="update-firmware" class="btn btn-primary"><?php echo tr('Update Firmware'); ?></button>
        </div>
    </div>

    <?php
    // UPDATE LOG FILE VIEWER
    // -------------------
    ?>
    <div id="update-logfile-view" class="panel hide">
        <div class="panel-header panel-header-static">
            <span class="panel-accent"></span>
            <span class="panel-name"><?php echo tr('Update Log'); ?></span>
            <button id="getupdatelog" type="button" class="btn btn-default btn-sm" aria-pressed="false" autocomplete="off"><?php echo tr('Auto refresh'); ?></button>
            <a href="<?php echo $path; ?>admin/update/log-download" class="btn btn-default btn-sm"><?php echo tr('Download Log'); ?></a>
            <button class="btn btn-default btn-sm" id="copyupdatelogfile" type="button"><?php echo tr('Copy Log to clipboard'); ?></button>
        </div>
        <pre id="update-log-bound" class="log"><div id="update-log"></div></pre>
        <div class="panel-footer-note"><?php echo sprintf("%s <code>%s</code>",tr('View last entries on the logfile:'), $update_log_filename); ?></div>
    </div>
</div>
<div id="snackbar" class=""></div>
<script>

var firmware_available = <?php echo json_encode($firmware_available); ?>;

var standard_firmware_button_label = <?php echo json_encode(tr('Update Firmware')); ?>;
var custom_firmware_button_label = <?php echo json_encode(tr('Upload & Flash')); ?>;
var flashing_message = <?php echo json_encode(tr('Flashing...')); ?>;
var select_hardware_message = <?php echo json_encode(tr('Select your hardware type to continue')); ?>;
var select_file_message = <?php echo json_encode(tr('Select a .hex or .bin firmware file to upload')); ?>;
var no_file_message = <?php echo json_encode(tr('No file selected')); ?>;
var no_firmware_message = <?php echo json_encode(tr('No firmware available for this hardware and radio format')); ?>;

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
    var active = $(this).toggleClass('active').hasClass('active');
    $(this).attr('aria-pressed', active);
    if (active) {
        refresherStart(getUpdateLog, 1000);
    } else {
        clearInterval(updates_log_interval);
    }
});
// update all button clicked
$(".update").click(function() {
    refresh_updateLog("");
    var type = $(this).attr("type");
    var serial_port = $("#select_serial_port").val();
    // In custom firmware mode the standard firmware selection must not be flashed,
    // it is only used to derive the upload settings (baud rate, core, autoreset)
    var firmware_key = firmware_source()=="custom" ? "none" : $("#selected_firmware").val();

    $.ajax({
        type: "POST",
        url: path+"admin/update/start",
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

    // custom firmware upload is only possible once a hardware type is selected,
    // the selected hardware provides the upload settings (baud rate, core, autoreset)
    if ($("#selected_hardware").val() == "none") {
        $("#firmware_source_custom").prop("disabled", true);
        $("input[name=firmware_source][value=standard]").prop("checked", true);
        set_firmware_source_view();
    } else {
        $("#firmware_source_custom").prop("disabled", false);
        update_firmware_summary();
    }
});

// port change
$("#select_serial_port").change(function(){
    update_firmware_summary();
});

$("#selected_radio_format").change(function(){
    draw_firmware_select_list();
});

$("#selected_firmware").change(function(){
    update_firmware_summary();
});

// switch between standard firmware and custom firmware file
$("input[name=firmware_source]").change(function(){
    set_firmware_source_view();
});

// selecting a file no longer starts the upload, it only updates the summary.
// the upload is started by the update firmware button, so that there is a
// single, explicit action that flashes the board
$("#custom_firmware").change(function(){
    update_custom_firmware_name();
    update_firmware_summary();
});

// the file input is visually hidden so the label needs to be keyboard operable
$("#custom_firmware_bound label.btn").keydown(function(event){
    if (event.which==13 || event.which==32) {
        event.preventDefault();
        $("#custom_firmware").click();
    }
});

// show the selected filename in place of the browsers native file input text
function update_custom_firmware_name() {
    var file = custom_firmware_file();
    if (file===null) {
        $("#custom_firmware_name").text(no_file_message).removeClass("file-selected");
    } else {
        $("#custom_firmware_name").text(file.name).addClass("file-selected");
    }
}

function firmware_source() {
    return $("input[name=firmware_source]:checked").val();
}

// selected custom firmware file, null if none selected
function custom_firmware_file() {
    var input = document.getElementById("custom_firmware");
    return (input && input.files.length) ? input.files[0] : null;
}

// show either the standard firmware list or the custom firmware file input
function set_firmware_source_view() {
    if (firmware_source()=="custom") {
        $("#standard_firmware_bound").hide();
        // the radio format only selects between the listed standard firmwares,
        // a custom firmware has its radio format built in
        $("#radio_format_bound").hide();
        $("#custom_firmware_bound").show();
        $("#update-firmware").text(custom_firmware_button_label);
    } else {
        $("#custom_firmware_bound").hide();
        $("#standard_firmware_bound").show();
        $("#radio_format_bound").show();
        $("#update-firmware").text(standard_firmware_button_label);
        // clear any previously selected file so that switching back to custom
        // does not silently re-use an old selection
        $("#custom_firmware").val("");
        update_custom_firmware_name();
    }
    update_firmware_summary();
}

// The upload settings (baud rate, core, autoreset) are a property of the
// hardware rather than of the individual firmware, so any entry for the
// selected hardware will do. Custom firmware uploads use this rather than the
// standard firmware selection, which is filtered by radio format and can
// therefore be empty for a hardware type that is otherwise perfectly valid.
function upload_settings(hardware) {
    for (var firmware_key in firmware_available) {
        if (firmware_available[firmware_key].hardware==hardware) return firmware_available[firmware_key];
    }
    return undefined;
}

// describe exactly what the update firmware button will write and where
function update_firmware_summary() {
    var port = $("#select_serial_port").val();
    var hardware = $("#selected_hardware").val();

    if (hardware=="none") {
        $("#firmware_summary").html(select_hardware_message);
        return;
    }

    if (firmware_source()=="custom") {
        var firmware = upload_settings(hardware);
        if (firmware===undefined) {
            $("#firmware_summary").html(no_firmware_message);
            return;
        }
        var file = custom_firmware_file();
        if (file===null) {
            $("#firmware_summary").html(select_file_message);
        } else {
            var settings = firmware.baud+" baud, core "+firmware.core;
            if (firmware.autoreset) settings += ", autoreset "+firmware.autoreset;
            $("#firmware_summary").html(
                "<b>"+$("<div>").text(file.name).html()+"</b> ("+Math.round(file.size/1024)+" kB) &rarr; <b>"+hardware+"</b> on <b>"+port+"</b><br>"+
                "Upload settings for "+hardware+": "+settings
            );
        }
    } else {
        var firmware = firmware_available[$("#selected_firmware").val()];
        if (firmware===undefined) {
            $("#firmware_summary").html(no_firmware_message);
            return;
        }
        $("#firmware_summary").html(
            "<b>"+firmware.description+" v"+firmware.version+"</b> &rarr; <b>"+hardware+"</b> on <b>"+port+"</b>"
        );
    }
}

// upload a custom firmware file and flash it
function upload_custom_firmware() {
    var file = custom_firmware_file();
    if (file===null) {
        alert(select_file_message);
        return;
    }

    var ext = file.name.split('.').pop().toLowerCase();
    if (ext != "hex" && ext != "bin") {
        alert("Please select a .hex or .bin file");
        return;
    }

    // the upload settings are taken from the selected hardware type
    var hardware = $("#selected_hardware").val();
    var firmware = upload_settings(hardware);
    if (firmware===undefined) {
        alert(hardware=="none" ? select_hardware_message : no_firmware_message);
        return;
    }

    var port = $("#select_serial_port").val();
    if (!confirm("Write "+file.name+" to "+hardware+" on "+port+"?\n\nThis will overwrite the firmware currently on the board.")) return;

    var formData = new FormData();
    formData.append('port', port);
    formData.append('baud_rate', firmware.baud);
    formData.append('core', firmware.core);
    formData.append('autoreset', firmware.autoreset);
    formData.append('custom_firmware', file);

    refresh_updateLog("");
    firmware_request({
        url: path+"admin/update/firmware-upload",
        data: formData,
        cache: false,
        contentType: false,
        processData: false
    });
}

// flash one of the listed standard firmwares
function update_standard_firmware() {
    var firmware_key = $("#selected_firmware").val();
    var firmware = firmware_available[firmware_key];
    if (firmware===undefined) {
        alert($("#selected_hardware").val()=="none" ? select_hardware_message : no_firmware_message);
        return;
    }

    var port = $("#select_serial_port").val();
    if (!confirm("Write "+firmware.description+" v"+firmware.version+" to "+$("#selected_hardware").val()+" on "+port+"?\n\nThis will overwrite the firmware currently on the board.")) return;

    refresh_updateLog("");
    firmware_request({
        url: path+"admin/update/firmware",
        data: "serial_port="+port+"&firmware_key="+firmware_key
    });
}

// shared request handling for both firmware update routes
function firmware_request(options) {
    var $button = $("#update-firmware");
    var label = firmware_source()=="custom" ? custom_firmware_button_label : standard_firmware_button_label;
    $button.prop("disabled", true).text(flashing_message);

    $.ajax($.extend({
        type: "POST",
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
        },
        complete: function() {
            $button.prop("disabled", false).text(label);
        }
    }, options));
}

function draw_firmware_select_list() {
    refresh_updateLog("");
    var hardware = $("#selected_hardware").val();
    var radio_format = $("#selected_radio_format").val();

    if (hardware=="none") {
        $("#selected_firmware").html("<option value='none'>none</option>");
        update_firmware_summary();
        return;
    }

    var out = "";
    for (var firmware_key in firmware_available) {
        var firmware = firmware_available[firmware_key];
        if (firmware.hardware==hardware && firmware.radio_format==radio_format) {
            out += "<option value='"+firmware_key+"'>"+firmware.description+", "+firmware.radio_format+", v"+firmware.version+"</option>";
        }
    }
    if (out=="") out = "<option value='none'>none</option>";
    $("#selected_firmware").html(out);
    update_firmware_summary();
}


// single action button, flashes either the selected standard firmware
// or the uploaded custom firmware file depending on the selected source
$("#update-firmware").click(function() {
    if (firmware_source()=="custom") {
        upload_custom_firmware();
    } else {
        update_standard_firmware();
    }
});

update_firmware_summary();


// shrink log file viewers
$('[data-dismiss="log"]').click(function(event){
    event.preventDefault();
    $(this).parents('pre').first().addClass('small');
})
getUpdateLog();
function getUpdateLog() {
  $.ajax({ url: path+"admin/update/log", async: true, dataType: "text", success: function(result)
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
