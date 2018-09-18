<?php
	global $path;
?>

<script src="<?php echo $path; ?>Modules/device/Views/device.js"></script>
<script src="<?php echo $path; ?>Modules/input/Views/input.js"></script>
<script src="<?php echo $path; ?>Modules/feed/feed.js"></script>
<script src="<?php echo $path; ?>Lib/responsive-linked-tables.js"></script>

<style>

.container-fluid { padding: 0px 10px 0px 10px; }

#footer {
    margin-left: 0px;
    margin-right: 0px;
}

.navbar-fixed-top {
    margin-left: 0px;
    margin-right: 0px;
}


input[type="checkbox"] { margin:0px; }
.controls { margin-bottom:10px; }
#inputs-to-delete { font-style:italic; }

#auth-check {
    padding:10px;
    background-color:#dc9696;
    margin-bottom:10px;
    font-weight:bold;
    border: 1px solid #de6464;
    color:#fff;
}

.auth-check-btn {
    float:right;
    margin-top:-2px;
}

#noprocesses .alert{margin:0;border-bottom-color:#fcf8e3;border-radius: 4px 4px 0 0;padding-right:14px}

@media (min-width: 768px) {
    .container-fluid { padding: 0px 20px 0px 20px; }
}

@media (max-width: 768px) {
    body {padding:0};
}

</style>

<div>
	<div id="apihelphead" style="float:right;"><a href="api"><?php echo _('Input API Help'); ?></a></div>
	<div id="localheading"><h3><?php echo _('Inputs'); ?></h3></div>

<div class="controls" data-spy="affix" data-offset-top="100">
    <button id="expand-collapse-all" class="btn" title="<?php echo _('Collapse') ?>" data-alt-title="<?php echo _('Expand') ?>"><i class="icon icon-resize-small"></i></button>
    <button id="select-all" class="btn" title="<?php echo _('Select all') ?>" data-alt-title="<?php echo _('Unselect all') ?>"><i class="icon icon-check"></i></button>
	<button class="btn input-delete hide" title="Delete"><i class="icon-trash" ></i></button>
</div>	
	
	<div id="auth-check" class="hide">
	    <i class="icon-exclamation-sign icon-white"></i> Device on ip address: <span id="auth-check-ip"></span> would like to connect 
	    <button class="btn btn-small auth-check-btn auth-check-allow">Allow</button>
    </div>
    
	<div id="noprocesses"></div>
	<div id="table" class="input-list"></div>
	
	<div id="output"></div>

	<div id="noinputs" class="alert alert-block hide">
        <h4 class="alert-heading"><?php echo _('No inputs created'); ?></h4>
        <p><?php echo _('Inputs are the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
	</div>
	
	<div id="input-loader" class="ajax-loader"></div>
</div>

<?php require "Modules/device/Views/device_dialog.php"; ?>
<?php require "Modules/input/Views/input_dialog.php"; ?>
<?php require "Modules/process/Views/process_ui.php"; ?>

<script>

var path = "<?php echo $path; ?>";

var devices = {};
var inputs = {};
var nodes = {};
var local_cache_key = 'input_nodes_display';
var nodes_display = docCookies.hasItem(local_cache_key) ? JSON.parse(docCookies.getItem(local_cache_key)) : {};
var selected_inputs = {};
var selected_device = false;

var device_templates = {};
$.ajax({ url: path+"device/template/listshort.json", dataType: 'json', async: true, success: function(data) { 
    device_templates = data; 
    update();
}});

var updater;
function updaterStart(func, interval){
	  clearInterval(updater);
	  updater = null;
	  if (interval > 0) updater = setInterval(func, interval);
}
updaterStart(update, 5000);

// ---------------------------------------------------------------------------------------------
// Fetch device and input lists
// ---------------------------------------------------------------------------------------------
var firstLoad = true
function update(){

    // Join and include device data
    $.ajax({ url: path+"device/list.json", dataType: 'json', async: true, success: function(data) {
        
        // Associative array of devices by nodeid
        devices = {};
        for (var z in data) devices[data[z].nodeid] = data[z];
        
        var requestTime = (new Date()).getTime();
        $.ajax({ url: path+"input/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
            table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
	          
	          // Associative array of inputs by id
            inputs = {};
	          for (var z in data) inputs[data[z].id] = data[z];
	          
	          // Assign inputs to devices
	          for (var z in inputs) {
	              // Device does not exist which means this is likely a new system or that the device was deleted
	              // There needs to be a corresponding device for every node and so the system needs to recreate the device here
	              if (devices[inputs[z].nodeid]==undefined) {
	                  devices[inputs[z].nodeid] = {description:""};
	                  // Device creation
	                  $.ajax({ url: path+"device/create.json?nodeid="+inputs[z].nodeid, dataType: 'json', async: false, success: function(deviceid) {
	                      if (!deviceid) {
	                          alert("There was an error creating device: nodeid="+inputs[z].nodeid+" deviceid="+deviceid); 
	                      } else {
	                          $.ajax({ url: path+"device/get.json?id="+deviceid, dataType: 'json', async: false, success: function(result) {
	                              devices[inputs[z].nodeid] = result;
	                          }});
	                      }
	                  }});
	              }
                  if (nodes_display[inputs[z].nodeid]==undefined) nodes_display[inputs[z].nodeid] = true;
                  // expand if only one feed available
	              if (devices[inputs[z].nodeid].inputs==undefined) devices[inputs[z].nodeid].inputs = [];
                  // expand if only one feed available or state locally cached in cookie
                  if (firstLoad && Object.keys(devices).length > 1 && Object.keys(nodes_display).length == 0) {
                      nodes_display[inputs[z].nodeid] = false
                  }
	              devices[inputs[z].nodeid].inputs.push(inputs[z]);
              }
              // cache state in cookie
              if(firstLoad) docCookies.setItem(local_cache_key, JSON.stringify(nodes_display))
              firstLoad = false;
              draw_devices();
              noProcessNotification(devices);
        }});
    }});
}
/** show a message to the user if no processes have been added */
function noProcessNotification(devices){
    let processList = [],  message = ''
    
    for (d in devices) {
        for (i in devices[d].inputs) {
            if(devices[d].inputs[i].processList.length>0) {
                processList.push(devices[d].inputs[i].processList)
            }
        }
    }
    if(processList.length<1 && Object.keys(devices).length > 0){
        message = '<div class="alert pull-right">%s</div>'.replace('%s',"<?php echo _("Configure your devices here") ?>")
    }
    $('#noprocesses').html(message)
}

// ---------------------------------------------------------------------------------------------
// Draw devices
// ---------------------------------------------------------------------------------------------
function draw_devices()
{
    // Draw node/input list
    var out = "";
    var counter = 0
    isCollapsed = !(Object.keys(devices).length > 1)

    for (var node in devices) {
        counter++
        isCollapsed = !nodes_display[node]
        out += "<div class='node accordion line-height-expanded'>";
        out += '   <div class="node-info accordion-toggle thead'+(isCollapsed ? ' collapsed' : '')+'" data-node="'+node+'" data-toggle="collapse" data-target="#collapse'+counter+'">'
        out += "     <div class='select text-center has-indicator' data-col='B' data-marker='✔'></div>";
        out += "     <h5 class='name' data-col='A'>"+node+":</h5>";
        out += "     <div class='processlist' data-col='F' data-col-width='auto'>"+devices[node].description+"</div>";
        out += "     <div class='pull-right'>"
        out += "        <div class='device-schedule text-center hidden' data-col='E' data-col-width='50'><i class='icon-time icon-white'></i></div>";
        out += "        <div class='device-key text-center' data-col='D' data-col-width='50'><i class='icon-lock icon-white'></i></div>"; 
        out += "        <div class='device-configure text-center' data-col='C' data-col-width='50'><i class='icon-wrench icon-white'></i></div>";
        out += "     </div>";
        out += "  </div>";

        out += "  <div id='collapse"+counter+"' class='node-inputs collapse tbody "+( !isCollapsed ? 'in':'' )+"' data-node='"+node+"'>";
        for (var i in devices[node].inputs) {
            var input = devices[node].inputs[i];
            var selected = selected_inputs[input.id] ? 'checked': ''
            var processlistHtml = processlist_ui ? processlist_ui.drawpreview(input.processList) : ''
            out += "<div class='node-input' id="+input.id+">";
            out += "  <div class='select text-center' data-col='B'>"
            out += "   <input class='input-select' type='checkbox' id='"+input.id+"' "+selected+" />"
            out += "  </div>";
            out += "  <div class='name' data-col='A'>"+input.name+"</div>";
            out += "  <div class='processlist' data-col='F'><div class='label-container line-height-normal'>"+processlistHtml+"</div></div>";
            out += "  <div class='pull-right'>";
            out += "    <div class='time text-center' data-col='C'>"+list_format_updated(input.time)+"</div>";
            out += "    <div class='value text-center' data-col='D'>"+list_format_value(input.value)+"</div>";
            out += "    <div class='configure text-center cursor-pointer' data-col='E' id='"+input.id+"'><i class='icon-wrench'></i></div>";
            out += "  </div>";
            out += "</div>";
        }
        
        out += "</div>";
        out += "</div>";
    }
    $("#table").html(out);

    $('#input-loader').hide();
    if (out=="") {
        $("#noinputs").show();
        $("#apihelphead").hide();
    } else {
        $("#noinputs").hide();
        $("#apihelphead").show();
    }

    for (var node in devices) {
        indicator = $(".node-info[data-node='"+node+"'] .device-schedule")
        if (device_templates[devices[node].type]!=undefined && !device_templates[devices[node].type].hasOwnProperty('control')) {
            indicator.removeClass('hidden')
        }
    }
    if(typeof $.fn.collapse == 'function'){
        $("#table .collapse").collapse({toggle: false})
        setExpandButtonState($('#table .collapsed').length == 0)
    }
    autowidth($('#table')) // set each column group to the same width
}
// ---------------------------------------------------------------------------------------------

$('#wrap').on("device-delete",function() { update(); });
$('#wrap').on("device-init",function() { update(); });

$("#table").on("click select",".input-select",function(e) {
    input_selection();
});
  
function input_selection() 
{
    selected_inputs = {};
    var num_selected = 0;
    $(".input-select").each(function(){
        var id = $(this).attr("id");
        selected_inputs[id] = $(this)[0].checked;
        if (selected_inputs[id]==true) num_selected += 1;
    });

    if (num_selected>0) {
        $(".input-delete").show();
    } else {
        $(".input-delete").hide();
    }

    if (num_selected==1) {
        // $(".feed-edit").show();	  
    } else {
        // $(".feed-edit").hide();
    }
}

// column title buttons ---

$("#table").on("click",".device-key",function(e) {
    e.stopPropagation();
    var node = $(this).parents('.node-info').first().data("node");
    $this = $(this)
    if(!$this.data('original')) $this.data('original',$this.html())
    if(!$this.data('originalWidth')) $this.data('originalWidth',$this.width())
    $this.data('state', !$this.data('state')||false)
    let width = 315
    if($this.data('state')){
        $this.html(devices[node].devicekey)
        $this.css({position:'absolute'}).animate({marginLeft:-Math.abs(width-$(this).width()), width:width}) // value will be of fixed size
    }else{
        $this.html($this.data('original'))
        $this.animate({marginLeft:0, width:$this.data('originalWidth')},'fast') // reset to original width
    }
});

$("#table").on("click",".device-schedule",function(e) {
    e.stopPropagation();
    var node = $(this).parents('.node-info').first().data("node");
    window.location = path+"demandshaper?node="+node;
    
});

$("#table").on("click",".device-configure",function(e) {
    e.stopPropagation();
    // Get device of clicked node
    node = $(this).parents('.node-info').first().data("node");
    var device = devices[node];
	device_dialog.loadConfig(device_templates, device);
});


// selection buttons ---

$(".input-delete").click(function(){
	  $('#inputDeleteModal').modal('show');
	  var out = "";
	  var ids = [];
	  for (var inputid in selected_inputs) {
		    if (selected_inputs[inputid]==true) {
			      var i = inputs[inputid];
			      if (i.processList == "" && i.description == "" && (parseInt(i.time) + (60*15)) < ((new Date).getTime() / 1000)){
				        // delete now if has no values and updated +15m
				        // ids.push(parseInt(inputid)); 
				        out += i.nodeid+":"+i.name+"<br>";
			      } else {
				        out += i.nodeid+":"+i.name+"<br>";		
			      }
		    }
	  }
	  
	  input.delete_multiple(ids);
	  update();
	  $("#inputs-to-delete").html(out);
});
  
$("#inputDelete-confirm").off('click').on('click', function(){
    var ids = [];
	  for (var inputid in selected_inputs) {
		    if (selected_inputs[inputid]==true) ids.push(parseInt(inputid));
	  }
	  input.delete_multiple(ids);
	  update();
	  $('#inputDeleteModal').modal('hide');
});
 
// Process list UI js
processlist_ui.init(0); // Set input context

$("#table").on('click', '.configure', function() {
    var i = inputs[$(this).attr('id')];
    var contextid = i.id; // Current Input ID
    // Input name
    var newfeedname = "";
    var contextname = "";
    if (i.description != "") { 
	      newfeedname = i.description;
	      contextname = "Node " + i.nodeid + " : " + newfeedname;
    }
    else { 
	      newfeedname = "node:" + i.nodeid+":" + i.name;
	      contextname = "Node " + i.nodeid + " : " + i.name;
    }
    var newfeedtag = "Node " + i.nodeid;
    var processlist = processlist_ui.decode(i.processList); // Input process list
    processlist_ui.load(contextid,processlist,contextname,newfeedname,newfeedtag); // load configs
});

$("#save-processlist").click(function (){
    var result = input.set_process(processlist_ui.contextid,processlist_ui.encode(processlist_ui.contextprocesslist));
    if (result.success) { processlist_ui.saved(table); } else { alert('ERROR: Could not save processlist. '+result.message); }
});

// -------------------------------------------------------------------------------------------------------
// Device authentication transfer
// -------------------------------------------------------------------------------------------------------
auth_check();
//setInterval(auth_check,5000);
function auth_check(){
    $.ajax({ url: path+"device/auth/check.json", dataType: 'json', async: true, success: function(data) {
        if (typeof data.ip !== "undefined") {
            $("#auth-check-ip").html(data.ip);
            $("#auth-check").show();
        } else {
            $("#auth-check").hide();
        }
    }});
}

$(".auth-check-allow").click(function(){
    var ip = $("#auth-check-ip").html();
    $.ajax({ url: path+"device/auth/allow.json?ip="+ip, dataType: 'json', async: true, success: function(data) {
        $("#auth-check").hide();
    }});
});

// -------------------------------------------------------------------------------------------------------
// Interface responsive
//
// The following implements the showing and hiding of the device fields depending on the available width
// of the container and the width of the individual fields themselves. It implements a level of responsivness
// that is one step more advanced than is possible using css alone.
// -------------------------------------------------------------------------------------------------------

// watchResize(onResize,50) // only call onResize() after delay (similar to debounce)

// debouncing causes odd rendering during resize - run this at all resize points...
$(window).on("resize",onResize)

</script>
