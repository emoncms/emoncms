<?php
    global $path, $feedviewpath;
    if (!isset($feedviewpath)) $feedviewpath = "vis/auto?feedid=";
?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

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
  font-weight:bold;
	float:left;
	padding:10px;
	padding-right:5px;
}


.node-feeds {
    padding: 0px 5px 5px 5px;
    background-color:#ddd;
}

.node-feed {
    background-color:#f0f0f0;
    border-bottom:1px solid #fff;
    border-left:2px solid #f0f0f0;
    height:41px;
}
.node-feed:hover{ border-left:2px solid #44b3e2; }

.node-feed .select {
    display:inline-block;
    padding-top: 10px;
    text-align:center;
}

.node-feed .name {
    display:inline-block;
}

.node-feed .public {
    display:inline-block;
    text-align:center;
}

.node-feed .size {
    display:inline-block;
    text-align:center;
}

.node-feed .engine {
    display:inline-block;
    text-align:center;
}

.node-feed-right {
    float:right;
}

.node-feed .time {
    display:inline-block;
    padding-top:10px;
    text-align:center;
}

.node-feed .value {
    display:inline-block;
    padding-top:10px;
    text-align:center;
}

.ipad {
    padding-left:10px;
}

input[type="checkbox"] { margin:0px; }
#feed-selection { width:80px; }
.controls { margin-bottom:10px; }
#feeds-to-delete { font-style:italic; }

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
	
	<button id="classic-view" class="btn" style="float:right">Classic</button>
</div>

<div id="table"></div>
<!--------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- FEED EDIT MODAL                                                                                                                               -->
<!--------------------------------------------------------------------------------------------------------------------------------------------------->
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
<!--------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- FEED DELETE MODAL                                                                                                                             -->
<!--------------------------------------------------------------------------------------------------------------------------------------------------->
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
<!--------------------------------------------------------------------------------------------------------------------------------------------------->
<script>
  var path = "<?php echo $path; ?>";
  var feedviewpath = "<?php echo $feedviewpath; ?>";
  
  var feeds = {};
  var selected_feeds = {};
  var nodes_display = {};
  
  var feed_engines = ['MYSQL','TIMESTORE','PHPTIMESERIES','GRAPHITE','PHPTIMESTORE','PHPFINA','PHPFIWA','VIRTUAL','MEMORY','REDISBUFFER','CASSANDRA'];

  update();
  //setInterval(update,5000);
  
  function update() 
  {
  
      $.ajax({ url: path+"feed/list.json", dataType: 'json', async: true, success: function(data) {
      
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
                  out += "<div class='node-feed' feedid="+feedid+">";
                  out += "<div class='select'><div class='ipad'><input class='feed-select' type='checkbox' feedid='"+feedid+"'/></div></div>";
                  out += "<div class='name'><div class='ipad'>"+nodes[node][feed].name+"</div></div>";
                  
                  var publicfeed = "<i class='icon-lock'></i>"
                  if (nodes[node][feed].public) publicfeed = "<i class='icon-globe'></i>";
                  
                  out += "<div class='public'><div class='ipad'>"+publicfeed+"</div></div>";
                  out += "<div class='engine'><div class='ipad'>"+feed_engines[nodes[node][feed].engine]+"</div></div>";
                  out += "<div class='size'><div class='ipad'>"+list_format_size(nodes[node][feed].size)+"</div></div>";
                  
                  out += "<div class='node-feed-right'>";
                  out += "<div class='value'>"+list_format_value(nodes[node][feed].value)+"</div>";
                  out += "<div class='time'>"+list_format_updated(nodes[node][feed].time)+"</div>";
                  out += "</div>";
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
  
  $("#classic-view").click(function(){ window.location = path+"feed/list-classic"; });
  
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
      $("#feed-public")[0].checked = feeds[feedid].public;
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

    $(".node-feed").each(function(){
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
</script>

