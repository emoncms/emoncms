<?php 
  global $path; 
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/dashboard.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
<style>
input[type="text"] {
     width: 88%; 
}
</style>

<div class="container">
    <h2><?php echo _('Dashboard'); ?></h2>
    <div id="table"></div>

    <div id="nodashboards" class="alert alert-block hide">
      <h4 class="alert-heading">No dashboards created</h4>
      <p>Maybe you would like to add your first dashboard using the 
      <a href="#" onclick="$.ajax({type: 'POST',url:'<?php echo $path; ?>dashboard/create.json',success: function(){update();} });"><i class="icon-plus-sign"></i></a> button.
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
    'alias':{'title':"<?php echo _('Alias'); ?>", 'type':"text"},
   // 'description':{'title':"<?php echo _('Description'); ?>", 'type':"text"},
    'main':{'title':"<?php echo _('Main'); ?>", 'type':"icon", 'trueicon':"icon-star", 'falseicon':"icon-star-empty"},
    'public':{'title':"<?php echo _('Public'); ?>", 'type':"icon", 'trueicon':"icon-globe", 'falseicon':"icon-lock"},
    'published':{'title':"<?php echo _('Published'); ?>", 'type':"icon", 'trueicon':"icon-ok", 'falseicon':"icon-remove"},

    // Actions
    'clone-action':{'title':'', 'type':"iconlink", 'icon':"icon-random", 'link':path+"dashboard/clone.json?id="},

    'edit-action':{'title':'', 'type':"edit"},
    'delete-action':{'title':'', 'type':"delete"},
    'view-action':{'title':'', 'type':"iconlink", 'link':path+"dashboard/view?id="}

  }

  update();

  function update() {
    table.data = dashboard.list();
    table.draw();
    if (table.data.length != 0) $("#nodashboards").hide(); else $("#nodashboards").show();
  }

  $("#table").bind("onEdit", function(e){});

  $("#table").bind("onSave", function(e,id,fields_to_update){
    dashboard.set(id,fields_to_update);
  });

  $("#table").bind("onDelete", function(e,id){
    dashboard.delete(id); 
  });

</script>
