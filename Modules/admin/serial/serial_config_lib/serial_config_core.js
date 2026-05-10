/**
 * serial_config_core.js
 *
 * Shared Vue data, methods, and utility functions for the Serial Config Tool.
 * Used by both:
 *   - The standalone web-serial version (serial_config_view.php / index.php)
 *   - The emoncms CMS version (Modules/admin/Views/serial_config_view.php)
 *
 * Each consumer must define globally before creating the Vue instance:
 *   - writeToStream(cmd)  — sends a command string to the serial device
 *
 * Each consumer must call populate_channels(n) after creating the Vue instance.
 */

// ---------------------------------------------------------------------------
// Shared Vue data
// ---------------------------------------------------------------------------
const serialConfigData = {
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
        vchannels: [
            {vcal: 100, active: true, vlead: 0},
            {vcal: 100, active: false, vlead: 0},
            {vcal: 100, active: false, vlead: 0}
        ],
        ichannels: []
    },
    connected: false,
    config_received: false,
    changes: false,
    input: ''
};

// ---------------------------------------------------------------------------
// Shared Vue methods
// ---------------------------------------------------------------------------
const serialConfigMethods = {
    set_ical: function(i) {
        let ical = app.device.ichannels[i].ical * 1;
        let ilead = app.device.ichannels[i].ilead * 1;
        let active = app.device.ichannels[i].active ? 1 : 0;
        if (app.device.hardware=='emonPi3') {
            writeToStream("k" + (i + 4) + " " + active + " " + ical.toFixed(3) + " " + ilead.toFixed(3) + " " + app.device.ichannels[i].vchan1 + " " + app.device.ichannels[i].vchan2);
        } else {
            writeToStream("k" + (i + 1) + " " + ical.toFixed(3) + " " + ilead.toFixed(3));
        }
        app.changes = true;
    },
    set_vcal: function() {
        let vcal = app.device.vcal * 1;
        writeToStream("k0 " + vcal.toFixed(3) + " 0");
        app.changes = true;
    },
    set_vchannel: function(i) {
        let vcal = app.device.vchannels[i].vcal * 1;
        let vlead = app.device.vchannels[i].vlead * 1;
        let active = app.device.vchannels[i].active ? 1 : 0;
        writeToStream("k" + (i + 1) + " " + active + " " + vcal.toFixed(3) + " " + vlead.toFixed(3));
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
    reset_to_defaults: function() {
        writeToStream("r");
        app.changes = true;
    },
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
            writeToStream(app.input);
            if (app.input == 's') {
                app.changes = false;
            }
            app.input = '';
        }
    }
};

// ---------------------------------------------------------------------------
// Utility: populate CT channel array on the Vue instance
// ---------------------------------------------------------------------------
function populate_channels(num_i_channels) {
    app.device.ichannels = [];
    for (var i = 0; i < num_i_channels; i++) {
        app.device.ichannels.push({
            active: true,
            ical: 20,
            ilead: '',
            vchan1: 1,
            vchan2: 1,
            name: "P" + (i + 1),
            power: '',
            energy: ''
        });
    }
}

// ---------------------------------------------------------------------------
// Utility: parse and handle a single line received from the device
// ---------------------------------------------------------------------------
function process_line(line) {

    // Try to parse as JSON first
    // e.g. {"P1":0.00,"P2":0.00,...,"E1":0.00,...}
    try {
        var json = JSON.parse(line);
        for (var c = 0; c < app.device.ichannels.length; c++) {
            var power_key = "P" + (c + 1);
            if (json[power_key] !== undefined) {
                app.device.ichannels[c].power = json[power_key] + 'W';
            }
            var energy_key = "E" + (c + 1);
            if (json[energy_key] !== undefined) {
                app.device.ichannels[c].energy = json[energy_key] + 'Wh';
            }
        }
        return;
    } catch (e) {
        // not valid JSON, continue
    }

    // Key:value pair data line (e.g. "MSG P1:0.00,P2:0.00,...")
    if (line.substring(0, 3) == "MSG") {
        let pairs = line.split(",");
        for (var z in pairs) {
            let keyval = pairs[z].split(":");
            for (var c = 0; c < app.device.ichannels.length; c++) {
                if (keyval[0] == "P" + (c + 1)) {
                    app.device.ichannels[c].power = keyval[1] + 'W';
                    break;
                }
                if (keyval[0] == "E" + (c + 1)) {
                    app.device.ichannels[c].energy = keyval[1] + 'Wh';
                    break;
                }
            }
        }
        return;
    }

    // firmware key — triggers new config format and channel population
    if (line.substring(0, 11) == "firmware = ") {
        app.device.firmware = line.split("=")[1].trim();
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

    // hardware key
    if (line.substring(0, 11) == "hardware = ") {
        app.device.hardware = line.split("=")[1].trim();
        if (app.device.hardware == "emonPi3") {
            app.new_config_format = true;
            app.device.emon_library = "integrated";
            app.device.firmware = "main";
            populate_channels(12);
        }
    }

    // Legacy firmware: "Settings:" line signals old format
    if (line.startsWith("Settings:")) {
        app.config_received = true;
        app.upgrade_required = true;
    }

    if (app.new_config_format) {
        // Each line may contain multiple comma-separated key=value pairs
        let sublines = line.split(",");
        for (var z in sublines) {
            let keyval = sublines[z].split("=");
            if (keyval.length == 2) {
                let key = keyval[0].trim();
                let val = keyval[1].trim();

                // CT calibration (all hardware)
                if (key.substring(0, 4) == "iCal") {
                    app.config_received = true;
                    let c = key.substring(4, 6).trim();
                    app.device.ichannels[c - 1].ical = Math.round(val);
                }

                // CT phase lead (all hardware)
                else if (key.substring(0, 5) == "iLead") {
                    let c = key.substring(5, 7).trim();
                    app.device.ichannels[c - 1].ilead = val * 1;
                }

                // CT active flag (emonPi3)
                else if (key.substring(0, 7) == "iActive") {
                    let c = key.substring(7, 9).trim();
                    app.device.ichannels[c - 1].active = (val == "1" || val == "on");
                }

                // Voltage channel assignment 1 (emonPi3)
                else if (key.substring(0, 6) == "v1Chan") {
                    let c = key.substring(6, 8).trim();
                    app.device.ichannels[c - 1].vchan1 = val * 1;
                }

                // Voltage channel assignment 2 (emonPi3)
                else if (key.substring(0, 6) == "v2Chan") {
                    let c = key.substring(6, 8).trim();
                    app.device.ichannels[c - 1].vchan2 = val * 1;
                }

                // Single voltage calibration (emonPi2, emonTx4, emonTx5)
                else if (key == "vCal") {
                    app.device.vcal = val;
                }

                // Per-channel voltage calibration (emonPi3)
                else if (key.substring(0, 4) == "vCal") {
                    let c = key.substring(4, 5).trim();
                    app.device.vchannels[c - 1].vcal = val * 1;
                }

                // Per-channel voltage phase correction (emonPi3)
                else if (key.substring(0, 5) == "vLead") {
                    let c = key.substring(5, 6).trim();
                    app.device.vchannels[c - 1].vlead = val * 1;
                }

                // Voltage channel active flag (emonPi3)
                else if (key.substring(0, 7) == "vActive") {
                    let c = key.substring(7, 8).trim();
                    app.device.vchannels[c - 1].active = (val == "1" || val == "on");
                }

                else if (key == "hardware") {
                    app.device.hardware = val;
                }
                else if (key == "version") {
                    app.device.firmware_version = val;
                }
                else if (key == "voltage") {
                    app.device.voltage = val;
                }

                // Radio
                else if (key == "RF") {
                    app.device.RF = (val == "on") ? 1 : 0;
                }
                else if (key == "rfNode") {
                    app.device.rfNode = val * 1;
                }
                else if (key == "rfGroup") {
                    app.device.rfGroup = val * 1;
                }
                else if (key == "rfBand") {
                    if (val.includes("433.92")) {
                        app.device.rfBand = 3;
                    }
                    else if (val.includes("433")) {
                        app.device.rfBand = 0;
                    }
                    else if (val.includes("868")) {
                        app.device.rfBand = 1;
                    }
                    else if (val.includes("915")) {
                        app.device.rfBand = 2;
                    }
                }
                else if (key == "rfPower") {
                    app.device.rfPower = val * 1;
                }
                else if (key == "rfFormat") {
                    app.device.rfFormat = val;
                }

                // Pulse
                else if (key == "pulse") {
                    app.device.pulse = (val == "off") ? 0 : 1;
                }
                else if (key == "pulsePeriod") {
                    // strip trailing "ms"
                    app.device.pulsePeriod = val.substring(0, val.length - 2) * 1;
                }

                // Datalog
                else if (key == "datalog") {
                    app.device.datalog = val;
                }
                else if (key == "json") {
                    app.device.json = (val == "off") ? 0 : 1;
                }
            }
        }
    }
}

// ---------------------------------------------------------------------------
// Utility: escape HTML for safe insertion into the log
// ---------------------------------------------------------------------------
function htmlEntities(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
