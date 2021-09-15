<?php
    defined('EMONCMS_EXEC') or die('Restricted access');
    global $path, $settings;
    $v=1;
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>

<script src="<?php echo $path; ?>Lib/moment.min.js"></script>
<script>
    var _user = {};
    _user.lang = "<?php echo $_SESSION['lang']; ?>";
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
        'Tag': "<?php echo _('Tag') ?>",
        'Feed ID': "<?php echo _('Feed ID') ?>",
        'Feed Interval': "<?php echo _('Feed Interval') ?>",
        'Feed Start Time': "<?php echo _('Feed Start Time') ?>",
        'Realtime': "<?php echo _('Realtime') ?>",
        'Daily': "<?php echo _('Daily') ?>"
    }
}
/**
 * wrapper for gettext like string replace function
 */
function _(str) {
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


<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js?v=<?php echo $v; ?>"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/responsive-linked-tables.js?v=<?php echo $v; ?>"></script>

<link href="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script src="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js"></script>

<script type="text/javascript" src="<?php echo $path; ?>Lib/misc/autocomplete.js?v=<?php echo $v; ?>"></script>
<link rel="stylesheet" href="<?php echo $path; ?>Lib/misc/autocomplete.css?v=<?php echo $v; ?>">

<style>
body{padding:0!important}

#table {
    margin-top:3rem
}
#footer {
    margin-left: 0px;
    margin-right: 0px;
}
.node .feed-graph-link {
    cursor:pointer;
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

#mouse-position{position:absolute;z-index:999999;width:0em;height:0em;background:red}

.node .accordion-toggle{
    border-bottom: 1px solid white;
}
.node .accordion-toggle,
.node-feeds .node-feed {
    position: relative;
}
.node .accordion-toggle:after,
.node-feeds .node-feed:after{
    content: '';
    width: .4em;
    height: 100%;
    display: block;
    position: absolute;
    top: 0;
    right: 0;
}

.node-feeds .node-feed.status-warning:after,
.node.status-warning .accordion-toggle:after {
    background: #FFC107;
}
.node-feeds .node-feed.status-success:after,
.node.status-success .accordion-toggle:after {
    background: #28A745;
}
.node-feeds .node-feed.status-danger:after,
.node.status-danger .accordion-toggle:after {
    background: #DC3545;
}

.node.status-warning .accordion-toggle .last-update,
.node-feeds .node-feed.status-warning .last-update{
    color: #C70!important;
}
.node.status-success .accordion-toggle .last-update,
.node-feeds .node-feed.status-success .last-update{
    color: #28A745!important; 
}
.node.status-danger .accordion-toggle .last-update,
.node-feeds .node-feed.status-danger .last-update{
    color: #DC3545!important;
}

</style>
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


<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED EDIT MODAL                                                                                                                               -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedEditModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="feedEditModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="feedEditModalLabel"><?php echo _('Edit feed'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Feed node:'); ?><br>
        <div class="autocomplete">
            <input id="feed-node" type="text" style="margin-bottom:0">
        </div>
        </p>

        <p><?php echo _('Feed name:'); ?><br>
        <input id="feed-name" type="text"></p>

        <p><?php echo _('Make feed public:'); ?>
        <input id="feed-public" type="checkbox"></p>

        <p><?php echo _('Feed Unit'); ?></p>
        <div class="input-prepend">
        <select id="feed_unit_dropdown" style="width:auto">
            <option value=""></option>
        <?php
        // add available units from units.php
        include('Lib/units.php');
        if (defined('UNITS')) {
            foreach(UNITS as $unit){
                printf('<option value="%s">%s (%1$s)</option>',$unit['short'],$unit['long']);
            }
        }
        ?>
            <option value="_other"><?php echo _('Other'); ?></option>
        </select>
        <input type="text" id="feed_unit_dropdown_other" style="width:100px"/>
        </div>
    </div>
    <div class="modal-footer">
        <div id="feed-edit-save-message" style="position:absolute"></div>
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Close'); ?></button>
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
                <p><b><?php echo _('End date & time ');?></b></p>
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
                <label><?php echo _('Offset secs (for daily)');?>&nbsp;<input id="export-timezone-offset" type="text" class="input-mini" disabled></label>
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
        <div class="clearfix d-flex row">
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

var feedviewpath = "<?php echo $settings['interface']['feedviewpath']; ?>";

var feeds = {};
var nodes = {};
var selected_feeds = {};
var local_cache_key = 'feed_nodes_display';
var nodes_display = {};
var feed_engines = ['MYSQL','TIMESTORE','PHPTIMESERIES','GRAPHITE','PHPTIMESTORE','PHPFINA','PHPFIWA (No longer supported)','VIRTUAL','MEMORY','REDISBUFFER','CASSANDRA'];

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
        // if(firstLoad) docCookies.setItem(local_cache_key, JSON.stringify(nodes_display));
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


// ---------------------------------------------------------------------------------------------
// EDIT FEED
// ---------------------------------------------------------------------------------------------
$(".feed-edit").click(function() {
    $('#feedEditModal').modal('show');
    var edited_feeds = $.map(selected_feeds, function(val,key){ return val ? key: null });
    var feedid = 0;
    // Now allows for multiple feed selection
    for (var z in selected_feeds) {
        if (selected_feeds[z]){
            feedid = z;
            if (edited_feeds.length == 1) {
                $("#feed-name").prop('disabled',false).val(feeds[feedid].name);
            } else {
                $("#feed-name").prop('disabled',true).val('').attr('placeholder',"<?php echo _('Unable to rename multiple feeds') ?>");
            }
            $("#feed-node").val(feeds[feedid].tag);
            var checked = false; if (feeds[feedid]['public']==1) checked = true;
            $("#feed-public")[0].checked = checked;
            
            // pre-select item if already set
            let $dropdown = $('#feed_unit_dropdown');
            $dropdown.val(feeds[feedid].unit);
            // set the dropdown to "other" if value not in list
            let options = [];
            $dropdown.find('option').each(function(key,elem){
                options.push(elem.value);
            })
            if (options.indexOf(feeds[feedid].unit) == -1) {
                $('#feed_unit_dropdown_other').val(feeds[feedid].unit);
                $dropdown.val('_other');
            }
            // show / hide "other" free text field on load and on change if "other" selected in dropdown
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
        }
    }
    
    buildFeedNodeList();
});

$(".feed-node").on('input', function(event){
    $('#feed-node').val($(this).val());
});

$("#feed-edit-save").click(function() {
    var feedid = 0;
    var edited_feeds = $.map(selected_feeds, function(val,key){ return val ? key: null });

    for (var z in selected_feeds) {
        if (selected_feeds[z]) {
            feedid = z; 
            
            var publicfeed = 0;
            if ($("#feed-public")[0].checked) publicfeed = 1;
            
            var unit = $('#feed_unit_dropdown').val();
            unit = unit == '_other' ? $('#feed_unit_dropdown_other').val() : unit;
            
            var fields = {
                'tag': $("#feed-node").val(), 
                'public': publicfeed,
                'unit': unit
            };
            // if only one feed selected add the name value
            if(edited_feeds.length==1) {
                fields.name = $("#feed-name").val();
            }
            // only send changed values
            var data = {};
            for (f in fields) {
                // console.log(fields[f],feeds[feedid][f],{matched:fields[f]===feeds[feedid][f]})
                if (!(fields[f]===feeds[feedid][f])) data[f] = fields[f];
            }
            // console.log(Object.keys(data).length);
            // dont send ajax if nothing changed
            if (Object.keys(data).length==0) {
                $('#feedEditModal').modal('hide');
                return;
            }
            $('#feed-edit-save-message').text('').hide();
            $.ajax({ url: path+"feed/set.json?id="+feedid+"&fields="+JSON.stringify(data), dataType: 'json'})
            .done(function(response) {
                if(response.success !== true) {
                    // error
                    $('#feed-edit-save-message').text(response.message).fadeIn();
                } else {
                    // ok
                    update_feed_list();
                    $('#feedEditModal').modal('hide');
                    $('#feed-edit-save-message').text('').hide();
                }
            })
        }
    }
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
        let_feeds = {}; // list of feeds and their accociated processes

    // create a list of all inputs that have processes
    for (inputid in processlist_ui.inputlist) {
        let input = processlist_ui.inputlist[inputid];
        if (input.processList.length>0) {
            inputs[inputid] = {
                processList: processlist_ui.decode(input.processList),
                nodeid: input.nodeid,
                name: input.name,
                inputid: inputid
            };
        }
    }
    // get all the processes that write to a feed - list them by numeric key (if available)
    for (processid in processlist_ui.processlist) {
        let process = processlist_ui.processlist[processid];
        if (process.feedwrite) {
            key = process.hasOwnProperty('id_num') ? process.id_num : processid;
            feedProcesses[key] = processid;
        }
    }

    // go through all the input processes and get all the feeds they output to
    for (inputid in inputs) {
        let input = inputs[inputid];
        // loop through the key / value pairs of each input processlist
        for (item in input.processList) {
            let processid = input.processList[item][0];
            let processval = input.processList[item][1] || null;
            if(feedProcesses[processid]){
                //this process writes to feed
                let_feeds[processval] = let_feeds[processval] || [];
                let_feeds[processval].push({
                    process: processlist_ui.processlist[feedProcesses[processid]],
                    input: input,
                    feedid: processval
                });
            }
        }
    }
    return let_feeds;
}

/**
 * output what feeds have been selected in the overlay modal box
 *
 * @return void
 */
function showSelectedFeeds(feed_inputs) {
    // loop through selection 
    let selected = [];
    for (var feedid in selected_feeds) {
        if (selected_feeds[feedid] == true) {
            selected[feedid] = feeds[feedid];
            if (feed_inputs[feedid]) {
                if (Array.isArray(feed_inputs[feedid])) {
                    selected[feedid].input = [];
                    selected[feedid].process = [];
                    for (f in feed_inputs[feedid]) {
                        selected[feedid].input.push(feed_inputs[feedid][f].input);
                        selected[feedid].process.push(feed_inputs[feedid][f].process);
                    }
                } else {
                    selected[feedid].input = [feed_inputs[feedid].input];
                    selected[feedid].process = [feed_inputs[feedid].process];
                }
            }
        }
    }

    // count the number of processess associated with the selected feeds
    let list='',titles={},linked=[],total_linked = 0;
    for(s in selected) {
        titles[s] = selected[s].tag+":"+selected[s].name;
        // virtual feed processes
        if ( selected[s].hasOwnProperty('processList') && selected[s].processList && selected[s].processList.length > 0 ) {
            linked.push(selected[s]);
            let virtualProcesses = processlist_ui.decode(selected[s].processList);
            for(p in virtualProcesses) {
                total_linked++;
            }
        }
        // feed's linked/parent process
        if ( selected[s].hasOwnProperty('process') && selected[s].process && selected[s].process.length > 0 ) {
            linked.push(selected[s]);
            for(i=0;i<selected[s].process.length;i++) {
                total_linked++;
            }
        }
    }
    // create html to display the results
    // notify user that feed is associated to processList
    
    // create a simple list of feed ids and names to display to the user
    let feedListShort = '';
    for(id in titles){
        feedListShort += '['+id+'] '+titles[id]+', ';
    }
    // remove the last comma
    feedListShort = feedListShort.slice(0, -2);

    // create a container to store the result that is displayed to the user
    total_summary = '<div id="deleteFeedModalSelectedItems">';
    total_selected = Object.keys(titles).length;
    if (total_selected == 1) {
    // if only one is selected display it's id & name
        feedProcessList = total_linked > 0 ? '<span class="badge badge-default" style="padding-left:4px"><i class="icon icon-white icon-exclamation-sign"></i> <?php echo _('1 Input process associated with this feed') ?>':'';
        total_summary += '<h5>'+feedListShort+'</h5>';
    } else {
    // show a summary total if more than one are selected
        feedProcessList = total_linked > 0 ? '<span class="badge badge-default" style="padding-left:4px"><i class="icon icon-white icon-exclamation-sign"></i> '+(' <?php echo _('%s Input processes associated with these feeds') ?>'.replace('%s',total_linked))+'</span>' : '';
        total_summary += '<h5 title="'+feedListShort+'"><?php echo _('%s Feeds selected') ?> <i class="icon icon-question-sign"></i></h5>'.replace('%s', total_selected);
    }
    total_summary += '</div>';
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
    let startDate = start_time==0 ? new Date() : new Date(start_time*1000);
    $datetimepicker = $('#feed_trim_datetimepicker');
    $datetimepicker
        .datetimepicker({startDate: startDate}) // restrict calendar selection to the start time
        .datetimepicker('setValue', startDate) // set the date/time picker to the start time
        .on('changeDate', function(event){
            // mark any matching buttons as active
            $('[data-relative_time]').each(function(i,elem){
                if ($(elem).data('startdate') != event.date) {
                    $(this).removeClass('active')
                }
            });
        });
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
    let startDate = start_time>0 ? new Date(start_time*1000) : new Date();

    $('[data-relative_time]').each(function(i,v){
        $btn = $(this);
        // add more cases here for additional options (and also data-relative_time='xyz' in the html)
        // returns function so that the dates are calculated to when the user clicks the buttons
        switch ($btn.data('relative_time')) {
        case '-2y':
            relativeTime = (function(){ now = new Date(); return new Date(now.getFullYear()-2,now.getMonth(),now.getDate(),now.getHours(),now.getMinutes(),now.getSeconds(),now.getMilliseconds()) });
            break;
        case '-1y':
            relativeTime = (function(){ now = new Date(); return new Date(now.getFullYear()-1,now.getMonth(),now.getDate(),now.getHours(),now.getMinutes(),now.getSeconds(),now.getMilliseconds()) });
            break;
        case 'start':
            relativeTime = startDate;
            break;
        default:
            relativeTime = new Date();
        }
        relativeTime = typeof relativeTime === 'function' ? relativeTime() : relativeTime;
        // set the timestamp as a data property of the button so that it can be referenced on click
        $btn.data('startdate', relativeTime.valueOf() );
        // make sure the calculated date is not beyond the start date
        if (relativeTime < startDate) {
            $btn.hide() // hide button date is beyond start date
            $btn.css({'font-style':'italic', color:'#9a9eaa'});
            $btn.attr('title',$btn.attr('title')+' - [<?php echo _('Out of range')?>]');
        }
    })
    // open date picker on input focus
    $('#trim_start_time').on('focus', function(event){ $datetimepicker.datetimepicker('show') });
    
    // alter the trim date / time picker on button presses
    $('[data-relative_time]').click(function(event){
        event.preventDefault();
        $btn = $(this);
        $btn.addClass('active').siblings().removeClass('active');
        $input = $('#trim_start_time');
        // get starttime from button's data
        date = new Date($btn.data('startdate'));
        // restrict selection to the earliest possible date
        if (date < startDate) {
            date = startDate;
        }
        // rebuild the date string from the new date object
        Y = date.getFullYear();
        m = (date.getMonth()+1).pad(2);
        d = date.getDate().pad(2);
        h = date.getHours().pad(2);
        i = date.getMinutes().pad(2);
        s = date.getSeconds().pad(2);
        
        // show date in input field - DD/MM/YYYY HH:MM:SS
        newDateString = [[d,m,Y].join('/'),[h,i,s].join(':')].join(' ');
        $input.val(newDateString);
    });
}

/**
 * compares all the selected feed start_times to see which is the best suited for the group 
 * @return int start_time timestamp
 */
function getEarliestStartTime() { 
    let start_time = 0;
    for (var feedid in selected_feeds) {
        if (selected_feeds[feedid] == true) {
            // record the earliest possible start_time for all the selected feeds
            start_time = feeds[feedid].start_time > start_time ? feeds[feedid].start_time : start_time;
        }
    }
    return start_time;
}

/**
 * mark button as selected if chosen date in date/time picker matches
 * jQuery Event handler for datetime picker's changeDate event
 */
$('#feed_trim_datetimepicker').on('changeDate',function(event){
    $('[data-relative_time]').each(function(){
        $btn = $(this);
        if ($btn.data('startdate') == event.date.valueOf()) {
            $btn.addClass('active').siblings().removeClass('active');
        }
    })
});

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
        const VIRTUALFEED = 7;   // Virtual feed, on demand post processing
        const MYSQLMEMORY = 8;   // Mysql with MEMORY tables on RAM. All data is lost on shutdown 
        const REDISBUFFER = 9;   // (internal use only) Redis Read/Write buffer, for low write mode
        const CASSANDRA = 10;    // Cassandra
    */
    let allowed_engines = [0,5,8] // array of allowed storage engines
    for (var feedid in selected_feeds) {
        engineid = parseInt(feeds[feedid].engine); // convert string to number
        // if feed selected and engineid is NOT found in allowed_engines
        if (selected_feeds[feedid] == true && !isNaN(engineid) && allowed_engines.indexOf(engineid) == -1) {
            return false;
        }
    }
    return true;
}

/**
 * display a message to the user in the delete feed modal
 *
 * restores the original message after delay
 *
 * @param string message text to show to user
 */
function updateFeedDeleteModalMessage(response){
    let message = response.message;
    let success = response.success;
    let $msg = $('#feedDelete-message');
    let cssClassName = success ? 'label-success' : 'label-important';

    $msg.stop().fadeOut(function(){
        $(this).text(message).removeClass('label-warning').addClass(cssClassName).fadeIn();
    });
    setTimeout(function(){
        $msg.stop().fadeOut(function(){
            $msg.text($msg.data('default')).removeClass(cssClassName).addClass('label-warning').fadeIn();
        })
    }, 3800);
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
        enableTrim(start_time);
    } else {
        disableTrim();
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
    showFeedStartDate(start_time);
    // make buttons under the trim date input react on click
    initRelativeStartDateButtons(start_time);

    // remove any styling the disableTrim() function created
    $('#trimContainer').attr('title','').removeClass('muted')//.show()
        .find('h4').addClass('text-info').removeClass('muted').end()
        .find('button,input').removeClass('disabled')
        .find('input').val('');
    
    // enable the confirm trim button
    $('#feedTrim-confirm')
        .unbind('click')
        .click(function(){
            $modal = $('#feedDeleteModal');
            let $input = $modal.find("#trim_start_time");
            let input_date_string = $input.val();
            // dont submit if nothing selected
            // convert uk dd/mm/yyyy h:m:s to RFC2822 date
            let start_date = new Date(input_date_string.replace( /(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})/, "$3-$2-$1T$4:$5:$6"));
            let isValidDate = !isNaN(start_date.getTime()) && input_date_string != "";
            // exit if supplied date not valid
            if (!isValidDate) {
                $('#trim_start_time_container').addClass('error');
                $input.focus();
                return false;
            }else{
                if(confirm("<?php echo _('This is a new feature. Consider backing up your data before you continue. OK to continue?') ?>") == true) {
                    $('#trim_start_time_container').removeClass('error');
                    // set to seconds from milliseconds
                    let start_time = start_date.getTime()/1000;
                    $("#feedDelete-loader").fadeIn();
                    // run the trim() function on all the selected feeds
                    for (let feedid in selected_feeds) {
                        if (selected_feeds[feedid]) {
                            let response = feed.trim(feedid, start_time);
                            updateFeedDeleteModalMessage(response);
                            if (!response.success) {
                                break;
                            }
                        }
                    }
                    $("#feedDelete-loader").stop().fadeOut();
                    update_feed_list();
                    updaterStart(update_feed_list, 5000);
                }
            }
        });
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
        .find('input').val('');
    $('#feedTrim-confirm').unbind('click'); // remove previous click event (if it exists)
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
    let feed_processes = getFeedProcess();
    let selected_feeds_inputs = {};
    for (i in selected_feeds){
        // if a selected feed has an associated process id then save it into an array
        if (selected_feeds[i] && typeof feed_processes[i] != 'undefined') {
            selected_feeds_inputs[i] = feed_processes[i];
        }
    }

    // show the selected feeds and any associated processList
    showSelectedFeeds(selected_feeds_inputs);

    initTrim();
    initClear();
});

function isSelectionValidForClear(){
    /*
        const MYSQL = 0;
        const TIMESTORE = 1;     // Depreciated
        const PHPTIMESERIES = 2;
        const GRAPHITE = 3;      // Not included in core
        const PHPTIMESTORE = 4;  // Depreciated
        const PHPFINA = 5;
        const VIRTUALFEED = 7;   // Virtual feed, on demand post processing
        const MYSQLMEMORY = 8;   // Mysql with MEMORY tables on RAM. All data is lost on shutdown 
        const REDISBUFFER = 9;   // (internal use only) Redis Read/Write buffer, for low write mode
        const CASSANDRA = 10;    // Cassandra
    */
    let allowed_engines = [0,5,8]; // array of allowed storage engines 
    for (var feedid in selected_feeds) {
        engineid = parseInt(feeds[feedid].engine); // convert string to number
        // if feed selected and engineid is NOT found in allowed_engines
        if (selected_feeds[feedid] == true && !isNaN(engineid) && allowed_engines.indexOf(engineid) == -1) {
            return false;
        }
    }
    return true;
}

function initClear(){
    // get the most suitable start_time for all selected feeds
    if (isSelectionValidForClear()) {
        enableClear();
    } else {
        disableClear();
    }
}

function enableClear(){
    // remove any disable styling
    $('#clearContainer').attr('title','').removeClass('muted')//.show()
        .find('h4').addClass('text-info').removeClass('muted').end()
        .find('button').removeClass('disabled');

    $("#feedClear-confirm")
        .unbind('click')
        .click(function(){
            if( confirm("<?php echo _('Are you sure you want to delete all the feeds data?') ?>") == true ){
                $modal = $('#feedDeleteModal');
                $("#feedDelete-loader").fadeIn();

                for (let feedid in selected_feeds) {
                    if (selected_feeds[feedid]) {
                        let response = feed.clear(feedid);
                        updateFeedDeleteModalMessage(response);
                        if (!response.success) {
                            break;
                        }
                    }
                }
                $("#feedDelete-loader").stop().fadeOut();
                update_feed_list();
                updaterStart(update_feed_list, 5000);
            }
        });
}

function disableClear(){
    $("#feedClear-confirm").unbind();

    $('#clearContainer').attr('title','<?php echo _('"Clear" not available for this storage engine') ?>').addClass('muted')//.hide()
        .find('h4').removeClass('text-info').addClass('muted').end()
        .find('button').addClass('disabled');
}

$("#feedDelete-confirm").click(function(){
    if( confirm("<?php echo _('Are you sure you want to delete?') ?>") == true) {
        for (let feedid in selected_feeds) {
            if (selected_feeds[feedid]) {
                let response = feed.remove(feedid);
                response = response ? response : {success:true, message: '<?php echo _("Feeds Deleted") ?>'};
                updateFeedDeleteModalMessage(response);
            }
        }
        
        setTimeout(function() {
            update_feed_list();
            updaterStart(update_feed_list, 5000);
            $('#feedDeleteModal').modal('hide');
            feed_selection();
        }, 5000);
    }
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
        update_feed_list(); 
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

    var downloadlimit = <?php echo $settings['feed']['csv_downloadlimit_mb']; ?>;
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
    $("#downloadsize").html((downloadsize / 1024 / 1024).toFixed(2));
    var downloadlimit = <?php echo $settings['feed']['csv_downloadlimit_mb']; ?>;
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
</script>

