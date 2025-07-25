<?php
    /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    */
    defined('EMONCMS_EXEC') or die('Restricted access');
    global $path, $embed, $vis_version;
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.merged.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/feed/feed.js?v=<?php echo $vis_version; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/vis.helper.js?v=<?php echo $vis_version; ?>"></script>

<div id="vis-title"></div>

<div id="graph_bound" style="height:400px; width:100%; position:relative; ">
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

        <div class='btn-group'>
            <button class='btn graph-exp' id='graph-fullscreen' type='button'><i class='icon-resize-full'></i></button>
        </div>
    </div>
    <h3 style="position:absolute; top:0px; left:32px;"><span id="stats"></span></h3>
</div>

<div id="info" style="padding:20px; margin:25px; background-color:rgb(245,245,245); font-style:italic; display:none">

    <p><b><?php echo tr("Mean:") ?></b> <span id="stats-mean"></span></p>
    <p><b><?php echo tr("Min:") ?></b> <span id="stats-min"></span></p>
    <p><b><?php echo tr("Max:") ?></b> <span id="stats-max"></span></p>
    <p><b><?php echo tr("Standard deviation:") ?></b> <span id="stats-stdev"></span></p>
    <p><b><?php echo tr("Datapoints in view:") ?></b> <span id="stats-npoints"></span></p>

</div>

<script id="source" language="javascript" type="text/javascript">

var feedid = <?php echo $feedid; ?>;
var feedname = "<?php echo $feedidname; ?>";
var apikey = "<?php echo $apikey; ?>";
feed.apikey = apikey;
var embed = <?php echo $embed; ?>;
var valid = "<?php echo $valid; ?>";
var previousPoint = false;

var plotColour = urlParams.colour;
    if (plotColour==undefined || plotColour=='') plotColour = "EDC240";

var backgroundColour = urlParams.colourbg;
if (backgroundColour==undefined || backgroundColour=='') backgroundColour = "ffffff";
$("body").css("background-color","#"+backgroundColour);
document.body.style.setProperty("--bg-vis-graph-color", "#"+backgroundColour);
  
var units = urlParams.units;
    if (units==undefined || units=='') units = "";
var dp = urlParams.dp;
    if (dp==undefined || dp=='') dp = 1;
var scale = urlParams.scale;
    if (scale==undefined || scale=='') scale = 1;
var average = urlParams.average;
    if (average==undefined || average=='') average = 0;
var skipmissing = urlParams.skipmissing;
    if (skipmissing==undefined || skipmissing=='') skipmissing = 1;
var delta = urlParams.delta;
    if (delta==undefined || delta=='') delta = 0;
var fill = +urlParams.fill;
    if (fill==undefined || fill=='') fill = 0;
    if (fill>0) fill = true;
var initzoom = urlParams.initzoom;
    if (initzoom==undefined || initzoom=='' || initzoom < 1) initzoom = '7'; // Initial zoom default to 7 days (1 week)
// Some browsers want the colour codes to be prepended with a "#". Therefore, we
// add one if it's not already there
if (plotColour.indexOf("#") == -1) {
    plotColour = "#" + plotColour;
}

var top_offset = 0;
var placeholder_bound = $('#graph_bound');
var placeholder = $('#graph');

var width = placeholder_bound.width();
var height = width * 0.5;

placeholder.width(width);
placeholder_bound.height(height);
placeholder.height(height-top_offset);

if (embed) placeholder.height($(window).height()-top_offset);

var timeWindow = (3600000*24.0*initzoom);
view.start = +new Date - timeWindow;
view.end = +new Date;
view.limit_x = false;

var data = [];

$(function() {

    if (embed==false) {
        $("#vis-title").html("<h2><?php echo tr("Raw:") ?> "+feedname+"<h2>");
        $("#info").show();
    }
    draw();
    
    $("#zoomout").click(function () {view.zoomout(); draw();});
    $("#zoomin").click(function () {view.zoomin(); draw();});
    $('#right').click(function () {view.panright(); draw();});
    $('#left').click(function () {view.panleft(); draw();});
    $("#graph-fullscreen").click(function () {view.fullscreen();});
    $('.graph-time').click(function () {view.timewindow($(this).attr("time")); draw();});
    
    placeholder.bind("plotselected", function (event, ranges)
    {
        view.start = ranges.xaxis.from;
        view.end = ranges.xaxis.to;
        draw();
    });

    placeholder.bind("plothover", function (event, pos, item)
    {
        if (item) {
            //var datestr = (new Date(item.datapoint[0])).format("ddd, mmm dS, yyyy");
            //$("#stats").html(datestr);
            if (previousPoint != item.datapoint)
            {
                previousPoint = item.datapoint;

                $("#tooltip").remove();
                var itemTime = item.datapoint[0];
                var itemVal = item.datapoint[1];

                // I'd like to eventually add colour hinting to the background of the tooltop.
                // This is why showTooltip has the bgColour parameter.
                tooltip(item.pageX, item.pageY, itemVal.toFixed(dp) + " " + units, "#DDDDDD");
            }
        }
        else
        {
            $("#tooltip").remove();
            previousPoint = null;
        }
    });

    function draw()
    {   
        view.calc_interval(2400);
        data = feed.getdata(feedid,view.start,view.end,view.interval,average,delta,skipmissing,1);
        
        var out = [];
        
        if (scale!=1) {
            for (var z=0; z<data.length; z++) {
                var val = data[z][1] * scale;
                out.push([data[z][0],val]);
            }
            data = out;
        } 
       
        var s = stats(data);
        $("#stats-mean").html(s.mean.toFixed(dp)+units);
        $("#stats-min").html(s.minval.toFixed(dp)+units);
        $("#stats-max").html(s.maxval.toFixed(dp)+units);
        $("#stats-stdev").html(s.stdev.toFixed(dp)+units);
        $("#stats-npoints").html(data.length);
        plot();
    }
    
    function plot()
    {
        var options = {
            canvas: true,
            lines: { fill: fill },
            xaxis: { mode: "time", timezone: "browser", min: view.start, max: view.end, minTickSize: [view.interval, "second"] },
            //yaxis: { min: 0 },
            grid: {hoverable: true, clickable: true},
            selection: { mode: "x" },
            touch: { pan: "x", scale: "x" }
        }

        $.plot(placeholder, [{data:data,color: plotColour}], options);
    }

    
    // Graph buttons and navigation efects for mouse and touch
    $("#graph").mouseenter(function(){
        $("#graph-navbar").show();
        $("#graph-buttons").stop().fadeIn();
        $("#stats").stop().fadeIn();
    });
    $("#graph_bound").mouseleave(function(){
        $("#graph-buttons").stop().fadeOut();
        $("#stats").stop().fadeOut();
    });
    $("#graph").bind("touchstarted", function (event, pos)
    {
        $("#graph-navbar").hide();
        $("#graph-buttons").stop().fadeOut();
        $("#stats").stop().fadeOut();
    });
    
    $("#graph").bind("touchended", function (event, ranges)
    {
        $("#graph-buttons").stop().fadeIn();
        $("#stats").stop().fadeIn();
        view.start = ranges.xaxis.from; 
        view.end = ranges.xaxis.to;
        draw();
    });

    $(document).on('window.resized hidden.sidebar.collapse shown.sidebar.collapse',vis_resize);
    
    function vis_resize() {
        var width = placeholder_bound.width();
        var height = width * 0.5;

        placeholder.width(width);
        placeholder_bound.height(height);
        placeholder.height(height-top_offset);

        if (embed) placeholder.height($(window).height()-top_offset);
        plot();
    }
    
});
</script>
