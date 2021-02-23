<?php global $path, $settings; ?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/moment.min.js"></script>
<script>var _user = {lang:"<?php echo $_SESSION['lang']; ?>"};</script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/user_locale.js"></script>
<?php require "Modules/feed/Views/translation_misc.php"; ?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/responsive-linked-tables.js"></script>

<link href="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script src="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js"></script>

<script type="text/javascript" src="<?php echo $path; ?>Lib/misc/autocomplete.js"></script>
<link rel="stylesheet" href="<?php echo $path; ?>Lib/misc/autocomplete.css">
<link rel="stylesheet" href="<?php echo $path; ?>Modules/feed/Views/feedlist.css">

<div id="feed-header">
    <span id="api-help" style="float:right"><a href="<?php echo $path; ?>feed/api"><?php echo _('Feed API Help'); ?></a></span>
    <h3><?php echo _('Feeds'); ?></h3>
</div>

<div class="controls" data-spy="affix" data-offset-top="100">
    <button id="expand-collapse-all" class="btn" title="<?php echo _('Collapse') ?>" data-alt-title="<?php echo _('Expand') ?>"><i class="icon icon-resize-small"></i></button>
    <button id="select-all" class="btn" title="<?php echo _('Select all') ?>" data-alt-title="<?php echo _('Unselect all') ?>"><i class="icon icon-check"></i></button>
    <button class="btn feed-edit hide" title="<?php echo _('Edit') ?>"><i class="icon-pencil"></i></button>
    <button class="btn feed-delete hide" title="<?php echo _('Delete') ?>"><i class="icon-trash" ></i></button>
    <button class="btn feed-download hide" title="<?php echo _('Download') ?>"><i class="icon-download"></i></button>
    <button class="btn feed-graph hide" title="<?php echo _('Graph view') ?>"><i class="icon-eye-open"></i></button>
    <button class="btn feed-process hide" title="<?php echo _('Process config') ?>"><i class="icon-wrench"></i></button>
</div>

<div id="table" class="feed-list"></div>

<div id="feed-none" class="alert alert-block hide">
    <h4 class="alert-heading"><?php echo _('No feeds created'); ?></h4>
    <p><?php echo _('Feeds are where your monitoring data is stored. The route for creating storage feeds is to start by creating inputs (see the inputs tab). Once you have inputs you can either log them straight to feeds or if you want you can add various levels of input processing to your inputs to create things like daily average data or to calibrate inputs before storage. Alternatively you can create Virtual feeds, this is a special feed that allows you to do post processing on existing storage feeds data, the main advantage is that it will not use additional storage space and you may modify post processing list that gets applyed on old stored data. You may want the next link as a guide for generating your request: '); ?><a href="api"><?php echo _('Feed API helper'); ?></a></p>
</div>

<div id="feed-footer" class="hide">
    <button id="refreshfeedsize" class="btn btn-small" ><i class="icon-refresh" ></i>&nbsp;<?php echo _('Refresh feed size'); ?></button>
    <button id="addnewvirtualfeed" class="btn btn-small" data-toggle="modal" data-target="#newFeedNameModal"><i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New virtual feed'); ?></button>
</div>
<div id="feed-loader" class="ajax-loader"></div>

<?php require "Modules/feed/Views/feed_edit_modal.php"; ?>
<?php require "Modules/feed/Views/feed_export_modal.php"; ?>
<?php require "Modules/feed/Views/feed_delete_modal.php"; ?>
<?php require "Modules/feed/Views/virtualfeed_modal.php"; ?>
<?php require "Modules/process/Views/process_ui.php"; ?>
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<script>

var feedviewpath = "<?php echo $settings['interface']['feedviewpath']; ?>";

var feeds = {};
var nodes = {};
var selected_feeds = {};
var local_cache_key = 'feed_nodes_display';
var nodes_display = docCookies.hasItem(local_cache_key) ? JSON.parse(docCookies.getItem(local_cache_key)) : {};
var feed_engines = ['MYSQL','TIMESTORE','PHPTIMESERIES','GRAPHITE','PHPTIMESTORE','PHPFINA','PHPFIWA','VIRTUAL','MEMORY','REDISBUFFER','CASSANDRA'];

// auto refresh
update_feed_list();
setInterval(update_feed_list,5000);

var firstLoad = true;
function update_feed_list() {
    $.ajax({ url: path+"feed/list.json", dataType: 'json', async: true, success: function(data) {
    
        if (data.message!=undefined && data.message=="Username or password empty") {
            window.location.href = "/";
            return false;
        }
    
        // Show/hide no feeds alert
        $('#feed-loader').hide();
        if (data.length == 0){
            //$("#feed-header").hide();
            $("#feed-footer").hide();
            $("#feed-none").show();
        } else {
            //$("#feed-header").show();
            $("#feed-footer").show();
            $("#feed-none").hide();
        }
        feeds = {};
        for (var z in data) feeds[data[z].id] = data[z];
        nodes = {};
        for (var z in feeds) {
            var node = feeds[z].tag;
            if (nodes[node]==undefined) nodes[node] = [];

            if (nodes_display[node]==undefined) nodes_display[node] = true;
            nodes[node].push(feeds[z]);
        }
        if (firstLoad && Object.keys(nodes).length > 1 && Object.keys(nodes_display).length == 0) {
            for (var node in nodes) {
                // collapse all if more than one node and not cached in cookie
                nodes_display[node] = false;
            }
        }
        // cache state in cookie
        if(firstLoad) docCookies.setItem(local_cache_key, JSON.stringify(nodes_display));
        firstLoad = false;
        var out = "";
        
        // get node overview
        var node_size = {},
            node_time = {};

        for (let n in nodes) {
            let node = nodes[n];
            node_size[n] = 0;
            node_time[n] = 0;
            for (let f in node) {
                let feed = node[f];
                node_size[n] += Number(feed.size);
                node_time[n] = parseInt(feed.engine) !== 7 && feed.time > node_time[n] ? feed.time : node_time[n];
            }
        }
        // todo: remove the requirement of a fixed list. Load from api?
        var datatypes = {
            1: _('Realtime'),
            2: _('Daily')
        }
        // display nodes and feeds
        var counter = 0;
        for (var node in nodes) {
            counter ++;
            isCollapsed = !nodes_display[node];
            out += '<div class="node accordion ' + nodeIntervalClass(nodes[node]) + '">';
            out += '    <div class="node-info accordion-toggle thead'+(isCollapsed ? ' collapsed' : '')+'" data-toggle="collapse" data-target="#collapse'+counter+'">'
            out += '      <div class="select text-center has-indicator" data-col="B"><span class="icon-chevron-'+(isCollapsed ? 'right' : 'down')+' icon-indicator"></span></div>';
            out += '      <h5 class="name" data-col="A">'+node+':</h5>';
            out += '      <div class="public" class="text-center" data-col="E"></div>';
            out += '      <div class="engine" data-col="G"></div>';
            out += '      <div class="size text-center" data-col="H">'+list_format_size(node_size[node])+'</div>';
            out += '      <div class="processlist" data-col="F"></div>';
            out += '      <div class="node-feed-right pull-right">';
            out += '        <div class="value" data-col="C"></div>';
            out += '        <div class="time" data-col="D">'+list_format_updated(node_time[node])+'</div>';
            out += '      </div>';
            out += '    </div>';
            
            out += "<div id='collapse"+counter+"' class='node-feeds collapse tbody "+( !isCollapsed ? 'in':'' )+"' data-node='"+node+"'>";
            
            for (var feed in nodes[node]) {
                var feed = nodes[node][feed];
                var feedid = feed.id;
                var datatype = datatypes[feed.datatype] || '';

                var title_lines = [feed.name,
                                  '-----------------------',
                                  _('Tag') + ': ' + feed.tag,
                                  _('Feed ID') + ': ' + feedid,
                                  _('Datatype') + ': ' + datatype]
                
                if(feed.engine == 5) {
                    title_lines.push(_('Feed Interval')+": "+(feed.interval||'')+'s')
                }
                var processListHTML = '';
                if(feed.processList!=undefined && feed.processList.length > 0){
                    processListHTML = processlist_ui ? processlist_ui.drawpreview(feed.processList, feed) : '';
                }

                // show the start time if available
                if(feed.start_time > 0) {
                    title_lines.push(_('Feed Start Time')+": "+feed.start_time);
                    title_lines.push(format_time(feed.start_time,'LL LTS')+" UTC");
                }

                row_title = title_lines.join("\n");

                out += "<div class='" + feedListItemIntervalClass(feed) + " node-feed feed-graph-link' feedid="+feedid+" title='"+row_title+"' data-toggle='tooltip'>";
                var checked = ""; if (selected_feeds[feedid]) checked = "checked";
                out += "<div class='select text-center' data-col='B'><input class='feed-select' type='checkbox' feedid='"+feedid+"' "+checked+"></div>";
                out += "<div class='name' data-col='A'>"+feed.name+"</div>";
                
                var publicfeed = "<i class='icon-lock'></i>";
                if (feed['public']==1) publicfeed = "<i class='icon-globe'></i>";
                
                out += '<div class="public text-center" data-col="E">'+publicfeed+'</div>';
                out += '  <div class="engine" data-col="G">'+feed_engines[feed.engine]+'</div>';
                out += '  <div class="size text-center" data-col="H">'+list_format_size(feed.size)+'</div>';
                out += '  <div class="processlist" data-col="F">'+processListHTML+'</div>';
                out += '  <div class="node-feed-right pull-right">';
                if (feed.unit==undefined) feed.unit = "";
                out += '    <div class="value" data-col="C">'+list_format_value(feed.value)+' '+feed.unit+'</div>';
                out += '    <div class="time" data-col="D">'+list_format_updated(feed.time)+'</div>';
                out += '  </div>';
                out += '</div>';
            }
            
            out += "</div>";
            out += "</div>";
        }
        $container = $('#table');
        $container.html(out);

        // reset the toggle state for all collapsable elements once data has loaded
        // css class "in" is used to remember the expanded state of the ".collapse" element
        if(typeof $.fn.collapse == 'function') {
            $("#table .collapse").collapse({toggle: false});
            setExpandButtonState($container.find('.collapsed').length == 0);
        }
        
        autowidth($container) // set each column group to the same width
        } // end of for loop
    }); // end of ajax callback
}// end of update_feed_list() function

// stop checkbox form opening graph view
$("#table").on("click",".tbody .select",function(e) {
    e.stopPropagation();
});

$("#table").on("click",".public",function(e) {
    e.stopPropagation();
});

$("#table").on("click select",".feed-select",function(e) {
    feed_selection();
});

$("#table").on("click",".feed-graph-link",function(e) {
    // ignore click on feed-info row
    if ($(this).parent().is('.node-info')) return false;
    var feedid = $(this).attr("feedid");
    window.location = path+feedviewpath+feedid;
});

$(".feed-graph").click(function(){
    var graph_feeds = [];
    for (var feedid in selected_feeds) {
        if (selected_feeds[feedid]==true) graph_feeds.push(feedid);
    }
    window.location = path+feedviewpath+graph_feeds.join(",");      
});

function buildFeedNodeList() {
    node_names = [];
    for (n in nodes) {
        let feed = nodes[n];
        node_names.push(feed[0].tag)
    }
    autocomplete(document.getElementById("feed-node"), node_names);
}


function missedIntervals(feed) {
    if (!feed) return void 0;
    var lastUpdated = new Date(feed.time * 1000);
    var now = new Date().getTime();
    var elapsed = (now - lastUpdated) / 1000;
    let missedIntervals = parseInt(elapsed / feed.interval);
    return missedIntervals;
}
function feedListItemIntervalClass (feed) {
    if (!feed) return void 0;
    let missed = missedIntervals(feed);
    let result = [];
    if (missed < 3) result.push('status-success');
    if (missed > 2 && missed < 9) result.push('status-warning');
    if (missed > 8) result.push('status-danger');
    return result.join(' ');
}
function nodeIntervalClass (feeds) {
    let nodeMissed = 0;
    for (f in feeds) {
        let missed = missedIntervals(feeds[f]);
        if (missed > nodeMissed) {
            nodeMissed = missed;
        }
    }
    let result = [];
    if (nodeMissed < 3) result.push('status-success');
    if (nodeMissed > 2 && nodeMissed < 9) result.push('status-warning');
    if (nodeMissed > 8) result.push('status-danger');
    return result.join(' ');
}




$(".feed-node").on('input', function(event){
    $('#feed-node').val($(this).val());
});



$("#refreshfeedsize").click(function(){
    $.ajax({ url: path+"feed/updatesize.json", async: true, success: function(data){ update_feed_list(); alert('<?php echo addslashes(_("Total size of used space for feeds:")); ?>' + list_format_size(data)); } });
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
        $(".feed-edit").show();
    } else {
        $(".feed-delete").hide();
        $(".feed-download").hide();
        $(".feed-graph").hide();
        $(".feed-edit").hide();
    }

    // There should only ever be one feed that is selected here:
    var feedid = 0; for (var z in selected_feeds) { if (selected_feeds[z]) feedid = z; }
    // Only show feed process button for Virtual feeds
    if (feeds[feedid] && feeds[feedid].engine==7 && num_selected==1) $(".feed-process").show(); else $(".feed-process").hide();
}

// -------------------------------------------------------------------------------------------------------
// Interface responsive
//
// The following implements the showing and hiding of the device fields depending on the available width
// of the container and the width of the individual fields themselves. It implements a level of responsivness
// that is one step more advanced than is possible using css alone.
// -------------------------------------------------------------------------------------------------------
watchResize(onResize, 20) // only call onResize() after 20ms of delay (similar to debounce)

</script>
<?php /*
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/feed_edit_modal.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/feed_export_modal.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/feed_delete_modal.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/virtualfeed_modal.js"></script>
*/?>
