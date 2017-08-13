<?php
    global $path;
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/device/Views/device.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>

<style>
#table input[type="text"] {
  width: 88%;
}
#table td:nth-of-type(1) { width:5%;}
#table th:nth-of-type(5), td:nth-of-type(5) { text-align: right; }
#table th:nth-of-type(6), td:nth-of-type(6) { text-align: right; }
#table th[fieldg="time"] { font-weight:normal; text-align: right; }
#table td:nth-of-type(7) { width:14px; text-align: center; }
#table td:nth-of-type(8) { width:14px; text-align: center; }
#table td:nth-of-type(9) { width:14px; text-align: center; }
#table td:nth-of-type(10) { width:14px; text-align: center; }
</style>

<div>
    <div id="api-help-header" style="float:right;"><a href="api"><?php echo _('Devices Help'); ?></a></div>
    <div id="local-header"><h2><?php echo _('Devices'); ?></h2></div>

    <div id="table"><div align='center'>loading...</div></div>

    <div id="device-none" class="hide">
        <div class="alert alert-block">
            <h4 class="alert-heading"><?php echo _('No device connections'); ?></h4><br>
            <p>
                <?php echo _('Device connections are used to configure and prepare the communication with different metering units.'); ?>
                <br><br>
                <?php echo _('A device configures and prepares inputs, feeds possible device channels, representing e.g. different registers of defined metering units (see the channels tab).'); ?>
                <br>
                <?php echo _('You may want the next link as a guide for generating your request: '); ?><a href="api"><?php echo _('Device API helper'); ?></a>
            </p>
        </div>
    </div>

    <div id="toolbar_bottom"><hr>
        <button id="device-new" class="btn btn-small" >&nbsp;<i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New device'); ?></button>
    </div>
</div>

<?php require "Modules/device/Views/device_dialog.php"; ?>

<script>
  var path = "<?php echo $path; ?>";
  var devices = <?php echo json_encode($devices); ?>;
  
  // Extend table library field types
  for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];
  table.element = "#table";
  table.groupprefix = "Location ";
  table.groupby = 'description';
  table.groupfields = {
    'dummy-4':{'title':'', 'type':"blank"},
    'dummy-5':{'title':'', 'type':"blank"},
    'time':{'title':'<?php echo _('Updated'); ?>', 'type':"group-updated"},
    'dummy-7':{'title':'', 'type':"blank"},
    'dummy-8':{'title':'', 'type':"blank"},
    'dummy-9':{'title':'', 'type':"blank"},
    'dummy-10':{'title':'', 'type':"blank"}
  }
  
  table.deletedata = false;
  table.fields = {
    //'id':{'type':"fixed"},
    'nodeid':{'title':'<?php echo _("Node"); ?>','type':"text"},
    'name':{'title':'<?php echo _("Name"); ?>','type':"text"},
    'description':{'title':'<?php echo _('Location'); ?>','type':"text"},
    'typename':{'title':'<?php echo _("Type"); ?>','type':"fixed"},
    'devicekey':{'title':'<?php echo _('Device access key'); ?>','type':"text"},
    'time':{'title':'<?php echo _("Updated"); ?>', 'type':"updated"},
    //'public':{'title':"<?php echo _('tbd'); ?>", 'type':"icon", 'trueicon':"icon-globe", 'falseicon':"icon-lock"},
    // Actions
    'edit-action':{'title':'', 'type':"edit"},
    'delete-action':{'title':'', 'type':"delete"},
    'init-action':{'title':'', 'type':"iconbasic", 'icon':'icon-refresh'},
    'config-action':{'title':'', 'type':"iconconfig", 'icon':'icon-wrench'}
  }

  update();

  function update(){
    var requestTime = (new Date()).getTime();
    $.ajax({ url: path+"device/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
      table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
      table.data = data;

      for (d in data) {
        if (data[d]['type'] !== null && data[d]['type'] != '') {
          data[d]['typename'] = devices[data[d]['type']].name;
        }
        else data[d]['typename'] = '';
        /*
        if (data[d]['own'] != true){ 
          data[d]['#READ_ONLY#'] = true;  // if the data field #READ_ONLY# is true, the fields type: edit, delete will be ommited from the table row and icon type will not update when clicked.
        }
        */
      }

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

  $("#table").bind("onDelete", function(e,id,row) {

    // Get device of clicked row
    var localDevice = device.get(id);
    device_dialog.loadDelete(localDevice, row);
  });

  $("#table").on('click', '.icon-refresh', function() {

    // Get device of clicked row
    var localDevice = table.data[$(this).attr('row')];
    device_dialog.loadInit(localDevice);
  });

  $("#table").on('click', '.icon-wrench', function() {

    // Get device of clicked row
    var localDevice = table.data[$(this).attr('row')];
    device_dialog.loadConfig(devices, localDevice);
  });

  $("#device-new").on('click', function () {

    device_dialog.loadConfig(devices, null);
  });

</script>
