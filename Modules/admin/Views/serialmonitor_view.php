<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css?v=1">
<style>
#emonhub-running-notice, #emonhub-stopped-notice {
  padding: 8px 8px 8px 14px;
  line-height:31px;
}
</style>
<div class="admin-container">
<h3><?php echo _('Serial Monitor'); ?></h3>

<div id="emonhub-running-notice" class="alert hide">
  <b><?php echo _('Note:'); ?></b> <?php echo _('EmonHub is currently running and may conflict with serial monitor'); ?>
  <button id="stopEmonHub" class="btn" style="float:right"><?php echo _('Stop EmonHub'); ?></button>
</div>

<div id="emonhub-stopped-notice" class="alert alert-success hide">
  <b><?php echo _('Note:'); ?></b> <?php echo _('EmonHub is currently stopped and will not interfere with serial monitor'); ?>
  <button id="startEmonHub" class="btn" style="float:right"><?php echo _('Start EmonHub'); ?></button>
</div>

<div class="input-prepend input-append start-options hide">
  <button id="start" class="btn"><?php echo _('Start'); ?></button>
  <select id="serialport">
    <?php foreach ($serial_ports as $port) { ?>
    <option><?php echo $port; ?></option>
    <?php } ?>
  </select>  
  <select id="baudrate">
    <option>9600</option>
    <option selected>38400</option>
    <option>115200</option>
  </select>
</div>

<button id="stop" class="btn hide" style="margin-bottom:10px"><?php echo _('Stop'); ?></button>

<div class="input-prepend input-append send-cmd">
  <span class="add-on"><?php echo _('Send command'); ?></span>
  <input id="cmd" type="text" style="width:300px" />
  <button id="send" class="btn"><?php echo _('Send'); ?></button>
</div>

<pre id="logreply-bound" class="log" style="min-height:320px; height:calc(100vh - 320px); display:none;"><div id="log"></div></pre>

</div>

<script>

var updates_log_interval;
updates_log_interval = setInterval(update_log,1000);
$("#logreply-bound").slideDown();

function update_log() {
    $.ajax({ 
        url: path+"admin/serialmonitor/log", 
        async: true, 
        dataType: "text", 
        success: function(result) {
            var isjson = true;
            try {
                data = JSON.parse(result);
                if (data.reauth == true) { window.location = "/"; }
                if (data.success == false)  { 
                    clearInterval(updates_log_interval); 
                    $("#log").append("<text style='color:red;'>"+ data.message+"</text>\n");
                }
            } catch (e) {
                isjson = false;
            }
            if (isjson == false )     {
                console.log(result)
                $("#log").append(htmlEntities(result));
            }
        }
    });
}

function is_running() {
    $.ajax({ 
        url: path+"admin/serialmonitor/running", 
        async: true, 
        dataType: "text", 
        success: function(pid) {
            if (pid) {
                $("#stop").show();
                $(".start-options").hide();
                $(".send-cmd").show();
            } else {
                clearInterval(updates_is_running);
                $(".start-options").show();
                $("#stop").hide();
                $(".send-cmd").hide();
                $("#log").append("Serialmonitor service is not running, click start to start!\n");
            }
        }
    });
}

function is_emonhub_running() {
    $.ajax({ 
        url: path+"admin/service/status?name=emonhub",
        async: true, 
        dataType: "json", 
        success: function(result) {
            if (result.reauth == true) { window.location.reload(true); }
            if (result.ActiveState=="active") {
                $("#emonhub-running-notice").show();
                $("#emonhub-stopped-notice").hide();
            } else {
                $("#emonhub-running-notice").hide();
                $("#emonhub-stopped-notice").show();
            }
        }
    });
}

function setService(name,action) {
    $("#log").html("");
    $.ajax({ 
        url: path+"admin/service/"+action+"?name="+name,
        async: true, 
        dataType: "json", 
        success: function(result) {
            if (result.reauth == true) { window.location.reload(true); }
            if (result.success == false)  { 
                $("#log").append("<text style='color:red;'>" + result.message + "</text>\n");
            } else {
                $("#log").append(htmlEntities(result.message) + "\n");
            }
        }
    });
}

is_emonhub_running();
var updates_is_emonhub_running;
updates_is_emonhub_running = setInterval(is_emonhub_running,2000);

is_running();
var updates_is_running;
updates_is_running = setInterval(is_running,2000);

$("#start").click(function() {
    $("#log").html("");
    var serialport = $("#serialport").val();
    var baudrate = $("#baudrate").val();

    $.ajax({ 
        type: "POST",
        url: path+"admin/serialmonitor/start", 
        data: "baudrate="+baudrate+"&serialport="+serialport,
        async: true, 
        dataType: "json", 
        success: function(result) {
            if (result.reauth == true) { window.location.reload(true); }
            if (result.success == false)  { 
                $("#log").append("<text style='color:red;'>" + result.message + "</text>\n");
            } else {
                $("#log").append(htmlEntities(result.message) + "\n");
            }
            setTimeout(function(){
                is_running();
            },500);
        } 
    });
});

$("#stop").click(function() {
    $("#log").html("");
    $.ajax({ 
        url: path+"admin/serialmonitor/stop", 
        async: true, 
        dataType: "json", 
        success: function(result) {
            if (result.reauth == true) { window.location.reload(true); }
            if (result.success == false)  { 
                $("#log").append("<text style='color:red;'>" + result.message + "</text>\n");
            } else {
                $("#log").append(htmlEntities(result.message) + "\n");
            }
            setTimeout(function(){
                is_running();
            },500);
        } 
    });
});

$("#send").click(function() {
    send_cmd($("#cmd").val())
});

$("#cmd").on('keyup', function (e) {
    if (e.key === 'Enter' || e.keyCode === 13) {
        send_cmd($("#cmd").val())
    }
});

function send_cmd(cmd) {
    // $("#log").html("");
    $.ajax({ 
        type: 'POST',
        url: path+"admin/serialmonitor/cmd",
        data: "cmd="+encodeURIComponent(cmd), 
        async: true, 
        dataType: "json", 
        success: function(result) {
            if (result.reauth == true) { window.location.reload(true); }
            if (result.success == false)  { 
                // $("#log").append("<text style='color:red;'>" + result.message + "</text>\n");
            } else {
                // $("#log").append(htmlEntities(result.message) + "\n");
            }
        } 
    });
}

$("#stopEmonHub").click(function() {
    setService("emonhub","stop");
});

$("#startEmonHub").click(function() {
    setService("emonhub","start");
});

function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

</script>
