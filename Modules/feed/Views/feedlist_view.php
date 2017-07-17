<?php
    global $path, $feedviewpath;
    if (!isset($feedviewpath)) $feedviewpath = "vis/auto?feedid=";
?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
<link href="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script type="text/javascript" src="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js"></script>

<style>
#table input[type="text"] {
         width: 88%;
}

#table td:nth-of-type(5) { width:14px; text-align: center; }
#table th:nth-of-type(8), td:nth-of-type(8) { text-align: right; }
#table th:nth-of-type(9), td:nth-of-type(9) { text-align: right; }
#table th:nth-of-type(10), td:nth-of-type(10) { text-align: right; }
#table th[fieldg="size"], th[fieldg="time"] { font-weight:normal; text-align: right; }
#table th[fieldg="processList"] { font-weight:normal; text-align: left; }
#table td:nth-of-type(11) { width:14px; text-align: center; }
#table td:nth-of-type(12) { width:14px; text-align: center; }
#table td:nth-of-type(13) { width:14px; text-align: center; }
#table td:nth-of-type(14) { width:14px; text-align: center; }
#table td:nth-of-type(15) { width:14px; text-align: center; }
</style>

<div>
    <div id="apihelphead" style="float:right;"><a href="<?php echo $path; ?>feed/api"><?php echo _('Feed API Help'); ?></a></div>
    <div id="localheading"><h2><?php echo _('Feeds'); ?></h2></div>

    <div id="table"></div>

    <div id="nofeeds" class="alert alert-block hide">
            <h4 class="alert-heading"><?php echo _('No feeds created'); ?></h4>
            <p><?php echo _('Feeds are where your monitoring data is stored. The route for creating storage feeds is to start by creating inputs (see the inputs tab). Once you have inputs you can either log them straight to feeds or if you want you can add various levels of input processing to your inputs to create things like daily average data or to calibrate inputs before storage. Alternatively you can create Virtual feeds, this is a special feed that allows you to do post processing on existing storage feeds data, the main advantage is that it will not use additional storage space and you may modify post processing list that gets applyed on old stored data. You may want the next link as a guide for generating your request: '); ?><a href="api"><?php echo _('Feed API helper'); ?></a></p>
    </div>

    <div id="feed-loader" class="ajax-loader"></div>

    <div id="bottomtoolbar" class="hide"><hr>
        <button id="refreshfeedsize" class="btn btn-small" ><i class="icon-refresh" ></i>&nbsp;<?php echo _('Refresh feed size'); ?></button>
        <button id="addnewvirtualfeed" class="btn btn-small" data-toggle="modal" data-target="#newFeedNameModal"><i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New virtual feed'); ?></button>
    </div>
</div>

<div id="feedDeleteModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="feedDeleteModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="feedDeleteModalLabel"><?php echo _('Delete feed'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Deleting a feed is permanent.'); ?></p>
        <br>
        <div id="deleteFeedText"><?php echo _('If you have Input Processlist processors that use this feed, after deleting it, review that process lists or they will be in error, freezing other Inputs. Also make sure no Dashboards use the deleted feed.'); ?></div>
        <div id="deleteVirtualFeedText"><?php echo _('This is a Virtual Feed, after deleting it, make sure no Dashboard continue to use the deleted feed.'); ?></div>
        <br><br>
        <p><?php echo _('Are you sure you want to delete?'); ?></p>
        <div id="feedDelete-loader" class="ajax-loader" style="display:none;"></div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="feedDelete-confirm" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
    </div>
</div>

<div id="feedExportModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="feedExportModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="feedExportModalLabel"><b><span id="SelectedExport"></span></b> CSV export</h3>
    </div>
    <div class="modal-body">
    <p>Select the time range and interval that you wish to export: </p>
        <table class="table">
        <tr>
            <td>
                <p><b>Start date & time</b></p>
                <div id="datetimepicker1" class="input-append date">
                    <input id="export-start" data-format="dd/MM/yyyy hh:mm:ss" type="text" />
                    <span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
                </div>
            </td>
            <td>
                <p><b>End date & time</b></p>
                <div id="datetimepicker2" class="input-append date">
                    <input id="export-end" data-format="dd/MM/yyyy hh:mm:ss" type="text" />
                    <span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <p><b>Interval</b></p>
                <select id="export-interval" >
                    <option value=1>Auto</option>
                    <option value=5>5s</option>
                    <option value=10>10s</option>
                    <option value=30>30s</option>
                    <option value=60>1 min</option>
                    <option value=300>5 mins</option>
                    <option value=600>10 mins</option>
                    <option value=900>15 mins</option>
                    <option value=1800>30 mins</option>
                    <option value=3600>1 hour</option>
                    <option value=21600>6 hour</option>
                    <option value=43200>12 hour</option>
                    <option value=86400>Daily</option>
                    <option value=604800>Weekly</option>
                    <option value=2678400>Monthly</option>
                    <option value=31536000>Annual</option>
                </select>
            </td>
            <td>
                <p><b>Date time format</b></p>
                <div class="checkbox">
                  <label><input type="checkbox" id="export-timeformat" value="" checked>Excel (d/m/Y H:i:s)</label>
                </div>
                <label>Offset secs (for daily)&nbsp;<input id="export-timezone-offset" type="text" class="input-mini" disabled=""></label>
            </td>
        </tr>
        </table>
            <div class="alert alert-info">
                <p>Selecting an interval shorter than the feed interval (or Auto) will use the feed interval instead. Averages are only returned for feed engines with built in averaging.</p>
                <p>Date time in excel format is in user timezone. Offset can be set if exporting in Unix epoch time format.</p>
            </div>
    </div>
    <div class="modal-footer">
        <div id="downloadsizeplaceholder" style="float: left">Estimated download size: <span id="downloadsize">0</span>MB</div>
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Close'); ?></button>
        <button class="btn" id="export">Export</button>
    </div>
</div>

<div id="newFeedNameModal" class="modal hide keyboard" tabindex="-1" role="dialog" aria-labelledby="newFeedNameModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="newFeedNameModalLabel"><?php echo _('New Virtual Feed'); ?></h3>
    </div>
    <div class="modal-body">
        <label>Feed Name: </label>
        <input type="text" value="New Virtual Feed" id="newfeed-name">
        <label>Feed Tag: </label>
        <input type="text" value="Virtual" id="newfeed-tag">
        <label>Feed DataType: </label>
        <select id="newfeed-datatype">
            <option value=1>Realtime</option>
            <option value=2>Daily</option>
        </select>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="newfeed-save" class="btn btn-primary"><?php echo _('Save'); ?></button>
    </div>
</div>

<?php require "Modules/process/Views/process_ui.php"; ?>

<script>
  var path = "<?php echo $path; ?>";
  var feedviewpath = "<?php echo $feedviewpath; ?>";

  // Extend table library field types
  for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];
  table.element = "#table";
  table.groupby = 'tag';
  table.groupfields = {
    'processList':{'title':'<?php echo _("Process list"); ?>','type':"group-processlist"},
    'dummy-4':{'title':'', 'type':"blank"},
    'dummy-5':{'title':'', 'type':"blank"},
    'dummy-6':{'title':'', 'type':"blank"},
    'size':{'title':"<?php echo _('Size'); ?>", 'type':"group-size"},
    'time':{'title':"<?php echo _('Updated'); ?>", 'type':"group-updated"},
    'dummy-9':{'title':'', 'type':"blank"},
    'dummy-10':{'title':'', 'type':"blank"},
    'dummy-11':{'title':'', 'type':"blank"},
    'dummy-12':{'title':'', 'type':"blank"},
    'dummy-13':{'title':'', 'type':"blank"},
    'exportall-action':{'title':'', 'type':"group-iconbasic", 'icon':'icon-circle-arrow-down'}
  }

  table.deletedata = false;
  table.fields = {
    'id':{'title':"<?php echo _('Id'); ?>", 'type':"fixed"},
    'tag':{'title':"<?php echo _('Tag'); ?>", 'type':"hinteditable"},
    'name':{'title':"<?php echo _('Name'); ?>", 'type':"text"},
    'processList':{'title':'<?php echo _("Process list"); ?>','type':"processlist"},
    'public':{'title':"<?php echo _('Public'); ?>", 'type':"icon", 'trueicon':"icon-globe", 'falseicon':"icon-lock"},
    'datatype':{'title':"<?php echo _('Datatype'); ?>", 'type':"fixedselect", 'options':['','REALTIME','DAILY','HISTOGRAM']},
    'engine':{'title':"<?php echo _('Engine'); ?>", 'type':"fixedselect", 'options':['MYSQL','TIMESTORE','PHPTIMESERIES','GRAPHITE','PHPTIMESTORE','PHPFINA','PHPFIWA','VIRTUAL','MEMORY','REDISBUFFER']},
    'size':{'title':"<?php echo _('Size'); ?>", 'type':"size"},
    'time':{'title':"<?php echo _('Updated'); ?>", 'type':"updated"},
    'value':{'title':"<?php echo _('Value'); ?>",'type':"value"},
    // Actions
    'edit-action':{'title':'', 'type':"edit"},
    'delete-action':{'title':'', 'type':"delete"},
    'view-action':{'title':'', 'type':"iconlink", 'link':path+feedviewpath},
    'processlist-action':{'title':'', 'type':"iconconfig", 'icon':'icon-wrench'},
    'export-action':{'title':'', 'type':"iconbasic", 'icon':'icon-download'}
  }

  update();

  function update() {
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "?apikey="+feed.apikey;

    var requestTime = (new Date()).getTime();
    $.ajax({ url: path+"feed/list.json"+apikeystr, dataType: 'json', async: true, success: function(data, textStatus, xhr) {
      table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
      table.data = data;
      for (z in table.data){
        if (data[z]['engine'] != 7){
          data[z]['#NO_CONFIG#'] = true;  // if the data field #NO_CONFIG# is true, the field type: iconconfig will be ommited from the table row
        }
      }
      table.draw();
      $('#feed-loader').hide();
      if (table.data.length == 0){
        $("#nofeeds").show();
        $("#localheading").hide();
        $("#apihelphead").hide();
        $("#bottomtoolbar").show();
        $("#refreshfeedsize").hide();
      } else {
        $("#nofeeds").hide();
        $("#localheading").show();
        $("#apihelphead").show();
        $("#bottomtoolbar").show();
        $("#refreshfeedsize").show();
      }
    } });
  }

  var updater;
  function updaterStart(func, interval){
    clearInterval(updater);
    updater = null;
    if (interval > 0) updater = setInterval(func, interval);
  }
  updaterStart(update, 5000);

  $("#table").bind("onEdit", function(e){
    updaterStart(update, 0);
  });

  $("#table").bind("onSave", function(e,id,fields_to_update){
    $('#feed-loader').show();
    feed.set(id,fields_to_update);
    $('#feed-loader').hide();
  });

  $("#table").bind("onResume", function(e){
    updaterStart(update, 5000);
  });

  $("#table").bind("onDelete", function(e,id,row){
    updaterStart(update, 0);
    if (table.data[row]['engine'] == 7) { //Virtual
      $('#feedDeleteModal #deleteFeedText').hide();
      $('#feedDeleteModal #deleteVirtualFeedText').show();
    } else {
      $('#feedDeleteModal #deleteFeedText').show();
      $('#feedDeleteModal #deleteVirtualFeedText').hide();
    }
    $('#feedDeleteModal').modal('show');
    $('#feedDeleteModal').attr('the_id',id);
    $('#feedDeleteModal').attr('the_row',row);
  });

  $("#feedDelete-confirm").click(function(){
    var id = $('#feedDeleteModal').attr('the_id');
    var row = $('#feedDeleteModal').attr('the_row');
    feed.remove(id);
    table.remove(row);
    update();

    $('#feedDeleteModal').modal('hide');
    updaterStart(update, 5000);
  });

  $("#refreshfeedsize").click(function(){
    $.ajax({ url: path+"feed/updatesize.json", async: true, success: function(data){ update(); alert("Total size of used space for feeds: " + list_format_size(data)); } });
  });


  // Export feature
  $("#table").on("click",".icon-circle-arrow-down,.icon-download", function(){
    var row = $(this).attr('row');
    if (row == undefined) {
      // is tag group
      $("#export").attr('export-type',"group");
      var group = $(this).attr('group');
      $("#export").attr('group',group);
      var rows = $(this).attr('rows').split(",");
      var feedids = [];
      for (i in rows) { feedids.push(table.data[rows[i]].id); } // get feedids from rowids
      $("#export").attr('feedids',feedids);
      $("#export").attr('feedcount',rows.length);
      $("#SelectedExport").html(group + " tag ("+rows.length+" feeds)");
      calculate_download_size(rows.length);
    } else {
      // is feed
      $("#export").attr('export-type',"feed");
      $("#export").attr('feedid',table.data[row].id);
      var name = table.data[row].tag+": "+table.data[row].name;
      $("#export").attr('name',name);
      $("#SelectedExport").html(name);
      calculate_download_size(1);
    }
    if ($("#export-timezone-offset").val()=="") {
      var timezoneoffset = user.timezoneoffset();
      if (timezoneoffset==null) timezoneoffset = 0;
      $("#export-timezone-offset").val(parseInt(timezoneoffset));
    }
    $('#feedExportModal').modal('show');
  });

  $('#datetimepicker1').datetimepicker({
    language: 'en-EN'
  });

  $('#datetimepicker2').datetimepicker({
    language: 'en-EN',
    useCurrent: false //Important! See issue #1075
  });

  $('#datetimepicker1').on("changeDate", function (e) {
    $('#datetimepicker2').data("datetimepicker").setStartDate(e.date);
  });

  $('#datetimepicker2').on("changeDate", function (e) {
    $('#datetimepicker1').data("datetimepicker").setEndDate(e.date);
  });

  now = new Date();
  today = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 00, 00);
  var picker1 = $('#datetimepicker1').data('datetimepicker');
  var picker2 = $('#datetimepicker2').data('datetimepicker');
  picker1.setLocalDate(today);
  picker2.setLocalDate(today);
  picker1.setEndDate(today);
  picker2.setStartDate(today);

  $('#export-interval, #export-timeformat').on('change', function(e) 
  {
    $("#export-timezone-offset").prop("disabled", $("#export-timeformat").prop('checked'));
    if ($("#export").attr('export-type') == 'group') {
      var downloadsize = calculate_download_size($("#export").attr('feedcount')); 
    } else {
      calculate_download_size(1); 
    }
  });

  $('#datetimepicker1, #datetimepicker2').on('changeDate', function(e) 
  {
    if ($("#export").attr('export-type') == 'group') {
      var downloadsize = calculate_download_size($("#export").attr('feedcount')); 
    } else {
      calculate_download_size(1); 
    }
  });
  
  $("#export").click(function()
  {
    var export_start = parse_timepicker_time($("#export-start").val());
    var export_end = parse_timepicker_time($("#export-end").val());
    var export_interval = $("#export-interval").val();
    var export_timezone_offset = parseInt($("#export-timezone-offset").val());
    var export_timeformat = ($("#export-timeformat").prop('checked') ? 1 : 0);
    if (export_timeformat) { export_timezone_offset = 0; }
    if (!export_start) {alert("Please enter a valid start date."); return false; }
    if (!export_end) {alert("Please enter a valid end date."); return false; }
    if (export_start>=export_end) {alert("Start date must be further back in time than end date."); return false; }
    if (export_interval=="") {alert("Please select interval to download."); return false; }
    var downloadlimit = <?php global $feed_settings; echo $feed_settings['csvdownloadlimit_mb']; ?>;

    if ($(this).attr('export-type') == 'group') {
      var feedids = $(this).attr('feedids');
      var downloadsize = calculate_download_size($(this).attr('feedcount')); 
      url = path+"feed/csvexport.json?ids="+feedids+"&start="+(export_start+(export_timezone_offset))+"&end="+(export_end+(export_timezone_offset))+"&interval="+export_interval+"&timeformat="+export_timeformat+"&name="+$(this).attr('group');
    } else {
      var feedid = $(this).attr('feedid');
      var downloadsize = calculate_download_size(1); 
      url = path+"feed/csvexport.json?id="+feedid+"&start="+(export_start+(export_timezone_offset))+"&end="+(export_end+(export_timezone_offset))+"&interval="+export_interval+"&timeformat="+export_timeformat+"&name="+$(this).attr('name');
    }
    console.log(url);
    if (downloadsize>(downloadlimit*1048576)) {
      var r = confirm("Estimated download file size is large.\nServer could take a long time or abort depending on stored data size.\Limit is "+downloadlimit+"MB.\n\nTry exporting anyway?");
      if (!r) return false;
    }
    window.open(url);
  });

  function calculate_download_size(feedcount){
    var export_start = parse_timepicker_time($("#export-start").val());
    var export_end = parse_timepicker_time($("#export-end").val());
    var export_interval = $("#export-interval").val();
    var export_timeformat_size = ($("#export-timeformat").prop('checked') ? 20 : 11);// bytes per timestamp
    var downloadsize = 0;
    if (!(!$.isNumeric(export_start) || !$.isNumeric(export_end) || !$.isNumeric(export_interval) || export_start > export_end )) { 
      downloadsize=((export_end - export_start) / export_interval) * (export_timeformat_size + (feedcount*7)); // avg bytes per data
    }
    $("#downloadsize").html((downloadsize/1024/1024).toFixed(2));
    var downloadlimit = <?php global $feed_settings; echo $feed_settings['csvdownloadlimit_mb']; ?>;
    $("#downloadsizeplaceholder").css('color', (downloadsize == 0 || downloadsize > (downloadlimit*1048576) ? 'red' : ''));
    return downloadsize;
  }

  function parse_timepicker_time(timestr){
    var tmp = timestr.split(" ");
    if (tmp.length!=2) return false;

    var date = tmp[0].split("/");
    if (date.length!=3) return false;

    var time = tmp[1].split(":");
    if (time.length!=3) return false;

    return new Date(date[2],date[1]-1,date[0],time[0],time[1],time[2],0).getTime() / 1000;
  }


  // Virtual Feed feature
  $("#newfeed-save").click(function (){
    var newfeedname = $('#newfeed-name').val();
    var newfeedtag = $('#newfeed-tag').val();
    var engine = 7;   // Virtual Engine
    var datatype = $('#newfeed-datatype').val();
    var options = {};
    
    var result = feed.create(newfeedtag,newfeedname,datatype,engine,options);
    feedid = result.feedid;

    if (!result.success || feedid<1) {
      alert('ERROR: Feed could not be created. '+result.message);
      return false;
    } else {
      update(); 
      $('#newFeedNameModal').modal('hide');
    }
  });


  // Process list UI js
  processlist_ui.init(1); // is virtual feed

  $("#table").on('click', '.icon-wrench', function() {
    var i = table.data[$(this).attr('row')];
    console.log(i);
    var contextid = i.id; // Feed ID
    var contextname = "";
    if (i.name != "") contextname = i.tag + " : " + i.name;
    else contextname = i.tag + " : " + i.id;    
    var processlist = processlist_ui.decode(i.processList); // Feed process list
    processlist_ui.load(contextid,processlist,contextname,null,null); // load configs
   });
  
  $("#save-processlist").click(function (){
    var result = feed.set_process(processlist_ui.contextid,processlist_ui.encode(processlist_ui.contextprocesslist));
    if (result.success) { processlist_ui.saved(table); } else { alert('ERROR: Could not save processlist. '+result.message); }
  }); 
</script>
