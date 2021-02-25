<?php global $path, $settings; ?>

<script src="https://cdn.jsdelivr.net/npm/handlebars@latest/dist/handlebars.js"></script>
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
<link rel="stylesheet" href="<?php echo $path; ?>Modules/feed/Views/css/feedlist.css">
<link rel="stylesheet" href="<?php echo $path; ?>Modules/feed/Views/css/feedlist2.css?v=1">
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/js/formats.js"></script>

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

var template = false;

var feedviewpath = "<?php echo $settings['interface']['feedviewpath']; ?>";
var downloadlimit = <?php echo $settings['feed']['csv_downloadlimit_mb']; ?>;
    
var feeds = {};
var nodes = {};
var selected_feeds = [];
var local_cache_key = 'feed_nodes_display';
var nodes_display = {};
var feed_engines = ['MYSQL','TIMESTORE','PHPTIMESERIES','GRAPHITE','PHPTIMESTORE','PHPFINA','PHPFIWA','VIRTUAL','MEMORY','REDISBUFFER','CASSANDRA'];

load_template(function() {
    update_feed_list();
});

// auto refresh
// setInterval(update_feed_list,5000);

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
            if (selected_feeds[feeds[z].id]!=undefined) {
                feeds[z].selected = true;
            } else {
                feeds[z].selected = false;
            }
        
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
        // if(firstLoad) docCookies.setItem(local_cache_key, JSON.stringify(nodes_display));
        firstLoad = false;
        
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

        $container = $('#table');
        
        $container.html(template({
            feeds:feeds,
            nodes:nodes,
            nodes_display: nodes_display,
            node_size:node_size,
            node_time:node_time,
            feed_engines:feed_engines
        }));

        // reset the toggle state for all collapsable elements once data has loaded
        // css class "in" is used to remember the expanded state of the ".collapse" element
        if(typeof $.fn.collapse == 'function') {
            $("#table .collapse").collapse({toggle: false});
            setExpandButtonState($container.find('.collapsed').length == 0);
        }
        
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
        graph_feeds.push(feedid);
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
        if ($(this)[0].checked==true) {
            selected_feeds[feedid] = true;
            num_selected += 1;
        } else {
            if (selected_feeds[feedid]!=undefined) {
                delete selected_feeds[feedid];
            }
        }
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
    var feedid = 0; for (var z in selected_feeds) { feedid = z; }
    // Only show feed process button for Virtual feeds
    if (feeds[feedid] && feeds[feedid].engine==7 && num_selected==1) $(".feed-process").show(); else $(".feed-process").hide();
}

Handlebars.registerHelper('format_size', function(bytes) { return format_size(bytes); });
Handlebars.registerHelper('format_value', function(value) { return format_value(value); });

Handlebars.registerHelper('format_time', function(time) {
    var fv = format_time(time);
    return "<span class='last-update' style='color:" + fv.color + ";'>" + fv.value + "</span>";
});

function load_template(callback) {
    $.ajax({
        url: path+'Modules/feed/Views/template.html',
        cache: true,
        success: function(source) {
            template  = Handlebars.compile(source);
            callback();
        }               
    });
}

</script>

<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/js/feed_edit_modal.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/js/feed_export_modal.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/js/feed_delete_modal.js"></script>
<?php/*
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/js/virtualfeed_modal.js"></script>
*/?>
