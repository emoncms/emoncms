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

?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.merged.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/vis.helper.js?v=<?php echo $vis_version; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/feed/feed.js?v=<?php echo $vis_version; ?>"></script>

<?php if (!$embed) { ?>
<h2><?php echo _("Simpler kWh/d zoomer"); ?></h2>
<?php } ?>

<div id="graph_bound" style="height:400px; width:100%; position:relative; ">
    <div id="graph"></div>
    <div id="graph-buttons" style="position:absolute; top:18px; right:32px; opacity:0.5;">
        <div class='btn-group'>
            <button class='btn graph-mode' id="mode" type="button">power</button>
        </div>
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

        <div class='btn-group'>
            <button class='btn graph-exp' id='graph-fullscreen' type='button'><i class='icon-resize-full'></i></button>
        </div>
    </div>
    <h3 style="position:absolute; top:0px; left:32px;"><span id="stats"></span></h3>
</div>

<script id="source" language="javascript" type="text/javascript">

var embed = <?php echo $embed; ?>;

var backgroundColour; //= urlParams.colourbg;
if (backgroundColour==undefined || backgroundColour=='') backgroundColour = "ffffff";
$("body").css("background-color","#"+backgroundColour);
document.body.style.setProperty("--bg-vis-graph-color", "#"+backgroundColour);

$('#graph').width($('#graph_bound').width());
$('#graph').height($('#graph_bound').height());
if (embed) $('#graph').height($(window).height());

var apikey = "<?php echo $apikey; ?>";
feed.apikey = apikey;

var power = "<?php echo $power; ?>";
var kwhd = "<?php echo $kwhd; ?>";
var delta = "<?php echo $delta; ?>";

var timeWindow = (3600000*24.0*30);
view.start = +new Date - timeWindow;
view.end = +new Date;
view.limit_x = false;

var kwhd_start = view.start; 
var kwhd_end = view.end;
var panning = false;

var plotdata = [];

var feedlist = [];
feedlist[0] = {id: power, interval:"auto", selected: 0, plot: {data: null, lines: { show: true, fill: true } } };
feedlist[1] = {id: kwhd, delta: delta, interval:"daily", selected: 1, plot: {data: null, bars: { show: true, align: "center", barWidth: 3600*18*1000, fill: true}, yaxis:2} };

$(document).on('window.resized hidden.sidebar.collapse shown.sidebar.collapse',vis_resize);

function vis_resize() {
    $('#graph').width($('#graph_bound').width());
    $('#graph').height($('#graph_bound').height());
    if (embed) $('#graph').height($(window).height());
    plot();
}

vis_feed_data();

/*

Handle_feeds

For all feeds in the feedlist:
- remove all plot data if the time window has changed
- if the feed is selected load new data
- add the feed to the multigraph plot
- plot the multigraph

*/
function vis_feed_data() {
    plotdata = [];
    for(var i in feedlist) {
        if (feedlist[i].selected) {
            var datastart = view.start;
            var dataend = view.end;
            if (feedlist[i].interval=="daily") {
                interval = "daily";
                var intervalms = 86400 * 1000;
                datastart = Math.floor(view.start / intervalms) * intervalms;
                dataend = Math.ceil(view.end / intervalms) * intervalms;
                skipmissing = 0
            } else {
                view.calc_interval(2400);
                interval = view.interval;
                skipmissing = 1
            }
            feedlist[i].plot.data = feed.getdata(feedlist[i].id,datastart,dataend,interval,0,feedlist[i].delta,skipmissing,0);
        
            if ( feedlist[i].plot.data) plotdata.push(feedlist[i].plot);
        }
    }

    if (feedlist[0].selected) {
        var st = stats(feedlist[0].plot.data);
        $("#stats").html("Average: "+st['mean'].toFixed(0)+"W | "+st['kwh'].toFixed(2)+" kWh");
    } else { 
        $("#stats").html(""); 
    }

    plot();
}

function plot() {
    var plot = $.plot($("#graph"), plotdata, {
        canvas: true,
        selection: { mode: "x" },
        grid: { show: true, clickable: true, hoverable: true },
        xaxis: { mode: "time", timezone: "browser", min: view.start, max: view.end },
        touch: { pan: "x", scale: "x" }
    });
}

$("#graph").bind("plothover", function (event, pos, item) {
    if (feedlist[1].selected) {
        if (item) {
            var mdate = new Date(item.datapoint[0]);
            $("#stats").html((item.datapoint[1]).toFixed(1)+"kWh | "+mdate.format("ddd, mmm dS, yyyy"));
        }
    }
});

// Graph zooming
$("#graph").bind("plotselected", function (event, ranges) {
    view.start = ranges.xaxis.from; 
    view.end = ranges.xaxis.to;
    vis_feed_data();
    panning = true; setTimeout(function() {panning = false; }, 100);
});

// Graph click
$("#graph").bind("plotclick", function (event, pos, item) {
    if (item!=null && feedlist[0].selected == 0 && !panning) {
        kwhd_start = view.start; 
        kwhd_end = view.end;
        view.start = item.datapoint[0]; 
        view.end = item.datapoint[0] + (3600000*24.0);
        feedlist[0].selected = 1;
        feedlist[1].selected = 0;
        $('#mode').html("kwhd");
        vis_feed_data();
    }
});

// Operate buttons
$("#zoomout").click(function () {view.zoomout(); vis_feed_data();});
$("#zoomin").click(function () {view.zoomin(); vis_feed_data();});
$('#right').click(function () {view.panright(); vis_feed_data();});
$('#left').click(function () {view.panleft(); vis_feed_data();});
$("#graph-fullscreen").click(function () {view.fullscreen();});
$('.graph-time').click(function () {view.timewindow($(this).attr("time")); vis_feed_data();});

$('#mode').click(function () {
    if ($(this).html() == "kwhd") {
        view.start = kwhd_start; 
        view.end = kwhd_end;
        feedlist[0].selected = 0;
        feedlist[1].selected = 1;
        $('#mode').html("power");
    } else if ($(this).html() == "power") {
        feedlist[0].selected = 1;
        feedlist[1].selected = 0;
        $('#mode').html("kwhd");
    }
    vis_feed_data();
});

// Graph buttons and navigation efects for mouse and touch
$("#graph").mouseenter(function() {
    $("#graph-navbar").show();
    $("#graph-buttons").stop().fadeIn();
    $("#stats").stop().fadeIn();
});
$("#graph-bound").mouseleave(function() {
    $("#graph-buttons").stop().fadeOut();
    $("#stats").stop().fadeOut();
});
$("#graph").bind("touchstarted", function (event, pos) {
    $("#graph-navbar").hide();
    $("#graph-buttons").stop().fadeOut();
    $("#stats").stop().fadeOut();
});

$("#graph").bind("touchended", function (event, ranges) {
    $("#graph-buttons").stop().fadeIn();
    $("#stats").stop().fadeIn();
    view.start = ranges.xaxis.from; 
    view.end = ranges.xaxis.to;
    vis_feed_data();
    panning = true; setTimeout(function() {panning = false; }, 100);
});
  
</script>

