<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/
    defined('EMONCMS_EXEC') or die('Restricted access');
    global $path, $embed, $vis_version;

    $type = 1;
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.merged.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/feed/feed.js?v=<?php echo $vis_version; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/vis.helper.js?v=<?php echo $vis_version; ?>"></script>

<?php if (!$embed) { ?>
<h3><?php echo _("Datapoint editor:"); ?> <span id="feed_name"></span></h3>
<p><?php echo _("Click on a datapoint to select, then in the edit box below the graph enter in the new value. You can also add another datapoint by changing the time to a point in time that does not yet have a datapoint."); ?></p>
<?php } ?>

<div id="graph_bound" style="width:100%; position:relative; margin-bottom:10px">
    <div id="graph"></div>
    <div id="graph-buttons" style="position:absolute; top:18px; right:42px">
        <div class='btn-group'>
            <button class='btn graph-time' type='button' time='1'>D</button>
            <button class='btn graph-time' type='button' time='7'>W</button>
            <button class='btn graph-time' type='button' time='30'>M</button>
            <button class='btn graph-time' type='button' time='365'>Y</button>
            <button class='btn graph-nav' id='zoomin'>+</button>
            <button class='btn graph-nav' id='zoomout'>-</button>
            <button class='btn graph-nav' id='left'><</button>
            <button class='btn graph-nav' id='right'>></button>
        </div>

    </div>
    <h3 style="position:absolute; top:0px; left:32px;"><span id="stats"></span></h3>
</div>

<div class="alert alert-info" id="alert"></span> seconds</div>

<div class="input-prepend" style="margin-right:10px"> 
    <span class="add-on"><?php echo _("Select feed"); ?></span>
    <select id="feedselector"></select>
</div>

<div id="dp-edit" class="input-prepend input-append" style="margin-right:10px"> 
    <span class="add-on"><?php echo _("Edit feed @ time"); ?></span>
    <input type="text" id="time" style="width:100px;" value="" />
    <span class="add-on"><?php echo _("new value"); ?></span>
    <input type="text" id="newvalue" style="width:80px;" value="" />
    <button id="okb" class="btn btn-info"><?php echo _('Save'); ?></button>
</div>

<div class="input-prepend input-append">
    <span class="add-on"><?php echo _("Multiply data in the window by a float or a fraction"); ?>
    <i class="icon icon-question-sign" style="cursor:pointer; margin-top:-1px" title="<?php echo _("To erase all the window with NAN > type NAN - To convert all the window to absolute values > type abs(x)"); ?>"></i></span>
    <input type="text" id="multiplyvalue" style="width:150px;" value="" />
    <button id="multiply-submit" class="btn btn-info"><?php echo _('Save'); ?></button>
</div>
<?php if (!$embed) { ?>
<div class="input-prepend input-append">
    <button id="delete-button" class="btn btn-danger"><i class="icon-trash"></i> <?php echo _('Delete data in window'); ?></button>
</div>
<?php } ?>

<div style="margin-top:10px">
    <button id="show-csv" class="btn btn-info"><i class="icon-download-alt icon-white"></i> <span id="show-csv-text"><?php echo _('Show CSV'); ?></span></button>
    <!-- save csv -->
    <a id="save-csv" class="btn btn-warning" style="display:none"><?php echo _('Import CSV'); ?></a>
</div>

<textarea id="csv" style="width:100%; height:200px; margin-top:10px; display:none"></textarea>


<script id="source" language="javascript" type="text/javascript">

var feedid = "<?php echo $feedid; ?>";
var feedname = "<?php echo $feedidname; ?>";
$("#feed_name").html(feedname);
var type = "<?php echo $type; ?>";
var apikey = "<?php echo $write_apikey; ?>";

var timeWindow = (3600000 * 24.0 * 7); //Initial time window
view.start = ((new Date()).getTime()) - timeWindow; //Get start time
view.end = (new Date()).getTime(); //Get end time
// disable x-axis limits
view.limit_x = false;

// Create feed selector
var feeds = feed.list();
// feeds by tag
var feeds_by_tag = {};
var feeds_by_id = {};
for (var z in feeds) {
    var tag = feeds[z].tag;
    if (!feeds_by_tag[tag]) feeds_by_tag[tag] = [];
    // check that engine is not virtual
    if (feeds[z].engine != 7) { 
        feeds_by_tag[tag].push(feeds[z]);
    }
    feeds_by_id[feeds[z].id] = feeds[z];
}
// populate feed selector with optgroups by tag
var feedselector = $("#feedselector");
for (var tag in feeds_by_tag) {
    var optgroup = $("<optgroup label='"+tag+"'>");
    for (var z in feeds_by_tag[tag]) {
        var f = feeds_by_tag[tag][z];
        optgroup.append("<option value='"+f.id+"'>"+f.name+"</option>");
    }
    feedselector.append(optgroup);
}
// select current feed
feedselector.val(feedid);

var feed_interval = false;
var meta = feed.getmeta(feedid);
if (meta) {
    feed_interval = meta.interval;
    view.end = meta.end_time * 1000;
    view.start = view.end - timeWindow;
}
var plotdata = {};

resize();
vis_feed_data();

function vis_feed_data() {
    view.calc_interval(1200);

    var feed_engine = feeds_by_id[feedid].engine;

    var interval = view.interval;
    if (feed_interval !== false && interval < feed_interval && feed_engine==5) {
        interval = feed_interval;
    }

    // hide delete button if engine == 5
    if (feed_engine==5) {
        $("#delete-button").hide();
    } else {
        $("#delete-button").show();
    }

    if (interval>feed_interval) {
        $("#alert").html("Current view interval "+interval+"s. Please zoom to feed interval ("+feed_interval+"s) to enable individual data point editing");
        $("#dp-edit").hide();
        $("#show-csv").hide();
    } else if (interval==feed_interval) {
        $("#alert").html("Current view interval matches "+feed_interval+"s feed interval. Individual data point editing enabled");
        $("#dp-edit").show();
        $("#show-csv").show();
    } else {
        $("#alert").html("Current view interval is less than "+feed_interval+"s feed interval. Individual data point editing enabled");
        $("#dp-edit").show();
        $("#show-csv").show();
    }

    // feedid,start,end,interval,average,delta,skipmissing,limitinterval,callback=false,context=false,timeformat='unixms'){
    var average = 0;
    var delta = 0;
    var skipmissing = 1;
    var limitinterval = 0;

    // PHPTimeSeries engine return original
    if (feeds_by_id[feedid].engine==0 || feeds_by_id[feedid].engine==2) {
        limitinterval = 2;
    }
    feed.getdata(feedid, view.start, view.end, interval, average, delta, skipmissing, limitinterval, function(graph_data) {
        plotdata = {
        data: graph_data,
        lines: {
            show: true,
            fill: false
        }
        };
        if (type == 2) plotdata = {
            data: graph_data,
            bars: {
                show: true,
                align: "center",
                barWidth: 3600 * 18 * 1000,
                fill: true
            }
        };

        redraw();

        // load csv
        var csv = "";
        for (var z in graph_data) {
            let value = graph_data[z][1];
            if (value % 1 != 0) value = value.toFixed(3);
            csv += parseInt(graph_data[z][0]*0.001) + ", " + value + "\n";
        }
        $("#csv").val(csv);
    });
}

function redraw() {
    var plot = $.plot($("#graph"), [plotdata], {
        canvas: true,
        //grid: { show: true, clickable: true},
        grid: {
            show: true,
            hoverable: true,
            clickable: true
        },
        xaxis: {
            mode: "time",
            timezone: "browser",
            min: view.start,
            max: view.end
        },
        selection: {
            mode: "x"
        },
        touch: {
            pan: "x",
            scale: "x"
        }
    });
    // get right hand graph offset
    var offset = plot.getPlotOffset();
    $("#graph-buttons").css("right", (offset.right+10) + "px");
}

$("#graph").bind("plotclick", function(event, pos, item) {
    if (item != null) {
        $("#time").val(item.datapoint[0] / 1000);
        // if integer, show integer, else show 3dp
        var value = item.datapoint[1];
        if (value % 1 == 0) {
            $("#newvalue").val(value);
        } else {
            var valuestr = value.toFixed(3);
            // remove trailing zeros
            while (valuestr[valuestr.length - 1] == '0') {
                valuestr = valuestr.substring(0, valuestr.length - 1);
            }
            $("#newvalue").val(valuestr);
        }
    }
});

// feed selector
$("#feedselector").change(function() {
    feedid = $(this).val();
    $("#feed_name").html(feeds_by_id[feedid].name);
    var meta = feed.getmeta(feedid);
    if (meta) feed_interval = meta.interval;
    vis_feed_data();
});

// Show hide csv
$("#show-csv").click(function() {
    // change button text
    if ($("#show-csv-text").html() == "Show CSV") {
        $("#show-csv-text").html("Hide CSV");
    } else {
        $("#show-csv-text").html("Show CSV");
    }
    $("#csv").toggle();
});

$("#csv").change(function() {
    // show save csv
    $("#save-csv").show();
});

var import_data = [];
$("#save-csv").click(function() {

    var csv = $("#csv").val();
    var lines = csv.split("\n");
    import_data = [];
    var time = 0;
    for (var z in lines) {
        var line = lines[z].split(",");
        if (line.length == 2) {
            var last_time = time;
            time = parseInt(line[0]);
            var value = parseFloat(line[1]);
            if (!isNaN(time) && !isNaN(value) && time > last_time) {
                import_data.push([time, value]);
            }
        }
    }

    $.ajax({ 
        type: 'POST', 
        url: path+"feed/post.json?id="+feedid,
        data: "data="+JSON.stringify(import_data),
        async: true, 
        dataType: 'json',
        success: function(result) {
            if (result.success!=undefined) {
                if (result.success) {
                    vis_feed_data();    
                } else {
                    alert('ERROR: '+result.message);
                }
            }
        }
    });

});

//--------------------------------------------------------------------------------------
// Graph zooming
//--------------------------------------------------------------------------------------
$("#graph").bind("plotselected", function(event, ranges) {
    view.start = ranges.xaxis.from;
    view.end = ranges.xaxis.to;
    vis_feed_data();
});
//----------------------------------------------------------------------------------------------
// Operate buttons
//----------------------------------------------------------------------------------------------
$("#zoomout").click(function() {
    view.zoomout();
    vis_feed_data();
});
$("#zoomin").click(function() {
    view.zoomin();
    vis_feed_data();
});
$('#right').click(function() {
    view.panright();
    vis_feed_data();
});
$('#left').click(function() {
    view.panleft();
    vis_feed_data();
});
$('.graph-time').click(function() {
    view.timewindow($(this).attr("time"));
    vis_feed_data();
});
//-----------------------------------------------------------------------------------------------

$('#okb').click(function() {
    var time = $("#time").val();
    var newvalue = $("#newvalue").val();

    $.ajax({
        url: path + 'feed/post.json',
        data: "&apikey=" + apikey + "&id=" + feedid + "&time=" + time + "&value=" + newvalue + "&skipbuffer=1",
        dataType: 'json',
        async: false,
        success: function() {}
    });
    vis_feed_data();
});

$('#multiply-submit').click(function() {

    var multiplyvalue = $("#multiplyvalue").val();

    $.ajax({
        url: path + 'feed/scalerange.json',
        data: "&apikey=" + apikey + "&id=" + feedid + "&start=" + parseInt(view.start*0.001) + "&end=" + parseInt(view.end*0.001) + "&value=" + multiplyvalue,
        dataType: 'json',
        async: false,
        success: function(result) {
            alert(result)
        }
    });
    vis_feed_data();
});

$('#delete-button').click(function() {
    // confirm delete data range
    if (confirm("Are you sure you want to delete this data range?")) {
        $.ajax({
            url: path + 'feed/deletedatarange.json',
            data: "&apikey=" + apikey + "&id=" + feedid + "&start=" + parseInt(view.start*0.001) + "&end=" + parseInt(view.end*0.001),
            dataType: 'json',
            async: true,
            success: function(result) {
                vis_feed_data();
            }
        });
    }
});

// on graph touch end, redraw graph
$("#graph").bind("touchended", function(event, ranges) {
    view.start = ranges.xaxis.from;
    view.end = ranges.xaxis.to;
    vis_feed_data();
});  

// on window resize, redraw graph
$(window).resize(function() {
    resize();
});

function resize() {
    // get width from graph bound
    var width = $("#graph_bound").width();
    $("#graph").width(width);

    // Find available height
    var height = $(window).height() - $("#graph_bound").offset().top - 20 - 200;
    $("#graph").height(height);

    redraw();
}

</script>
