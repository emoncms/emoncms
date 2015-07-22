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
#table td:nth-of-type(7) { width:14px; text-align: center; }
#table td:nth-of-type(8) { width:14px; text-align: center; }
#table td:nth-of-type(9) { width:14px; text-align: center; }
</style>

<div>
    <div id="apihelphead" style="float:right;"><a href="api"><?php echo _('Input API Help'); ?></a></div>
    <div id="localheading"><h2><?php echo _('Inputs'); ?></h2></div>

    <?php require "Modules/process/Views/process_ui.php"; ?>
    
    <div id="table"><div align='center'>loading...</div></div>

    <div id="noinputs" class="alert alert-block hide">
            <h4 class="alert-heading"><?php echo _('No inputs created'); ?></h4>
            <p><?php echo _('Inputs is the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
    </div>
</div>

<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel"><?php echo _('Delete Input'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Deleting an input will loose its name and configured process list.<br>An new blank input is automatic created by API data post if it does not already exists.'); ?>
        </p>
        <p>
           <?php echo _('Are you sure you want to delete?'); ?>
        </p>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="confirmdelete" class="btn btn-primary"><?php echo _('Delete'); ?></button>
    </div>
</div>

<script>
  var path = "<?php echo $path; ?>";

  // Extend table library field types
  for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];
  table.element = "#table";
  table.groupprefix = "Node ";
  table.groupby = 'nodeid';
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

  function update()
  {   
    $.ajax({ url: path+"input/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
      table.timeServerLocalOffset = (new Date()).getTime()-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
      table.data = data;
      table.draw();
      if (table.data.length != 0) {
        $("#noinputs").hide();
        $("#localheading").show();
        $("#apihelphead").show();
      } else {
        $("#noinputs").show();
        $("#localheading").hide();
        $("#apihelphead").hide();
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
    input.set(id,fields_to_update);
  });

  $("#table").bind("onResume", function(e){
    updaterStart(update, 10000);
  });

  $("#table").bind("onDelete", function(e,id,row){
    $('#myModal').modal('show');
    $('#myModal').attr('the_id',id);
    $('#myModal').attr('the_row',row);
  });

  $("#confirmdelete").click(function()
  {
    var id = $('#myModal').attr('the_id');
    var row = $('#myModal').attr('the_row');
    input.remove(id);
    table.remove(row);
    update();

    $('#myModal').modal('hide');
  });

  
  // Process list UI js
  processlist_ui.init(0); // Set input context

  $("#table").on('click', '.icon-wrench', function() {
    var i = table.data[$(this).attr('row')];
    console.log(i);
    var contextid = i.id; // Current Input ID

    // Input name
    var newfeedname = "";
    if (i.description != "") newfeedname = i.description;
    else newfeedname = "node:" + i.nodeid+":" + i.name;
    var newfeedtag = "Node " + i.nodeid;
    var contextname = "Node" + i.nodeid + " : " + newfeedname;

    // Input process list
    var processlist = processlist_ui.decode(i.processList);

    processlist_ui.load(contextid,processlist,contextname,newfeedname,newfeedtag); // load configs
    window.scrollTo(0,0);
   });

  $("#save-processlist").click(function (){
    var result = input.set_process(processlist_ui.contextid,processlist_ui.encode(processlist_ui.contextprocesslist));
    if (result.success) { processlist_ui.saved(); } else { alert('ERROR: Could not save processlist. '+result.message); }
  });
</script>
