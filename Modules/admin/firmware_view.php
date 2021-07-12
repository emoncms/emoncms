<?php global $path; ?>
<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css?v=<?php echo $v ?>">
<style>
.content-container {
  max-width:1150px;
}

</style>
<h3>Firmware</h3>

<h4>Serial Monitor</h4>
<div class="input-prepend input-append">
  <button id="start" class="btn hide">Start</button>
  <button id="stop" class="btn hide">Stop</button>
</div>
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
            if (result) {
                console.log(result)
                $("#log").append(result);
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
                $("#start").hide();
                $(".send-cmd").show();
            } else {
                $("#start").show();
                $("#stop").hide();
                $(".send-cmd").hide();
                $("#log").html("Serialmonitor is not running, click start to start!");
            }
        }
    });
}

is_running();
setInterval(is_running,2000);

$("#start").click(function() {
    $.ajax({ 
        url: path+"admin/serialmonitor/start", 
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
    var cmd = $("#cmd").val();
    $.ajax({ 
        url: path+"admin/serialmonitor/cmd",
        data: "cmd="+cmd, 
        async: true, 
        dataType: "text", 
        success: function(result) {
            // alert(result);
        } 
    });
});



</script>
