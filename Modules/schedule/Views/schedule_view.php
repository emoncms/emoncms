<?php
    global $path;
?>
<?php
    $domain4 = "schedule_messages";
    bindtextdomain($domain4, "Modules/schedule/locale");
    bind_textdomain_codeset($domain4, 'UTF-8');
?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/schedule/Views/schedule.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>

<style>
#table input[type="text"] {
  width: 88%;
}
</style>

<div>
    <div id="apihelphead" style="float:right;"><a href="api"><?php echo dgettext('schedule_messages','Schedule Help'); ?></a></div>
    <div id="localheading"><h2><?php echo dgettext('schedule_messages','Schedules'); ?></h2></div>

    <div id="table"></div>

    <div id="noschedules" class= "alert alert-block hide">
            <h4 class="alert-heading"><?php echo dgettext('schedule_messages','No schedules'); ?></h4><br>
            <p><?php echo dgettext('schedule_messages','There are no public schedules and you have not created your own yet. Please add a new schedule.<br><br>For help and examples on how to configure a schedule, read the <a href="api#expression">Expression documentation</a>.'); ?></p>
    </div>

    <div id="schedule-loader" class="ajax-loader"></div>

    <div id="bottomtoolbar"><hr>
        <button id="addnewschedule" class="btn btn-small" >&nbsp;<i class="icon-plus-sign" ></i>&nbsp;<?php echo dgettext('schedule_messages','New schedule'); ?></button>
    </div>
</div>

<div id="scheduleDeleteModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="scheduleDeleteModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="scheduleDeleteModalLabel"><?php echo dgettext('schedule_messages','Delete schedule'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo dgettext('schedule_messages','Deleting a schedule is permanent.'); ?>
           <br><br>
           <?php echo dgettext('schedule_messages','If you have an Input or Feed Processlist that use this schedule, after deleting it, review that process list or it will be in error freezing other process lists.'); ?>
           <br><br>
           <?php echo dgettext('schedule_messages','Are you sure you want to delete?'); ?>
        </p>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo dgettext('schedule_messages','Cancel'); ?></button>
        <button id="confirmdelete" class="btn btn-primary"><?php echo dgettext('schedule_messages','Delete permanently'); ?></button>
    </div>
</div>

<script>
  var path = "<?php echo $path; ?>";

  // Extend table library field types
  for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];
  table.element = "#table";
  //table.groupby = 'userid';
  table.deletedata = false;
  table.fields = {
    'id':{'type':"fixed"},
    'name':{'title':'<?php echo dgettext('schedule_messages',"Name"); ?>','type':"text"},
    'expression':{'title':'<?php echo dgettext('schedule_messages','Expression'); ?>','type':"text"},
    'public':{'title':"<?php echo dgettext('schedule_messages','Public'); ?>", 'type':"icon", 'trueicon':"icon-globe", 'falseicon':"icon-lock"},
    // Actions
    'edit-action':{'title':'', 'type':"edit"},
    'delete-action':{'title':'', 'type':"delete"},
    'view-action':{'title':'', 'type':"iconbasic", 'icon':'icon-wrench'},
    'test-action':{'title':'', 'type':"iconbasic", 'icon':'icon-eye-open'}
  }

  update();

  function update()
  {   
    $.ajax({ url: path+"schedule/list.json", dataType: 'json', async: true, success: function(data) {
    
      table.data = data;
      for (d in data) {
        if (data[d]['own'] != true){ 
          data[d]['#READ_ONLY#'] = true;  // if the data field #READ_ONLY# is true, the fields type: edit, delete will be ommited from the table row and icon type will not update when clicked.
        }
      }

      table.draw();
      $('#schedule-loader').hide();
      if (table.data.length != 0) {
        $("#noschedules").hide();
        $("#localheading").show();
        $("#apihelphead").show();
      } else {
        $("#noschedules").show();
        $("#localheading").hide();
        $("#apihelphead").hide();
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
    $('#schedule-loader').show();
    var result = schedule.set(id,fields_to_update);
    if (!result.success) {
         alert(result.message);
    }
    $('#schedule-loader').hide();
  });

  $("#table").bind("onResume", function(e){
    updaterStart(update, 10000);
  });

  $("#table").bind("onDelete", function(e,id,row){
    $('#scheduleDeleteModal').modal('show');
    $('#scheduleDeleteModal').attr('scheduleid',id);
    $('#scheduleDeleteModal').attr('feedrow',row);
  });

  $("#confirmdelete").click(function()
  {
    var id = $('#scheduleDeleteModal').attr('scheduleid');
    var row = $('#scheduleDeleteModal').attr('schedulerow');
    schedule.remove(id);
    table.remove(row);
    update();

    $('#scheduleDeleteModal').modal('hide');
  });

  $("#addnewschedule").click(function(){
    $.ajax({ url: path+"schedule/create.json", success: function(data){update();} });
  });


// Expression helper UI js
 
  $("#table").on('click', '.icon-wrench', function() {
    var i = table.data[$(this).attr('row')];
    console.log(i);
    alert("TBD: Javascript expression builder " + i['id']);

  });

  $("#table").on('click', '.icon-eye-open', function() {
    var i = table.data[$(this).attr('row')];
    console.log(i);
    var result = schedule.test(i['id']);
    alert("Schedule expression returned '" + result['result'] +"'.\n\nDetails:\n"+ result['debug']);

  });
</script>
