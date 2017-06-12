<?php
    global $path;

	$devices = array();
	foreach($devices_templates as $key => $value)
	{
		$devices[$key] = ((!isset($value->name) || $value->name == "" ) ? $key : $value->name);
	}
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/device/Views/device.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>

<style>
#table input[type="text"] {
  width: 88%;
}
</style>

<div>
    <div id="apihelphead" style="float:right;"><a href="api"><?php echo _('Devices Help'); ?></a></div>
    <div id="localheading"><h2><?php echo _('Devices'); ?></h2></div>

    <div id="table"><div align='center'>loading...</div></div>
	
    <div id="nodevices" class="hide">
        <div class="alert alert-block">
            <h4 class="alert-heading"><?php echo _('No devices'); ?></h4><br>
            <p><?php echo _('There are no devices configured. Please add a new device.'); ?></p>
        </div>
    </div>

    <div id="bottomtoolbar"><hr>
        <button id="addnewdevice" class="btn btn-small" >&nbsp;<i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New device'); ?></button>
    </div>
</div>

<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel"><?php echo _('Delete device'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Deleting a device is permanent.'); ?>
           <br><br>
           <?php echo _('If this device is active and is using a device key, it will no longer be able to post data.'); ?>
		   <br><br>
		   <?php echo _('Inputs and Feeds that this device uses are not deleted and all historic data is kept. To remove them, deleted manualy afterwards.'); ?>
           <br><br>
           <?php echo _('Are you sure you want to delete?'); ?>
        </p>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="confirmdelete" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
    </div>
</div>

<div id="initdeviceModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="initdeviceModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="initdeviceModalLabel"><?php echo _('Initialize device'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Default inputs and associated feeds will be automaticaly created.'); ?>
		   <br><br>
		   <?php echo _('Make sure the selected device node and type are correcly configured before proceding.'); ?>
		   <br>
		   <?php echo _('Initializing a device usualy should only be done once on installation.'); ?>
		   <br>
           <?php echo _('If the node name already exists, new default inputs and feeds will be added.'); ?>
		   <br><br>
        </p>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="confirminitdevice" class="btn btn-primary"><?php echo _('Initialize'); ?></button>
    </div>
</div>

<script>
  var path = "<?php echo $path; ?>";
  var devices = <?php echo json_encode($devices); ?>;
  
  // Extend table library field types
  for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];
  table.element = "#table";
  //table.groupby = 'description';
  table.deletedata = false;
  table.fields = {
    //'id':{'type':"fixed"},
    'name':{'title':'<?php echo _("Name"); ?>','type':"text"},
    'description':{'title':'<?php echo _('Location'); ?>','type':"text"},
    'nodeid':{'title':'<?php echo _("Node"); ?>','type':"text"},
	'type':{'title':'<?php echo _("Type"); ?>','type':"select",'options':devices},
	'devicekey':{'title':'<?php echo _('Device access key'); ?>','type':"text"},
	'time':{'title':'<?php echo _("Updated"); ?>', 'type':"updated"},
    //'public':{'title':"<?php echo _('tbd'); ?>", 'type':"icon", 'trueicon':"icon-globe", 'falseicon':"icon-lock"},
    // Actions
    'edit-action':{'title':'', 'type':"edit"},
    'delete-action':{'title':'', 'type':"delete"},
    //'view-action':{'title':'', 'type':"iconbasic", 'icon':'icon-wrench'},
    'create-action':{'title':'', 'type':"iconbasic", 'icon':'icon-file'}
  }

  update();

  function update(){
    var requestTime = (new Date()).getTime();
    $.ajax({ url: path+"device/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
      table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
      table.data = data;
/*
	  for (d in data) {
        if (data[d]['own'] != true){ 
          data[d]['#READ_ONLY#'] = true;  // if the data field #READ_ONLY# is true, the fields type: edit, delete will be ommited from the table row and icon type will not update when clicked.
        }
      }
*/
      table.draw();
      if (table.data.length != 0) {
        $("#nodevices").hide();
        $("#localheading").show();
      } else {
        $("#nodevices").show();
        $("#localheading").hide();
      }
    }});
  }

  var updater;
  function updaterStart(func, interval)
  {
    clearInterval(updater);
    updater = null;
    if (interval > 0) updater = setInterval(func, interval);
  }
  updaterStart(update, 10000);

  $("#table").bind("onEdit", function(e){
    updaterStart(update, 0);
  });

  $("#table").bind("onSave", function(e,id,fields_to_update){
    device.set(id,fields_to_update);
  });

  $("#table").bind("onResume", function(e){
    updaterStart(update, 10000);
  });

  $("#table").bind("onDelete", function(e,id,row){
    $('#myModal').modal('show');
    $('#myModal').attr('deviceid',id);
    $('#myModal').attr('devicerow',row);
  });

  $("#confirmdelete").click(function()
  {
    var id = $('#myModal').attr('deviceid');
    var row = $('#myModal').attr('schedulerow');
    device.remove(id);
    table.remove(row);
    update();

    $('#myModal').modal('hide');
  });

  $("#addnewdevice").click(function(){
    $.ajax({ url: path+"device/create.json", success: function(data){update();} });
  });
 
  $("#table").on('click', '.icon-file', function() {
    $('#initdeviceModal').modal('show');
    $('#initdeviceModal').attr('deviceid',table.data[$(this).attr('row')]['id']);
  });
  
  $("#confirminitdevice").click(function()
  {
    var id = $('#initdeviceModal').attr('deviceid');
    var result = device.inittemplate(id);
    alert(result['message']);
    $('#initdeviceModal').modal('hide');
  });

</script>

