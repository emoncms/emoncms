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

.node-feeds .node-feed:after,
.accordion-toggle:after {
    background: var(--status-color)!important;
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

<div id="table" class="feed-list"></div>

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


<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED EDIT MODAL                                                                                                                               -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedEditModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="feedEditModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="feedEditModalLabel"><?php echo tr('Edit feed'); ?></h3>
    </div>
    <div class="modal-body">

        <div class="input-prepend input-append" id="edit-feed-name-div">
          <span class="add-on" style="width:100px"><?php echo tr('Name'); ?></span>
          <input id="feed-name" type="text" style="width:250px">
          <button class="btn btn-primary feed-edit-save" field="name">Save</button>
        </div>
    
        <div class="input-prepend input-append">
          <span class="add-on" style="width:100px"><?php echo tr('Node'); ?></span>
          <div class="autocomplete">
              <input id="feed-node" type="text" style="width:250px">
          </div>
          <button class="btn btn-primary feed-edit-save" field="node">Save</button>
        </div>

        <div class="input-prepend input-append">
          <span class="add-on" style="width:100px"><?php echo tr('Make public'); ?></span>
          <span class="add-on" style="width:255px"><input id="feed-public" type="checkbox"></span>
          <button class="btn btn-primary feed-edit-save" field="public">Save</button>
        </div>

        <div class="input-prepend input-append" id="edit-feed-name-div">
          <span class="add-on" style="width:100px"><?php echo tr('Unit'); ?></span>
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
              <option value="_other"><?php echo tr('Other'); ?></option>
          </select>
          <input type="text" id="feed_unit_dropdown_other" style="width:100px"/>       
          <button class="btn btn-primary feed-edit-save" field="unit">Save</button>
        </div>
    </div>
    <div class="modal-footer">
        <div id="feed-edit-save-message" style="position:absolute"></div>
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo tr('Close'); ?></button>
    </div>
</div>

<?php require "Modules/feed/Views/exporter.php"; ?>

<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED DELETE MODAL                                                                                                                             -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedDeleteModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="feedDeleteModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="feedDeleteModalLabel"><?php echo tr('Delete feed'); ?> 
        <span id="feedDelete-message" class="label label-warning" data-default="<?php echo tr('Deleting a feed is permanent.'); ?>"><?php echo tr('Deleting a feed is permanent.'); ?></span>
        </h3>
    </div>
    <div class="modal-body">
        <div class="clearfix d-flex row">
            <div id="clearContainer" class="span6">
                <div style="min-height:12.1em; position:relative" class="well well-small">
                    <h4 class="text-info"><?php echo tr('Clear') ?>:</h4>
                    <p><?php echo tr('Empty feed of all data') ?></p>
                    <button id="feedClear-confirm" class="btn btn-inverse" style="position:absolute;bottom:.8em"><?php echo tr('Clear Data'); ?>&hellip;</button>
                </div>
            </div>

            <div id="trimContainer" class="span6">
                <div class="well well-small">
                    <h4 class="text-info"><?php echo tr('Trim') ?>:</h4>
                    <p><?php echo tr('Empty feed data up to') ?>:</p>
                    <div id="trim_start_time_container" class="control-group" style="margin-bottom:1.3em">
                        <div class="controls">
                            <div id="feed_trim_datetimepicker" class="input-append date" style="margin-bottom:0">
                                <input id="trim_start_time" class="input-medium" data-format="dd/MM/yyyy hh:mm:ss" type="text" placeholder="dd/mm/yyyy hh:mm:ss">
                                <span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-calendar"></i></span>
                            </div>
                            <div class="btn-group" style="margin-bottom:-4px">
                                <button class="btn btn-mini active" title="<?php echo tr('Set to the start date') ?>" data-relative_time="start"><?php echo tr('Start') ?></button>
                                <button class="btn btn-mini" title="<?php echo tr('One year ago') ?>" data-relative_time="-1y"><?php echo tr('- 1 year') ?></button>
                                <button class="btn btn-mini" title="<?php echo tr('Two years ago') ?>" data-relative_time="-2y"><?php echo tr('- 2 year') ?></button>
                                <button class="btn btn-mini" title="<?php echo tr('Set to the current date/time') ?>" data-relative_time="now"><?php echo tr('Now') ?></button>
                            </div>
                        </div>
                    </div>
                    <button id="feedTrim-confirm" class="btn btn-inverse"><?php echo tr('Trim Data'); ?>&hellip;</button>
                </div>
            </div>
        </div>
        
        <div class="well well-small" style="margin-bottom:0">
            <h4 class="text-info"><?php echo tr('Delete')?>: <span id="feedProcessList"></span></h4>
            <p id="deleteFeedText"><?php echo tr('If you have Input Processlist processors that use this feed, after deleting it, review that process lists or they will be in error, freezing other Inputs. Also make sure no Dashboards use the deleted feed.'); ?></p>
            <p id="deleteVirtualFeedText"><?php echo tr('This is a Virtual Feed, after deleting it, make sure no Dashboard continue to use the deleted feed.'); ?></p>
            <button id="feedDelete-confirm" class="btn btn-danger"><?php echo tr('Delete feed permanently'); ?></button>
        </div>
    </div>
    <div class="modal-footer">
        <div id="feeds-to-delete" class="pull-left"></div>
        <div id="feedDelete-loader" class="ajax-loader" style="display:none;"></div>
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo tr('Close'); ?></button>
    </div>
</div>

<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- NEW VIRTUAL FEED                                                                                                                              -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="newFeedNameModal" class="modal hide keyboard" tabindex="-1" role="dialog" aria-labelledby="newFeedNameModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="newFeedNameModalLabel"><?php echo tr('New Feed'); ?></h3>
    </div>
    <div class="modal-body">
        <label><?php echo tr('Feed Name: '); ?></label>
        <input type="text" value="New Feed" id="newfeed-name">
        <label><?php echo tr('Feed Tag: '); ?></label>
        <input type="text" value="" id="newfeed-tag">
        <label><?php echo tr('Feed Engine: '); ?></label>
        <select id="newfeed-engine" style="width:350px">
            <option value="7" selected>VIRTUAL Feed</option>
            <?php foreach (Engine::get_all_descriptive() as $engine) { ?>
            <option value="<?php echo $engine["id"]; ?>"><?php echo $engine["description"]; ?></option>
            <?php } ?>
        </select>      
        <select id="newfeed-interval" class="input-mini hide">
            <?php foreach (Engine::available_intervals() as $i) { ?>
            <option value="<?php echo $i["interval"]; ?>"><?php echo $i["description"]; ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo tr('Cancel'); ?></button>
        <button id="newfeed-save" class="btn btn-primary"><?php echo tr('Save'); ?></button>
    </div>
</div>

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
            //$("#feed-header").hide();
            if (public_userid) {
                $("#public-feeds-none").show();
            } else {
                $("#feed-none").show();
            }
        } else {
            //$("#feed-header").show();
            $("#feed-none").hide();
            $("#public-feeds-none").hide();
        }
        feeds = {};
        filterText = filter.value.toLowerCase()
        for (var z in data) {
            if (filterText == '' || data[z].name.toLowerCase().includes(filterText)) {
                feeds[data[z].id] = data[z];
            }
        }
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
        


        // display nodes and feeds
        var counter = 0;
        for (var node in nodes) {
            counter ++;
            isCollapsed = !nodes_display[node];
            
            let feed_section = "";
            feed_section += "<div id='collapse"+counter+"' class='node-feeds collapse tbody "+( !isCollapsed ? 'in':'' )+"' data-node='"+node+"'>";
            
            // Color code for node is based on highest level of feeds
            var node_color_code = 0;
            var node_fv = null;
            var node_size = 0;

            // 1. Generate the feed rows

            for (var feed in nodes[node]) {
                var feed = nodes[node][feed];
                var feedid = feed.id;
                // Sum the size of all feeds in this node
                node_size += Number(feed.size);

                var title_lines = [feed.name,
                                  '-----------------------',
                                  tr('Tag') + ': ' + feed.tag,
                                  tr('Feed ID') + ': ' + feedid,
                                  tr('Feed Engine') + ': ' + feed_engines[feed.engine]]
                
                if(feed.engine == 5) {
                    title_lines.push(tr('Feed Interval')+": "+(feed.interval||'')+'s')
                } else {
                    title_lines.push(tr('Feed Interval (approx)')+": "+(feed.interval||'')+'s')
                }
                var processListHTML = '';
                if(feed.processList!=undefined && feed.processList.length > 0){
                    processListHTML = process_vue ? process_vue.drawPreview(feed.processList, feed) : '';
                }

                // show the start time if available
                if(feed.start_time > 0) {
                    title_lines.push(tr('Feed Start Time')+": "+feed.start_time);
                    title_lines.push(format_time(feed.start_time,'LL LTS')+" UTC");
                }

                if(feed.end_time > 0) {
                    title_lines.push(tr('Feed End Time')+": "+feed.end_time);
                    title_lines.push(format_time(feed.end_time,'LL LTS')+" UTC");
                }

                row_title = title_lines.join("\n");

                // Get the format for the feed
                var fv = list_format_updated_obj(feed.time,feed.interval);
                if (feed.time != null && parseInt(feed.engine) !== 7 && fv.color_code > node_color_code) {
                    // If the current feed has a higher color code, set it as the node color code
                    node_color_code = fv.color_code;
                    node_fv = fv;
                }

                feed_section += "<div class='node-feed feed-graph-link' style=\"--status-color: "+ fv.color + "\" feedid="+feedid+" title='"+row_title+"' data-toggle='tooltip'>";
                var checked = ""; if (selected_feeds[feedid]) checked = "checked";
                feed_section += "<div class='select text-center' data-col='B'><input class='feed-select' type='checkbox' feedid='"+feedid+"' "+checked+"></div>";
                feed_section += "<div class='name' data-col='A'>"+feed.name+"</div>";
                
                var publicfeed = "<i class='icon-lock'></i>";
                if (feed['public']==1) publicfeed = "<i class='icon-globe'></i>";
                
                feed_section += '<div class="public text-center" data-col="E">'+publicfeed+'</div>';
                
                let intervalstr = "";
                if (feed.engine==5) {
                    intervalstr = " ("+feed.interval+"s)";
                }
                
                let engine_name = feed_engines[feed.engine];
                if (engine_name=="PHPFINA") engine_name = "FIXED";
                else if (engine_name=="PHPTIMESERIES") engine_name = "VARIABLE";  
                
                feed_section += '  <div class="engine" data-col="G">'+engine_name+intervalstr+'</div>';
                feed_section += '  <div class="size text-center" data-col="H">'+list_format_size(feed.size)+'</div>';
                feed_section += '  <div class="processlist" data-col="F">'+processListHTML+'</div>';
                feed_section += '  <div class="node-feed-right pull-right">';
                if (feed.unit==undefined) feed.unit = "";
                feed_section += '    <div class="value text-center" data-col="C">'+list_format_value(feed.value)+' '+feed.unit+'</div>';
                feed_section += '    <div class="time text-center" data-col="D">'+list_format_updated(feed.time,feed.interval)+'</div>';
                feed_section += '  </div>';
                feed_section += '</div>';

            }
            
            feed_section += "</div>";

            // 2. Generate the node row for the feeds above

            // If not set, use default
            if (node_fv==null) node_fv = list_format_updated_obj(0);

            let node_row = "";
            node_row += '<div class="node accordion" style="--status-color: '+ node_fv.color + '">';
            node_row += '    <div class="node-info accordion-toggle thead'+(isCollapsed ? ' collapsed' : '')+'" data-toggle="collapse" data-target="#collapse'+counter+'">'
            node_row += '      <div class="select text-center has-indicator" data-col="B"><span class="icon-chevron-'+(isCollapsed ? 'right' : 'down')+' icon-indicator"></span></div>';
            node_row += '      <h5 class="name" data-col="A">'+node+':</h5>';
            node_row += '      <div class="public" class="text-center" data-col="E"></div>';
            node_row += '      <div class="engine" data-col="G"></div>';
            node_row += '      <div class="size text-center" data-col="H">'+list_format_size(node_size)+'</div>';
            node_row += '      <div class="processlist" data-col="F"></div>';
            node_row += '      <div class="node-feed-right pull-right">';
            node_row += '        <div class="value text-center" data-col="C"></div>';
            node_row += '        <div class="time text-center" data-col="D"><span class="last-update" style="color:' + node_fv.color + ';">' + node_fv.value + '</span></div>';
            node_row += '      </div>';
            node_row += '    </div>';

            out += node_row + feed_section + "</div>";
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
    
    var public_username_str = "";
    if (public_userid) public_username_str = public_username+"/";
    
    window.location = path+public_username_str+feedviewpath+feedid;
});

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
                $("#edit-feed-name-div").show();               
            } else {
                $("#edit-feed-name-div").hide();
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

$(".feed-edit-save").click(function() {
    var feedid = 0;
    var edited_feeds = $.map(selected_feeds, function(val,key){ return val ? key: null });
    
    var edit_field = $(this).attr("field");

    var error = false;

    for (var z in selected_feeds) {
        if (selected_feeds[z]) {
            feedid = z; 
            
            var fields = {}

            if (edit_field=="name" && edited_feeds.length==1) {
                fields.name = $("#feed-name").val();
            }
            
            if (edit_field=="node") {
                fields.tag = $("#feed-node").val();
            }
            
            if (edit_field=="public") {
                var publicfeed = 0;
                if ($("#feed-public")[0].checked) publicfeed = 1;  
                fields.public = publicfeed;
            }

            if (edit_field=="unit") {
                var unit = $('#feed_unit_dropdown').val();
                unit = unit == '_other' ? $('#feed_unit_dropdown_other').val() : unit;
                fields.unit = unit;
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
                    error = true;
                }
            })
        }
    }
    
    if (!error) {
        update_feed_list();
        $('#feedEditModal').modal('hide');
        $('#feed-edit-save-message').text('').hide();
    }
    
});

// ---------------------------------------------------------------------------------------------
// DELETE FEED
// ---------------------------------------------------------------------------------------------

/**
 * getFeedProcess
 * 
 * Scans all input processes and identifies which processes are linked to feeds.
 * Returns an object mapping feed IDs to their associated input, process definition, and feed ID.
 * Used to determine which feeds are referenced by input process lists for safe deletion and management.
 *
 * @returns {Object} feedInputs - Map of feed IDs to input/process details.
 */
function getFeedProcess() {
    const feedInputs = {};

    // 1. Build a map of processes that are linked to feeds
    const feedProcesses = {};
    for (const key in process_vue.processes_by_key) {
        const proc = process_vue.processes_by_key[key];

        // Cycle through args, check for feed args
        for (let i = 0; i < proc.args.length; i++) {
            const arg = proc.args[i];
            if (arg.type === 2) {
                // Could filter for set engines field here but think it's useful to flag all feed linked processes
                feedProcesses[key] = proc;
                break; // No need to check further args
            }
        }
    }

    // 2. For each input, decode its processList and check for feed linked processes
    for (const input of Object.values(process_vue.inputs)) {
        if (!input.processList.length) continue;
        const decodedList = process_api.decode(input.processList);

        decodedList.forEach(procItem => {
            const procDef = feedProcesses[procItem.fn];
            if (!procDef) return;
            procDef.args.forEach((arg, idx) => {
                if (arg.type === 2) {
                    const feedid = procItem.args[idx];
                    feedInputs[feedid] = {
                        input: {
                            nodeid: input.nodeid,
                            name: input.name,
                            inputid: input.id,
                            // processList: decodedList (Not currently used)
                        },
                        process: procDef,
                        feedid: feedid
                    };
                }
            });
        });
    }

    return feedInputs;
}

/**
 * output what feeds have been selected in the overlay modal box
 *
 * @return void
 */
function showSelectedFeeds(feed_inputs) {
    let total_selected = 0;
    let total_input_processes_linked = 0;
    let total_virtual_feed_processes_linked = 0;
    let feedListShort = '';

    for (const feedid in selected_feeds) {
        if (!selected_feeds[feedid]) continue;
        total_selected++;
        const feed = feeds[feedid];
        feedListShort += `[${feedid}] ${feed.tag}:${feed.name}, `;

        // Virtual feed: count its processList items
        if (parseInt(feed.engine) === 7 && feed.processList != "") {
            total_virtual_feed_processes_linked += process_api.decode(feed.processList).length;
        }
        // Non-virtual: count input processes referencing this feed
        else if (feed_inputs[feedid]) {
            if (Array.isArray(feed_inputs[feedid])) {
                total_input_processes_linked += feed_inputs[feedid].length;
            } else {
                total_input_processes_linked += 1;
            }
        }
    }

    // Remove trailing comma
    if (feedListShort.endsWith(', ')) feedListShort = feedListShort.slice(0, -2);

    // Build summary
    let total_summary = '<div id="deleteFeedModalSelectedItems">';
    let feedProcessList = '';

    if (total_selected === 1) {
        total_summary += `<h5>${feedListShort}</h5>`;
    } else {
        total_summary += `<h5 title="${feedListShort}"><?php echo tr('%s Feeds selected') ?> <i class="icon icon-question-sign"></i></h5>`.replace('%s', total_selected);
    }

    // Compose the combined message
    if (total_input_processes_linked > 0 || total_virtual_feed_processes_linked > 0) {
        let msg = '';
        if (total_input_processes_linked > 0) {
            msg += total_input_processes_linked + ' <?php echo tr("input processes") ?>';
        }
        if (total_virtual_feed_processes_linked > 0) {
            if (msg.length > 0) msg += ' and ';
            msg += total_virtual_feed_processes_linked + ' <?php echo tr("virtual feed processes") ?>';
        }
        msg += total_selected === 1
            ? ' <?php echo tr("associated with this feed") ?>'
            : ' <?php echo tr("associated with these feeds") ?>';

        feedProcessList = `<span class="badge badge-default" style="padding-left:4px;margin-right:6px"><i class="icon icon-white icon-exclamation-sign"></i> ${msg}</span>`;
    }

    total_summary += '</div>';

    $("#feeds-to-delete").html(total_summary);
    $("#feedProcessList").html(feedProcessList);
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
            $btn.attr('title',$btn.attr('title')+' - [<?php echo tr('Out of range')?>]');
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
    let allowed_engines = [0,2,5,8] // array of allowed storage engines
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
                if(confirm("<?php echo tr('This is a new feature. Consider backing up your data before you continue. OK to continue?') ?>") == true) {
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
    $('#trimContainer').attr('title','<?php echo tr('"Trim" not available for this storage engine') ?>').addClass('muted')//.hide()
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
    let allowed_engines = [0,2,5,8]; // array of allowed storage engines 
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
            if( confirm("<?php echo tr('Are you sure you want to delete all the feeds data?') ?>") == true ){
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

    $('#clearContainer').attr('title','<?php echo tr('"Clear" not available for this storage engine') ?>').addClass('muted')//.hide()
        .find('h4').removeClass('text-info').addClass('muted').end()
        .find('button').addClass('disabled');
}

$("#feedDelete-confirm").click(function(){
    if( confirm("<?php echo tr('Are you sure you want to delete?') ?>") == true) {
        for (let feedid in selected_feeds) {
            if (selected_feeds[feedid]) {
                let response = feed.remove(feedid);
                response = response ? response : {success:true, message: '<?php echo tr("Feeds Deleted") ?>'};
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

// ---------------------------------------------------------------------------------------------
// Create new feed dialog
// ---------------------------------------------------------------------------------------------

for (var e in engines_hidden) {
    $('#newfeed-engine option[value='+engines_hidden[e]+']').hide();
}

$("#newfeed-save").click(function (){
    var name = $('#newfeed-name').val();
    var tag = $('#newfeed-tag').val();
    var engine = $('#newfeed-engine').val();
    
    var options = {};
    if (engine==5) {
        options.interval = $('#newfeed-interval').val();
    }
    
    var result = feed.create(tag,name,engine,options);
    feedid = result.feedid;

    if (!result.success || feedid<1) {
        alert('ERROR: Feed could not be created. '+result.message);
        return false;
    } else {
        update_feed_list(); 
        $('#newFeedNameModal').modal('hide');
    }
});

$('#newfeed-engine').change(function(){
    var engine = $(this).val();
    if (engine==5) {
        $('#newfeed-interval').show();
    } else {
        $('#newfeed-interval').hide();
    }
});

$(".feed-process").click(function() {
    // There should only ever be one feed that is selected here:
    var feedid = 0; for (var z in selected_feeds) { if (selected_feeds[z]) feedid = z; }
    var contextid = feedid;
    var contextname = "";
    if (feeds[feedid].name != "") contextname = feeds[feedid].tag + " : " + feeds[feedid].name;
    else contextname = feeds[feedid].tag + " : " + feeds[feedid].id;    
    process_vue.load(1, contextid, feeds[feedid].processList, contextname, null, null); // load configs
});

function save_processlist(feed_id, process_list) {
    var result = feed.set_process(feed_id, process_list);
    if (!result.success) {
        alert('ERROR: Could not save processlist. '+result.message); 
        return false;
    } else {
        update_feed_list();
        return true;
    }
}

// Translations
var downloadlimit = <?php echo $settings['feed']['csv_downloadlimit_mb']; ?>;
var str_enter_valid_start_date = "<?php echo tr('Please enter a valid start date.'); ?>";
var str_enter_valid_end_date = "<?php echo tr('Please enter a valid end date.'); ?>";
var str_start_before_end = "<?php echo tr('Start date must be further back in time than end date.'); ?>";
var str_interval_for_download = "<?php echo tr('Please select interval to download.'); ?>";
var str_large_download = "<?php echo tr('Estimated download file size is large.'); ?>\n<?php echo tr('Server could take a long time or abort depending on stored data size.'); ?>\n<?php echo tr('Limit is'); ?> "+downloadlimit+"MB.\n\n<?php echo tr('Try exporting anyway?'); ?>";
</script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/exporter.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/importer.js?v=2"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/downsample.js?v=2"></script>

