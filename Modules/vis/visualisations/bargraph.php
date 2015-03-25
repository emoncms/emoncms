<?php
    /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    */

    global $path, $embed;
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/vis.helper.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/date.format.js"></script>

<div id="vis-title"></div>

<div id="placeholder_bound" style="width:100%; height:400px; position:relative; ">
    <div id="placeholder" style="position:absolute; top:0px;"></div>
    <div id="graph_buttons" style="position:absolute; top:18px; left:32px; opacity:0.5;">

        <div class='btn-group'>
            <button class='btn time' type='button' time='1'>D</button>
            <button class='btn time' type='button' time='7'>W</button>
            <button class='btn time' type='button' time='30'>M</button>
            <button class='btn time' type='button' time='365'>Y</button>
        </div>

        <div class='btn-group'>
            <button id='zoomin' class='btn' >+</button>
            <button id='zoomout' class='btn' >-</button>
            <button id='left' class='btn' ><</button>
            <button id='right' class='btn' >></button>
        </div>

        <div class='btn-group'>
            <button class='btn interval' type='button' interval='d'><span id="textunitD"></span>/D</button>
            <button class='btn interval' type='button' interval='m'><span id="textunitM"></span>/M</button>
            <button class='btn interval' type='button' interval='y'><span id="textunitY"></span>/Y</button>
        </div>

    </div>

    <h3 style="position:absolute; top:0px; right:25px;"><span id="stats"></span></h3>
</div>

<script id="source" language="javascript" type="text/javascript">

console.log(urlParams);

var feedname = "<?php echo $feedidname; ?>";
var path = "<?php echo $path; ?>";
var apikey = "<?php echo $apikey; ?>";
var embed = <?php echo $embed; ?>;
var valid = "<?php echo $valid; ?>";

var feedid = urlParams.feedid;

var interval = urlParams.interval;
if (interval==undefined || interval=='') interval = 3600*24;

var plotColour = urlParams.colour;
if (plotColour==undefined || plotColour=='') plotColour = "EDC240";

var units = urlParams.units;
if (units==undefined || units=='') units = "";

var dp = urlParams.dp;
if (dp==undefined || dp=='') dp = 1;

var scale = urlParams.scale;
if (scale==undefined || scale=='') scale = 1;

var delta = urlParams.delta;
if (delta==undefined || delta=='') delta = 0;

document.getElementById("textunitD").innerHTML=units;
document.getElementById("textunitM").innerHTML=units;
document.getElementById("textunitY").innerHTML=units;

// Some browsers want the colour codes to be prepended with a "#". Therefore, we
// add one if it's not already there
if (plotColour.indexOf("#") == -1) {
    plotColour = "#" + plotColour;
}

var top_offset = 0;
var placeholder_bound = $('#placeholder_bound');
var placeholder = $('#placeholder');

var width = placeholder_bound.width();
var height = width * 0.5;

placeholder.width(width);
placeholder_bound.height(height);
placeholder.height(height-top_offset);

if (embed) placeholder.height($(window).height()-top_offset);
    
var intervalcode=interval;
if (intervalcode==0 || intervalcode=='y' || intervalcode=='m' || intervalcode=='d') interval = 3600*24;

var intervalms = interval * 1000;

var timeWindow;

if (intervalcode=='y')
   timeWindow = 3600000*24*365*5;
else if (intervalcode=='m')
   timeWindow = 3600000*24*365;
else if (intervalcode=='d')
   timeWindow = 3600000*24*10;
else
   timeWindow = 3600000*24*31;

view.start = +new Date - timeWindow;
view.end = +new Date;

var data = [];

$(function() {

    if (embed==false) $("#vis-title").html("<br><h2><?php echo _("Bar graph:") ?> "+feedname+"<h2>");
    draw();
    
    $("#zoomout").click(function () {view.zoomout(); draw();});
    $("#zoomin").click(function () {view.zoomin(); draw();});
    $('#right').click(function () {view.panright(); draw();});
    $('#left').click(function () {view.panleft(); draw();});
    $('.time').click(function () {view.timewindow($(this).attr("time")); draw();});
    $('.interval').click(function () {

	intervalcode=$(this).attr("interval");

	if (intervalcode==0 || intervalcode=='y' || intervalcode=='m' || intervalcode=='d') 
        	interval = 3600*24;

	intervalms = interval * 1000;

	if (intervalcode=='y')
	   timeWindow = 3600000*24*365*5;
	else if (intervalcode=='m')
	   timeWindow = 3600000*24*365;
	else if (intervalcode=='d')
	   timeWindow = 3600000*24*10;
	else
	   timeWindow = 3600000*24*31;

	view.start = +new Date - timeWindow;
	view.end = +new Date;

	draw();

   });
    
    placeholder.bind("plotselected", function (event, ranges)
    {
        view.start = ranges.xaxis.from;
        view.end = ranges.xaxis.to;
        draw();
    });


    placeholder.bind("plotclick", function (event, pos, item)
    {
        if (!item){
		if (intervalcode=='d') {
		    intervalcode='m';
		    timeWindow = 3600000*24*365;
		    view.start = view.start-timeWindow/2;
		    view.end = view.start+timeWindow;
		    draw();
		}

		else if (intervalcode=='m') {
		    intervalcode='y';
		    timeWindow = 3600000*24*365*5;
		    view.start = view.start-timeWindow/2;
		    view.end = view.start+timeWindow;
		    draw();
		}
        }else{
		if (intervalcode=='m') {
		    intervalcode='d';
		     var ndaysofthemonth= 31;
		     var monthid=new Date(item.datapoint[0]).getMonth();
		    if (monthid==1) 
		        ndaysofthemonth=28;
		    if (monthid==3 || monthid==5 || monthid==8 || monthid==10) 
		        ndaysofthemonth=30;
		    timeWindow = 3600000*24*(1+ndaysofthemonth);
		    view.start = item.datapoint[0]-3600000*24/2;
		    view.end = view.start+timeWindow;
		    draw();
		}

		else if (intervalcode=='y') {
		    intervalcode="m";
		    timeWindow = 3600000*24*365;
		    view.start = item.datapoint[0]-3600000*24*31/2;
		    view.end = view.start+timeWindow;
		    draw();
		}
	}

   });

    placeholder.bind("plothover", function (event, pos, item)
    {
        if (item) {
            var datestr;
            if (intervalcode=='y')
               datestr = new Date(item.datapoint[0]).format("yyyy");
            else if (intervalcode=='m')
               datestr = new Date(item.datapoint[0]).format("mmm, yyyy");
            else if (intervalcode=='d')
               datestr = new Date(item.datapoint[0]).format("ddd, mmm dS, yyyy");
            else
                datestr = (new Date(item.datapoint[0]))./*toLocaleDateString()+","+(new Date(item.datapoint[0])).toLocaleTimeString();*/format("ddd, mmm dS, yyyy, hh:MM:ss");

            $("#stats").html(item.datapoint[1].toFixed(dp)+units+" | "+datestr);
        }
    });

    function draw()
    {
        data = [];
        
        var d = new Date()
        var n = d.getTimezoneOffset();
        var offset = n / -60;
    
        var datastart = Math.floor(view.start / intervalms) * intervalms;
        var dataend = Math.ceil(view.end / intervalms) * intervalms;
        datastart -= offset * 3600000;
        dataend -= offset * 3600000;
 
        //need to be fixed, when the interval is a day, it returns the kwh elapsed in 24h from an eratic time (9:08 by example). It should returns the kwh elapsed in 24h from midnight to midnight.
        $.ajax({                                      
            url: path+'feed/data.json',                         
            data: "id="+feedid+"&start="+datastart+"&end="+dataend+"&interval="+interval+"&skipmissing=0",
            dataType: 'json',
            async: false,                      
            success: function(data_in) { data = data_in; } 
        });
 
        var out = [];
        
        if (delta==1) {
            for (var z=1; z<data.length; z++) {
                if (data[z][1]!=null && data[z-1][1]!=null) {
                    var val = (data[z][1] - data[z-1][1]) * scale;
                    out.push([data[z-1][0],val]);
                }
            }
            data = out;
        } else if (scale!=1) {
            for (var z=0; z<data.length; z++) {
                var val = data[z][1] * scale;
                out.push([data[z][0],val]);
            }
            data = out;
        } 
       
        /* to align the day bar and the day text (not needed if if the ajax request is fixed)
        for (var x=0;x<data.length;x++){
           offset= new Date (data[x][0]).getTimezoneOffset();
           data[x][0]=Math.floor(data[x][0] / intervalms) * intervalms + offset*60000;
        }*/
       
       out = [];
       var year = new Date (data[0][0]).getFullYear();
       var month= new Date (data[0][0]).getMonth();
       var sumtime=0;
       var sum=0;
 
        if (intervalcode=='y'){
           sumtime= new Date (year,0,1);
           for (var x=0;x<data.length;x++){
              if (new Date (data[x][0]).getFullYear() == year)
                 sum+=data[x][1];
              else {
                 out.push([sumtime,sum]);
                 year = new Date (data[x][0]).getFullYear();
                 sumtime= new Date (year,0,1);
                 sum=data[x][1];
              }
           }
           out.push([sumtime,sum]);
           data=out;
        }

        else if (intervalcode=='m'){
           sumtime= new Date (year,month,1);
           for (var x=0;x<data.length;x++){
              if (new Date (data[x][0]).getMonth() == month)
                 sum+=data[x][1];
              else {
                 out.push([sumtime,sum]);
                 month= new Date (data[x][0]).getMonth();
                 year = new Date (data[x][0]).getFullYear();
                 sumtime= new Date (year,month,1);
                 sum=data[x][1];
              }
           }
           out.push([sumtime,sum]);
           data=out;
        }

        else
           ;

        stats.calc(data);

        plot();
    }
    
    function plot()
    {

        if (intervalcode=='y')
            intervalrange=interval*365;
        else if (intervalcode=='m')
            intervalrange=interval*30;
        else
            intervalrange=interval;

        var options = {
            bars: { show: true, align: "center", barWidth: 0.75*intervalrange*1000, fill: true},
            xaxis: { mode: "time", timezone: "browser",
            min: view.start, max: view.end, minTickSize: [intervalrange, "second"] },
            //yaxis: { min: 0 },
            grid: {hoverable: true, clickable: true},
            selection: { mode: "x" }
        }

        $.plot(placeholder, [{data:data,color: plotColour}], options);
    }

    placeholder.click(function(){
      $("#graph_buttons").css('opacity',0.5);
    });
            
    // Fade in/out the control buttons on mouse-over the plot container
    placeholder_bound.mouseenter(function(){
        $("#graph_buttons").stop().fadeIn();
        $("#stats").stop().fadeIn();
    }).mouseleave(function(){
        $("#graph_buttons").stop().fadeOut();
        $("#stats").stop().fadeOut();
    });
    
    $(window).resize(function(){
        var width = placeholder_bound.width();
        var height = width * 0.5;

        placeholder.width(width);
        placeholder_bound.height(height);
        placeholder.height(height-top_offset);

        if (embed) placeholder.height($(window).height()-top_offset);
        plot();
    });
    
});
</script>

