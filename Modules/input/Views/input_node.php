<?php 
  global $path; 
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/input.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
<style>
input[type="text"] {
     width: 88%; 
}
</style>

<br>
<div id="apihelphead"><div style="float:right;"><a href="api"><?php echo _('Input API Help'); ?></a></div></div>

<div class="container">
    <div id="localheading"><h2><?php echo _('Inputs'); ?></h2></div>
    <div id="table"></div>

    <div id="noinputs" class="alert alert-block hide">
        <h4 class="alert-heading"><?php echo _('No inputs created'); ?></h4>
        <p><?php echo _('Inputs is the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
    </div>

</div>

<script>

  var path = "<?php echo $path; ?>";

  // Extemd table library field types
  for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];

  table.element = "#table";

  table.fields = {
    //'id':{'type':"fixed"},
    'nodeid':{'type':"fixed"},
    'name':{'type':"text"},
    'description':{'type':"text"},
    'processList':{'type':"processlist"},
    // 'time':{'title':'last updated', 'type':"updated"},
    // 'value':{'type':"value"},

    // Actions
    'edit-action':{'title':'', 'type':"edit"},
    'delete-action':{'title':'', 'type':"delete"},
    'view-action':{'title':'', 'type':"iconlink", 'link':path+"input/process/list.html?inputid=", 'icon':'icon-arrow-right'}

  }

  table.groupby = 'nodeid';

  update();

  function update()
  {
    table.data = input.list();
    table.draw();
    if (table.data.length != 0) {
      $("#noinputs").hide();
      $("#apihelphead").show();      
      $("#localheading").show();
    } else {
      $("#noinputs").show();
      $("#localheading").hide();
      $("#apihelphead").hide(); 
    }
  }

  var updater = setInterval(update, 10000);

  $("#table").bind("onEdit", function(e){
    clearInterval(updater);
  });

  $("#table").bind("onSave", function(e,id,fields_to_update){
    input.set(id,fields_to_update); 
    updater = setInterval(update, 10000);
  });

  $("#table").bind("onDelete", function(e,id){
    input.remove(id); 
    update();
  });

</script>
