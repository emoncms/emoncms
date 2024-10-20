<?php global $path; ?>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
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
        <button class="btn btn-success" @click="start"><?php echo _('Start'); ?></button>
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

    <button class="btn btn-danger" style="float:right; margin-top:4px" @click="stop" v-if="connected"><?php echo _('Stop Serial'); ?></button>

    <h3 style="color:#333">Serial Config Tool</h3>

    <div id="emonhub-running-notice" class="alert hide">
        <b><?php echo _('Note:'); ?></b> <?php echo _('EmonHub is currently running and may conflict with serial monitor'); ?>
        <button id="stopEmonHub" class="btn" style="float:right"><?php echo _('Stop EmonHub'); ?></button>
    </div>

    <div id="emonhub-stopped-notice" class="alert alert-success hide">
        <b><?php echo _('Note:'); ?></b> <?php echo _('EmonHub is currently stopped and will not interfere with serial monitor'); ?>
        <button id="startEmonHub" class="btn" style="float:right"><?php echo _('Start EmonHub'); ?></button>
    </div>

    <div v-if="new_config_format">

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Hardware</th>
                    <th>Firmware</th>
                    <th>Version</th>
                    <th>Voltage</th>
                    <th>Emon Library</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ device.hardware }}</td>
                    <td>{{ device.firmware }}</td>
                    <td>{{ device.firmware_version }}</td>
                    <td>{{ device.voltage }}</td>
                    <td>{{ device.emon_library }}</td>
                </tr>
            </tbody>
        </table>



        <div class="input-prepend input-append">
            <span class="add-on">Voltage calibration</span>
            <input type="text" v-model="device.vcal" style="width:60px" @change="set_vcal" :disabled="!connected" />
            <span class="add-on">%</span>
        </div>

        <table class="table">
            <tr>
                <th>Channel</th>
                <th>CT Type</th>
                <th>Phase Correction</th>
                <th>Power</th>
                <th>Energy</th>
            </tr>
            <tr v-for="(channel,index) in device.channels">
                <td>CT {{ index+1 }}</td>
                <td>
                    <select style="width:80px" v-model="channel.ical" @change="set_ical(index)" :disabled="!connected">
                        <option v-for="rating in cts_available" v-bind:value="rating">{{ rating }}A</option>
                    </select>
                </td>
                <td><input type="text" v-model="channel.ilead" @change="set_ical(index)" style="width:80px" :disabled="!connected" /></td>
                <td>{{ channel.power }}</td>
                <td>{{ channel.energy }}</td>
            </tr>
        </table>


        <div class="input-prepend input-append" v-if="device.hardware!='emonPi2'">
            <span class="add-on">Radio enabled</span>
            <span class="add-on"><input type="checkbox" style="margin-top:2px" v-model="device.RF" @change="set_radio" :disabled="!connected"></span>
        </div><br>

        <table class="table table-bordered">
            <tr v-if="device.hardware!='emonPi2' && device.RF">
                <th>Node ID</th>
                <th>Group</th>
                <th>Frequency</th>
                <th>Format</th>
            </tr>
            <tr v-if="device.hardware!='emonPi2' && device.RF">
                <td><input type="text" v-model="device.rfNode" style="width:80px; margin:0" @change="set_rfNode" :disabled="!connected" /></td>
                <td><input type="text" v-model="device.rfGroup" style="width:80px; margin:0" @change="set_rfGroup" :disabled="!connected" /></td>
                <td><select style="width:100px; margin:0" v-model="device.rfBand" @change="set_rfBand" :disabled="!connected">
                        <option>433 MHz</option>
                        <option>868 Mhz</option>
                        <option>915 MHz</option>
                    </select></td>
                <td>{{ device.rfFormat }}</td>
            </tr>

            <tr>
                <th>Pulse enabled</th>
                <th>Pulse period</th>
                <th>Datalog</th>
                <th>Serial format</th>
            </tr>
            <tr>
                <td><input type="checkbox" v-model="device.pulse" style="width:80px" :disabled="!connected" @change="set_pulse" /></td>
                <td><input type="text" v-model="device.pulsePeriod" style="width:80px" :disabled="!connected" @change="set_pulsePeriod" /></td>
                <td><input type="text" v-model="device.datalog" style="width:80px" :disabled="!connected" @change="set_datalog" /></td>
                <td><select v-model="device.json" :disabled="!connected" @change="set_json">
                        <option value=0>Simple key:value pairs</option>
                        <option value=1>Full JSON</option>
                    </select></td>
            </tr>
        </table>

        <!-- reset to default values -->
        <button class="btn btn-primary" @click="reset_to_defaults" :disabled="!connected" style="float:right; margin-left:10px">Reset to default values</button>
        <!-- zero energy values -->
        <button class="btn btn-info" @click="zero_energy_values" :disabled="!connected" style="float:right">Zero energy values</button>
        <button v-if="changes" class="btn btn-warning" :disabled="!changes" @click="save">Save changes</button>



        <br><br>
    </div>

    <div v-if="!config_received" class="alert alert-info">Waiting for configuration from device...</div>


    <div class="alert alert-danger" v-if="upgrade_required"><b>Firmware update required:</b> Looks like you are running an older firmware version on this device, please upgrade the device firmware to use this tool.<br><br>Alternatively, enter commands manually to configure, send command ? to list configuration commands and options.</div>

    <div class="input-prepend input-append">
        <span class="add-on"><b>Console</b></span>
        <input v-model="input" type="text" :disabled="!connected" />
        <button class="btn" @click="send_cmd" :disabled="!connected">Send</button>
    </div>
</div>

<pre id="log" class="log" style="padding:10px"></pre>

<script>
    const log = document.getElementById("log")
    var buffer = "";
    var flag = false;

    var app = new Vue({
        el: '#app',
        data: {
            serialport: "ttyUSB0",
            baudrate: 115200,
            button_connect_text: "Connect",
            cts_available: [200, 100, 50, 25, 20],
            new_config_format: false,
            upgrade_required: false,
            device: {
                ssid: '',
                psk: '',
                apikey: '',
                // Firmware & hardware
                firmware: '',
                firmware_version: '',
                hardware: '',
                voltage: '',

                // Radio settings
                RF: 1,
                rfNode: '',
                rfGroup: '',
                rfBand: '',
                rfPower: '',
                rfFormat: '',

                // Pulse
                pulse: '',
                pulsePeriod: '',

                // Datalog
                datalog: '',
                json: '',

                // Calibration
                emon_library: "emonLibDB",
                vcal: '',
                channels: [
                    /*
                    {
                    	ical: 20,
                    	ilead: '',
                    	name: "P1",
                    	power: '',
                    	energy: ''
                    },
                    ...
                    */
                ]
            },
            connected: false,
            config_received: false,
            changes: false,
            input: ''
        },
        methods: {
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
            },
            set_ical: function(i) {
                let ical = app.device.channels[i].ical * 1;
                let ilead = app.device.channels[i].ilead * 1;
                writeToStream("k" + (i + 1) + " " + ical.toFixed(3) + " " + ilead.toFixed(3))
                app.changes = true;
            },
            set_vcal: function() {
                let vcal = app.device.vcal * 1;
                writeToStream("k0 " + vcal.toFixed(3) + " 0");
                app.changes = true;
            },
            set_radio: function() {
                writeToStream("w" + Math.round(app.device.RF));
                app.changes = true;
            },
            set_rfNode: function() {
                writeToStream("n" + Math.round(app.device.rfNode));
                app.changes = true;
            },
            set_rfGroup: function() {
                writeToStream("g" + Math.round(app.device.rfGroup));
                app.changes = true;
            },
            set_rfBand: function() {
                writeToStream("b" + Math.round(app.device.rfBand[0]));
                app.changes = true;
            },

            set_pulse: function() {
                if (app.device.pulse == 0) {
                    writeToStream("m0");
                } else {
                    writeToStream("m1 " + Math.round(app.device.pulsePeriod));
                }
                app.changes = true;
            },
            set_pulsePeriod: function() {
                writeToStream("m1 " + Math.round(app.device.pulsePeriod));
                app.changes = true;
            },

            set_datalog: function() {
                writeToStream("d" + parseFloat(app.device.datalog));
                app.changes = true;
            },

            set_json: function() {
                writeToStream("j" + app.device.json);
                app.changes = true;
            },

            // Reset to default values
            reset_to_defaults: function() {
                writeToStream("r");
                app.changes = true;
            },
            // Zero energy values
            zero_energy_values: function() {
                writeToStream("z");
                app.changes = true;
            },

            save: function() {
                writeToStream("s");
                app.changes = false;
            },
            send_cmd: function() {
                if (app.input != '') {
                    writeToStream(app.input)
                    if (app.input == 's') {
                        app.changes = false;
                    }
                    app.input = '';
                }
            }
        }
    });

    function writeToStream(cmd) {
        console.log("writeToStream", cmd);
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
                if (result.success == false) {
                    // $("#log").append("<text style='color:red;'>" + result.message + "</text>\n");
                } else {
                    // $("#log").append(htmlEntities(result.message) + "\n");
                }
            }
        });
    }

    function process_line(line) {

        // Is the line valid JSON?
        // {"MSG":"P1:0.00,P2:0.00,P3:0.00,P4:0.00,P5:0.00,P6:0.00,E1:0.00,E2:0.00,E3:0.00,E4:0.00,E5:0.00,E6:0.00"}
        // decode data line 
        //if (line.substring(0, 1) == "{") {
        try {
            var json = JSON.parse(line);

            for (var c = 0; c < app.device.channels.length; c++) {
                var power_key = "P" + (c + 1);
                if (json[power_key] !== undefined) {
                    app.device.channels[c].power = json[power_key] + 'W';
                }
                var energy_key = "E" + (c + 1);
                if (json[energy_key] !== undefined) {
                    app.device.channels[c].energy = json[energy_key] + 'Wh';
                }
            }
            return;
        } catch (e) {
            // not valid JSON
        }
        //}

        // else process as key value pairs
        // Update values on page
        if (line.substring(0, 3) == "MSG") {
            let pairs = line.split(",");
            for (var z in pairs) {
                let keyval = pairs[z].split(":");
                for (var c = 0; c < app.device.channels.length; c++) {
                    if (keyval[0] == "P" + (c + 1)) {
                        app.device.channels[c].power = keyval[1] + 'W';
                        break;
                    }
                    if (keyval[0] == "E" + (c + 1)) {
                        app.device.channels[c].energy = keyval[1] + 'Wh';
                        break;
                    }
                }
            }
            return;
        }

        // firmware key
        if (line.substring(0, 11) == "firmware = ") {
            app.device.firmware = line.split("=")[1].trim();
            console.log("firmware:", app.device.firmware);
            app.new_config_format = true;

            if (app.device.firmware == "emon_DB_6CT") {
                app.device.emon_library = "emonLibDB";
                populate_channels(6);
            } else if (app.device.firmware == "emon_DB_12CT") {
                app.device.emon_library = "emonLibDB";
                populate_channels(12);
            } else if (app.device.firmware == "emon_CM_6CT_temperature") {
                app.device.emon_library = "emonLibCM";
                populate_channels(6);
            } else if (app.device.firmware == "emon_CM_6CT") {
                app.device.emon_library = "emonLibCM";
                populate_channels(6);
            }
            return;
        }

        if (line.startsWith("Settings:")) {
            app.config_received = true;
            app.upgrade_required = true;
        }

        if (app.new_config_format) {
            // split by comma into sublines
            let sublines = line.split(",");
            // for each subline
            for (var z in sublines) {
                // each subline must have a key = value pair
                let keyval = sublines[z].split("=");
                if (keyval.length == 2) {
                    let key = keyval[0].trim();
                    let val = keyval[1].trim();
                    console.log(key + ": " + val);

                    // CT calibration
                    if (key.substring(0, 4) == "iCal") {
                        // Use iCal as flag to indicate that config has been received
                        app.config_received = true;

                        // channel could be double digit
                        let c = key.substring(4, 6).trim();
                        app.device.channels[c - 1].ical = Math.round(val);
                    }

                    // CT phase lead
                    else if (key.substring(0, 5) == "iLead") {
                        let c = key.substring(5, 7).trim();
                        app.device.channels[c - 1].ilead = val * 1;
                    }

                    // Voltage calibration
                    else if (key == "vCal") {
                        app.device.vcal = val;
                    }

                    // hardware
                    else if (key == "hardware") {
                        app.device.hardware = val;
                    }

                    // version
                    else if (key == "version") {
                        app.device.firmware_version = val;
                    }

                    // voltage
                    else if (key == "voltage") {
                        app.device.voltage = val;
                    }

                    // Radio settings
                    // radio state
                    else if (key == "RF") {
                        if (val == "on") {
                            app.device.RF = 1;
                        } else {
                            app.device.RF = 0;
                        }
                    }

                    // rfNode
                    else if (key == "rfNode") {
                        app.device.rfNode = val * 1;
                    }

                    // rfGroup
                    else if (key == "rfGroup") {
                        app.device.rfGroup = val * 1;
                    }

                    // rfBand
                    else if (key == "rfBand") {
                        app.device.rfBand = val;
                    }

                    // rfPower
                    else if (key == "rfPower") {
                        app.device.rfPower = val * 1
                    }

                    // rfFormat
                    else if (key == "rfFormat") {
                        app.device.rfFormat = val;
                    }

                    // Pulse
                    else if (key == "pulse") {
                        if (val == "off") {
                            app.device.pulse = 0;
                        } else {
                            app.device.pulse = 1;
                        }
                    } else if (key == "pulsePeriod") {
                        // strip ms
                        val = val.substring(0, val.length - 2);
                        app.device.pulsePeriod = val * 1;
                    }

                    // Datalog
                    else if (key == "datalog") {
                        app.device.datalog = val;
                    } else if (key == "json") {
                        if (val == "off") {
                            app.device.json = 0;
                        } else {
                            app.device.json = 1;
                        }
                    }
                }
            }
        }
    }

    populate_channels(6);

    function populate_channels(num_i_channels) {
        app.device.channels = [];
        // Populate 6 CT channels
        for (var i = 0; i < num_i_channels; i++) {
            app.device.channels.push({
                ical: 20,
                ilead: '',
                name: "P" + (i + 1),
                power: '',
                energy: ''
            });
        }
    }


    var updates_log_interval;
    updates_log_interval = setInterval(update_log, 1000);

    function update_log() {
        $.ajax({
            url: path + "admin/serialmonitor/log",
            async: true,
            dataType: "text",
            success: function(result) {
                var isjson = true;
                try {
                    data = JSON.parse(result);
                    if (data.reauth == true) {
                        window.location = "/";
                    }
                    if (data.success == false) {
                        clearInterval(updates_log_interval);
                        $("#log").append("<text style='color:red;'>" + data.message + "</text>\n");
                    }
                } catch (e) {
                    isjson = false;
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

                    // If config has not been received, wait 2s and if it still has not been received send 'l' to request config
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

    /*
    $("#cmd").on('keyup', function (e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            send_cmd($("#cmd").val())
        }
    });
    */

    $("#stopEmonHub").click(function() {
        setService("emonhub", "stop");
    });

    $("#startEmonHub").click(function() {
        setService("emonhub", "start");
    });

    function htmlEntities(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
</script>
