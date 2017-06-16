<?php
	global $path;
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/input.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

<style>
#table input[type="text"] {
	width: 88%;
}

#table td:nth-of-type(1) { width:5%;}
#table td:nth-of-type(2) { width:5%;}
#table td:nth-of-type(3) { width:20%;}
#table th:nth-of-type(5), td:nth-of-type(5) { text-align: right; }
#table th:nth-of-type(6), td:nth-of-type(6) { text-align: right; }
#table th[fieldg="time"] { font-weight:normal; text-align: right; }
#table th[fieldg="processList"] { font-weight:normal; text-align: left; }
#table td:nth-of-type(7) { width:14px; text-align: center; }
#table td:nth-of-type(8) { width:14px; text-align: center; }
#table td:nth-of-type(9) { width:14px; text-align: center; }
</style>

<div>
	<div id="apihelphead" style="float:right;"><a href="api"><?php echo _('Input API Help'); ?></a></div>
	<div id="localheading"><h2><?php echo _('Inputs'); ?></h2></div>

	<div id="table"></div>

	<div id="noinputs" class="alert alert-block hide">
			<h4 class="alert-heading"><?php echo _('No inputs created'); ?></h4>
			<p><?php echo _('Inputs are the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
	</div>
	
	<div id="input-loader" class="ajax-loader"></div>
</div>

<?php require "Modules/input/Views/input_dialog.php"; ?>

<?php require "Modules/process/Views/process_ui.php"; ?>

<script>
  var path = "<?php echo $path; ?>";

  // Extend table library field types
  for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];
  table.element = "#table";
  table.groupprefix = "Node ";
  table.groupby = 'nodeid';
  table.groupfields = {
	'processList':{'title':'<?php echo _("Process list"); ?>','type':"group-processlist"},
	'time':{'title':"<?php echo _('Updated'); ?>", 'type':"group-updated"},
	'dummy-6':{'title':'', 'type':"blank"},
	'dummy-7':{'title':'', 'type':"blank"},
	'dummy-8':{'title':'', 'type':"blank"},
	'dummy-9':{'title':'', 'type':"blank"}
  }

  table.deletedata = false;
  table.fields = {
	//'id':{'type':"fixed"},
	'nodeid':{'title':'<?php echo _("Node"); ?>','type':"fixed"},
	'name':{'title':'<?php echo _("Key"); ?>','type':"text"},
	'description':{'title':'<?php echo _("Name"); ?>','type':"text"},
	'processList':{'title':'<?php echo _("Process list"); ?>','type':"processlist"},
	'time':{'title':'<?php echo _("Updated"); ?>', 'type':"updated"},
	'value':{'title':'<?php echo _("Value"); ?>','type':"value"},
	// Actions
	'edit-action':{'title':'', 'type':"edit"},
	'delete-action':{'title':'', 'type':"delete"},
	'view-action':{'title':'', 'type':"iconbasic", 'icon':'icon-wrench'}
  }

  update();

  function update(){   
	var requestTime = (new Date()).getTime();
	$.ajax({ url: path+"input/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
	  table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
	  table.data = data;
	  table.draw();
	  $('#input-loader').hide();
	  if (table.data.length == 0) {
		$("#noinputs").show();
		$("#localheading").hide();
		$("#apihelphead").hide();
	  } else {
		$("#noinputs").hide();
		$("#localheading").show();
		$("#apihelphead").show();
	  }
	}});
  }

  var updater;
  function updaterStart(func, interval){
	clearInterval(updater);
	updater = null;
	if (interval > 0) updater = setInterval(func, interval);
  }
  updaterStart(update, 10000);

  $("#table").bind("onEdit", function(e){
	updaterStart(update, 0);
  });

  $("#table").bind("onSave", function(e,id,fields_to_update){
	$('#input-loader').show();
	input.set(id,fields_to_update);
	$('#input-loader').hide();
  });

  $("#table").bind("onResume", function(e){
	updaterStart(update, 10000);
  });

  $("#table").bind("onDelete", function(e,id,row){
	var i = table.data[row];
	if (i.processList == "" && i.description == "" && (parseInt(i.time) + (60*15)) < ((new Date).getTime() / 1000)){
	  // delete now if has no values and updated +15m
	  input.remove(id);
	  table.remove(row);
	  update();
	} else {
	  input_dialog.loadDelete(null, id, row);
	}
  });

 
  // Process list UI js
  processlist_ui.init(0); // Set input context

  $("#table").on('click', '.icon-wrench', function() {
	var i = table.data[$(this).attr('row')];
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
</script>
