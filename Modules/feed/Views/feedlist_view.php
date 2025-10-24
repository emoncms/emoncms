<?php
    defined('EMONCMS_EXEC') or die('Restricted access');
    global $path, $settings, $session;
    $v=7;
    
    $lang = "";
    if (isset($_SESSION['lang'])) {
        $lang = $_SESSION['lang']; 
    }
    
    $public_username_str = "";
    if ($session['public_userid']) {
        $public_username_str = $session['public_username']."/";
    }
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>

<script src="<?php echo $path; ?>Lib/moment.min.js?v=1"></script>
<script>
    var _user = {};
    _user.lang = "<?php echo $lang; ?>";
</script>
<script src="<?php echo $path; ?>Lib/user_locale.js?v=<?php echo $v; ?>"></script>
<script>

/**
 * uses moment.js to format to local time 
 * @param int time unix epoc time
 * @param string format moment.js date formatting options
 * @see date format options - https://momentjs.com/docs/#/displaying/
 */
function format_time(time,format){
    if(!Number.isInteger(time)) return time;
    format = format || 'YYYY-MM-DD';
    formatted_date = moment.unix(time).utc().format(format);
    return formatted_date;
}
</script>

<script>
// @todo: standardise these translations functions, also used in admin_main_view.php and input_view.php
/**
 * return object of gettext translated strings
 *
 * @return object
 */
function getTranslations(){
    return {
        'Tag': "<?php echo tr('Tag') ?>",
        'Feed ID': "<?php echo tr('Feed ID') ?>",
        'Feed Interval': "<?php echo tr('Feed Interval') ?>",
        'Feed Start Time': "<?php echo tr('Feed Start Time') ?>",
        'Realtime': "<?php echo tr('Realtime') ?>",
        'Daily': "<?php echo tr('Daily') ?>"
    }
}
/**
 * wrapper for gettext like string replace function
 */
function tr(str) {
    return translate(str);
}
/**
 * emulate the php gettext function for replacing php strings in js
 */
function translate(property) {
    _strings = typeof translations === 'undefined' ? getTranslations() : translations;
    if (_strings.hasOwnProperty(property)) {
        return _strings[property];
    } else {
        return property;
    }
}
</script>


<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js?v=8"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/responsive-linked-tables.js?v=<?php echo $v; ?>"></script>

<link href="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script src="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js"></script>

<script type="text/javascript" src="<?php echo $path; ?>Lib/misc/autocomplete.js?v=<?php echo $v; ?>"></script>
<link rel="stylesheet" href="<?php echo $path; ?>Lib/misc/autocomplete.css?v=<?php echo $v; ?>">

<style>
body{padding:0!important}

#footer {
    margin-left: 0px;
    margin-right: 0px;
}

.controls { margin-bottom:10px; }

#feeds-to-delete { font-style:italic; }

#deleteFeedModalSelectedItems{
    position:absolute;
    overflow:hidden;
    text-align:left;
    background: #f5f5f5;
}
#deleteFeedModalSelectedItems h5{ margin:0 }
#deleteFeedModalSelectedItems ol{
    max-width:80%;
    position:absolute;
}

/* CSS Grid Feed List Styles */
.feed-grid {
    display: grid;
    /* Columns:            Name,           Public, Engine, Size, Value, Updated */
    grid-template-columns: 30px minmax(150px, 3fr) 80px 120px 80px 80px 100px;
    align-items: center;
    cursor: default;
    min-height: 41px;
    width: 100%;
    box-sizing: border-box;
}

.feed-grid.feed-header {
    background: #ddd;
    font-weight: bold;
    border-bottom: 1px solid #ccc;
}

.feed-grid .grid-cell {
    padding: 0 8px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.text-center {
    text-align: center;
}

/* Node header styles for grid */
.node .feed-grid.node-header {
    background: #ddd;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
    display: grid;
    border-right: 4px solid var(--status-color);
}

.node .feed-grid.node-header:hover {
    background-color: #e3e3e3;
}

.node .feed-grid.node-header h5 {
    margin: 0;
    font-weight: bold;
    font-size: 14px;
}

/* Feed row styles for grid */
.node-feeds .feed-grid.node-feed {
    border-bottom: 1px solid #fff;
    background: #f0f0f0;
    position: relative;
    transition: background-color 0.1s ease;
    display: grid;
    width: 100%;
    cursor: pointer;
    border-right: 4px solid var(--status-color);
}

.node-feeds .feed-grid.node-feed:hover {
    background-color: #f5f5f5;
}

.node-feeds .feed-grid.node-feed:last-child {
    border-bottom-width: 0;
}

/* Feed status indicator on right side */
.node-feeds .feed-grid.node-feed:after {
    content: '';
    width: 0px;
    background: var(--status-color);
    height: 100%;
    display: block;
    position: absolute;
    left: 0;
    top: 0;
    transition: width .2s ease-out;
}

.node-feeds .feed-grid.node-feed:hover:after {
    width: 2px;
}

/* Arrow animation for Vue */
.arrow-icon {
    transition: transform 0.3s ease;
    opacity: 0.333;
}

/* Collapsible content for Vue */
.vue-collapsible-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease-in-out;
    will-change: max-height;
}

.vue-collapsible-content.is-expanded {
    max-height: 2000px;
}
</style>
<div id="feed-header">
    <span id="api-help" style="float:right"><a href="<?php echo $path.$public_username_str; ?>feed/api"><?php echo tr('Feed API Help'); ?></a></span>
    <h3 id="feeds-title"><?php echo tr('Feeds'); ?></h3>
    <h3 id="public-feeds-title" class="hide"><?php echo tr('Public Feeds'); ?></h3>
</div>

<input type="text" name="filter" id="filter" placeholder="Filter feeds" style="float:right">

<div class="sticky-sentinel" style="height: 1px; position: absolute; top: 45px; width: 100%; pointer-events: none;"></div>
<div class="sticky-controls">
    <button id="expand-collapse-all" class="btn" title="<?php echo tr('Collapse') ?>" data-alt-title="<?php echo tr('Expand') ?>"><i class="icon icon-resize-small"></i></button>
    <button id="select-all" class="btn" title="<?php echo tr('Select all') ?>" data-alt-title="<?php echo tr('Unselect all') ?>"><i class="icon icon-check"></i></button>
    <button class="btn feed-edit hide" title="<?php echo tr('Edit') ?>"><i class="icon-pencil"></i></button>
    <button class="btn feed-delete hide" title="<?php echo tr('Delete') ?>"><i class="icon-trash" ></i></button>
    <button class="btn feed-downsample hide" title="<?php echo tr('Downsample') ?>"><i class="icon-repeat"></i></button>
    <button class="btn feed-download hide" title="<?php echo tr('Download') ?>"><i class="icon-download"></i></button>
    <button class="btn feed-graph hide" title="<?php echo tr('Graph view') ?>"><i class="icon-eye-open"></i></button>
    <button class="btn feed-process hide" title="<?php echo tr('Process config') ?>"><i class="icon-wrench"></i></button>

</div>

<div id="table" class="feed-list">
    <!-- Vue.js Feed List Component -->
    <div id="feed-app" v-if="nodes && Object.keys(nodes).length > 0">
        <!-- Header Row -->
        <div class="feed-grid feed-header">
            <div class="grid-cell"></div>
            <div class="grid-cell">Name</div>
            <div class="grid-cell text-center">Public</div>
            <div class="grid-cell">Engine</div>
            <div class="grid-cell text-center">Size</div>
            <div class="grid-cell text-center">Value</div>
            <div class="grid-cell text-center">Updated</div>
        </div>

        <!-- Node Groups -->
        <div v-for="(nodeFeeds, node) in nodes" :key="node" class="node accordion" :style="{'--status-color': getNodeColor(nodeFeeds)}">
            <!-- Node Header -->
            <div class="feed-grid node-header" @click="nodesDisplay[node] = !nodesDisplay[node]" :class="{'collapsed': !nodesDisplay[node]}">
                <div class="grid-cell text-center has-indicator">
                    <i class="arrow-icon" :class="[nodesDisplay[node] ? 'icon-chevron-down' : 'icon-chevron-right']" style="transition: transform 0.3s ease;"></i>
                </div>
                <div class="grid-cell">
                    <h5>{{ node }}:</h5>
                </div>
                <div class="grid-cell"></div>
                <div class="grid-cell"></div>
                <div class="grid-cell text-center">{{ getNodeSize(nodeFeeds) }}</div>
                <div class="grid-cell"></div>
                <div class="grid-cell text-center">
                    <span class="last-update" :style="{color: getNodeColor(nodeFeeds)}">{{ getNodeTime(nodeFeeds) }}</span>
                </div>
            </div>

            <!-- Node Feeds -->
            <div class="vue-collapsible-content node-feeds" :class="{'is-expanded': nodesDisplay[node]}">
                <div v-for="feed in nodeFeeds" :key="feed.id" 
                     class="feed-grid node-feed feed-graph-link" 
                     :style="{'--status-color': getFeedColor(feed)}"
                     :feedid="feed.id"
                     :title="getFeedTooltip(feed)"
                     @click="openFeedGraph(feed.id)">
                    
                    <div class="grid-cell text-center select" @click.stop>
                        <input class="feed-select" type="checkbox" :feedid="feed.id" v-model="selectedFeeds[feed.id]" @change="onFeedSelectionChange">
                    </div>
                    <div class="grid-cell name">{{ feed.name }}</div>
                    <div class="grid-cell text-center public" @click.stop="toggleFeedPublic(feed)">
                        <i :class="feed.public == 1 ? 'icon-globe' : 'icon-lock'"></i>
                    </div>
                    <div class="grid-cell engine">{{ formatEngine(feed.engine, feed.interval) }}</div>
                    <div class="grid-cell text-center size">{{ formatSize(feed.size) }}</div>
                    <div class="grid-cell text-center value" v-html="formatValue(feed.value, feed.unit)"></div>
                    <div class="grid-cell text-center time" v-html="formatTime(feed.time, feed.interval)"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="feed-none" class="alert alert-block hide">
    <h4 class="alert-heading"><?php echo tr('No feeds created'); ?></h4>
    <p><?php echo tr('Feeds are where your monitoring data is stored. The route for creating storage feeds is to start by creating inputs (see the inputs tab). Once you have inputs you can either log them straight to feeds or if you want you can add various levels of input processing to your inputs to create things like daily average data or to calibrate inputs before storage. Alternatively you can create Virtual feeds, this is a special feed that allows you to do post processing on existing storage feeds data, the main advantage is that it will not use additional storage space and you may modify post processing list that gets applyed on old stored data. You may want the next link as a guide for generating your request: '); ?><a href="api"><?php echo tr('Feed API helper'); ?></a></p>
</div>

<div id="public-feeds-none" class="alert alert-block hide">
    <h4 class="alert-heading"><?php echo tr('No public feeds available'); ?></h4>
</div>

<div id="feed-footer">
    <button id="refreshfeedsize" class="btn btn-small" ><i class="icon-refresh" ></i>&nbsp;<?php echo tr('Refresh feed size'); ?></button>
    <button id="addnewfeed" class="btn btn-small" data-toggle="modal" data-target="#newFeedNameModal"><i class="icon-plus-sign" ></i>&nbsp;<?php echo tr('New feed'); ?></button>
    <button id="importdata" class="btn btn-small" data-toggle="modal" data-target="#importDataModal"><i class="icon-arrow-up" ></i>&nbsp;<?php echo tr('Import data'); ?></button>
</div>
<div id="feed-loader" class="ajax-loader"></div>

<?php require "Modules/feed/Views/feed_delete_modal.php"; ?>
<?php require "Modules/feed/Views/feed_edit_modal.php"; ?>
<?php require "Modules/feed/Views/exporter.php"; ?>
<?php require "Modules/feed/Views/importer.php"; ?>
<?php require "Modules/feed/Views/downsample.php"; ?>
<?php require "Modules/process/Views/process_ui.php"; ?>

<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<script>
if (public_userid) {
   $("#feeds-title").hide();
   $("#public-feeds-title").show();
}
if (!session_write) {
   $("#feed-footer").hide();
}

var feedviewpath = "<?php echo $settings['interface']['feedviewpath']; ?>";

var app = {};
var feeds = {};
var nodes = {};
var selected_feeds = {};
var local_cache_key = 'feed_nodes_display';
var nodes_display = {};
var feed_engines = ['MYSQL','TIMESTORE','PHPTIMESERIES','GRAPHITE','PHPTIMESTORE','PHPFINA','PHPFIWA (No longer supported)','VIRTUAL','MEMORY','REDISBUFFER','CASSANDRA'];
var engines_hidden = <?php echo json_encode($settings["feed"]['engines_hidden']); ?>;

var available_intervals = <?php echo json_encode(Engine::available_intervals()); ?>;
var tmp = []; for (var z in available_intervals) tmp.push(available_intervals[z]['interval']); available_intervals = tmp;

// Get filter element
var filter = document.getElementById("filter");

// auto refresh
// update_feed_list();
setTimeout(update_feed_list,1);
setInterval(update_feed_list,5000);
filter.oninput = update_feed_list;

var firstLoad = true;
function update_feed_list() {
    var public_username_str = "";
    if (public_userid) public_username_str = public_username+"/";
    var requestTime = (new Date()).getTime();

    $.ajax({ url: path+public_username_str+"feed/list.json?meta=1", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
        if( typeof app !== 'undefined') app.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
        if (data.message!=undefined && data.message=="Username or password empty") {
            window.location.href = "/";
            return false;
        }
    
        // Show/hide no feeds alert
        $('#feed-loader').hide();
        if (data.length == 0){
            if (public_userid) {
                $("#public-feeds-none").show();
            } else {
                $("#feed-none").show();
            }
            // Clear Vue data
            if (typeof feedApp !== 'undefined') {
                feedApp.nodes = {};
                feedApp.feeds = {};
            }
        } else {
            $("#feed-none").hide();
            $("#public-feeds-none").hide();
        }
        
        // Filter feeds
        feeds = {};
        filterText = filter.value.toLowerCase()
        for (var z in data) {
            if (filterText == '' || data[z].name.toLowerCase().includes(filterText)) {
                feeds[data[z].id] = data[z];
            }
        }
        
        // Group feeds by node
        nodes = {};
        for (var z in feeds) {
            var node = feeds[z].tag;
            if (nodes[node]==undefined) nodes[node] = [];

            if (nodes_display[node]==undefined) nodes_display[node] = true;
            nodes[node].push(feeds[z]);
        }
        
        // Auto-collapse logic for first load
        if (firstLoad && Object.keys(nodes).length > 1 && Object.keys(nodes_display).length == 0) {
            for (var node in nodes) {
                nodes_display[node] = false;
            }
        }
        firstLoad = false;
        
        // Update Vue.js data
        if (typeof feedApp !== 'undefined') {
            feedApp.nodes = Object.assign({}, nodes);
            feedApp.feeds = Object.assign({}, feeds);
            feedApp.nodesDisplay = Object.assign({}, nodes_display);
            
            // Preserve existing selections
            var newSelectedFeeds = {};
            for (var feedid in feeds) {
                newSelectedFeeds[feedid] = selected_feeds[feedid] || false;
            }
            feedApp.selectedFeeds = newSelectedFeeds;
        }
        
    }}); // end of ajax callback
}// end of update_feed_list() function

// Updated event handlers for Vue.js integration

// Handle feed graph clicks via custom event  
document.addEventListener('feedGraphClick', function(e) {
    var feedid = e.detail.feedid;
    var public_username_str = "";
    if (public_userid) public_username_str = public_username+"/";
    
    window.location = path+public_username_str+feedviewpath+feedid;
});

// Handle public toggle clicks via custom event  
document.addEventListener('feedPublicClick', function(e) {
    var feedid = e.detail.feedid;
    e.stopPropagation();
    // Existing functionality preserved - can be extended here
});

// Legacy jQuery handlers for elements not in Vue
$("#table").on("click select",".feed-select",function(e) {
    feed_selection();
});

// Keep existing graph button functionality
$(".feed-graph").click(function(){
    var graph_feeds = [];
    for (var feedid in selected_feeds) {
        if (selected_feeds[feedid]==true) graph_feeds.push(feedid);
    }

    var public_username_str = "";
    if (public_userid) public_username_str = public_username+"/";
    
    window.location = path+public_username_str+feedviewpath+graph_feeds.join(",");      
});

function buildFeedNodeList() {
    node_names = [];
    for (n in nodes) {
        let feed = nodes[n];
        node_names.push(feed[0].tag)
    }
    autocomplete(document.getElementById("feed-node"), node_names);
}



$("#refreshfeedsize").click(function(){
    $.ajax({ url: path+"feed/updatesize.json", async: true, success: function(data){ update_feed_list(); alert('<?php echo addslashes(tr("Total size of used space for feeds:")); ?>' + list_format_size(data)); } });
});

// ---------------------------------------------------------------------------------------------
// ---------------------------------------------------------------------------------------------
function feed_selection() 
{
    selected_feeds = {};
    var num_selected = 0;
    var phpfina_selected = 0;
    $(".feed-select").each(function(){
        var feedid = $(this).attr("feedid");
        selected_feeds[feedid] = $(this)[0].checked;
        if (selected_feeds[feedid]==true) {
            num_selected += 1;
            if (feeds[feedid].engine==5) phpfina_selected += 1;
        }
    });
    
    if (num_selected>0) {
        if (session_write) $(".feed-delete").show();
        $(".feed-download").show();
        $(".feed-graph").show();
        if (session_write) $(".feed-edit").show();
        $("#filter").hide();
    } else {
        $(".feed-delete").hide();
        $(".feed-download").hide();
        $(".feed-graph").hide();
        $(".feed-edit").hide();
        $("#filter").show();
    }
    
    if (phpfina_selected>0 && num_selected == phpfina_selected) {
        $(".feed-downsample").show();
    } else {
        $(".feed-downsample").hide();
    }

    // There should only ever be one feed that is selected here:
    var feedid = 0; for (var z in selected_feeds) { if (selected_feeds[z]) feedid = z; }
    // Only show feed process button for Virtual feeds
    if (feeds[feedid] && feeds[feedid].engine==7 && num_selected==1) {
        if (session_write) $(".feed-process").show(); 
    } else {
        $(".feed-process").hide();
    }
}

// -------------------------------------------------------------------------------------------------------
// Interface responsive
//
// The following implements the showing and hiding of the device fields depending on the available width
// of the container and the width of the individual fields themselves. It implements a level of responsivness
// that is one step more advanced than is possible using css alone.
// -------------------------------------------------------------------------------------------------------
watchResize(onResize, 20) // only call onResize() after 20ms of delay (similar to debounce)


// Translations
var downloadlimit = <?php echo $settings['feed']['csv_downloadlimit_mb']; ?>;
var str_enter_valid_start_date = "<?php echo tr('Please enter a valid start date.'); ?>";
var str_enter_valid_end_date = "<?php echo tr('Please enter a valid end date.'); ?>";
var str_start_before_end = "<?php echo tr('Start date must be further back in time than end date.'); ?>";
var str_interval_for_download = "<?php echo tr('Please select interval to download.'); ?>";
var str_large_download = "<?php echo tr('Estimated download file size is large.'); ?>\n<?php echo tr('Server could take a long time or abort depending on stored data size.'); ?>\n<?php echo tr('Limit is'); ?> "+downloadlimit+"MB.\n\n<?php echo tr('Try exporting anyway?'); ?>";

// Vue.js Feed List Application
var feedApp = new Vue({
    el: '#feed-app',
    data: {
        nodes: {},
        feeds: {},
        selectedFeeds: {},
        nodesDisplay: {}
    },
    methods: {
        getNodeColor: function(nodeFeeds) {
            var maxColorCode = 0;
            var nodeColor = '#999';
            
            for (var i = 0; i < nodeFeeds.length; i++) {
                var feed = nodeFeeds[i];
                if (feed.time != null && parseInt(feed.engine) !== 7) {
                    var fv = list_format_updated_obj(feed.time, feed.interval);
                    if (fv.color_code > maxColorCode) {
                        maxColorCode = fv.color_code;
                        nodeColor = fv.color;
                    }
                }
            }
            return nodeColor;
        },
        
        getNodeSize: function(nodeFeeds) {
            var totalSize = 0;
            for (var i = 0; i < nodeFeeds.length; i++) {
                totalSize += Number(nodeFeeds[i].size);
            }
            return list_format_size(totalSize);
        },
        
        getNodeTime: function(nodeFeeds) {
            var maxColorCode = 0;
            var nodeTime = '';
            
            for (var i = 0; i < nodeFeeds.length; i++) {
                var feed = nodeFeeds[i];
                if (feed.time != null && parseInt(feed.engine) !== 7) {
                    var fv = list_format_updated_obj(feed.time, feed.interval);
                    if (fv.color_code > maxColorCode) {
                        maxColorCode = fv.color_code;
                        nodeTime = fv.value;
                    }
                }
            }
            return nodeTime;
        },
        
        getFeedColor: function(feed) {
            var fv = list_format_updated_obj(feed.time, feed.interval);
            return fv.color;
        },
        
        getFeedTooltip: function(feed) {
            var titleLines = [feed.name,
                             '-----------------------',
                             tr('Tag') + ': ' + feed.tag,
                             tr('Feed ID') + ': ' + feed.id,
                             tr('Feed Engine') + ': ' + feed_engines[feed.engine]];
            
            if(feed.engine == 5) {
                titleLines.push(tr('Feed Interval')+": "+(feed.interval||'')+'s');
            } else {
                titleLines.push(tr('Feed Interval (approx)')+": "+(feed.interval||'')+'s');
            }
            
            if(feed.start_time > 0) {
                titleLines.push(tr('Feed Start Time')+": "+feed.start_time);
                titleLines.push(format_time(feed.start_time,'LL LTS')+" UTC");
            }

            if(feed.end_time > 0) {
                titleLines.push(tr('Feed End Time')+": "+feed.end_time);
                titleLines.push(format_time(feed.end_time,'LL LTS')+" UTC");
            }
            
            return titleLines.join("\n");
        },
        
        formatEngine: function(engine, interval) {
            var engineName = feed_engines[engine];
            if (engineName == "PHPFINA") engineName = "FIXED";
            else if (engineName == "PHPTIMESERIES") engineName = "VARIABLE";
            
            var intervalStr = "";
            if (engine == 5) {
                intervalStr = " (" + interval + "s)";
            }
            
            return engineName + intervalStr;
        },
        
        formatSize: function(size) {
            return list_format_size(size);
        },
        
        formatValue: function(value, unit) {
            if (unit == undefined) unit = "";
            return list_format_value(value) + ' ' + unit;
        },
        
        formatTime: function(time, interval) {
            return list_format_updated(time, interval);
        },
        
        openFeedGraph: function(feedid) {
            // Trigger the existing feed graph functionality
            var event = new CustomEvent('feedGraphClick', { detail: { feedid: feedid } });
            document.dispatchEvent(event);
        },
        
        toggleFeedPublic: function(feed) {
            // Trigger the existing public toggle functionality
            var event = new CustomEvent('feedPublicClick', { detail: { feedid: feed.id } });
            document.dispatchEvent(event);
        },
        
        onFeedSelectionChange: function() {
            // Update the global selected_feeds object
            selected_feeds = Object.assign({}, this.selectedFeeds);
            feed_selection();
        }
    },
    
    watch: {
        selectedFeeds: {
            handler: function(newVal) {
                selected_feeds = Object.assign({}, newVal);
                feed_selection();
            },
            deep: true
        },
        
        nodesDisplay: {
            handler: function(newVal) {
                // Update global nodes_display object
                nodes_display = Object.assign({}, newVal);
            },
            deep: true
        }
    }
});

// Vue-compatible expand/collapse and select-all functionality  
$(document).ready(function() {
    // Wait for Vue to initialize before overriding button handlers
    setTimeout(function() {
        // Override the expand-collapse button for Vue compatibility
        $("#expand-collapse-all").off('click').on('click', function() {
            if (typeof feedApp !== 'undefined' && feedApp.nodes) {
                // Determine if we should expand or collapse all
                var allExpanded = true;
                for (var node in feedApp.nodes) {
                    if (!feedApp.nodesDisplay[node]) {
                        allExpanded = false;
                        break;
                    }
                }
                
                // Set all nodes to opposite state
                var newState = {};
                for (var node in feedApp.nodes) {
                    newState[node] = !allExpanded;
                }
                feedApp.nodesDisplay = newState;
                
                // Update button state
                var $btn = $(this);
                var $icon = $btn.find('.icon');
                $icon.toggleClass('icon-resize-small', !allExpanded)
                     .toggleClass('icon-resize-full', allExpanded);
                
                if (!$btn.data('original-title')) $btn.data('original-title', $btn.attr('title'));
                $btn.attr('title', allExpanded ? $btn.data('alt-title') : $btn.data('original-title'));
            }
        });

        // Override the select-all button for Vue compatibility
        $("#select-all").off('click').on('click', function() {
            if (typeof feedApp !== 'undefined' && feedApp.feeds) {
                var $btn = $(this);
                var currentState = $btn.data('state') !== false;
                
                // Toggle all checkboxes
                var newSelections = {};
                for (var feedid in feedApp.feeds) {
                    newSelections[feedid] = currentState;
                }
                feedApp.selectedFeeds = newSelections;
                
                // Update button appearance
                $btn.find('.icon').toggleClass('icon-ban-circle', currentState)
                                  .toggleClass('icon-check', !currentState);
                
                if (!$btn.data('title-original')) {
                    $btn.data('title-original', $btn.attr('title'));
                }
                var title = currentState ? $btn.data('alt-title') : $btn.data('title-original');
                $btn.attr('title', title);
                $btn.data('state', !currentState);
                
                // Expand all if selecting all
                if (currentState) {
                    var expandedState = {};
                    for (var node in feedApp.nodes) {
                        expandedState[node] = true;
                    }
                    feedApp.nodesDisplay = expandedState;
                }
            }
        });
    }, 100);
});
</script>

<?php require "Modules/feed/Views/feed_new_modal.php"; ?>


<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/exporter.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/importer.js?v=2"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/downsample.js?v=2"></script>

