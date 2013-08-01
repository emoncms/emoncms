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

<br>

<div id="apihelphead"><div style="float:right;"><a href="api"><?php echo _('Feed API Help'); ?></a></div></div>

<div class="container">
    <div id="localheading"><h2>Preparing your feeds for conversion to timestore</h2></div>

<p>One of the central principles behind the <a src="mikestirling.co.uk/redmine/projects/timestore" >Timestore data storage</a> approach is that datapoints are stored at a fixed interval. Because you know that there is a datapoint every 10s you dont need to store the timestamp for each datapoint, you only need to store the timestamp for the first datapoint. The timestamp for every other datapoint can be worked out i.e:</p>

<p><pre>timestamp = start + position * interval.</pre></p>

<p>Storing time series data in this way makes it really easy and very fast to query. The tests so far have shown timestore to be <a src="http://openenergymonitor.blogspot.co.uk/2013/07/from-13-minutes-to-196ms-timestore-on.html" >several magnitudes faster</a> while also using significantly less disk space to use.</p>

<p>The following interface provides an opportunity to review and select your preferred interval rate for each realtime feed that your logging.</p>

<p>The Interval column states the average interval rate of the existing feed and is calculated simply as the end time minus the start time divided by the number of datapoints in the feed. This interval rate can be skewed if the monitor dropped off for a period, so its worth double checking that it is correct.</p>

<p>You may wish to change your interval rate, if your logging temperature data at 5s intervals and 60s is enough to see the changes in temperature you want to see then select 60s as this reduces the disk use of the feed considerably.</p> 

<p>To set the interval rate you wish your feed to be converted to, click on the <b>pencil button</b> to bring up in-line editing:</p>

<img src="http://emoncms.org/Modules/feed/Views/step1.png" />

<p>Click on the drop down menu under convert to and select from the list the interval you wish to use. Click on the tick button to complete.</p>

<img src="http://emoncms.org/Modules/feed/Views/step2.png" />

<p>Repeat the above steps for every feed you wish to convert and then once your done click on <b>Add feeds to conversion queue</b> button below</p>
    
<h2>Feed list</h2>

    <div id="table"></div>

    <div id="nofeeds" class="alert alert-block hide">
        <h4 class="alert-heading"><?php echo _('No feeds created'); ?></h4>
        <p><?php echo _('Feeds are where your monitoring data is stored. The recommended route for creating feeds is to start by creating inputs (see the inputs tab). Once you have inputs you can either log them straight to feeds or if you want you can add various levels of input processing to your inputs to create things like daily average data or to calibrate inputs before storage. You may want to follow the link as a guide for generating your request.'); ?><a href="api"><?php echo _('Feed API helper'); ?></a></p>
    </div>
</div>

<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="false">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="myModalLabel"><?php echo _('WARNING deleting a feed is permanent'); ?></h3>
  </div>
  <div class="modal-body">
    <p><?php echo _('Are you sure you want to delete this feed?'); ?></p>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
    <button id="confirmdelete" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
  </div>
</div>

<div id="addtoqueue" class="btn btn-large btn-info" >Add feeds to conversion queue</div>
<div id="alreadyadded"></div>

<script>

  var path = "<?php echo $path; ?>";

  var converted = false;
  $.ajax({ url: path+"user/getconvert.json", async: false, dataType: 'JSON', success: function(data){converted = data.convert;} });

  console.log(converted);

  if (converted) { $("#addtoqueue").hide(); $("#alreadyadded").html("<h2>Account already submitted for conversion, thankyou!</h2>");}

  // Extemd table library field types
  for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];

  table.element = "#table";

  table.fields = {
    'id':{'title':"<?php echo _('Id'); ?>", 'type':"fixed"},
    'name':{'title':"<?php echo _('Name'); ?>", 'type':"text"},
    'tag':{'title':"<?php echo _('Tag'); ?>", 'type':"text"},
    'size':{'title':"<?php echo _('Size'); ?>", 'type':"fixed"},
    'dpinterval':{'title':"<?php echo _('Interval'); ?>", 'type':"fixed"},
    'convert':{'title':"<?php echo _('Covert to'); ?>", 'type':"select", 'options':{0:'not set',5:'5s',10:'10s',15:'15s',20:'20s',25:'25s',30:'30s',60:'60s',120:'2 mins',300:'5 mins',600:'10 mins',1800:'30 mins',3600:'1 hour',21600:'6 hours',43200:'12 hours',86400:'24 hours'}},
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

    var data = [];
    for (z in table.data)
    {
      if (table.data[z]['datatype']==1 && table.data[z]['timestore']==0) data.push(table.data[z]);
    }
    table.data = eval(JSON.stringify(data));
 
    table.draw();
    if (table.data.length != 0) {
      $("#nofeeds").hide();
      $("#apihelphead").show();      
      $("#localheading").show();      
    } else {
      $("#nofeeds").show();
      $("#localheading").hide();
      $("#apihelphead").hide(); 
    }
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
    update();

    $('#myModal').modal('hide');
  });

  $("#addtoqueue").click(function(){
    console.log("Add to queue");

    $.ajax({ url: path+"user/setconvert.json", async: false, dataType: 'JSON', success: function(data){converted = data.convert;} });
    if (converted) { $("#addtoqueue").hide(); $("#alreadyadded").html("<h2>Account submitted for conversion, thankyou!</h2>");}

  });


</script>
