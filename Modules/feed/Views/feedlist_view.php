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
.feed-list-grid {
    display: grid;
    /* Columns:            Checkbox, Name,           Public, Engine, Size, Process List, Value, Updated */
    grid-template-columns: 30px max-content max-content max-content max-content minmax(150px, 1fr) 80px 100px;
    width: 100%;
    box-sizing: border-box;
}

.grid-row {
    display: grid;
    grid-column: 1 / -1;
    grid-template-columns: subgrid;
    align-items: center;
    min-height: 41px;
    cursor: default;
}

/* Responsive behavior - hide public, engine, and size columns on smaller screens */
@media (max-width: 768px) {
    .feed-list-grid {
        grid-template-columns: 30px minmax(200px, 1fr) 80px 80px;
    }
    
    /* Hide public, engine, and size columns */
    .grid-row > .grid-cell:nth-child(3),  /* Public column */
    .grid-row > .grid-cell:nth-child(4),  /* Engine column */
    .grid-row > .grid-cell:nth-child(5), /* Size column */
    .grid-row > .grid-cell:nth-child(6) { /* Process List column */
        display: none;
    }
}

@media (max-width: 480px) {
    .feed-list-grid {
        grid-template-columns: 30px minmax(200px, 1fr) 80px 80px;
    }
    
    /* Hide public, engine, size, and process list columns on very small screens */
    .grid-row > .grid-cell:nth-child(3),  /* Public column */
    .grid-row > .grid-cell:nth-child(4),  /* Engine column */
    .grid-row > .grid-cell:nth-child(5),  /* Size column */
    .grid-row > .grid-cell:nth-child(6) { /* Process List column */
        display: none;
    }
}

.feed-header {
    background: #ddd;
    font-weight: bold;
    border-bottom: 1px solid #ccc;
}

.grid-cell {
    padding: 0 8px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.text-center {
    text-align: center;
}

.text-left {
    text-align: left;
}

/* Node header styles for grid */
.node-header {
    background: #ddd;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
    border-right: 4px solid var(--status-color);
}

.node-header:hover {
    background-color: #e3e3e3;
}

.node-header h5 {
    margin: 0;
    font-weight: bold;
    font-size: 14px;
    grid-column: 2;
}

/* Feed row styles for grid */
.node-feed {
    border-bottom: 1px solid #fff;
    background: #f0f0f0;
    position: relative;
    transition: background-color 0.1s ease;
    width: 100%;
    cursor: pointer;
    border-right: 4px solid var(--status-color);
}

.node-feed:hover {
    background-color: #f5f5f5;
}

.node-feed:last-of-type {
    border-bottom-width: 0;
}

/* Feed status indicator on right side */
.node-feed:after {
    content: '';
    width: 0px;
    background: var(--bg-menu-top);
    height: 100%;
    display: block;
    position: absolute;
    left: 0;
    top: 0;
    transition: width .2s ease-out;
}

.node-feed:hover:after {
    width: 2px;
}

/* Arrow animation for Vue */
.arrow-icon {
    transition: transform 0.3s ease;
    opacity: 0.333;
}

/* Collapsible content for Vue */
.vue-collapsible-content {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: subgrid;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.2s ease-in-out;
    will-change: max-height;
}

.vue-collapsible-content.is-expanded {
    max-height: 2000px; /* Adjust as needed, should be larger than any possible content height */
}
</style>
<div id="feed-header">
    <span id="api-help" style="float:right"><a href="<?php echo $path.$public_username_str; ?>feed/api"><?php echo tr('Feed API Help'); ?></a></span>
    <h3 id="feeds-title"><?php echo tr('Feeds'); ?></h3>
    <h3 id="public-feeds-title" class="hide"><?php echo tr('Public Feeds'); ?></h3>
</div>

<input type="text" name="filter" id="filter" placeholder="Filter feeds" style="float:right">

<div id="feed-app">
    <div class="sticky-sentinel" style="height: 1px; position: absolute; top: 45px; width: 100%; pointer-events: none;"></div>
    <div class="sticky-controls">
        <button class="btn" :title="allExpanded ? '<?php echo tr('Collapse') ?>' : '<?php echo tr('Expand') ?>'" @click="expandAllNodes()">
            <i class="icon" :class="allExpanded ? 'icon-resize-small' : 'icon-resize-full'"></i>
        </button>
        <button class="btn" :title="allSelected ? '<?php echo tr('Unselect all') ?>' : '<?php echo tr('Select all') ?>'" @click="selectAllFeeds()">
            <i class="icon" :class="allSelected ? 'icon-ban-circle' : 'icon-check'"></i> <span>{{ selectedFeedCount }}</span>
        </button>
        <button class="btn" v-if="selectedFeedCount>0" title="<?php echo tr('Edit') ?>" @click="editFeeds">
            <i class="icon-pencil"></i>
        </button>
        <button class="btn" :class="{hide: !selectedFeedCount || !session_write}" title="<?php echo tr('Delete') ?>" @click="deleteFeeds">
            <i class="icon-trash"></i>
        </button>
        <button class="btn" :class="{hide: !showDownsample}" title="<?php echo tr('Downsample') ?>" @click="downsampleFeeds">
            <i class="icon-repeat"></i>
        </button>
        <button class="btn" v-if="selectedFeedCount>0" title="<?php echo tr('Download') ?>" @click="exportFeeds">
            <i class="icon-download"></i>
        </button>
        <button class="btn" v-if="selectedFeedCount>0" title="<?php echo tr('Graph view') ?>" @click="graphSelectedFeeds">
            <i class="icon-eye-open"></i>
        </button>
        <button class="btn" :class="{hide: !showProcess}" title="<?php echo tr('Process config') ?>" @click="processSelectedFeed">
            <i class="icon-wrench"></i>
        </button>
    </div>

<!-- Vue.js Feed List Component -->
    <div v-if="nodes && Object.keys(nodes).length > 0" class="feed-list-grid">
        <!-- Header Row -->
        <!--
        <div class="grid-row feed-header">
            <div class="grid-cell"></div>
            <div class="grid-cell">Name</div>
            <div class="grid-cell text-center">Public</div>
            <div class="grid-cell">Engine</div>
            <div class="grid-cell text-center">Size</div>
            <div class="grid-cell text-left">Process List</div>
            <div class="grid-cell text-center">Value</div>
            <div class="grid-cell text-center">Updated</div>
        </div>
        -->
        <!-- Node Groups -->
        <template v-for="(nodeFeeds, node) in nodes">
            <!-- Node Header -->
            <div :key="node" class="grid-row node-header" @click="nodesDisplay[node] = !nodesDisplay[node]" :class="{'collapsed': !nodesDisplay[node]}" :style="{'--status-color': node_time_and_colour[node].color}">
                <div class="grid-cell text-center has-indicator">
                    <i class="arrow-icon" :class="[nodesDisplay[node] ? 'icon-chevron-down' : 'icon-chevron-right']" style="transition: transform 0.3s ease;"></i>
                </div>
                <h5>{{ node }}:</h5>
                <div class="grid-cell"></div>
                <div class="grid-cell"></div>
                <div class="grid-cell text-center">{{ getNodeSize(nodeFeeds) }}</div>
                <div class="grid-cell"></div>
                <div class="grid-cell"></div>
                <div class="grid-cell text-center" :style="{color: node_time_and_colour[node].color}">
                    {{ node_time_and_colour[node].text }}
                </div>
            </div>

            <!-- Node Feeds -->
            <div class="vue-collapsible-content" :class="{'is-expanded': nodesDisplay[node]}">
                <div v-for="feed in nodeFeeds" :key="feed.id"
                     class="grid-row node-feed feed-graph-link"
                     :style="{'--status-color': feed.color}"
                     :feedid="feed.id"
                     :title="getFeedTooltip(feed)"
                     @click="openFeedGraph(feed.id)">

                    <div class="grid-cell text-center" @click.stop>
                        <input class="feed-select" type="checkbox" :feedid="feed.id" v-model="selectedFeeds[feed.id]" @change="onFeedSelectionChange">
                    </div>
                    <div class="grid-cell">{{ feed.name }}</div>
                    <div class="grid-cell text-center">
                        <i :class="feed.public == 1 ? 'icon-globe' : 'icon-lock'"></i>
                    </div>
                    <div class="grid-cell">{{ formatEngine(feed.engine, feed.interval) }}</div>
                    <div class="grid-cell text-center">{{ formatSize(feed.size) }}</div>
                    <div class="grid-cell text-left" v-html="feed.processListHTML"></div>
                    <div class="grid-cell text-center" v-html="formatValue(feed.value, feed.unit)"></div>
                    <div class="grid-cell text-center" :style="{color: feed.color}">
                        {{ feed.formatted_time }}
                    </div>
                </div>
            </div>

            <!-- Spacer for clarity -->
            <div style="height:10px; grid-column: 1 / -1; background: #fff;"></div>
        </template>
    </div>

    <div id="feed-none" class="alert alert-block" v-show="showNoFeeds">
        <h4 class="alert-heading"><?php echo tr('No feeds created'); ?></h4>
        <p><?php echo tr('Feeds are where your monitoring data is stored. The route for creating storage feeds is to start by creating inputs (see the inputs tab). Once you have inputs you can either log them straight to feeds or if you want you can add various levels of input processing to your inputs to create things like daily average data or to calibrate inputs before storage. Alternatively you can create Virtual feeds, this is a special feed that allows you to do post processing on existing storage feeds data, the main advantage is that it will not use additional storage space and you may modify post processing list that gets applyed on old stored data. You may want the next link as a guide for generating your request: '); ?><a href="api"><?php echo tr('Feed API helper'); ?></a></p>
    </div>

    <div id="public-feeds-none" class="alert alert-block" v-show="showNoPublicFeeds">
        <h4 class="alert-heading"><?php echo tr('No public feeds available'); ?></h4>
    </div>

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
var nodes_display = {};
var node_time_and_colour = {};
var feed_engines = ['MYSQL','TIMESTORE','PHPTIMESERIES','GRAPHITE','PHPTIMESTORE','PHPFINA','PHPFIWA (No longer supported)','VIRTUAL','MEMORY','REDISBUFFER','CASSANDRA'];
var engines_hidden = <?php echo json_encode($settings["feed"]['engines_hidden']); ?>;
var selected_feeds = {}; // Global scope used by edit/delete/downsample/export modals

var available_intervals = <?php echo json_encode(Engine::available_intervals()); ?>;
var tmp = []; for (var z in available_intervals) tmp.push(available_intervals[z]['interval']); available_intervals = tmp;

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
        nodesDisplay: {},
        node_time_and_colour: {},
        feedsLoaded: false
    },
    computed: {
        allExpanded: function() {
            for (var node in this.nodes) {
                if (!this.nodesDisplay[node]) {
                    return false;
                }
            }
            return true;
        },
        
        allSelected: function() {
            for (var feedid in this.feeds) {
                if (!this.selectedFeeds[feedid]) {
                    return false;
                }
            }
            return Object.keys(this.feeds).length > 0;
        },
        
        selectedFeedCount: function() {
            var count = 0;
            for (var feedid in this.selectedFeeds) {
                if (this.selectedFeeds[feedid]) count++;
            }
            return count;
        },
        
        showDownsample: function() {
            if (this.selectedFeedCount === 0) return false;
            var phpfinaSelected = 0;
            for (var feedid in this.selectedFeeds) {
                if (this.selectedFeeds[feedid] && this.feeds[feedid] && this.feeds[feedid].engine == 5) {
                    phpfinaSelected++;
                }
            }
            return phpfinaSelected > 0 && this.selectedFeedCount == phpfinaSelected;
        },
        
        showProcess: function() {
            if (this.selectedFeedCount !== 1) return false;
            var feedid = 0;
            for (var z in this.selectedFeeds) {
                if (this.selectedFeeds[z]) feedid = z;
            }
            return this.feeds[feedid] && this.feeds[feedid].engine == 7;
        },
        
        showNoFeeds: function() {
            return this.feedsLoaded && Object.keys(this.feeds).length === 0 && !public_userid;
        },
        
        showNoPublicFeeds: function() {
            return this.feedsLoaded && Object.keys(this.feeds).length === 0 && public_userid;
        }
    },
    methods: {
        
        getNodeSize: function(nodeFeeds) {
            var totalSize = 0;
            for (var i = 0; i < nodeFeeds.length; i++) {
                totalSize += Number(nodeFeeds[i].size);
            }
            return this.formatSize(totalSize);
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
        
        formatSize: function(bytes) {
            if (!$.isNumeric(bytes)) {
                return "n/a";
            } else if (bytes < 1024) {
                return bytes + "B";
            } else if (bytes < 1024 * 100) {
                return (bytes / 1024).toFixed(1) + "KB";
            } else if (bytes < 1024 * 1024) {
                return Math.round(bytes / 1024) + "KB";
            } else if (bytes <= 1024 * 1024 * 1024) {
                return Math.round(bytes / (1024 * 1024)) + "MB";
            } else {
                return (bytes / (1024 * 1024 * 1024)).toFixed(1) + "GB";
            }
        },
        
        formatValue: function(value, unit) {
            if (unit == undefined) unit = "";
            return this.formatValueDynamic(value) + ' ' + unit;
        },
        
        formatValueDynamic: function(value) {
            if (value == null) return "NULL";
            value = parseFloat(value);
            if (value >= 1000) value = parseFloat(value.toFixed(0));
            else if (value >= 100) value = parseFloat(value.toFixed(1));
            else if (value >= 10) value = parseFloat(value.toFixed(2));
            else if (value <= -1000) value = parseFloat(value.toFixed(0));
            else if (value <= -100) value = parseFloat(value.toFixed(1));
            else if (value < 10) value = parseFloat(value.toFixed(2));
            return value;
        },
        
        openFeedGraph: function(feedid) {
            var public_username_str = "";
            if (public_userid) public_username_str = public_username+"/";            
            window.location = path+public_username_str+feedviewpath+feedid;
        },
        
        toggleFeedPublic: function(feed) {

        },
        
        onFeedSelectionChange: function() {
            // Hide filter when feeds are selected
            if (this.selectedFeedCount > 0) {
                $("#filter").hide();
            } else {
                $("#filter").show();
            }
        },
        
        // Integrated expand/collapse functionality
        expandAllNodes: function(state) {
            if (typeof state == 'undefined') {
                // Determine current state - true if all expanded
                var allExpanded = true;
                for (var node in this.nodes) {
                    if (!this.nodesDisplay[node]) {
                        allExpanded = false;
                        break;
                    }
                }
                state = !allExpanded;
            }
            
            // Set all nodes to the new state
            var newState = {};
            for (var node in this.nodes) {
                newState[node] = state;
            }
            this.nodesDisplay = newState;
        },
        
        // Integrated select all functionality
        selectAllFeeds: function(state) {
            if (typeof state == 'undefined') {
                state = !this.allSelected;
            }
            
            var newSelections = {};
            for (var feedid in this.feeds) {
                newSelections[feedid] = state;
            }
            this.selectedFeeds = newSelections;
            
            // Expand all if selecting all
            if (state === true) {
                this.expandAllNodes(true);
            }
        },
        
        graphSelectedFeeds: function() {
            var graph_feeds = [];
            for (var feedid in this.selectedFeeds) {
                if (this.selectedFeeds[feedid]) graph_feeds.push(feedid);
            }

            var public_username_str = "";
            if (public_userid) public_username_str = public_username+"/";
            
            window.location = path+public_username_str+feedviewpath+graph_feeds.join(",");
        },
        
        processSelectedFeed: function() {
            // There should only ever be one feed that is selected here:
            var feedid = 0;
            for (var z in this.selectedFeeds) {
                if (this.selectedFeeds[z]) feedid = z;
            }
            var contextname = this.feeds[feedid].tag + ": " + this.feeds[feedid].name;
            process_vue.load(1, feedid, this.feeds[feedid].processList, contextname, null, null); // load configs
        },

        // Integrated refresh functionality
        refreshFeedSize: function() {
            var self = this;
            $.ajax({ url: path+"feed/updatesize.json", async: true, success: function(bytes){ 
                update_feed_list(); 
                alert('<?php echo addslashes(tr("Total size of used space for feeds:")); ?>' + self.formatSize(bytes)); 
            } });
        },

        // Actions: edit, delete, downsample, export
        editFeeds: function() {
            selected_feeds = this.selectedFeeds;
            openEditFeedModal();
        },
        deleteFeeds: function() {
            selected_feeds = this.selectedFeeds;
            openDeleteFeedModal();
        },
        downsampleFeeds: function() {
            selected_feeds = this.selectedFeeds;
            openDownsampleModal();
        },
        exportFeeds: function() {
            selected_feeds = this.selectedFeeds;
            openFeedExportModal();
        }
    }
});

// -----------------------------------------------------------------------------
// Feed list update function
// -----------------------------------------------------------------------------
setTimeout(update_feed_list,1);
setInterval(update_feed_list,5000);
filter.oninput = update_feed_list;

var first_load = true;
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
    
        // Show/hide no feeds alert - now handled by Vue
        $('#feed-loader').hide();
        
        // Filter feeds
        feeds = {};
        filterText = filter.value.toLowerCase()
        for (var z in data) {
            if (filterText == '' || data[z].name.toLowerCase().includes(filterText)) {
                feeds[data[z].id] = data[z];
            }
        }

        node_time_and_colour = {};

        // Get feed and node colours and formatted values
        for (var z in feeds) {
            var formatted_time = formatTime(feeds[z].time, feeds[z].interval);

            feeds[z].color = formatted_time.color;
            feeds[z].color_code = formatted_time.color_code;
            feeds[z].formatted_time = formatted_time.text;

            if (node_time_and_colour[feeds[z].tag]==undefined || formatted_time.color_code > node_time_and_colour[feeds[z].tag].color_code) {
                node_time_and_colour[feeds[z].tag] = formatted_time;
            }
        }

        // get processList html
        for (var z in feeds) {
            if (feeds[z].processList != undefined && feeds[z].processList) {
                feeds[z].processListHTML = process_vue ? process_vue.drawPreview(feeds[z].processList, feeds[z]) : '';
            }
        }
        
        // Group feeds by node
        nodes = {};
        for (var z in feeds) {
            var node = feeds[z].tag;
            if (nodes[node]==undefined) nodes[node] = [];
            nodes[node].push(feeds[z]);
        }

        // make copy of current nodesDisplay on first load
        let nodes_display = feedApp.nodesDisplay;
        for (var n in nodes) {
            if (nodes_display[n] === undefined) {
                // First time seeing this node, expand by default
                nodes_display[n] = true;
            }
        }

        
        // Update Vue.js data
        feedApp.nodes = Object.assign({}, nodes);
        feedApp.feeds = Object.assign({}, feeds);
        feedApp.node_time_and_colour = Object.assign({}, node_time_and_colour);
        feedApp.nodesDisplay = Object.assign({}, nodes_display);
        feedApp.feedsLoaded = true;
    }}); // end of ajax callback
}// end of update_feed_list() function

// Used by new feed modal autocomplete?
function buildFeedNodeList() {
    node_names = [];
    for (n in nodes) {
        let feed = nodes[n];
        node_names.push(feed[0].tag)
    }
    autocomplete(document.getElementById("feed-node"), node_names);
}

// -----------------------------------------------------------------------------
// Helper functions
// -----------------------------------------------------------------------------

// This could be moved to a shared utility file if needed elsewhere
function formatTime(time, interval) {
    interval = interval || 1;
    var servertime = (new Date()).getTime() - (app.timeServerLocalOffset || 0);
    time = new Date(time * 1000);
    var update = time.getTime();

    var delta = servertime - update;
    var secs = Math.abs(delta) / 1000;
    var mins = secs / 60;
    var hour = secs / 3600;
    var day = hour / 24;

    var updated = secs.toFixed(0) + "s";
    if ((update == 0) || (!$.isNumeric(secs))) updated = "n/a";
    else if (secs.toFixed(0) == 0) updated = "now";
    else if (day > 365 && delta > 0) updated = time.toLocaleDateString("en-GB",{year:"numeric", month:"short"});
    else if (day > 31 && delta > 0) updated = time.toLocaleDateString("en-GB",{month:"short", day:"numeric"});
    else if (day > 2) updated = day.toFixed(0) + " days";
    else if (hour > 2) updated = hour.toFixed(0) + " hrs";
    else if (secs > 180) updated = mins.toFixed(0) + " mins";

    secs = Math.abs(secs);

    var color_code = 5;                                  // grey    - Inactive

    if (interval == 1) {                                 // => Variable Interval Feeds
        if (delta < 0) color_code = 0;                   // blue    - Ahead of time!
        else if (secs < 30) color_code = 1;              // green   - < 30s
        else if (secs < 60) color_code = 2;              // yellow  - < 2 min
        else if (secs < (60 * 60)) color_code = 3;       // orange  - < 1h
        else if (secs < (3600*24*31)) color_code = 4;    // red     - < 1 month
    }
    else {                                               // => Fixed Interval Feeds
        if (delta < 0) color_code = 0;                   // blue    - Ahead of time!
        else if (secs < interval*3) color_code = 1;      // green   - < 3x interval
        else if (secs < interval*6) color_code = 2;      // yellow  - < 6x interval
        else if (secs < interval*12) color_code = 3;     // orange  - < 12x interval
        else if (secs < (3600*24*31)) color_code = 4;    // red     - < 1 month
    }

    var colours = [
        "rgb(60,135,170)",  // 0: blue
        "rgb(50,200,50)",   // 1: green
        "rgb(240,180,20)",  // 2: yellow
        "rgb(255,125,20)",  // 3: orange
        "rgb(255,0,0)",     // 4: red
        "rgb(150,150,150)", // 5: grey
    ];

    var color = colours[color_code];

    return {color:color, color_code: color_code, text:updated};
}

// -----------------------------------------------------------------------------


$("#refreshfeedsize").click(function(){
    feedApp.refreshFeedSize();
});

</script>
<?php require "Modules/feed/Views/feed_new_modal.php"; ?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/exporter.js?v=5"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/importer.js?v=5"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/downsample.js?v=5"></script>