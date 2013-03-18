<?php 
  global $path; 
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
<style>
input[type="text"] {
     width: 88%; 
}
</style>

<br><div style="float:right;"><a href="api">Feed API Help</a></div>

<div class="container">
    <h2><?php echo _('Feeds'); ?></h2>
    <div id="table"></div>

    <div id="nofeeds" class="alert alert-block hide">
        <h4 class="alert-heading">No feeds created</h4>
        <p>Feeds are where your monitoring data is stored. The recommended route for creating feeds is to start by creating inputs (see the inputs tab). Once you have inputs you can either log them straight to feeds or if you want you can add various levels of input processing to your inputs to create things like daily average data or to calibrate inputs before storage.</p>
    </div>
</div>

<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="false">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="myModalLabel">WARNING deleting a feed is permanent</h3>
  </div>
  <div class="modal-body">
    <p>Are you sure you want to delete this feed?</p>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
    <button id="confirmdelete" class="btn btn-primary">Delete permanently</button>
  </div>
</div>

<script>

  var path = "<?php echo $path; ?>";

  // Extemd table library field types
  for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];

  table.element = "#table";

  table.fields = {
    'id':{'title':"<?php echo _('Id'); ?>", 'type':"fixed"},
    'name':{'title':"<?php echo _('Name'); ?>", 'type':"text"},
    'tag':{'title':"<?php echo _('Tag'); ?>", 'type':"text"},
    'datatype':{'title':"<?php echo _('Datatype'); ?>", 'type':"select", 'options':['','REALTIME','DAILY','HISTOGRAM']},
    'public':{'title':"<?php echo _('Public'); ?>", 'type':"icon", 'trueicon':"icon-globe", 'falseicon':"icon-lock"},
    'time':{'title':"<?php echo _('Updated'); ?>", 'type':"updated"},
    'value':{'title':"<?php echo _('Value'); ?>",'type':"value"},

    // Actions
    'edit-action':{'title':'', 'type':"edit"},
    'delete-action':{'title':'', 'type':"delete"},
    'view-action':{'title':'', 'type':"iconlink", 'link':path+"vis/auto?feedid="}

  }

  table.groupby = 'tag';
  table.deletedata = false;

  table.draw();

  update();

  function update()
  {
    table.data = feed.list();
    table.draw();
    if (table.data.length != 0) $("#nofeeds").hide(); else $("#nofeeds").show();
  }

  var updater = setInterval(update, 5000);

  $("#table").bind("onEdit", function(e){
    clearInterval(updater);
  });

  $("#table").bind("onSave", function(e,id,fields_to_update){
    feed.set(id,fields_to_update); 
    updater = setInterval(update, 5000);
  });

  $("#table").bind("onDelete", function(e,id,row){
    $('#myModal').modal('show');
    $('#myModal').attr('feedid',id);
    $('#myModal').attr('feedrow',row);
  });

  $("#confirmdelete").click(function()
  {
    var id = $('#myModal').attr('feedid');
    var row = $('#myModal').attr('feedrow');
    feed.remove(id); 
    table.remove(row);

    $('#myModal').modal('hide');
  });

</script>
