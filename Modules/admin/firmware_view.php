<?php global $path; ?>
<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css?v=<?php echo $v ?>">
<style>
.content-container {
  max-width:1150px;
}

#emonhub-running-notice, #emonhub-stopped-notice {
  padding: 8px 8px 8px 14px;
  line-height:31px;
}

</style>

<h3>Serial Monitor</h3>

<div id="emonhub-running-notice" class="alert hide">
  <b>Note:</b> EmonHub is currently running and may conflict with serial monitor 
  <button id="stopEmonHub" class="btn" style="float:right">Stop EmonHub</button>
</div>

<div id="emonhub-stopped-notice" class="alert alert-success hide">
  <b>Note:</b> EmonHub is currently stopped and will not interfere with serial monitor
  <button id="startEmonHub" class="btn" style="float:right">Start EmonHub</button>
</div>

<div class="input-prepend input-append start-options hide">
  <button id="start" class="btn">Start</button>
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

<button id="stop" class="btn hide" style="margin-bottom:10px">Stop</button>

<div class="input-prepend input-append send-cmd">
  <span class="add-on">Send command</span>
  <input id="cmd" type="text" style="width:300px" />
  <button id="send" class="btn">Send</button>
</div>

<pre class="log" style="height:600px"><div id="log"></div></pre>

<script>

setInterval(update_log,250);

function update_log() {
    $.ajax({ 
        url: path+"admin/serialmonitor/log", 
        async: true, 
        dataType: "text", 
        success: function(result) {
            if (result=="Admin re-authentication required") {
                window.location = "/";
            }        
            if (result) {
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
                $(".start-options").show();
                $("#stop").hide();
                $(".send-cmd").hide();
                $("#log").html("Serialmonitor is not running, click start to start!");
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
            if (result==null) return false;
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
    $.ajax({ 
        url: path+"admin/service/"+action+"?name="+name,
        async: true, 
        dataType: "json", 
        success: function(result) {
        
        }
    });
}

is_emonhub_running();
setInterval(is_emonhub_running,2000);

is_running();
setInterval(is_running,2000);

$("#start").click(function() {
    var serialport = $("#serialport").val();
    var baudrate = $("#baudrate").val();
    
    $.ajax({ 
        type: "POST",
        url: path+"admin/serialmonitor/start", 
        data: "baudrate="+baudrate+"&serialport="+serialport,
        async: true, 
        dataType: "text", 
        success: function(result) {
            setTimeout(function(){
                is_running();
            },500);
            // alert(result);
            $("#log").html("");
        } 
    });
});

$("#stop").click(function() {
    $.ajax({ 
        url: path+"admin/serialmonitor/stop", 
        async: true, 
        dataType: "text", 
        success: function(result) {
            setTimeout(function(){
                is_running();
            },500);
            // alert(result);
            
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
    $.ajax({ 
        type: 'POST',
        url: path+"admin/serialmonitor/cmd",
        data: "cmd="+encodeURIComponent(cmd), 
        async: true, 
        dataType: "text", 
        success: function(result) {
            // alert(result);
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
