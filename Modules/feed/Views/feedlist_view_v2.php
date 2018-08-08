<?php
    global $path, $feedviewpath;
    if (!isset($feedviewpath)) $feedviewpath = "vis/auto?feedid=";
?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

<link href="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script type="text/javascript" src="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js"></script>

<style>

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

.node-info {
    height:40px;
    background-color:#ddd;
    cursor:pointer;
}
.node-name {
    padding: 5px;
    font-weight:bold;
}
.node-feeds{
    padding: 3px 5px 4px 4px;
    background-color:#ddd;
}
.node-feed {
    background-color:#f0f0f0;
    border-bottom:1px solid #fff;
    border-left:2px solid transparent;
    cursor: pointer;
    transition: background .3s ease-in;
    padding: .2em 0
}
.node-feed:hover{ 
    border-left:2px solid #44b3e2;
    background-color:#F3F3F3;
}
#feeds-to-delete { font-style:italic; }
.checkbox-large{ 
    transform: scale(1.4)!important;
    margin:0 .5em!important;
}
.controls { margin-bottom:10px; }
.controls select { width: auto!important; }
.node-feed [class*="span"] { line-height:2; }


/* override old bootstrap mobile grid */
/* would not required in recent versions of bootstrap */
@media (max-width: 767px) {
    /* only has 3 columns - 3,4 & 5 are hidden */
    .node-feed [class*="span"]{ float:left!important; }
    .node-feed>[class*="span"]{ width: auto;}
    .node-feed>[class*="span"]:nth-child(1){width: 40%;}
    .node-feed>[class*="span"]:nth-child(1) [class*="span"]{ width: auto; }
    /* global/private */
    .node-feed>[class*="span"]:nth-child(2){width: 20%;}
    /* value & unit */
    .node-feed>[class*="span"]:nth-child(6){width: 40%;}
    /* sub column splits */
    .node-feed>[class*="span"] [class*="span"]{ width: 50%; }

}
/* extra small devices */
@media (max-width: 464px) {
    /* additional responsive show/hide for smaller devices */
    .hidden-phone-small{  display:none!important }

    /* only has 3 columns - 3,4 & 5 are hidden */
    .node-feed>[class*="span"]:nth-child(1){width: 50%;}
    .node-feed>[class*="span"]:nth-child(1) [class*="span"]:first-child{ width: 25%; } /* shrink the tickbox column */
    /* global/private */
    .node-feed>[class*="span"]:nth-child(2){width: 10%;} /* shrink the global/private column */
    /* value & unit */
    .node-feed>[class*="span"]:nth-child(6) [class*="span"]:first-child{width: 70%;} /* grow the value column */
    .node-feed>[class*="span"]:nth-child(6) [class*="span"]:last-child{width: 30%;} /* shrink the unit column */
}


@media (min-width: 768px) {
    .container-fluid { padding: 0px 20px 0px 20px; }
}

@media (max-width: 768px) {
    body {padding:0};
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
        <h3 id="feedDeleteModalLabel"><?php echo _('Delete feed'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Deleting a feed is permanent.'); ?></p>
        <br>
        <div id="deleteFeedText"><?php echo _('If you have Input Processlist processors that use this feed, after deleting it, review that process lists or they will be in error, freezing other Inputs. Also make sure no Dashboards use the deleted feed.'); ?></div>
        <div id="deleteVirtualFeedText"><?php echo _('This is a Virtual Feed, after deleting it, make sure no Dashboard continue to use the deleted feed.'); ?></div>
        <br><br>
        <p><?php echo _('Are you sure you want to delete:'); ?></p>
		<div id="feeds-to-delete"></div>
        <div id="feedDelete-loader" class="ajax-loader" style="display:none;"></div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="feedDelete-confirm" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
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

  // @todo: match this with the user's preferences
  user.timeFormat = 'uk';
  user.dateFormat = 'uk';

  update();
//   setInterval(update,5000);
  
function update() {
    $.ajax({ url: path+"feed/list.json", dataType: 'json'})
    .done(function(data) {
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
          
        for (var node in nodes) {
            var visible = "hide"; if (nodes_display[node]) visible = "";

            out += "<div class='node'>";
            out += "<div class='node-info' node='"+node+"'>";
            out += "<div class='node-name'>"+node+":</div>";
            out += "</div>";

            out += "<div class='node-feeds "+visible+"' node='"+node+"'>";
            for (var feed in nodes[node]) {
                var feedid = nodes[node][feed].id;
                out += "<div class='row-fluid node-feed' feedid="+feedid+">";
                var checked = ""; if (selected_feeds[feedid]) checked = "checked";
                out += '<div class="span3">';
                out += '  <div class="row-fluid">';
                out += "    <div class='span2 select'><input class='feed-select checkbox-large' type='checkbox' feedid='"+feedid+"' "+checked+"/></div>";
                out += "    <div class='span10 name' title='ID:"+feedid+"'>"+nodes[node][feed].name+"</div>";
                out += "  </div>";
                out += "</div>";

                var publicfeed = "<i class='icon-lock'></i>"

                if (nodes[node][feed]['public']==1) publicfeed = "<i class='icon-globe'></i>";
                out += "<div class='span1 public'>"+publicfeed+"</div>";
                out += "<div class='span2 hidden-phone engine'>"+feed_engines[nodes[node][feed].engine]+"</div>";
                out += "<div class='span1 hidden-phone size'>"+list_format_size(nodes[node][feed].size)+"</div>";
                out += '<div class="span2 hidden-phone start_time" title="'+formatTimestamp(nodes[node][feed].start_time)+'">'+formatTimestamp(nodes[node][feed].start_time,'d/m/y')+"</div>";

                out += "<div class='span3 pull-right'>";
                out += "  <div class='row-fluid'>";
                out += '    <div class="span6">';
                out += '      <div class="row-fluid">';
                out += "        <div class='span6 value text-right'>"+list_format_value(nodes[node][feed].value)+"</div>";
                out += "        <div class='span6 unit'>"+nodes[node][feed].unit+"</div>";
                out += "      </div>";
                out += "    </div>";
                out += "    <div class='span6 time hidden-phone-small'>"+list_format_updated(nodes[node][feed].time)+"</div>";
                out += "  </div>";
                out += "</div>";
                out += "</div>";
            }

            out += "</div>";
            out += "</div>";
        }
        $("#table").html(out);
        
        resize();
    })
}
    /**
    * format unix timestamp to date string
    *
    * @todo moment.js to offer better multilingual js date formatting offers
    * @param int timestamp unix timestamp
    * @param string format name for the format to output to
    */
    function formatTimestamp(timestamp, format){
        // set fromat to empty string if not passed
        // convert unix timestamp to js date (milliseconds)
        date = new Date(timestamp*1000)
        if (isNaN(date.getTime())) return timestamp
        // rebuild the date string from the new date object
        let Y = date.getFullYear(),
            y = date.getFullYear().toString().substr(-2, 2),
            m = (date.getMonth()+1).pad(2),
            d = date.getDate().pad(2),
            h = date.getHours().pad(2),
            i = date.getMinutes().pad(2),
            s = date.getSeconds().pad(2)

        let dateFormat = user.dateFormat || 'uk',
            timeFormat = user.timeFormat || 'uk',
            formattedDate = ''

        switch(dateFormat) {
            case 'us':
                newDate = [m,d,Y].join('/')
                break
            case 'iso':
                newDate = [d,m,Y].join('/')
                break
            case 'uk':
            default:
                newDate = [Y,m,d].join('/')
        }

        switch(timeFormat) {
            default : 
                newTime = [h,i,s].join(':')
        }
        
        formattedDate = [newDate, newTime].join(' ')

        // if format passed override output to match format
        switch(format) {
            case 'm/d/Y': // USA
                // MM/DD/YYYY
                formattedDate = [m,d,Y].join('/')
            case 'Y-m-d': // ISO
                // YYYY-MM-DD
                formattedDate = [Y,m,d].join('-')
            case 'd/m/y':
                // DD/MM/YY
                formattedDate = [d,m,y].join('/')
                break;
        }
        
        return formattedDate
    }

    $("#table").on("click",".node-info",function() {
        var node = $(this).attr("node");
        if (nodes_display[node]) {
            $(".node-feeds[node='"+node+"']").hide();
            nodes_display[node] = false;
        } else {
            $(".node-feeds[node='"+node+"']").show();
            nodes_display[node] = true;
        }
    });

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

    $("#table").on("click",".node-feed",function() {
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
  });

  $("#feed-edit-save").click(function(){
      var feedid = 0;
      // There should only ever be one feed that is selected here:
      for (var z in selected_feeds) { if (selected_feeds[z]) feedid = z; }
      
      var publicfeed = false;
      if ($("#feed-public")[0].checked) publicfeed = true;
      
      var fields = {
          tag: $("#feed-node").val(), 
          name: $("#feed-name").val(),
          public: publicfeed
      };
      
      $.ajax({ url: path+"feed/set.json?id="+feedid+"&fields="+JSON.stringify(fields), dataType: 'json', async: true, success: function(data) {
          update();
          $('#feedEditModal').modal('hide');
      }});
  });

  // ---------------------------------------------------------------------------------------------
  // DELETE FEED
  // ---------------------------------------------------------------------------------------------
  $(".feed-delete").click(function(){
      $('#feedDeleteModal #deleteFeedText').show();
      $('#feedDeleteModal #deleteVirtualFeedText').hide();
      $('#feedDeleteModal').modal('show');
	    var out = "";
	    for (var feedid in selected_feeds) {
		      if (selected_feeds[feedid]==true) out += feeds[feedid].tag+":"+feeds[feedid].name+"<br>";
	    }
	    $("#feeds-to-delete").html(out);
  });

  $("#feedDelete-confirm").click(function(){
	    for (var feedid in selected_feeds) {
          if (selected_feeds[feedid]==true) feed.remove(feedid);
	    }
	    update();
      $('#feedDeleteModal').modal('hide');
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

    $(".node-feedx").each(function(){
         var node_feed_width = $(this).width();
         if (node_feed_width>0) {
             var w = node_feed_width-10;
             
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
         }
    });
    
    // if (show_select) $(".select").show(); else $(".select").hide();
    // if (show_time) $(".time").show(); else $(".time").hide();
    // if (show_value) $(".value").show(); else $(".value").hide();
    // if (show_public) $(".public").show(); else $(".public").hide();
    // if (show_engine) $(".engine").show(); else $(".engine").hide();
    // if (show_size) $(".size").show(); else $(".size").hide();
    
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

