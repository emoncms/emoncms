<?php global $path; ?>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
<script src="<?php echo $path; ?>Modules/admin/Views/serial_config_lib/serial_config_core.js"></script>
<link rel="stylesheet" href="<?php echo $path ?>Modules/admin/static/admin_styles.css?v=1">
<style>
    #emonhub-running-notice,
    #emonhub-stopped-notice {
        padding: 8px 8px 8px 14px;
        line-height: 31px;
    }
</style>

<div id="app">

    <div class="input-prepend input-append start-options" style="float:right; margin-top:4px" v-if="!connected">
        <button class="btn btn-success" @click="start"><?php echo tr('Start'); ?></button>
        <select v-model="serialport">
            <?php foreach ($serial_ports as $port) { ?>
                <option><?php echo $port; ?></option>
            <?php } ?>
        </select>
        <select v-model="baudrate">
            <option>9600</option>
            <option selected>38400</option>
            <option>115200</option>
        </select>
    </div>

    <button class="btn btn-danger" style="float:right; margin-top:4px" @click="stop" v-if="connected"><?php echo tr('Stop Serial'); ?></button>

    <h3 style="color:#333">Serial Config Tool</h3>

    <div id="emonhub-running-notice" class="alert hide">
        <b><?php echo tr('Note:'); ?></b> <?php echo tr('EmonHub is currently running and may conflict with serial monitor'); ?>
        <button id="stopEmonHub" class="btn" style="float:right"><?php echo tr('Stop EmonHub'); ?></button>
    </div>

    <div id="emonhub-stopped-notice" class="alert alert-success hide">
        <b><?php echo tr('Note:'); ?></b> <?php echo tr('EmonHub is currently stopped and will not interfere with serial monitor'); ?>
        <button id="startEmonHub" class="btn" style="float:right"><?php echo tr('Start EmonHub'); ?></button>
    </div>

    <?php include __DIR__ . '/serial_config_lib/serial_config_template.php'; ?>

</div>

<pre id="log" class="log" style="padding:10px; height: 500px"></pre>

<script>
    const log = document.getElementById("log");
    var buffer = "";

    var app = new Vue({
        el: '#app',
        data: Object.assign({}, serialConfigData),
        methods: Object.assign({}, serialConfigMethods, {
            start: function() {
                $("#log").html("");
                $.ajax({
                    type: "POST",
                    url: path + "admin/serialmonitor/start",
                    data: "baudrate=" + app.baudrate + "&serialport=" + app.serialport,
                    async: true,
                    dataType: "json",
                    success: function(result) {
                        if (result.reauth == true) {
                            window.location.reload(true);
                        }
                        if (result.success == false) {
                            $("#log").append("<text style='color:red;'>" + result.message + "</text>\n");
                        } else {
                            $("#log").append(htmlEntities(result.message) + "\n");
                        }
                        setTimeout(function() {
                            is_running();
                        }, 500);
                    }
                });
            },
            stop: function() {
                $("#log").html("");
                $.ajax({
                    url: path + "admin/serialmonitor/stop",
                    async: true,
                    dataType: "json",
                    success: function(result) {
                        if (result.reauth == true) {
                            window.location.reload(true);
                        }
                        if (result.success == false) {
                            $("#log").append("<text style='color:red;'>" + result.message + "</text>\n");
                        } else {
                            $("#log").append(htmlEntities(result.message) + "\n");
                        }
                        setTimeout(function() {
                            is_running();
                        }, 500);
                    }
                });
            }
        })
    });

    populate_channels(6);

    function writeToStream(cmd) {
        $.ajax({
            type: 'POST',
            url: path + "admin/serialmonitor/cmd",
            data: "cmd=" + encodeURIComponent(cmd),
            async: true,
            dataType: "json",
            success: function(result) {
                if (result.reauth == true) {
                    window.location.reload(true);
                }
            }
        });
    }

    var updates_log_interval;
    updates_log_interval = setInterval(update_log, 1000);

    function update_log() {
        $.ajax({
            url: path + "admin/serialmonitor/log",
            async: true,
            dataType: "text",
            success: function(result) {
                try {
                    var data = JSON.parse(result);
                    if (data.reauth == true) {
                        window.location = "/";
                    }
                    if (data.success == false) {
                        clearInterval(updates_log_interval);
                        $("#log").append("<text style='color:red;'>" + data.message + "</text>\n");
                    }
                } catch (e) {
                    // not JSON, treat as raw serial text
                }

                for (var i = 0; i < result.length; i++) {
                    if (result[i] == '\n') {
                        var line = buffer.trim();
                        process_line(line);
                        buffer = "";
                        log.textContent += line + "\n";
                        log.scrollTop = log.scrollHeight;
                    } else {
                        buffer += result[i];
                    }
                }
            }
        });
    }

    function is_running() {
        $.ajax({
            url: path + "admin/serialmonitor/running",
            async: true,
            dataType: "text",
            success: function(pid) {
                if (pid) {
                    $("#stop").show();
                    $(".start-options").hide();
                    $(".send-cmd").show();
                    app.connected = true;

                    if (!app.config_received) {
                        wait_for_config_interval = setTimeout(function() {
                            if (!app.config_received) {
                                writeToStream("l");
                            }
                        }, 2000);
                    }
                } else {
                    clearInterval(updates_is_running);
                    $(".start-options").show();
                    $("#stop").hide();
                    $(".send-cmd").hide();
                    $("#log").append("Serialmonitor service is not running, click start to start!\n");
                    app.connected = false;
                }
            }
        });
    }

    function is_emonhub_running() {
        $.ajax({
            url: path + "admin/service/status?name=emonhub",
            async: true,
            dataType: "json",
            success: function(result) {
                if (result.reauth == true) {
                    window.location.reload(true);
                }
                if (result.ActiveState == "active") {
                    $("#emonhub-running-notice").show();
                    $("#emonhub-stopped-notice").hide();
                } else {
                    $("#emonhub-running-notice").hide();
                    $("#emonhub-stopped-notice").show();
                }
            }
        });
    }

    function setService(name, action) {
        $("#log").html("");
        $.ajax({
            url: path + "admin/service/" + action + "?name=" + name,
            async: true,
            dataType: "json",
            success: function(result) {
                if (result.reauth == true) {
                    window.location.reload(true);
                }
                if (result.success == false) {
                    $("#log").append("<text style='color:red;'>" + result.message + "</text>\n");
                } else {
                    $("#log").append(htmlEntities(result.message) + "\n");
                }
            }
        });
    }

    is_emonhub_running();
    var updates_is_emonhub_running;
    updates_is_emonhub_running = setInterval(is_emonhub_running, 2000);

    var wait_for_config_interval;
    is_running();
    var updates_is_running;
    updates_is_running = setInterval(is_running, 2000);

    $("#stopEmonHub").click(function() {
        setService("emonhub", "stop");
    });

    $("#startEmonHub").click(function() {
        setService("emonhub", "start");
    });
</script>
