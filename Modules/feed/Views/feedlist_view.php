<?php
    global $path, $feedviewpath;
    if (!isset($feedviewpath)) $feedviewpath = "vis/auto?feedid=";
?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

<style>
.node {margin-bottom:10px;}

.node-info {
    padding:10px;
    background-color:#ddd;
    font-weight:bold;
    cursor:pointer;
}

.node-feeds {
    padding: 0px 5px 5px 5px;
    background-color:#ddd;
}

.node-feed {
    background-color:#f0f0f0;
    border-bottom:1px solid #fff;
    border-left:2px solid #f0f0f0;
    cursor:pointer;
    height:41px;
    padding-right:10px;
}

.node-feed:hover{ border-left:2px solid #44b3e2; }

.node-feed .select {
    width:20px;
    padding: 10px;
    float:left;
    text-align:center;
}

.node-feed .name {
    padding-top:10px;
    float:left;
}

.node-feed .time {
    width:60px;
    padding-top:10px;
    float:right;
    text-align:center;
}

.node-feed .value {
    width:60px;
    padding-top:10px;
    float:right;
    text-align:center;
}

.node-feed .view {
    width:40px;
    padding-top:10px;
    float:right;
    text-align:center;
}

input[type="checkbox"] { margin:0px; }
#feed-selection { width:80px; }
.controls { margin-bottom:10px; }
#feeds-to-delete { font-style:italic; }

</style>

<div id="apihelphead" style="float:right;"><a href="<?php echo $path; ?>feed/api"><?php echo _('Feed API Help'); ?></a></div>
<div id="localheading"><h3><?php echo _('Feeds'); ?></h3></div>

<div class="controls">
	<div class="input-prepend" style="margin-bottom:0px">
		<span class="add-on">Select</span>
		<select id="feed-selection">
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

<script>
  var path = "<?php echo $path; ?>";
  var feedviewpath = "<?php echo $feedviewpath; ?>";
  
  var feeds = {};
  var selected_feeds = {};
  var nodes_display = {};

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
              out += "<div class='node-info'>"+node+"</div>";
              out += "<div class='node-feeds "+visible+"' node='"+node+"'>";
              
              for (var feed in nodes[node]) {
				  var feedid = nodes[node][feed].id;
                  out += "<div class='node-feed' feedid="+feedid+">";
                  out += "<div class='select'><input class='feed-select' type='checkbox' feedid='"+feedid+"'/></div>";
                  out += "<div class='name'>"+nodes[node][feed].name+"</div>";
                  out += "<div class='value'>"+list_format_value(nodes[node][feed].value)+"</div>";
                  out += "<div class='time'>"+list_format_updated(nodes[node][feed].time)+"</div>";
                  out += "</div>";
              }
              
              out += "</div>";
              out += "</div>";
          }
          $("#table").html(out);
      }});
  }
  
  $("#table").on("click",".node-info",function() {
      var node = $(this).html();
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

  $("#table").on("click",".feed-select",function(e) {
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
</script>
