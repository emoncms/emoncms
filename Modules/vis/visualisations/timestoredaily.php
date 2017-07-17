<?php

    // All Emoncms code is released under the GNU Affero General Public License.
    // See COPYRIGHT.txt and LICENSE.txt.
    // ---------------------------------------------------------------------
    // Emoncms - open source energy visualisation
    // Part of the OpenEnergyMonitor project:
    // http://openenergymonitor.org

    global $path, $embed;


?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.touch.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.time.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/date.format.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.canvas.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/base64.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/canvas2image.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/jquery.flot.saveAsImage.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/vis.helper.js"></script>

<style>
#vis-title {
    font-size:24px;
    font-weight:bold;
}
</style>

<div id="vis-title"></div>
<div id="#stats"></div>


<div id="placeholder_bound" style="width:100%; height:400px; position:relative; ">
    <div id="placeholder" style="position:absolute; top:0px;"></div>
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



<script id="source" language="javascript" type="text/javascript">

var feedid = <?php echo $feedid; ?>;
var feedname = "<?php echo $feedidname; ?>";
var units = "<?php echo $units; ?>";
var embed = <?php echo $embed; ?>;

var interval = 3600*24;

var top_offset = 0;

var path = "<?php echo $path; ?>";
var apikey = "";

// var feedid = urlParams['feedid'];
// var embed = urlParams['embed'] || false;

var initzoom = urlParams.initzoom;
    if (initzoom==undefined || initzoom=='' || initzoom < 1) initzoom = '7'; // Initial zoom default to 7 days (1 week)
var placeholder_bound = $('#placeholder_bound');
var placeholder = $('#placeholder').width(placeholder_bound.width()).height($('#placeholder_bound').height()-top_offset);
if (embed) placeholder.height($(window).height()-top_offset);



var timeWindow = (3600000*24.0*initzoom);
view.start = +new Date - timeWindow;
view.end = +new Date;

var plotdata = [];

$(function() {

    if (embed==false) $("#vis-title").html("<br><?php echo _("Timestore Daily:"); ?> "+feedname+"<br><br>");
    draw();

    $("#zoomout").click(function () {view.zoomout(); draw();});
    $("#zoomin").click(function () {view.zoomin(); draw();});
    $('#right').click(function () {view.panright(); draw();});
    $('#left').click(function () {view.panleft(); draw();});
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
            var mdate = new Date(item.datapoint[0]);
            if (units=='') $("#stats").html(item.datapoint[1].toFixed(1)+" | "+mdate.format("ddd, mmm dS, yyyy"));
            if (units=='kWh') $("#stats").html(item.datapoint[1].toFixed(1)+" kWh | "+mdate.format("ddd, mmm dS, yyyy"));
            if (units=='C') $("#stats").html(item.datapoint[1].toFixed(1)+" C | "+mdate.format("ddd, mmm dS, yyyy"));
            if (units=='V') $("#stats").html(item.datapoint[1].toFixed(1)+" V | "+mdate.format("ddd, mmm dS, yyyy"));
            if (units=='A') $("#stats").html(item.datapoint[1].toFixed(2)+" A | "+mdate.format("ddd, mmm dS, yyyy"));
            if (units=='Hz') $("#stats").html(item.datapoint[1].toFixed(2)+" Hz | "+mdate.format("ddd, mmm dS, yyyy"));
        }
    });

    function draw()
    {
        plotdata = [];

        var d = new Date()
        var n = d.getTimezoneOffset();
        var offset = n / -60;

        var datastart = (Math.round((view.start/1000.0)/interval) * interval)+3600*offset;
        plotdata = get_feed_data(feedid,datastart*1000,view.end+(interval*1000),interval,1,1);

        if (units=='kWh') {
            for (z in plotdata)
            {
                plotdata[z][1] = plotdata[z][1] * 0.024;
                plotdata[z][0] = plotdata[z][0] - 3600000*offset;
            }
        }
        // If last data point corresponds to today, then it's not a full day and needs adjustment
        var now=new Date();
        var last=plotdata.length-1;
        if ((now-plotdata[last][0])<(24*60*60*1000)) {
            var hourstoday=(now-new Date(now.getFullYear(),now.getMonth(),now.getDate()))/(60*60*1000);
            plotdata[last][1]=(plotdata[last][1]/0.024)*(hourstoday/1000);
        }

        stats.calc(plotdata);
        //console.log(stats.mean);

        plot();
    }
    
    function plot()
    {
        var plot = $.plot(placeholder, [plotdata], {
            canvas: true,
            //points: {show:true},
            bars: { show: true, align: "center", barWidth: 0.75*interval*1000, fill: true},
            xaxis: { mode: "time", timezone: "browser", min: view.start, max: view.end, minTickSize: [interval, "second"] },
            grid: {hoverable: true, clickable: true},
            selection: { mode: "x" },
            touch: { pan: "x", scale: "x" }
        });
    }
    
    // Graph buttons and navigation efects for mouse and touch
    placeholder.mouseenter(function(){
        $("#graph-navbar").show();
        $("#graph-buttons").stop().fadeIn();
        $("#stats").stop().fadeIn();
    });
    placeholder_bound.mouseleave(function(){
        $("#graph-buttons").stop().fadeOut();
        $("#stats").stop().fadeOut();
    });
    placeholder.bind("touchstarted", function (event, pos)
    {
        $("#graph-navbar").hide();
        $("#graph-buttons").stop().fadeOut();
        $("#stats").stop().fadeOut();
    });
    

    placeholder.bind("touchended", function (event, ranges)
    {
        $("#graph-buttons").stop().fadeIn();
        $("#stats").stop().fadeIn();
        view.start = ranges.xaxis.from;
        view.end = ranges.xaxis.to;
        draw();
    });
        

    $(window).resize(function(){
        placeholder.width(placeholder_bound.width());
        if (embed) placeholder.height($(window).height()-top_offset);

        plot();
    });
});

</script>
