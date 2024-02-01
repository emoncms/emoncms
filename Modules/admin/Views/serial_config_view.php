<?php global $path; ?>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css?v=1">
<style>
#emonhub-running-notice, #emonhub-stopped-notice {
  padding: 8px 8px 8px 14px;
  line-height:31px;
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

  <h3 style="color:#333">EmonTx4 Serial Config Tool</h3>

  <div id="emonhub-running-notice" class="alert hide">
    <b><?php echo _('Note:'); ?></b> <?php echo _('EmonHub is currently running and may conflict with serial monitor'); ?>
    <button id="stopEmonHub" class="btn" style="float:right"><?php echo _('Stop EmonHub'); ?></button>
  </div>

  <div id="emonhub-stopped-notice" class="alert alert-success hide">
    <b><?php echo _('Note:'); ?></b> <?php echo _('EmonHub is currently stopped and will not interfere with serial monitor'); ?>
    <button id="startEmonHub" class="btn" style="float:right"><?php echo _('Start EmonHub'); ?></button>
  </div>

  <div class="input-prepend input-append">
    <span class="add-on">WiFi</span>
    <span class="add-on">SSID</span>
    <input type="text" v-model="emontx.ssid" style="width:150px" @change="set_ssid" :disabled="!connected" />
    <span class="add-on">PSK</span>
    <input type="text" v-model="emontx.psk" style="width:150px" @change="set_psk" :disabled="!connected" />
    <span class="add-on">APIKEY</span>
    <input type="text" v-model="emontx.apikey" style="width:150px" @change="set_apikey" :disabled="!connected" />
  </div>
  <br>
    
  <div class="input-prepend input-append">
    <span class="add-on">Radio</span>
    <span class="add-on">Enabled <input type="checkbox" v-model="emontx.radio_enabled" @change="set_radio" :disabled="!connected"></span>

    <span class="add-on">Node ID</span>
    <input type="text" v-model="emontx.nodeid" style="width:80px" @change="set_nodeid" :disabled="!connected" />
    <span class="add-on">Group</span>
    <input type="text" v-model="emontx.group" style="width:80px" @change="set_group" :disabled="!connected" />
  </div>

  <div class="input-prepend">
    <span class="add-on">Voltage calibration</span>
    <input type="text" v-model="emontx.vcal" style="width:80px" @change="set_vcal" :disabled="!connected" />
  </div>
    
  <div class="input-prepend">
    <span class="add-on">Firmware version</span>
    <select v-model="emontx.calibration_type" @change="set_calibration_type" :disabled="!connected">
      <option value="emonlibcm">emonLibCM</option>
      <option value="emonlibdb">emonLibDB</option>
    </select>
  </div>
    
  <table class="table">
  <tr>
    <th>Channel</th>
    <th>Enabled</th>
    <th>CT Type</th>
    <th>Phase Correction</th>
    <th>Value</th>
  </tr>
  <tr v-for="(channel,index) in emontx.channels">
    <td>CT {{ index+1 }}</td>
    <td><input type="checkbox" v-model="channel.enabled" :disabled="!connected"></td>
    <td>
      <select style="width:80px" v-model="channel.ical" @change="set_ical(index)" :disabled="!connected">
        <option v-for="rating in cts_available" v-bind:value="rating">{{ rating }}A</option>
      </select>
    </td>
    <td>
      <input type="text" v-model="channel.ilead" @change="set_ical(index)" style="width:80px" :disabled="!connected"/>
    </td>
    <td>
    {{ channel.value }}
    </td>
  </tr>
  </table>
  
  <button v-if="changes" class="btn btn-warning" :disabled="!changes" @click="save">Save changes</button>
  <br>
  
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
    cts_available: [200,100,50,25,20],
    emontx: {
      ssid:'',
      psk:'',
      apikey:'',
      nodeid: '',
      group: '',
      vcal: '',
      radio_enabled: 1,
      calibration_type: "emonlibdb",
      channels: [
        {enabled:1, ical:20, ilead: '', name:"P1", value:''},
        {enabled:1, ical:20, ilead: '', name:"P2", value:''},
        {enabled:1, ical:20, ilead: '', name:"P3", value:''},
        {enabled:1, ical:20, ilead: '', name:"P4", value:''},
        {enabled:1, ical:20, ilead: '', name:"P5", value:''},
        {enabled:1, ical:20, ilead: '', name:"P6", value:''}
      ]
    },
    connected:false,
    changes: false,
    input: ''
  },
  methods: {
    start: function() {
      $("#log").html("");
      $.ajax({ 
          type: "POST",
          url: path+"admin/serialmonitor/start", 
          data: "baudrate="+app.baudrate+"&serialport="+app.serialport,
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
    },
    stop: function() {
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
    },
    set_calibration_type: function() {
    
    },
    set_ical: function(i) {
      let ical = app.emontx.channels[i].ical / 0.333;
      if (app.emontx.calibration_type=="emonlibdb") {
        ical = app.emontx.channels[i].ical;
      }
      let ilead = app.emontx.channels[i].ilead*1;
      writeToStream("k"+(i+1)+" "+ical.toFixed(3)+" "+ilead.toFixed(3))
      app.changes = true;
    },
    set_vcal: function() {
      let vcal = app.emontx.vcal*1;
      writeToStream("k0 "+vcal.toFixed(3));
      app.changes = true;
    },
    set_nodeid: function() {
      writeToStream("n"+Math.round(app.emontx.nodeid));
      app.changes = true;
    },
    set_group: function() {
      writeToStream("g"+Math.round(app.emontx.group));
      app.changes = true;
    },
    set_radio: function() {
      writeToStream("w"+Math.round(app.emontx.radio_enabled));
      app.changes = true;
    },
    set_ssid: function() {
      writeToStream("essid:"+app.emontx.ssid);
    },
    set_psk: function() {
      writeToStream("epsk:"+app.emontx.psk);
    },
    set_apikey: function() {
      writeToStream("eapikey:"+app.emontx.apikey);
    },
    save: function() {
      writeToStream("s");
      app.changes = false;
    },
    send_cmd: function() {
      if (app.input!='') {
        writeToStream(app.input)
        if (app.input=='s') {
          app.changes = false;
        }
        app.input = '';
      }
    }
  }
});

function writeToStream(cmd) {
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

function process_line(line) {

  // Update values on page
  if (line.substring(0,3)=="MSG") {
    let pairs = line.split(",");
    for (var z in pairs) {
      let keyval = pairs[z].split(":");
      for (var c in app.emontx.channels) {
        if (keyval[0] == app.emontx.channels[c].name) {
          app.emontx.channels[c].value = keyval[1]+'W';
          break;
        }
      } 
    }
    
  // -------------------------------------------------------
    
  // CT calibration (format 1)
  } else if (line[0]=='i' && line.substring(2,5)=="Cal") {
    let p = line.split("=");
    let c = line[1].trim();
    let cal = p[1].trim();
    
    if (app.emontx.calibration_type=="emonlibcm") {
      app.emontx.channels[c-1].ical = Math.round(cal*0.333);
    } else {
      app.emontx.channels[c-1].ical = Math.round(cal);
    }
    
  // CT phase lead (format 1)
  } else if (line[0]=='i' && line.substring(2,6)=="Lead") {
    let p = line.split("=");
    let c = line[1].trim();
    let cal = p[1].trim();
    app.emontx.channels[c-1].ilead = cal;

  // -------------------------------------------------------

  // CT calibration (format 2)
  } else if (line.substring(0,4)=="iCal") {
    let l = line.replace("iCal","");
    let p = l.split("=");
    let c = p[0].trim();
    let cal = p[1].trim();
    
    if (app.emontx.calibration_type=="emonlibcm") {
      app.emontx.channels[c-1].ical = Math.round(cal*0.333);
    } else {
      app.emontx.channels[c-1].ical = Math.round(cal);
    }
    
  // CT phase lead (format 2)
  } else if (line.substring(0,5)=="iLead") {
    let l = line.replace("iLead","");
    let p = l.split("=");
    let c = p[0].trim();
    let cal = p[1].trim();
    app.emontx.channels[c-1].ilead = cal;

  // -------------------------------------------------------
    
  // Voltage calibration
  } else if (line.substring(0,4)=="vCal") {
    let p = line.split("=");
    app.emontx.vcal = p[1].trim();
    
  // Radio state
  } else if (line.substring(0,6)=="RF on") {
    app.emontx.radio_enabled = 1;
  } else if (line.substring(0,7)=="RF off") {
    app.emontx.radio_enabled = 0;

  } else if (line.substring(0,4)=="Band") {
    let p = line.replace(/\s+/g, '').split(",");
    app.emontx.group = p[1].replace("Group",'');
    app.emontx.nodeid = p[2].replace("Node",'');
  } else if (line.substring(0,12)=="emonTx V4 DB") {
    app.emontx.calibration_type = "emonlibdb";
  } else if (line.substring(0,12)=="emonTx V4 CM") {
    app.emontx.calibration_type = "emonlibcm";
  }

  /*
  var keyval = line.split(":");
  if (keyval.length==2) {
    if (keyval[0]=="ssid") {
      $("#ssid").val(keyval[1]);
    } else if (keyval[0]=="psk") {
      $("#psk").val(keyval[1]);
    } else if (keyval[0]=="apikey") {
      $("#apikey").val(keyval[1]);  
    }
  }

  if (flag) {

    console.log("reply rx",line);
    if (line==("Set:"+$("#ssid").val())) {
      writeToStream("psk:"+$("#psk").val())
    } else if (line==("Set:"+$("#psk").val())) {
      writeToStream("apikey:"+$("#apikey").val())
    } else if (line==("Set:"+$("#apikey").val())) {
      writeToStream("save");
      flag = false;
    } else {
      flag = false;
    }

  }*/

}

var updates_log_interval;
updates_log_interval = setInterval(update_log,1000);

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
                //console.log(result)
                //$("#log").append(htmlEntities(result));
                //buffer += result
                
                for (var i=0; i<result.length; i++) {
                    if (result[i]=='\n') {
                        var line = buffer.trim();
                        process_line(line);
                        buffer = "";
                        log.textContent += line+"\n";
                        log.scrollTop = log.scrollHeight;                 
                    } else {
                        buffer += result[i];
                    }
                }
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
                app.connected = true;
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

/*
$("#cmd").on('keyup', function (e) {
    if (e.key === 'Enter' || e.keyCode === 13) {
        send_cmd($("#cmd").val())
    }
});
*/

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
