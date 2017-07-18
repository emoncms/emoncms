<?php
	global $path;
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/input.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

<style>
.node {margin-bottom:10px;}

.node-info {
    height:40px;
    background-color:#ddd;
    cursor:pointer;
}

.device-name { 
  font-weight:bold;
	float:left;
	padding:10px;
	padding-right:5px;
}

.device-description { 
  color:#666;
	float:left;
	padding-top:10px;
}

.device-key {
	float:right;
	padding:10px;
	min-width:50px;
	text-align:center;
	color:#fff;
	border-left: 1px solid #eee;
}

.device-schedule {
	float:right;
	padding:10px;
	min-width:50px;
	text-align:center;
	color:#fff;
	border-left: 1px solid #eee;
	display:none;
}

.device-configure {
	float:right;
	padding:10px;
	width:50px;
	text-align:center;
	color:#666;
	border-left: 1px solid #eee;
}

.device-key:hover {background-color:#eaeaea;}
.device-configure:hover {background-color:#eaeaea;}

.node-inputs {
    padding: 0px 5px 5px 5px;
    background-color:#ddd;
}

.node-input {
    background-color:#f0f0f0;
    border-bottom:1px solid #fff;
    border-left:2px solid #f0f0f0;
    height:41px;
    /*
    padding-left:10px;
    padding-right:10px;*/
}

.node-input:hover{ border-left:2px solid #44b3e2; }

.node-input .select {
    display:inline-block;
    width:20px;
    padding-top: 10px;
    /*padding-right: 10px;*/
    text-align:center;
}

.node-input .name {
    display:inline-block;
    /*padding-top:10px;*/
}

.node-input .processlist {
    display:inline-block;
    padding-top:10px;
    /*padding-left:10px;*/
}

.node-input-right {
    float:right;
}

.node-input .time {
    display:inline-block;
    width:60px;
    padding-top:10px;
    text-align:center;
}

.node-input .value {
    display:inline-block;
    padding-top:10px;
    text-align:center;
}

.node-input .configure {
    display:inline-block;
    width:40px;
    padding-top:10px;
    text-align:center;
	  cursor:pointer;
}

input[type="checkbox"] { margin:0px; }
#input-selection { width:80px; }
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

@media (min-width: 768px) and (max-width: 979px) {

}

@media (min-width: 480px) and (max-width: 768px) {

}

@media (max-width: 480px) {
/*.node-input .processlist { display:none}
.node-input .value { display:none}
.node-input .time { display:none}*/
}

</style>

<div>
	<div id="apihelphead" style="float:right;"><a href="api"><?php echo _('Input API Help'); ?></a></div>
	<div id="localheading"><h3><?php echo _('Inputs'); ?></h3></div>

<div class="controls">
	<div class="input-prepend" style="margin-bottom:0px">
		<span class="add-on">Select</span>
		<select id="input-selection">
		  <option value="custom">Custom</option>
			<option value="all">All</option>
			<option value="none">None</option>
		</select>
	</div>
	
	<button class="btn input-delete hide" title="Delete"><i class="icon-trash" ></i></button>
	
	<button id="classic-view" class="btn" style="float:right">Classic</button>
</div>	
	
	<div id="auth-check" class="hide">
	    <i class="icon-exclamation-sign icon-white"></i> Device on ip address: <span id="auth-check-ip"></span> would like to connect 
	    <button class="btn btn-small auth-check-btn auth-check-allow">Allow</button>
	</div>
	
	<div id="table"></div>
	
	<div id="output"></div>

	<div id="noinputs" class="alert alert-block hide">
			<h4 class="alert-heading"><?php echo _('No inputs created'); ?></h4>
			<p><?php echo _('Inputs are the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
	</div>
	
	<div id="input-loader" class="ajax-loader"></div>
</div>

<?php require "Modules/input/Views/input_dialog.php"; ?>

<?php require "Modules/process/Views/process_ui.php"; ?>

<div id="deviceConfigureModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="deviceConfigureModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="deviceConfigureModalLabel"><?php echo _('Device configure'); ?></h3>
    </div>
    <div class="modal-body">

        <div class="input-prepend input-append">
		        <span class="add-on" style="width:160px">Description or Location</span>
		        <input id="device-description-input" type="text" />
		        <button id="device-description-save" class="btn">Save</button>
		        <span class="add-on" id="device-description-saved">Saved</span>
	      </div>
        
        <div class="input-prepend input-append">
		        <span class="add-on" style="width:160px">Device template</span>
		        <select id="device-type-select"></select>
		        <button id="device-initialise" class="btn">Initialise</button>
	      </div>
	      <p><i>A device can be automatically initialised by including input name "nodename/describe":"template-name"</i></p>
        
    </div>
    <div class="modal-footer">
        <button id="device-delete" class="btn btn-danger"><i class="icon-trash icon-white"></i> <?php echo _('Delete device'); ?></button>
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Close'); ?></button>
    </div>
</div>

<script>

var path = "<?php echo $path; ?>";

var devices = {};
var inputs = {};
var nodes = {};
var nodes_display = {};
var selected_inputs = {};
var selected_device = false;

var device_templates = {};
$.ajax({ url: path+"device/listtemplates-short.json", dataType: 'json', async: true, success: function(data) { 
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
	                  $.ajax({ url: path+"device/create.json?nodeid="+inputs[z].nodeid, dataType: 'json', async: true, success: function(data) {
	                      if (!data) alert("There was an rrror creating device: "+inputs[z].nodeid); 
	                  }});
	              }
	              if (nodes_display[inputs[z].nodeid]==undefined) nodes_display[inputs[z].nodeid] = true;
	              if (devices[inputs[z].nodeid].inputs==undefined) devices[inputs[z].nodeid].inputs = [];
	              devices[inputs[z].nodeid].inputs.push(inputs[z]);
	          }
	          
	          draw_devices();
        }});
    }});
}

// ---------------------------------------------------------------------------------------------
// Draw devices
// ---------------------------------------------------------------------------------------------
function draw_devices()
{
    // Draw node/input list
    var out = "";
    for (var node in devices) {
        var visible = "hide"; if (nodes_display[node]) visible = "";
        
        out += "<div class='node'>";
        out += "  <div class='node-info' node='"+node+"'>";
        out += "    <div class='device-name'>"+node+":</div>";
        out += "    <div class='device-description'>"+devices[node].description+"</div>";
        out += "    <div class='device-configure'><i class='icon-wrench icon-white'></i></div>";
        out += "    <div class='device-key'>KEY</div>";
        out += "    <div class='device-schedule'>SCHEDULE</div>";
        out += "  </div>";
        out += "<div class='node-inputs "+visible+"' node='"+node+"'>";
        
        for (var i in devices[node].inputs) {
            var input = devices[node].inputs[i];
            
            var selected = "";
            if (selected_inputs[input.id]!=undefined && selected_inputs[input.id]==true) 
                selected = "checked";
            
            out += "<div class='node-input' id="+input.id+">";
            out += "<div class='select'><input class='input-select' type='checkbox' id='"+input.id+"' "+selected+" /></div>";
            out += "<div class='name'>"+input.name+"</div>";
            
            if (processlist_ui != undefined)  out += "<div class='processlist'>"+processlist_ui.drawpreview(input.processList)+"</div>";
            
            out += "<div class='node-input-right'>";
            out += "<div class='time'>"+list_format_updated(input.time)+"</div>";
            out += "<div class='value'>"+list_format_value(input.value)+"</div>";
            out += "<div class='configure' id='"+input.id+"'><i class='icon-wrench'></i></div>";
            out += "</div>";
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
        if (device_templates[node]!=undefined && device_templates[node].control) {
            $(".node-info[node='"+node+"'] .device-schedule").show();
        }
    }
    
    autowidth(".node-inputs .name",0);
    autowidth(".node-inputs .value",10);
    resize();
}
// ---------------------------------------------------------------------------------------------

function autowidth(element,padding) {
    var mw = 0;
    $(element).each(function(){
        var w = $(this).width();
        if (w>mw) mw = w;
    });
    
    $(element).width(mw+padding);
    return mw;
}

// Show/hide node on click
$("#table").on("click",".node-info",function() {
    var node = $(this).attr('node');
    if (nodes_display[node]) {
        $(".node-inputs[node='"+node+"']").hide();
        nodes_display[node] = false;
    } else {
        $(".node-inputs[node='"+node+"']").show();
        nodes_display[node] = true;
    }
});

$("#table").on("click",".input-select",function(e) {
    input_selection();
});

$("#input-selection").change(function(){
    var selection = $(this).val();
    
    if (selection=="all") {
        for (var id in inputs) selected_inputs[id] = true;
        $(".input-select").prop('checked', true); 
        
    } else if (selection=="none") {
        selected_inputs = {};
        $(".input-select").prop('checked', false); 
    }
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

$("#table").on("click",".device-key",function(e) {
    e.stopPropagation();
    var node = $(this).parent().attr("node");
    $(".node-info[node='"+node+"'] .device-key").html(devices[node].devicekey);    
});

$("#table").on("click",".device-schedule",function(e) {
    e.stopPropagation();
    var node = $(this).parent().attr("node");
    window.location = path+"input/schedule?node="+node;
    
});

$("#table").on("click",".device-configure",function(e) {
    e.stopPropagation();
    selected_device = $(this).parent().attr("node");
    $('#deviceConfigureModal').modal('show');
    $("#device-description-input").val(devices[selected_device].description);
    $("#device-description-saved").hide();
    
    var out = "";
    for (var z in device_templates) out += "<option value='"+z+"'>"+device_templates[z].name+"</option>";
    $("#device-type-select").html(out);
});

$("#device-description-input").keyup(function(){
    $("#device-description-save").show();
    $("#device-description-saved").hide();
});

$("#device-description-save").click(function(){
    $.ajax({ 
        url: path+"device/set.json", 
        data: "id="+devices[selected_device].id+"&fields="+JSON.stringify({"description":$("#device-description-input").val()}), 
        async: true, success: function(data) {
            $("#device-description-saved").show();
            $("#device-description-save").hide();
    }});
});

$("#device-initialise").click(function(){
    $.ajax({ url: path+"device/inittemplate.json", data: "id="+devices[selected_device].id+"&type="+$("#device-type-select").val(), dataType: 'json', async: false, success: function(data) {
        alert("Device '"+selected_device+"' initialised using template '"+$("#device-type-select").val()+"', inputs configured and feeds created");
    }});
});

$("#device-delete").click(function(){

    var ids = [];
	  for (var i in devices[selected_device].inputs) {
	      var inputid = devices[selected_device].inputs[i].id;
		    ids.push(parseInt(inputid));
	  }
	  input.delete_multiple(ids);
	  
    $.ajax({ url: path+"device/delete.json", data: "id="+devices[selected_device].id, dataType: 'json', async: false, success: function(data) {
        $('#deviceConfigureModal').modal('hide');
        update();
    }});
});

$(".input-delete").click(function(){
	  $('#inputDeleteModal').modal('show');
	  var out = "";
	  var ids = [];
	  for (var inputid in selected_inputs) {
		    if (selected_inputs[inputid]==true) {
			      var i = inputs[inputid];
			      if (i.processList == "" && i.description == "" && (parseInt(i.time) + (60*15)) < ((new Date).getTime() / 1000)){
				        // delete now if has no values and updated +15m
				        ids.push(parseInt(inputid)); 
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
    console.log(i);
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
setInterval(auth_check,5000);
function auth_check(){
    $.ajax({ url: path+"device/auth/check.json", dataType: 'json', async: true, success: function(data) {
        if (data!="no devices") {
            $("#auth-check").show();
            $("#auth-check-ip").html(data.ip);
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

$("#classic-view").click(function(){ window.location = path+"input/view-classic"; });


// -------------------------------------------------------------------------------------------------------
// Interface responsive
//
// The following implements the showing and hiding of the device fields depending on the available width
// of the container and the width of the individual fields themselves. It implements a level of responsivness
// that is one step more advanced than is possible using css alone.
// -------------------------------------------------------------------------------------------------------
var show_processlist = true;
var show_select = true;
var show_time = true;
var show_value = true;

$(window).resize(function(){ resize(); });

function resize() 
{
    show_processlist = true;
    show_select = true;
    show_time = true;
    show_value = true;

    $(".node-input").each(function(){
       var w = $(this).width()-10;
       
       var tw = 0;
       tw += $(this).find(".name").width();
       tw += $(this).find(".configure").width();

       tw += $(this).find(".select").width();
       if (tw>w) show_select = false;
       
       tw += $(this).find(".value").width();
       if (tw>w) show_value = false;
       
       tw += $(this).find(".time").width();
       if (tw>w) show_time = false;   
          
       tw += $(this).find(".processlist").width();
       if (tw>w) show_processlist = false;
    });
    
    if (show_select) $(".select").show(); else $(".select").hide();
    if (show_time) $(".time").show(); else $(".time").hide();
    if (show_value) $(".value").show(); else $(".value").hide();
    if (show_processlist) $(".processlist").show(); else $(".processlist").hide();
    
}
</script>
