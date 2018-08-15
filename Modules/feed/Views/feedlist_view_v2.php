<?php
    global $path, $feedviewpath;
    if (!isset($feedviewpath)) $feedviewpath = "vis/auto?feedid=";
?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

<link href="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script type="text/javascript" src="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js"></script>

<style>
body{padding:0!important}
.container-fluid { padding: 0px 10px 0px 10px; }

#footer {
    margin-left: 0px;
    margin-right: 0px;
}

.navbar-fixed-top {
    margin-left: 0px;
    margin-right: 0px;
}

.node {margin-bottom:10px;}

.node-info.node-feeds {
    padding:0;
    line-height:2.7;
}
.node-info .node-feed {
    padding-left: 10px;
    background-color:#ddd;
    cursor:pointer;
    border: 0 solid transparent;
}
.node-info .name { font-weight:bold; font-size: larger }
.node-info .time {
    padding-right: 4px;
}

.node-name { font-weight:bold; }
.node-name,
.node-size,
.node-latest{
  float:left;
}


.node-feeds {
    padding: 0px 5px 5px 5px;
    background-color:#ddd;
}
.node-feed {
    background-color:#f0f0f0;
    border-bottom:1px solid #fff;
    border-left:2px solid transparent;
    min-height:41px;
    line-height:41px;
    transition: background .2s ease-in;
    overflow:hidden;
}
.node-feed:last-child{
    border-bottom:0px solid transparent;
}
.node-feed:hover {
    background-color:#EBEBEB;
    cursor: pointer
}
.node-feed:hover{ border-left-color: #44b3e2; }

.node-feed > *,
.node-feed > .node-feed-right > * {
    display:inline-block;
}

input[type="checkbox"] { vertical-align:text-bottom;}

.select::before {
    content: '';
    background: #00f0;
    width: 1em;
    height: 0;
    display: block;
    float: left;
}


#feed-selection { width:80px; }
.controls { margin-bottom:10px; }
#feeds-to-delete { font-style:italic; }

#deleteFeedModalSelectedItems{
    postion:absolute;
    overflow:hidden;
    text-align:left;
    background: #f5f5f5;
}
#deleteFeedModalSelectedItems h5{ margin:0 }
#deleteFeedModalSelectedItems ol{
    max-width:80%;
    position:absolute;
}
@media (min-width: 768px) {
    .container-fluid { padding: 0px 20px 0px 20px; }
}

@media (max-width: 468px) {
    #table .row-fluid .span6-xs, #table .row-fluid .span6-xs {width: 48.88%!important}
}
</style>
<div id="apihelphead" style="float:right;"><a href="<?php echo $path; ?>feed/api"><?php echo _('Feed API Help'); ?></a></div>
<div id="localheading"><h3><?php echo _('Feeds'); ?></h3></div>

<div class="controls">
	<div class="input-prepend" style="margin-bottom:0px">
		<span class="add-on">Select</span>
		<select id="feed-selection">
		  <option value="custom">Custom</option>
			<option value="all">All</option>
			<option value="none">None</option>
		</select>
	</div>
	
	<button class="btn feed-show-hide-all" data-expanded="true" title="<?php echo _('Reduce') ?>" data-title-expanded="<?php echo _('Expand') ?>" data-title-reduced="<?php echo _('Reduce') ?>"><i class="icon icon-resize-small"></i></button>
	<button class="btn feed-edit hide" title="Edit"><i class="icon-pencil"></i></button>
	<button class="btn feed-delete hide" title="Delete"><i class="icon-trash" ></i></button>
	<button class="btn feed-download hide" title="Download"><i class="icon-download"></i></button>
	<button class="btn feed-graph hide" title="Graph view"><i class="icon-eye-open"></i></button>
	<button class="btn feed-process hide" title="Process config"><i class="icon-wrench"></i></button>
	
</div>

<div id="table"></div>

<div id="nofeeds" class="alert alert-block hide">
    <h4 class="alert-heading"><?php echo _('No feeds created'); ?></h4>
    <p><?php echo _('Feeds are where your monitoring data is stored. The route for creating storage feeds is to start by creating inputs (see the inputs tab). Once you have inputs you can either log them straight to feeds or if you want you can add various levels of input processing to your inputs to create things like daily average data or to calibrate inputs before storage. Alternatively you can create Virtual feeds, this is a special feed that allows you to do post processing on existing storage feeds data, the main advantage is that it will not use additional storage space and you may modify post processing list that gets applyed on old stored data. You may want the next link as a guide for generating your request: '); ?><a href="api"><?php echo _('Feed API helper'); ?></a></p>
</div>

<div id="feed-loader" class="ajax-loader"></div>
    
<div id="bottomtoolbar" class="hide">
    <button id="refreshfeedsize" class="btn btn-small" ><i class="icon-refresh" ></i>&nbsp;<?php echo _('Refresh feed size'); ?></button>
    <button id="addnewvirtualfeed" class="btn btn-small" data-toggle="modal" data-target="#newFeedNameModal"><i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New virtual feed'); ?></button>
</div>

<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED EDIT MODAL                                                                                                                               -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedEditModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="feedEditModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="feedEditModalLabel"><?php echo _('Edit feed'); ?></h3>
    </div>
    <div class="modal-body">
        <p>Feed name:<br>
        <input id="feed-name" type="text"></p>

        <p>Feed node:<br>
        <input id="feed-node" type="text"></p>

        <p>Make feed public: 
        <input id="feed-public" type="checkbox"></p>

        <p>Feed Unit</p>
        <select id="feed_unit_dropdown">
            <option value=""></option>
            <option value="W">W</option>
            <option value="kWh">kWh</option>
            <option value="Wh">Wh</option>
            <option value="V">V</option>
            <option value="VA">VA</option>
            <option value="A">A</option>
            <option value="°C">°C</option>
            <option value="K">K</option>
            <option value="°F">°F</option>
            <option value="%">%</option>
            <option value="Hz">Hz</option>
            <option value="pulses">pulses</option>
            <option value="dB">dB</option>
            <option value="_other">Other</option>
        </select>
        <input id="feed_unit_dropdown_other">
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="feed-edit-save" class="btn btn-primary"><?php echo _('Save'); ?></button>
    </div>
</div>

<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED EXPORT                                                                                                                                   -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedExportModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="feedExportModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="feedExportModalLabel"><b><span id="SelectedExport"></span></b> <?php echo _('CSV export'); ?></h3>
    </div>
    <div class="modal-body">
    <p><?php echo _('Select the time range and interval that you wish to export: '); ?></p>
        <table class="table">
        <tr>
            <td>
                <p><b><?php echo _('Start date & time'); ?></b></p>
                <div id="datetimepicker1" class="input-append date">
                    <input id="export-start" data-format="dd/MM/yyyy hh:mm:ss" type="text" />
                    <span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
                </div>
            </td>
            <td>
                <p><b><?php echo _('End date & time ');?></b></b></p>
                <div id="datetimepicker2" class="input-append date">
                    <input id="export-end" data-format="dd/MM/yyyy hh:mm:ss" type="text" />
                    <span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <p><b><?php echo _('Interval');?></b></p>
                <select id="export-interval" >
                    <option value=1><?php echo _('Auto');?></option>
                    <option value=5><?php echo _('5s');?></option>
                    <option value=10><?php echo _('10s');?></option>
                    <option value=30><?php echo _('30s');?></option>
                    <option value=60><?php echo _('1 min');?></option>
                    <option value=300><?php echo _('5 mins');?></option>
                    <option value=600><?php echo _('10 mins');?></option>
                    <option value=900><?php echo _('15 mins');?></option>
                    <option value=1800><?php echo _('30 mins');?></option>
                    <option value=3600><?php echo _('1 hour');?></option>
                    <option value=21600><?php echo _('6 hour');?></option>
                    <option value=43200><?php echo _('12 hour');?></option>
                    <option value=86400><?php echo _('Daily');?></option>
                    <option value=604800><?php echo _('Weekly');?></option>
                    <option value=2678400><?php echo _('Monthly');?></option>
                    <option value=31536000><?php echo _('Annual');?></option>
                </select>
            </td>
            <td>
                <p><b><?php echo _('Date time format');?></b></p>
                <div class="checkbox">
                  <label><input type="checkbox" id="export-timeformat" value="" checked>Excel (d/m/Y H:i:s)</label>
                </div>
                <label><?php echo _('Offset secs (for daily)');?>&nbsp;<input id="export-timezone-offset" type="text" class="input-mini" disabled=""></label>
            </td>
        </tr>
        </table>
            <div class="alert alert-info">
                <p><?php echo _('Selecting an interval shorter than the feed interval (or Auto) will use the feed interval instead. Averages are only returned for feed engines with built in averaging.');?></p>
                <p><?php echo _('Date time in excel format is in user timezone. Offset can be set if exporting in Unix epoch time format.');?></p>
            </div>
    </div>
    <div class="modal-footer">
        <div id="downloadsizeplaceholder" style="float: left"><?php echo _('Estimated download size: ');?><span id="downloadsize">0</span>MB</div>
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Close'); ?></button>
        <button class="btn" id="export"><?php echo _('Export'); ?></button>
    </div>
</div>

<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED DELETE MODAL                                                                                                                             -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedDeleteModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="feedDeleteModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="feedDeleteModalLabel"><?php echo _('Delete feed'); ?> 
        <span id="feedDelete-message" class="label label-warning" data-default="<?php echo _('Deleting a feed is permanent.'); ?>"><?php echo _('Deleting a feed is permanent.'); ?></span>
        </h3>
    </div>
    <div class="modal-body">
        <div class="clearfix">
            <div id="clearContainer" class="span6">
                <div style="min-height:12.1em; position:relative" class="well well-small">
                    <h4 class="text-info"><?php echo _('Clear') ?>:</h4>
                    <p><?php echo _('Empty feed of all data') ?></p>
                    <button id="feedClear-confirm" class="btn btn-inverse" style="position:absolute;bottom:.8em"><?php echo _('Clear Data'); ?>&hellip;</button>
                </div>
            </div>

            <div id="trimContainer" class="span6">
                <div class="well well-small">
                    <h4 class="text-info"><?php echo _('Trim') ?>:</h4>
                    <p><?php echo _('Empty feed data up to') ?>:</p>
                    <div id="trim_start_time_container" class="control-group" style="margin-bottom:1.3em">
                        <div class="controls">
                            <div id="feed_trim_datetimepicker" class="input-append date" style="margin-bottom:0">
                                <input id="trim_start_time" class="input-medium" data-format="dd/MM/yyyy hh:mm:ss" type="text" placeholder="dd/mm/yyyy hh:mm:ss">
                                <span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-calendar"></i></span>
                            </div>
                            <div class="btn-group" style="margin-bottom:-4px">
                                <button class="btn btn-mini active" title="<?php echo _('Set to the start date') ?>" data-relative_time="start"><?php echo _('Start') ?></button>
                                <button class="btn btn-mini" title="<?php echo _('One year ago') ?>" data-relative_time="-1y"><?php echo _('- 1 year') ?></button>
                                <button class="btn btn-mini" title="<?php echo _('Two years ago') ?>" data-relative_time="-2y"><?php echo _('- 2 year') ?></button>
                                <button class="btn btn-mini" title="<?php echo _('Set to the current date/time') ?>" data-relative_time="now"><?php echo _('Now') ?></button>
                            </div>
                        </div>
                    </div>
                    <button id="feedTrim-confirm" class="btn btn-inverse"><?php echo _('Trim Data'); ?>&hellip;</button>
                </div>
            </div>
        </div>
        
        <div class="well well-small" style="margin-bottom:0">
            <h4 class="text-info"><?php echo _('Delete')?>: <span id="feedProcessList"></span></h4>
            <p id="deleteFeedText"><?php echo _('If you have Input Processlist processors that use this feed, after deleting it, review that process lists or they will be in error, freezing other Inputs. Also make sure no Dashboards use the deleted feed.'); ?></p>
            <p id="deleteVirtualFeedText"><?php echo _('This is a Virtual Feed, after deleting it, make sure no Dashboard continue to use the deleted feed.'); ?></p>
            <button id="feedDelete-confirm" class="btn btn-danger"><?php echo _('Delete feed permanently'); ?></button>
        </div>
    </div>
    <div class="modal-footer">
        <div id="feeds-to-delete" class="pull-left"></div>
        <div id="feedDelete-loader" class="ajax-loader" style="display:none;"></div>
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Close'); ?></button>
    </div>
</div>

<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- NEW VIRTUAL FEED                                                                                                                              -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="newFeedNameModal" class="modal hide keyboard" tabindex="-1" role="dialog" aria-labelledby="newFeedNameModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="newFeedNameModalLabel"><?php echo _('New Virtual Feed'); ?></h3>
    </div>
    <div class="modal-body">
        <label><?php echo _('Feed Name: '); ?></label>
        <input type="text" value="New Virtual Feed" id="newfeed-name">
        <label><?php echo _('Feed Tag: '); ?></label>
        <input type="text" value="Virtual" id="newfeed-tag">
        <label><?php echo _('Feed DataType: '); ?></label>
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
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<script>
  var path = "<?php echo $path; ?>";
  var feedviewpath = "<?php echo $feedviewpath; ?>";
  
  var feeds = {};
  var selected_feeds = {};
  var nodes_display = {};
  
  var feed_engines = ['MYSQL','TIMESTORE','PHPTIMESERIES','GRAPHITE','PHPTIMESTORE','PHPFINA','PHPFIWA','VIRTUAL','MEMORY','REDISBUFFER','CASSANDRA'];

  update();
//   setInterval(update,5000);
  
  function update() 
  {
  
      $.ajax({ url: path+"feed/list.json", dataType: 'json', async: true, success: function(data) {
      
          // Show/hide no feeds alert
          $('#feed-loader').hide();
          if (data.length == 0){
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
		      
          feeds = {};
		      for (var z in data) feeds[data[z].id] = data[z];
		      
          
          var nodes = {};
          for (var z in feeds) {
              var node = feeds[z].tag;
              if (nodes[node]==undefined) nodes[node] = [];
              if (nodes_display[node]==undefined) nodes_display[node] = true;
              nodes[node].push(feeds[z]);
          }

          var out = "";
          
          // get node overview
          var node_size = {},
              node_time = {}
            for (let node in nodes) {
                node_size[node] = 0
                node_time[node] = 0
                for (let feed in nodes[node]) {
                    node_size[node] += Number(nodes[node][feed].size)
                    node_time[node] = nodes[node][feed].time > node_time[node] ? nodes[node][feed].time : node_time[node]
                }
          }
          // display nodes and feeds
          for (var node in nodes) {
              var visible = "hide"; if (nodes_display[node]) visible = "";
              
              out += '<div class="node">';
              out += '  <div class="node-info node-feeds">';
              out += '    <div class="node-feed" node="'+node+'">'
              out += '      <div class="select text-center"></div>';
              out += '      <div class="name">'+node+':</div>';
              out += '      <div class="public text-center"></div>';
              out += '      <div class="engine"></div>';
              out += '      <div class="size text-center">'+list_format_size(node_size[node])+'</div>';
              out += '      <div class="node-feed-right pull-right">';
              out += '        <div class="value"></div>';
              out += '        <div class="time">'+list_format_updated(node_time[node])+'</div>';
              out += '      </div>';
              out += '    </div>';
              out += '  </div>';
              
              out += "<div class='node-feeds "+visible+"' node='"+node+"'>";
              
              for (var feed in nodes[node]) {
                  var feedid = nodes[node][feed].id;
                  var row_title = ["Feed ID: "+feedid,
                                    "Feed Interval: "+(nodes[node][feed].interval||'')+'s',
                                    "Feed Start Time: "+format_time(nodes[node][feed].start_time,'LLLL')
                  ].join("\n")

                  out += "<div class='node-feed feed-graph-link' feedid="+feedid+" title='"+row_title+"'>";
                  var checked = ""; if (selected_feeds[feedid]) checked = "checked";
                  out += "<div class='select text-center'><input class='feed-select' type='checkbox' feedid='"+feedid+"' "+checked+"></div>";
                  out += "<div class='name'>"+nodes[node][feed].name+"</div>";
                  
                  var publicfeed = "<i class='icon-lock'></i>"
                  if (nodes[node][feed]['public']==1) publicfeed = "<i class='icon-globe'></i>";
                  
                  out += "<div class='public text-center'>"+publicfeed+"</div>";
                  out += "  <div class='engine'>"+feed_engines[nodes[node][feed].engine]+"</div>";
                  out += "  <div class='size text-center'>"+list_format_size(nodes[node][feed].size)+"</div>";
                  out += "  <div class='node-feed-right pull-right'>";
                  out += "    <div class='value'>"+list_format_value(nodes[node][feed].value)+nodes[node][feed].unit+"</div>";
                  out += "    <div class='time'>"+list_format_updated(nodes[node][feed].time)+"</div>";
                  out += "  </div>";
                  out += "</div>";
              }
              
              out += "</div>";
              out += "</div>";
          }
          $("#table").html(out);
          
          autowidth(".node-feeds .name",20);
          autowidth(".node-feeds .public",20);
          autowidth(".node-feeds .engine",20);
          autowidth(".node-feeds .size",20);
          
          autowidth(".node-feeds .value",20);
          autowidth(".node-feeds .time",20);

          resize();
      }});
  }
  

  $(".feed-show-hide-all").on("click", function(event) {
      // hide expanded groups if the switch is set to expanded false
      // show all shrunk groups if the switch is set to expanded true
      event.preventDefault()
      let $this = $(this)
      let $icon = $this.find('.icon')
      // initial state
      let expanded = $(this).data('expanded')
      // str to bool
      expanded = expanded || expanded === 'true'
      // save the opposite for next click
      $this.data('expanded',!expanded)
      // notify the user by changing the button
      $icon.toggleClass('icon-resize-full icon-resize-small')
      let title = expanded ? $this.data('title-expanded') : $this.data('title-reduced')
      $this.attr('title',title)
      // interact with each row. one at a time
      $(".node-info").each(function(i,v){
        node = $(this).attr('node')
        // click open ones if expanded == true
        if (nodes_display[node] && expanded) {
            $(this).click()
        // click closed ones if expanded == false
        } else if (!nodes_display[node] && !expanded) {
            $(this).click()
        }
      })
      // clean the screen arrangement of elements
      resize()
  })

    function showHideFeedGroup(event){
        $elem = $(event.target)
        var node = $elem.attr("node");
        if (nodes_display[node]) {
            $(".node-feeds[node='"+node+"']").hide();
            nodes_display[node] = false;
        } else {
            $(".node-feeds[node='"+node+"']").show();
            nodes_display[node] = true;
        }
    }

  $("#table").on("click",".node-info",showHideFeedGroup);

  $("#table").on("click",".select",function(e) {
      e.stopPropagation();
  });
  
  $("#table").on("click",".public",function(e) {
      e.stopPropagation();
  });

  $("#table").on("click",".feed-select",function(e) {
      feed_selection();
  });

  $("#feed-selection").change(function(){
      var selection = $(this).val();
      
      if (selection=="all") {
          for (var id in feeds) selected_feeds[id] = true;
          $(".feed-select").prop('checked', true); 
          
      } else if (selection=="none") {
          selected_feeds = {};
          $(".feed-select").prop('checked', false); 
      }
      feed_selection();
  });

  $("#table").on("click",".feed-graph-link",function(e) {
      // ignore click on feed-info row
      if ($(this).parent().is('.node-info')) return false
      var feedid = $(this).attr("feedid");
      window.location = path+"graph/"+feedid;
  });
  
  $(".feed-graph").click(function(){
      var graph_feeds = [];
      for (var feedid in selected_feeds) {
          if (selected_feeds[feedid]==true) graph_feeds.push(feedid);
      }
      window.location = path+"graph/"+graph_feeds.join(",");	  
  });
  
  // ---------------------------------------------------------------------------------------------
  // EDIT FEED
  // ---------------------------------------------------------------------------------------------
  $(".feed-edit").click(function() {
      $('#feedEditModal').modal('show');
      
      var feedid = 0;
      // There should only ever be one feed that is selected here:
      for (var z in selected_feeds) { if (selected_feeds[z]) feedid = z; }

      $("#feed-name").val(feeds[feedid].name);
      $("#feed-node").val(feeds[feedid].tag);
      var checked = false; if (feeds[feedid].public==1) checked = true;
      $("#feed-public")[0].checked = checked;

        let $dropdown = $('#feed_unit_dropdown')
        $dropdown.val(feeds[feedid].unit)
        let options = []
        $dropdown.find('option').each(function(key,elem){
            options.push(elem.value)
        })
        if (options.indexOf(feeds[feedid].unit) == -1) {
            $('#feed_unit_dropdown_other').val(feeds[feedid].unit)
            $dropdown.val('_other')
        }
        if($dropdown.val()=='_other') {
            $dropdown.next('input').show();
        }else{
            $dropdown.next('input').hide();
        }
        $dropdown.change(function(event){
            if(event.target.value=='_other') {
                $(event.target).next('input').show();
            }else{
                $(event.target).next('input').hide();
            }
        });

  });

  $("#feed-edit-save").click(function(){
      var feedid = 0;
      // There should only ever be one feed that is selected here:
      for (var z in selected_feeds) { if (selected_feeds[z]) feedid = z; }
      
      var publicfeed = 0;
      if ($("#feed-public")[0].checked) publicfeed = 1;
      
      var unit = $('#feed_unit_dropdown').val()
      unit = unit == '_other' ? $('#feed_unit_dropdown_other').val() : unit

      var fields = {
        tag: $("#feed-node").val(), 
        name: $("#feed-name").val(),
        public: publicfeed,
        unit: unit
      };
      // only send changed values
      var data = {}
      for(f in fields){
          console.log(fields[f],feeds[feedid][f],{matched:fields[f]===feeds[feedid][f]})
          if (!(fields[f]===feeds[feedid][f])) data[f] = fields[f];
      }
      console.log(Object.keys(data).length);
      // dont send ajax if nothing changed
      if (Object.keys(data).length==0) {
        $('#feedEditModal').modal('hide')
        return
      }

      $.ajax({ url: path+"feed/set.json?id="+feedid+"&fields="+JSON.stringify(data), dataType: 'json', async: true, success: function(data) {
          update();
          $('#feedEditModal').modal('hide');
      }});
  });



  // ---------------------------------------------------------------------------------------------
  // DELETE FEED
  // ---------------------------------------------------------------------------------------------

/**
 * find which inputs and processess write to a feed
 *
 * the returned object is a list of arrays that store the process/input pairs that make up the specific output to feed
 *   obj[feedid][0].input.nodeid --- will get the nodeid for the first input that outputs to the given feed
 *   obj[feedid][0].process.short --- will get the short name for the first process that outputs to the given feed
 *
 * @return object
 */
function getFeedProcess(){
    let inputs = {}, // list of inputs and their processes
        feedProcesses = {}, // list of process that write to feeds
        let_feeds = {} // list of feeds and their accociated processes

    // create a list of all inputs that have processes
    for (inputid in processlist_ui.inputlist) {
        let input = processlist_ui.inputlist[inputid]
        if (input.processList.length>0) {
            inputs[inputid] = {
                processList: processlist_ui.decode(input.processList),
                nodeid: input.nodeid,
                name: input.name,
                inputid: inputid
            }
        }
    }
    // get all the processes that write to a feed - list them by numeric key (if available)
    for (processid in processlist_ui.processlist) {
        let process = processlist_ui.processlist[processid]
        if (process.feedwrite) {
            key = process.hasOwnProperty('id_num') ? process.id_num : processid
            feedProcesses[key] = processid
        }
    }

    // go through all the input processes and get all the feeds they output to
    for (inputid in inputs) {
        let input = inputs[inputid]
        // loop through the key / value pairs of each input processlist
        for (item in input.processList) {
            let processid = input.processList[item][0]
            let processval = input.processList[item][1] || null
            if(feedProcesses[processid]){
                //this process writes to feed
                let_feeds[processval] = let_feeds[processval] || []
                let_feeds[processval].push({
                    process: processlist_ui.processlist[feedProcesses[processid]],
                    input: input,
                    feedid: processval
                })
            }
        }
    }
    return let_feeds
}
/**
 * output what feeds have been selected in the overlay modal box
 *
 * @return void
 */
function showSelectedFeeds(feed_inputs) {
    // loop through selection 
    let selected = []
    for (var feedid in selected_feeds) {
        if (selected_feeds[feedid] == true) {
            selected[feedid] = feeds[feedid];
            if (feed_inputs[feedid]) {
                if (Array.isArray(feed_inputs[feedid])) {
                    selected[feedid].input = []
                    selected[feedid].process = []
                    for (f in feed_inputs[feedid]) {
                        selected[feedid].input.push(feed_inputs[feedid][f].input)
                        selected[feedid].process.push(feed_inputs[feedid][f].process)
                    }
                } else {
                    selected[feedid].input = [feed_inputs[feedid].input]
                    selected[feedid].process = [feed_inputs[feedid].process]
                }
            }
        }
    }

    // count the number of processess associated with the selected feeds
    let list='',titles={},linked=[],total_linked = 0
    for(s in selected) {
        titles[s] = selected[s].tag+":"+selected[s].name
        // virtual feed processes
        if ( selected[s].hasOwnProperty('processList') && selected[s].processList.length > 0 ) {
            linked.push(selected[s])
            let virtualProcesses = processlist_ui.decode(selected[s].processList)
            for(p in virtualProcesses) {
                total_linked ++
            }
        }
        // feed's linked/parent process
        if ( selected[s].hasOwnProperty('process') && selected[s].process && selected[s].process.length > 0 ) {
            linked.push(selected[s])
            for(i=0;i<selected[s].process.length;i++) {
                total_linked ++
            }
        }
        
    }
    // create html to display the results
    // notify user that feed is associated to processList
    
    // create a simple list of feed ids and names to display to the user
    let feedListShort = ''
    for(id in titles){
        feedListShort += '['+id+'] '+titles[id]+', '
    }
    // remove the last comma
    feedListShort = feedListShort.slice(0, -2);

    // create a container to store the result that is displayed to the user
    total_summary = '<div id="deleteFeedModalSelectedItems">'
    total_selected = Object.keys(titles).length
    if (total_selected == 1) {
    // if only one is selected display it's id & name
        feedProcessList = total_linked > 0 ? '<span class="badge badge-default" style="padding-left:4px"><i class="icon icon-white icon-exclamation-sign"></i> <?php echo _('1 Input process associated with this feed') ?>':''
        total_summary += '<h5>'+feedListShort+'</h5>'
    } else {
    // show a summary total if more than one are selected
        feedProcessList = total_linked > 0 ? '<span class="badge badge-default" style="padding-left:4px"><i class="icon icon-white icon-exclamation-sign"></i> '+(' <?php echo _('%s Input processes associated with these feeds') ?>'.replace('%s',total_linked))+'</span>' : ''
        total_summary += '<h5 title="'+feedListShort+'"><?php echo _('%s Feeds selected') ?> <i class="icon icon-question-sign"></i></h5>'.replace('%s', total_selected)
    }
    total_summary += '</div>'
    $("#feeds-to-delete").html(total_summary); // show how many feeds have been selected
    $("#feedProcessList").html(feedProcessList); // show how many processes are associated with the selected feeds

}

/**
 * show the trim start time in the date time picker and input field
 * 
 * will also highlight a button if it matches the currently selected timestamp
 *
 * @param int start_time unix timestamp (seconds)
 */
function showFeedStartDate(start_time){
    let startDate = start_time==0 ? new Date() : new Date(start_time*1000)
    $datetimepicker = $('#feed_trim_datetimepicker')
    $datetimepicker
    .datetimepicker({startDate: startDate}) // restrict calendar selection to the start time
    .datetimepicker('setValue', startDate) // set the date/time picker to the start time
    .on('changeDate', function(event){
        // mark any matching buttons as active
        $('[data-relative_time]').each(function(i,elem){
            if ($(elem).data('startdate') != event.date) {
                $(this).removeClass('active')
            }
        })
    })
}

/**
 * Initialises the different events to enable the "relative date" selections below the date/time picker
 * 
 * Set the data property of each button to store correct Date() for each button. Each button must have a 
 * "data-relative_time" attribute with one of the following values:-
 *   "-2y", "-1y", "start" or "now" (default)
 *
 * Each button shows a formatted date in the input field and also sets the date time picker to the relevant position
 * @param int start_time the earliest possible timestamp for all the selected feeds - does not allow trimming beyond this point
 *
 * @return void
 */
function initRelativeStartDateButtons(start_time){
    let startDate = start_time>0 ? new Date(start_time*1000) : new Date()

    $('[data-relative_time]').each(function(i,v){
        $btn = $(this)
        // add more cases here for additional options (and also data-relative_time='xyz' in the html)
        // returns function so that the dates are calculated to when the user clicks the buttons
        switch ($btn.data('relative_time')) {
        case '-2y':
            relativeTime = (function(){ now = new Date(); return new Date(now.getFullYear()-2,now.getMonth(),now.getDate(),now.getHours(),now.getMinutes(),now.getSeconds(),now.getMilliseconds()) })
            break
        case '-1y':
            relativeTime = (function(){ now = new Date(); return new Date(now.getFullYear()-1,now.getMonth(),now.getDate(),now.getHours(),now.getMinutes(),now.getSeconds(),now.getMilliseconds()) })
            break
        case 'start':
            relativeTime = startDate
            break
        default:
            relativeTime = new Date()
        }
        relativeTime = typeof relativeTime === 'function' ? relativeTime() : relativeTime;
        // set the timestamp as a data property of the button so that it can be referenced on click
        $btn.data('startdate', relativeTime.valueOf() )
        // make sure the calculated date is not beyond the start date
        if (relativeTime < startDate) {
            $btn.hide() // hide button date is beyond start date
            $btn.css({'font-style':'italic', color:'#9a9eaa'})
            $btn.attr('title',$btn.attr('title')+' - [<?php echo _('Out of range')?>]')
        }
    })
    // open date picker on input focus
    $('#trim_start_time').on('focus', function(event){ $datetimepicker.datetimepicker('show') })
    
    // alter the trim date / time picker on button presses
    $('[data-relative_time]').click(function(event){
        event.preventDefault()
        $btn = $(this)
        $btn.addClass('active').siblings().removeClass('active')
        $input = $('#trim_start_time')
        // get starttime from button's data
        date = new Date($btn.data('startdate'))
        // restrict selection to the earliest possible date
        if (date < startDate) {
            date = startDate
        }
        // rebuild the date string from the new date object
        Y = date.getFullYear()
        m = (date.getMonth()+1).pad(2)
        d = date.getDate().pad(2)
        h = date.getHours().pad(2)
        i = date.getMinutes().pad(2)
        s = date.getSeconds().pad(2)
        
        // show date in input field - DD/MM/YYYY HH:MM:SS
        newDateString = [[d,m,Y].join('/'),[h,i,s].join(':')].join(' ')
        $input.val(newDateString)
    })
}

/**
 * compares all the selected feed start_times to see which is the best suited for the group 
 * @return int start_time timestamp
 */
function getEarliestStartTime() { 
    let start_time = 0
    for (var feedid in selected_feeds) {
        if (selected_feeds[feedid] == true) {
            // record the earliest possible start_time for all the selected feeds
            start_time = feeds[feedid].start_time > start_time ? feeds[feedid].start_time : start_time
        }
    }
    return start_time
}
/**
 * mark button as selected if chosen date in date/time picker matches
 * jQuery Event handler for datetime picker's changeDate event
 */
$('#feed_trim_datetimepicker').on('changeDate',function(event){
    $('[data-relative_time]').each(function(){
        $btn = $(this)
        if ($btn.data('startdate') == event.date.valueOf()) {
            $btn.addClass('active').siblings().removeClass('active')
        }
    })
})
/**
 * returns true if trim function available for all the selected feed engine types
 *
 * @return boolean
 */
function isSelectionValidForTrim(){
    /*
        const MYSQL = 0;
        const TIMESTORE = 1;     // Depreciated
        const PHPTIMESERIES = 2;
        const GRAPHITE = 3;      // Not included in core
        const PHPTIMESTORE = 4;  // Depreciated
        const PHPFINA = 5;
        const PHPFIWA = 6;
        const VIRTUALFEED = 7;   // Virtual feed, on demand post processing
        const MYSQLMEMORY = 8;   // Mysql with MEMORY tables on RAM. All data is lost on shutdown 
        const REDISBUFFER = 9;   // (internal use only) Redis Read/Write buffer, for low write mode
        const CASSANDRA = 10;    // Cassandra
    */
    let allowed_engines = [0,5,8] // array of allowed storage engines
    for (var feedid in selected_feeds) {
        engineid = parseInt(feeds[feedid].engine) // convert string to number
        // if feed selected and engineid is NOT found in allowed_engines
        if (selected_feeds[feedid] == true && !isNaN(engineid) && allowed_engines.indexOf(engineid) == -1) {
            return false
        }
    }
    return true
}
/**
 * display a message to the user in the delete feed modal
 *
 * restores the original message after delay
 *
 * @param string message text to show to user
 */
function updateFeedDeleteModalMessage(response){
    let message = response.message
    let success = response.success
    let $msg = $('#feedDelete-message')
    let cssClassName = success ? 'label-success' : 'label-important'

    $msg.stop().fadeOut(function(){
        $(this).text(message).removeClass('label-warning').addClass(cssClassName).fadeIn()
    })
    setTimeout(function(){
        $msg.stop().fadeOut(function(){
            $msg.text($msg.data('default')).removeClass(cssClassName).addClass('label-warning').fadeIn()
        })
    }, 3800)
}

/**
 * function call queue - clears previous interval if interrupted
 */
var updater;
function updaterStart(func, interval){
    clearInterval(updater);
    updater = null;
    if (interval > 0) updater = setInterval(func, interval);
}



/**
 * Enables/Disables the feed trim() feature based on selected feeds
 *
 * @return void
 */
function initTrim(){
    // get the most suitable start_time for all selected feeds
    if (isSelectionValidForTrim()) {
        let start_time = getEarliestStartTime()
        enableTrim(start_time)
    } else {
        disableTrim()
    }
}
/**
 * Allows feed(s) to be trimmed to a new start_date
 *
 * @param int start_time new timestamp to trim to
 * @return void
 */
function enableTrim(start_time){
    // populate the trim() date input with the feed's current start date
    showFeedStartDate(start_time)
    // make buttons under the trim date input react on click
    initRelativeStartDateButtons(start_time)

    // remove any styling the disableTrim() function created
    $('#trimContainer').attr('title','').removeClass('muted')//.show()
    .find('h4').addClass('text-info').removeClass('muted').end()
    .find('button,input').removeClass('disabled')
    .find('input').val('')
    
    // enable the confirm trim button
    $('#feedTrim-confirm')
    .unbind('click')
    .click(function(){
        $modal = $('#feedDeleteModal')
        let $input = $modal.find("#trim_start_time")
        let input_date_string = $input.val()
        // dont submit if nothing selected
        // convert uk dd/mm/yyyy h:m:s to RFC2822 date
        let start_date = new Date(input_date_string.replace( /(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})/, "$3-$2-$1T$4:$5:$6"))
        let isValidDate = !isNaN(start_date.getTime()) && input_date_string != ""
        // exit if supplied date not valid
        if (!isValidDate) {
            $('#trim_start_time_container').addClass('error')
            $input.focus()
            return false
        }else{
            if(confirm("<?php echo _("This is a new feature. Consider backing up your data before you continue. OK to continue?") ?>") == true ) {
                $('#trim_start_time_container').removeClass('error')
                // set to seconds from milliseconds
                let start_time = start_date.getTime()/1000
                $("#feedDelete-loader").fadeIn()
                // run the trim() function on all the selected feeds
                for (let feedid in selected_feeds) {
                    if (selected_feeds[feedid]) {
                        let response = feed.trim(feedid, start_time)
                        updateFeedDeleteModalMessage(response)
                        if (!response.success) {
                            break;
                        }
                    }
                }
                $("#feedDelete-loader").stop().fadeOut()
                update()
                updaterStart(update, 5000)
            }
        }
    })
}
/**
 * hide the trim feature
 *
 * @return void
 */
function disableTrim(){
    $('#trimContainer').attr('title','<?php echo _('"Trim" not available for this storage engine') ?>').addClass('muted')//.hide()
    .find('h4').removeClass('text-info').addClass('muted').end()
    .find('button,input').addClass('disabled')
    .find('input').val('')
    $('#feedTrim-confirm').unbind('click') // remove previous click event (if it exists)
}

/**
 * trigger off the modal overlay to display delete options
 * 
 * jQuery Event handler for the delete feed button
 * also shows items selected as well as a processlist warning
 */
$(".feed-delete").click(function(){
    $('#feedDeleteModal #deleteFeedText').show();
    $('#feedDeleteModal #deleteVirtualFeedText').hide();
    $('#feedDeleteModal').modal('show'); //show the delete modal

    // get the list of input processlists that write to feeds
    let feed_processes = getFeedProcess()
    let selected_feeds_inputs = {}
    for (i in selected_feeds){
        // if a selected feed has an associated process id then save it into an array
        if (selected_feeds[i] && typeof feed_processes[i] != 'undefined') {
            selected_feeds_inputs[i] = feed_processes[i]
        }
    }

    // show the selected feeds and any associated processList
    showSelectedFeeds(selected_feeds_inputs)

    initTrim()
    initClear()
});

    function isSelectionValidForClear(){
        /*
            const MYSQL = 0;
            const TIMESTORE = 1;     // Depreciated
            const PHPTIMESERIES = 2;
            const GRAPHITE = 3;      // Not included in core
            const PHPTIMESTORE = 4;  // Depreciated
            const PHPFINA = 5;
            const PHPFIWA = 6;
            const VIRTUALFEED = 7;   // Virtual feed, on demand post processing
            const MYSQLMEMORY = 8;   // Mysql with MEMORY tables on RAM. All data is lost on shutdown 
            const REDISBUFFER = 9;   // (internal use only) Redis Read/Write buffer, for low write mode
            const CASSANDRA = 10;    // Cassandra
        */
        let allowed_engines = [0,5,8] // array of allowed storage engines 
        for (var feedid in selected_feeds) {
            engineid = parseInt(feeds[feedid].engine) // convert string to number
            // if feed selected and engineid is NOT found in allowed_engines
            if (selected_feeds[feedid] == true && !isNaN(engineid) && allowed_engines.indexOf(engineid) == -1) {
                return false
            }
        }
        return true
    }
    function initClear(){
        // get the most suitable start_time for all selected feeds
        if (isSelectionValidForClear()) {
            enableClear()
        } else {
            disableClear()
        }
    }
    function enableClear(){
        // remove any disable styling
        $('#clearContainer').attr('title','').removeClass('muted')//.show()
        .find('h4').addClass('text-info').removeClass('muted').end()
        .find('button').removeClass('disabled')

        $("#feedClear-confirm")
        .unbind('click')
        .click(function(){
            if( confirm("<?php echo _("Are you sure you want to delete all the feed's data??") ?>") == true ){
                $modal = $('#feedDeleteModal')
                $("#feedDelete-loader").fadeIn();

                for (let feedid in selected_feeds) {
                    if (selected_feeds[feedid]) {
                        let response = feed.clear(feedid);
                        updateFeedDeleteModalMessage(response)
                        if (!response.success) {
                            break;
                        }
                    }
                }
                $("#feedDelete-loader").stop().fadeOut();
                update();
                updaterStart(update, 5000);
            }
        });
    }
    function disableClear(){
        $("#feedClear-confirm").unbind()

        $('#clearContainer').attr('title','<?php echo _('"Clear" not available for this storage engine') ?>').addClass('muted')//.hide()
        .find('h4').removeClass('text-info').addClass('muted').end()
        .find('button').addClass('disabled')
    }




  $("#feedDelete-confirm").click(function(){
    if( confirm("<?php echo _('Are you sure you want to delete?') ?>") == true) {
        for (let feedid in selected_feeds) {
            if (selected_feeds[feedid]) {
                let response = feed.remove(feedid)
                response = response ? response : {success:true, message: '<?php echo _("Feeds Deleted") ?>'}
                updateFeedDeleteModalMessage(response)
            }
        }
        setTimeout(function(){
            update();
            updaterStart(update, 5000);
            $('#feedDeleteModal').modal('hide')
        }, 5000)
    }
  });

  $("#refreshfeedsize").click(function(){
    $.ajax({ url: path+"feed/updatesize.json", async: true, success: function(data){ update(); alert("<?php echo _('Total size of used space for feeds:'); ?>" + list_format_size(data)); } });
  });

  // ---------------------------------------------------------------------------------------------
  // ---------------------------------------------------------------------------------------------
  function feed_selection() 
  {
	    selected_feeds = {};
	    var num_selected = 0;
	    $(".feed-select").each(function(){
          var feedid = $(this).attr("feedid");
	        selected_feeds[feedid] = $(this)[0].checked;
	        if (selected_feeds[feedid]==true) num_selected += 1;
      });
	    
	    if (num_selected>0) {
	        $(".feed-delete").show();
          $(".feed-download").show();
          $(".feed-graph").show();
	    } else {
          $(".feed-delete").hide();
          $(".feed-download").hide();
	        $(".feed-graph").hide();
	    }
	    
	    if (num_selected==1) {
          // There should only ever be one feed that is selected here:
          var feedid = 0; for (var z in selected_feeds) { if (selected_feeds[z]) feedid = z; }
          // Only show feed process button for Virtual feeds
	        if (feeds[feedid].engine==7) $(".feed-process").show(); else $(".feed-process").hide();
	    
	        $(".feed-edit").show();
	    } else {
		      $(".feed-edit").hide();
	    }
  }
  
  
// -------------------------------------------------------------------------------------------------------
// Interface responsive
//
// The following implements the showing and hiding of the device fields depending on the available width
// of the container and the width of the individual fields themselves. It implements a level of responsivness
// that is one step more advanced than is possible using css alone.
// -------------------------------------------------------------------------------------------------------
var show_size = true;
var show_engine = true;
var show_public = true;
var show_select = true;
var show_time = true;
var show_value = true;

$(window).resize(function(){ resize(); });

function resize() 
{
    show_size = true;
    show_engine = true;
    show_public = true;
    show_select = true;
    show_time = true;
    show_value = true;
    show_start_time = true;

    $(".node-feed").each(function(){
         var node_feed_width = $(this).width();
         if (node_feed_width>0) {
             var w = node_feed_width-20;
             
             var tw = 0;
             tw += $(this).find(".name").width();

             tw += $(this).find(".select").width();
             if (tw>w) show_select = false;
             
             tw += $(this).find(".value").width();
             if (tw>w) show_value = false;
             
             tw += $(this).find(".time").width();
             if (tw>w) show_time = false;   

             tw += $(this).find(".public").width();
             if (tw>w) show_public = false;
             
             tw += $(this).find(".engine").width();
             if (tw>w) show_engine = false;
              
             tw += $(this).find(".size").width();
             if (tw>w) show_size = false;
             
             tw += $(this).find(".start_time").width();
             if (tw>w) show_start_time = false;
         }
    });
    
    if (show_select) $(".select").show(); else $(".select").hide();
    if (show_time) $(".time").show(); else $(".time").hide();
    if (show_value) $(".value").show(); else $(".value").hide();
    if (show_public) $(".public").show(); else $(".public").hide();
    if (show_engine) $(".engine").show(); else $(".engine").hide();
    if (show_size) $(".size").show(); else $(".size").hide();
}

function autowidth(element,padding) {
    var mw = 0;
    $(element).each(function(){
        var w = $(this).width();
        if (w>mw) mw = w;
    });
    
    $(element).width(mw+padding);
    return mw;
}

  
// Calculate and color updated time
function list_format_updated(time) {
  time = time * 1000;
  var servertime = (new Date()).getTime();// - table.timeServerLocalOffset;
  var update = (new Date(time)).getTime();

  var secs = (servertime-update)/1000;
  var mins = secs/60;
  var hour = secs/3600;
  var day = hour/24;

  var updated = secs.toFixed(0) + "s";
  if ((update == 0) || (!$.isNumeric(secs))) updated = "n/a";
  else if (secs< 0) updated = secs.toFixed(0) + "s"; // update time ahead of server date is signal of slow network
  else if (secs.toFixed(0) == 0) updated = "now";
  else if (day>7) updated = "inactive";
  else if (day>2) updated = day.toFixed(1)+" days";
  else if (hour>2) updated = hour.toFixed(0)+" hrs";
  else if (secs>180) updated = mins.toFixed(0)+" mins";

  secs = Math.abs(secs);
  var color = "rgb(255,0,0)";
  if (secs<25) color = "rgb(50,200,50)"
  else if (secs<60) color = "rgb(240,180,20)"; 
  else if (secs<(3600*2)) color = "rgb(255,125,20)"

  return "<span style='color:"+color+";'>"+updated+"</span>";
}

// Format value dynamically 
function list_format_value(value) {
  if (value == null) return 'NULL';
  value = parseFloat(value);
  if (value>=1000) value = parseFloat((value).toFixed(0));
  else if (value>=100) value = parseFloat((value).toFixed(1));
  else if (value>=10) value = parseFloat((value).toFixed(2));
  else if (value<=-1000) value = parseFloat((value).toFixed(0));
  else if (value<=-100) value = parseFloat((value).toFixed(1));
  else if (value<10) value = parseFloat((value).toFixed(2));
  return value;
}

function list_format_size(bytes) {
  if (!$.isNumeric(bytes)) {
    return "n/a";
  } else if (bytes<1024) {
    return bytes+"B";
  } else if (bytes<1024*100) {
    return (bytes/1024).toFixed(1)+"KB";
  } else if (bytes<1024*1024) {
    return Math.round(bytes/1024)+"KB";
  } else if (bytes<=1024*1024*1024) {
    return Math.round(bytes/(1024*1024))+"MB";
  } else {
    return (bytes/(1024*1024*1024)).toFixed(1)+"GB";
  }
}

// ---------------------------------------------------------------------------------------------
// Virtual Feed feature
// ---------------------------------------------------------------------------------------------
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

$(".feed-process").click(function() {
    // There should only ever be one feed that is selected here:
    var feedid = 0; for (var z in selected_feeds) { if (selected_feeds[z]) feedid = z; }
    var contextid = feedid;
    var contextname = "";
    if (feeds[feedid].name != "") contextname = feeds[feedid].tag + " : " + feeds[feedid].name;
    else contextname = feeds[feedid].tag + " : " + feeds[feedid].id;    
    var processlist = processlist_ui.decode(feeds[feedid].processList); // Feed process list
    processlist_ui.load(contextid,processlist,contextname,null,null); // load configs
 });

$("#save-processlist").click(function (){
    var result = feed.set_process(processlist_ui.contextid,processlist_ui.encode(processlist_ui.contextprocesslist));
    if (result.success) { processlist_ui.saved(table); } else { alert('ERROR: Could not save processlist. '+result.message); }
}); 

// ---------------------------------------------------------------------------------------------
// Export feature
// ---------------------------------------------------------------------------------------------
$(".feed-download").click(function(){
    var ids = [];
	  for (var feedid in selected_feeds) {
		    if (selected_feeds[feedid]==true) ids.push(parseInt(feedid));
	  }
	  
	  $("#export").attr('feedcount',ids.length);
	  calculate_download_size(ids.length);
	  
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

now = new Date();
today = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 00, 00);
var picker1 = $('#datetimepicker1').data('datetimepicker');
var picker2 = $('#datetimepicker2').data('datetimepicker');
picker1.setLocalDate(today);
picker2.setLocalDate(today);
picker1.setEndDate(today);
picker2.setStartDate(today);

$('#datetimepicker1').on("changeDate", function (e) {
    $('#datetimepicker2').data("datetimepicker").setStartDate(e.date);
});

$('#datetimepicker2').on("changeDate", function (e) {
    $('#datetimepicker1').data("datetimepicker").setEndDate(e.date);
});

$('#export-interval, #export-timeformat').on('change', function(e) {
    $("#export-timezone-offset").prop("disabled", $("#export-timeformat").prop('checked'));
    calculate_download_size($("#export").attr('feedcount')); 
});

$('#datetimepicker1, #datetimepicker2').on('changeDate', function(e) {
    calculate_download_size($("#export").attr('feedcount')); 
});

$("#export").click(function()
{
    var ids = [];
	  for (var feedid in selected_feeds) {
		    if (selected_feeds[feedid]==true) ids.push(parseInt(feedid));
	  }

    var export_start = parse_timepicker_time($("#export-start").val());
    var export_end = parse_timepicker_time($("#export-end").val());
    var export_interval = $("#export-interval").val();
    var export_timezone_offset = parseInt($("#export-timezone-offset").val());
    var export_timeformat = ($("#export-timeformat").prop('checked') ? 1 : 0);
    if (export_timeformat) { export_timezone_offset = 0; }

    if (!export_start) {alert("<?php echo _('Please enter a valid start date.'); ?>"); return false; }
    if (!export_end) {alert("<?php echo _('Please enter a valid end date.'); ?>"); return false; }
    if (export_start>=export_end) {alert("<?php echo _('Start date must be further back in time than end date.'); ?>"); return false; }
    if (export_interval=="") {alert("<?php echo _('Please select interval to download.'); ?>"); return false; }

    var downloadlimit = <?php global $feed_settings; echo $feed_settings['csvdownloadlimit_mb']; ?>;
    var downloadsize = calculate_download_size(ids.length);
    
    if (ids.length>1) {
        url = path+"feed/csvexport.json?ids="+ids.join(",")+"&start="+(export_start+(export_timezone_offset))+"&end="+(export_end+(export_timezone_offset))+"&interval="+export_interval+"&timeformat="+export_timeformat+"&name="+ids.join("_");
    } else {
        url = path+"feed/csvexport.json?id="+ids.join(",")+"&start="+(export_start+(export_timezone_offset))+"&end="+(export_end+(export_timezone_offset))+"&interval="+export_interval+"&timeformat="+export_timeformat+"&name="+ids.join("_");
    }

    if (downloadsize>(downloadlimit*1048576)) {
        var r = confirm("<?php echo _('Estimated download file size is large.'); ?>\n<?php echo _('Server could take a long time or abort depending on stored data size.'); ?>\n<?php echo _('Limit is'); ?> "+downloadlimit+"MB.\n\n<?php echo _('Try exporting anyway?'); ?>");
        if (!r) return false;
    }
    window.open(url);
});

function calculate_download_size(feedcount){

    var export_start = parse_timepicker_time($("#export-start").val());
    var export_end = parse_timepicker_time($("#export-end").val());
    var export_interval = $("#export-interval").val();
    var export_timeformat_size = ($("#export-timeformat").prop('checked') ? 20 : 11); // bytes per timestamp
    var export_data_size = 7;                                                         // avg bytes per data
    
    var downloadsize = 0;
    if (!(!$.isNumeric(export_start) || !$.isNumeric(export_end) || !$.isNumeric(export_interval) || export_start > export_end )) { 
        downloadsize = ((export_end - export_start) / export_interval) * (export_timeformat_size + export_data_size) * feedcount; 
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

/**
 * alter the Number primitive to include a new method to pad out numbers with zeros
 * @param int size - number of characters to fill with zeros
 * @return string
 */
Number.prototype.pad = function(size) {
  var s = String(this);
  while (s.length < (size || 2)) {s = "0" + s;}
  return s;
}
</script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/moment-with-locales.js"></script>
<script>
/**
 * uses moment.js to format to local time 
 * @param int time unix epoc time
 * @param string format moment.js date formatting options
 * @see date format options - https://momentjs.com/docs/#/displaying/
 */
function format_time(time,format){
    time = time || (new Date().valueOf() / 1000)
    format = format || ''
    formatted_date = moment.unix(time).format(format)
    return formatted_date
}
</script>

