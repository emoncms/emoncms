
if (public_userid) {
   $("#feeds-title").hide();
   $("#public-feeds-title").show();
}
if (!session_write) {
   $("#feed-footer").hide();
}

var feeds = {};
var nodes = {};
var nodes_display = {};
var node_time_and_colour = {};
var feed_engines = ['MYSQL','TIMESTORE','PHPTIMESERIES','GRAPHITE','PHPTIMESTORE','PHPFINA','PHPFIWA (No longer supported)','VIRTUAL','MEMORY','REDISBUFFER','CASSANDRA'];

available_intervals = available_intervals.map(function(a) { return a['interval']; });

// Vue.js Feed List Application
var feedApp = new Vue({
    el: '#feed-app',
    data: {
        nodes: {},
        feeds: {},
        selectedFeeds: {},
        nodesDisplay: {},
        node_time_and_colour: {},
        feedsLoaded: false,
        filterText: ''
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
        },

        filteredNodes: function() {
            if (!this.filterText) return this.nodes;
            var filterText = this.filterText.toLowerCase();
            var result = {};
            for (var node in this.nodes) {
                var nodeMatch = node.toLowerCase().includes(filterText);
                var filteredFeeds = nodeMatch ? this.nodes[node] : this.nodes[node].filter(function(feed) {
                    return feed.name.toLowerCase().includes(filterText);
                });
                if (filteredFeeds.length > 0) {
                    result[node] = filteredFeeds;
                }
            }
            return result;
        }
    },
    watch: {
        filterText: function(val) {
            if (!val) return;
            // Auto-expand any node that has a matching feed
            var newDisplay = Object.assign({}, this.nodesDisplay);
            for (var node in this.filteredNodes) {
                newDisplay[node] = true;
            }
            this.nodesDisplay = newDisplay;
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
            var cssClass = 'engine-other';
            if (engineName == 'PHPFINA')           { engineName = 'FIXED';    cssClass = 'engine-fixed'; }
            else if (engineName == 'PHPTIMESERIES'){ engineName = 'VARIABLE'; cssClass = 'engine-variable'; }
            else if (engineName == 'MYSQL')        { cssClass = 'engine-mysql'; }
            else if (engineName == 'VIRTUAL')      { cssClass = 'engine-virtual'; }
            else if (engineName == 'MEMORY')       { cssClass = 'engine-memory'; }
            else if (engineName == 'REDISBUFFER')  { engineName = 'REDIS'; cssClass = 'engine-redis'; }
            else if (engineName == 'CASSANDRA')    { cssClass = 'engine-cassandra'; }
            var intervalTag = (engine == 5)
                ? '<span class="interval-sep"></span><span class="interval-tag">' + interval + 's</span>'
                : '';
            return '<span class="engine-badge ' + cssClass + '">' + engineName + intervalTag + '</span>';
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
            // handled reactively via :class binding on filter input
        },
        
        // Integrated expand/collapse functionality
        toggleNode: function(node) {
            Vue.set(this.nodesDisplay, node, !this.nodesDisplay[node]);
        },

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
        nodeHasSelection: function(node) {
            var nodeFeeds = this.nodes[node];
            if (!nodeFeeds) return false;
            for (var i = 0; i < nodeFeeds.length; i++) {
                if (this.selectedFeeds[nodeFeeds[i].id]) return true;
            }
            return false;
        },
        
        isNodeFullySelected: function(node) {
            var nodeFeeds = this.nodes[node];
            if (!nodeFeeds || nodeFeeds.length === 0) return false;
            for (var i = 0; i < nodeFeeds.length; i++) {
                if (!this.selectedFeeds[nodeFeeds[i].id]) return false;
            }
            return true;
        },
        
        getNodeSelectedCount: function(node) {
            var nodeFeeds = this.nodes[node];
            if (!nodeFeeds) return 0;
            var count = 0;
            for (var i = 0; i < nodeFeeds.length; i++) {
                if (this.selectedFeeds[nodeFeeds[i].id]) count++;
            }
            return count;
        },
        
        selectAllNodeFeeds: function(node) {
            var nodeFeeds = this.nodes[node];
            if (!nodeFeeds) return;
            var fullySelected = this.isNodeFullySelected(node);
            var newSelections = Object.assign({}, this.selectedFeeds);
            for (var i = 0; i < nodeFeeds.length; i++) {
                newSelections[nodeFeeds[i].id] = !fullySelected;
            }
            this.selectedFeeds = newSelections;
            this.onFeedSelectionChange();
        },
        
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
                alert(tr("Total size of used space for feeds:") + self.formatSize(bytes)); 
            } });
        },

        // Actions: edit, delete, downsample, export
        editFeeds: function() {
            openEditFeedModal();
        },
        deleteFeeds: function() {
            openDeleteFeedModal();
        },
        downsampleFeeds: function() {
            openDownsampleModal();
        },
        exportFeeds: function() {
            openFeedExportModal();
        }
    }
});

// -----------------------------------------------------------------------------
// Feed list update function
// -----------------------------------------------------------------------------
setTimeout(update_feed_list,1);
setInterval(update_feed_list,5000);

function update_feed_list() {
    var public_username_str = "";
    if (public_userid) public_username_str = public_username+"/";
    var requestTime = (new Date()).getTime();

    $.ajax({ url: path+public_username_str+"feed/list.json?meta=1", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
        feedApp.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
        if (data.message!=undefined && data.message=="Username or password empty") {
            window.location.href = "/";
            return false;
        }

        $('#feed-loader').hide();

        // Index incoming data by id
        var incoming = {};
        for (var z in data) {
            incoming[data[z].id] = data[z];
        }

        // Detect structural changes: feeds added/removed, or a feed moved to a different node.
        // In-place updates are used for all other polls to avoid full list re-renders.
        var incomingIds = Object.keys(incoming);
        var structuralChange = !feedApp.feedsLoaded || incomingIds.length !== Object.keys(feedApp.feeds).length;
        if (!structuralChange) {
            for (var id in incoming) {
                if (!feedApp.feeds[id] || feedApp.feeds[id].tag !== incoming[id].tag) {
                    structuralChange = true;
                    break;
                }
            }
        }

        node_time_and_colour = {};

        if (structuralChange) {
            // Full rebuild: feeds were added/removed or moved between nodes
            feeds = {};
            for (var z in data) {
                feeds[data[z].id] = data[z];
            }

            for (var z in feeds) {
                var formatted_time = formatTime(feeds[z].time, feeds[z].interval);
                feeds[z].color = formatted_time.color;
                feeds[z].color_code = formatted_time.color_code;
                feeds[z].formatted_time = formatted_time.text;
                if (node_time_and_colour[feeds[z].tag]==undefined || formatted_time.color_code > node_time_and_colour[feeds[z].tag].color_code) {
                    node_time_and_colour[feeds[z].tag] = formatted_time;
                }
                if (feeds[z].processList) {
                    feeds[z].processListHTML = process_vue ? process_vue.drawPreview(feeds[z].processList, feeds[z]) : '';
                }
            }

            nodes = {};
            for (var z in feeds) {
                var node = feeds[z].tag;
                if (nodes[node]==undefined) nodes[node] = [];
                nodes[node].push(feeds[z]);
            }

            var nodes_display = feedApp.nodesDisplay;
            for (var n in nodes) {
                if (nodes_display[n] === undefined) nodes_display[n] = true;
            }

            feedApp.feeds = Object.assign({}, feeds);
            feedApp.nodes = Object.assign({}, nodes);
            feedApp.node_time_and_colour = Object.assign({}, node_time_and_colour);
            feedApp.nodesDisplay = Object.assign({}, nodes_display);
        } else {
            // In-place update: patch only display fields on existing reactive feed objects.
            // Because feedApp.feeds[id] and feedApp.nodes[tag][i] reference the same feed object,
            // updating one updates both, and Vue surgically re-renders only the changed cells.
            for (var id in incoming) {
                var fresh = incoming[id];
                var existing = feedApp.feeds[id];
                var formatted_time = formatTime(fresh.time, fresh.interval);

                existing.value    = fresh.value;
                existing.time     = fresh.time;
                existing.size     = fresh.size;
                existing.public   = fresh.public;
                existing.unit     = fresh.unit;
                existing.name     = fresh.name;
                existing.color    = formatted_time.color;
                existing.color_code = formatted_time.color_code;
                existing.formatted_time = formatted_time.text;

                if (fresh.processList !== existing.processList) {
                    existing.processList = fresh.processList;
                    existing.processListHTML = process_vue ? process_vue.drawPreview(fresh.processList, existing) : '';
                }

                var tag = fresh.tag;
                if (node_time_and_colour[tag]==undefined || formatted_time.color_code > node_time_and_colour[tag].color_code) {
                    node_time_and_colour[tag] = formatted_time;
                }
            }

            feedApp.node_time_and_colour = Object.assign({}, node_time_and_colour);
        }

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
    var servertime = (new Date()).getTime() - (feedApp.timeServerLocalOffset || 0);
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