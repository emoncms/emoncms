<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/
    defined('EMONCMS_EXEC') or die('Restricted access');
    global $path, $embed;

    $type = 1;
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.merged.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/feed/feed.js?v=<?php echo $vis_version; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/vis.helper.js?v=<?php echo $vis_version; ?>"></script>

<?php if (!$embed) { ?>
<h2><?php echo _("Datapoint editor:"); ?> <?php echo $feedidname; ?></h2>
<p><?php echo _("Click on a datapoint to select, then in the edit box below the graph enter in the new value. You can also add another datapoint by changing the time to a point in time that does not yet have a datapoint."); ?></p>
<?php } ?>

<div id="graph_bound" style="height:350px; width:100%; position:relative; ">
    <div id="graph"></div>
    <div id="graph-buttons" style="position:absolute; top:18px; right:32px; opacity:0.5;">
        <div class='btn-group'>
            <button class='btn graph-time' type='button' time='1'>D</button>
            <button class='btn graph-time' type='button' time='7'>W</button>
            <button class='btn graph-time' type='button' time='30'>M</button>
            <button class='btn graph-time' type='button' time='365'>Y</button>
        </div>

        <div class='btn-group' id='graph-navbar' style='display: none;'>
            <button class='btn graph-nav' id='zoomin'>+</button>
            <button class='btn graph-nav' id='zoomout'>-</button>
            <button class='btn graph-nav' id='left'><</button>
            <button class='btn graph-nav' id='right'>></button>
        </div>

    </div>
    <h3 style="position:absolute; top:0px; left:32px;"><span id="stats"></span></h3>
</div>

<div style="width:100%; height:50px; background-color:#ddd; padding:10px; margin:10px;">
    <?php echo _("Edit feed_"); ?><?php echo $feedid; ?> <?php echo _("@ time:"); ?> <input type="text" id="time" style="width:150px;" value="" /> <?php echo _("new value:"); ?>
    <input type="text" id="newvalue" style="width:150px;" value="" />
    <button id="okb" class="btn btn-info"><?php echo _('Save'); ?></button>
    <button id="delete-button" class="btn btn-danger"><i class="icon-trash"></i><?php echo _('Delete data in window'); ?></button>
</div>

<div style="width:100%; height:50px; background-color:#ddd; padding:10px; margin:10px;">
    <?php echo _("Multiply data in the window by a float or a fraction"); ?> <input type="text" id="multiplyvalue" style="width:150px;" value="" />
    <button id="multiply-submit" class="btn btn-info"><?php echo _('Save'); ?></button>
    <?php echo _("<br>To erase all the window with NAN > type NAN - To convert all the window to absolute values > type abs(x)"); ?>
</div>

<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel"><?php echo _('Delete feed data'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Deleting feed data in this window is permanent.'); ?>
           <br><br>
           <?php echo _('Are you sure you want to delete?'); ?>
        </p>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="confirmdelete" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
    </div>
</div>

<script id="source" language="javascript" type="text/javascript">
$('#graph').width($('#graph_bound').width());
$('#graph').height($('#graph_bound').height());

var feedid = "<?php echo $feedid; ?>";
var feedname = "<?php echo $feedidname; ?>";
var type = "<?php echo $type; ?>";
var apikey = "<?php echo $write_apikey; ?>";

var timeWindow = (3600000 * 24.0 * 7); //Initial time window
view.start = ((new Date()).getTime()) - timeWindow; //Get start time
view.end = (new Date()).getTime(); //Get end time

var feed_interval = false;
$.ajax({
    url: path + 'feed/getmeta.json',
    data: "&apikey=" + apikey + "&id=" + feedid,
    dataType: 'json',
    async: false,
    success: function(result) {
        if (result && result.interval != undefined) {
            feed_interval = result.interval;
        }
    }
});

vis_feed_data();

function vis_feed_data() {
    view.calc_interval(800);

    var interval = view.interval;
    if (feed_interval !== false && interval < feed_interval) {
        interval = feed_interval;
    }

    var graph_data = feed.getdata(feedid, view.start, view.end, interval, 0, 0, 1, 0);

    var plotdata = {
        data: graph_data,
        lines: {
            show: true,
            fill: true
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

}

$("#graph").bind("plotclick", function(event, pos, item) {
    if (item != null) {
        $("#time").val(item.datapoint[0] / 1000);
        $("#newvalue").val(item.datapoint[1]);
    }
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

    console.log(time + " " + newvalue);

    $.ajax({
        url: path + 'feed/update.json',
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
    $('#myModal').modal('show');
});

$("#confirmdelete").click(function() {
    $.ajax({
        url: path + 'feed/deletedatarange.json',
        data: "&apikey=" + apikey + "&id=" + feedid + "&start=" + parseInt(view.start*0.001) + "&end=" + parseInt(view.end*0.001),
        dataType: 'json',
        async: false,
        success: function(result) {
            alert(result)
        }
    });
    vis_feed_data();
    $('#myModal').modal('hide');
});


// Graph buttons and navigation efects for mouse and touch
$("#graph").mouseenter(function() {
    $("#graph-navbar").show();
    $("#graph-buttons").stop().fadeIn();
    $("#stats").stop().fadeIn();
});
$("#graph_bound").mouseleave(function() {
    $("#graph-buttons").stop().fadeOut();
    $("#stats").stop().fadeOut();
});
$("#graph").bind("touchstarted", function(event, pos) {
    $("#graph-navbar").hide();
    $("#graph-buttons").stop().fadeOut();
    $("#stats").stop().fadeOut();
});

$("#graph").bind("touchended", function(event, ranges) {
    $("#graph-buttons").stop().fadeIn();
    $("#stats").stop().fadeIn();
    view.start = ranges.xaxis.from;
    view.end = ranges.xaxis.to;
    vis_feed_data();
});  
</script>
