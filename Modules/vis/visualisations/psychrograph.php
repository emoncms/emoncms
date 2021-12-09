<!--All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    
    //******************************************************************************************************
    //31/10/2018 - Alexandre CUER - psychrometric diagram implementation on the basis of multigraphs
-->

<?php
    global $path;
    //$embed = (int)(get("embed"));
    //$mid = (int)(get("mid"));
    //$hrtohabs = (int)(get("hrtohabs"));
    //$givoni = (int)(get("givoni"));
?>
<script>
//console.log(path);
var flot0='<script language="javascript" type="text/javascript" src="'+path+'Lib/flot/jquery.flot.min.js"><\/script>';
var flot1='<script language="javascript" type="text/javascript" src="'+path+'Lib/flot/jquery.flot.selection.min.js"><\/script>';
var flot2='<script language="javascript" type="text/javascript" src="'+path+'Lib/flot/jquery.flot.touch.min.js"><\/script>';
var flot3='<script language="javascript" type="text/javascript" src="'+path+'Lib/flot/jquery.flot.togglelegend.min.js"><\/script>';
var flot4='<script language="javascript" type="text/javascript" src="'+path+'Lib/flot/jquery.flot.time.min.js"><\/script>';
var flot5='<script language="javascript" type="text/javascript" src="'+path+'Lib/flot/jquery.flot.stack.min.js"><\/script>';
var flot6='<script language="javascript" type="text/javascript" src="'+path+'Lib/flot/jquery.flot.canvas.js"><\/script>';
var flot7='<script language="javascript" type="text/javascript" src="'+path+'Lib/flot/plugin/saveAsImage/lib/base64.js"><\/script>';
var flot8='<script language="javascript" type="text/javascript" src="'+path+'Lib/flot/plugin/saveAsImage/lib/canvas2image.js"><\/script>';
var flot9='<script language="javascript" type="text/javascript" src="'+path+'Lib/flot/plugin/saveAsImage/jquery.flot.saveAsImage.js"><\/script>';
document.write(flot0);
document.write(flot1);
document.write(flot2);
document.write(flot3);
document.write(flot4);
document.write(flot5);
document.write(flot6);
document.write(flot7);
document.write(flot8);
document.write(flot9);
var common0='<script language="javascript" type="text/javascript" src="'+path+'Modules/feed/feed.js"><\/script>';
var common1='<script language="javascript" type="text/javascript" src="'+path+'Lib/vis.helper.js"><\/script>';
var psychro='<script language="javascript" type="text/javascript" src="'+path+'Modules/vis/visualisations/psychrograph.js"><\/script>';
document.write(common0);
document.write(common1);
document.write(psychro);
var link0='<link href="'+path+'Lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">';
var link1='<link href="'+path+'Lib/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">';
var link2='<link href="'+path+'Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css" rel="stylesheet">';
document.write(link0);
document.write(link1);
document.write(link2);
var bstrap0='<script language="javascript" type="text/javascript" src="'+path+'Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js"><\/script>';
var bstrap1='<script language="javascript" type="text/javascript" src="'+path+'Lib/bootstrap/js/bootstrap.js"><\/script>';
document.write(bstrap0);
document.write(bstrap1);	
</script>
<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<br>
<div class = "container-fluid">             
    <div id='graph-buttons' style='position:relative; '>
      <div class='input-prepend input-append'>
        <span class='add-on'>Select time window</span>
        <span class='add-on'>Start:</span>
        <span id='datetimepicker1'>
            <input id='timewindow-start' data-format='dd/MM/yyyy hh:mm:ss' type='text' style='width:140px'/>
            <span class='add-on'><i data-time-icon='icon-time' data-date-icon='icon-calendar'></i></span>
        </span>
            <span class='add-on'>End:</span>
            <span id='datetimepicker2'>
                <input id='timewindow-end' data-format='dd/MM/yyyy hh:mm:ss' type='text' style='width:140px'/>
                <span class='add-on'><i data-time-icon='icon-time' data-date-icon='icon-calendar'></i></span>
            </span>
        <button class='btn graph-timewindow-set' type='button'><i class='icon-ok'></i></button>
        <br>
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
    <div class="input-prepend">
        <span class="add-on">Tmin</span>
        <input id="Tmin" type="text"  style="width:50px" value='15'>
        <span class="add-on">Tmax</span>
        <input id="Tmax" type="text"  style="width:50px" value='35'>
        <span class="add-on">habs_min</span>
        <input id="habs_min" type="text"  style="width:50px" value='0'>
        <span class="add-on">habs_max</span>
        <input id="habs_max" type="text"  style="width:50px" value='40'>
    </div><br>
    
    <h2><div id="multigraph_name"></div></h2>

    <div id="multigraph" style="height:100px; width:100%; position:relative;"></div>
    <br>On the following scatterplot diagrams, yaxis is absolute humidity expressed in g/kg and xaxis is temperature expressed in °C<br><br>
    <div id="psychrograph" style="width:100%; height:400px; position: relative;"></div>
    <button class='btn graph-nav' id='calc'>calculate %</button>
    <button class='btn graph-nav' id='clear'>clear</button>
    <div id='psychrotext' style='width:100%; height:200px; position: relative;'>do change</div>

    <div id="givonigraph" style="width:100%; height:400px; position: relative;"></div>
</div>

<script id="source" language="javascript" type="text/javascript">
    //console.log(window.location.search);
    //nota : there is also a urlParams var created by a custom function
    //console.log(urlParams);
    const url_Params = new URLSearchParams(window.location.search);
    var mid = url_Params.get("mid");
    var embed = url_Params.get("embed");
    var hrtohabs = url_Params.get("hrtohabs");
    var givoni = url_Params.get("givoni");
    var apikey="";
    if (url_Params.has("apikey")){
      apikey = url_Params.get("apikey");
    }
    feed.apikey = apikey;
    var datetimepicker_previous = null;
    var multigraph_feedlist = {};
    
    if (mid==0) $("body").css('background-color','#eee');
    var timeWindow = (3600000*24.0*7);        //Initial time window
    view.start = ((new Date()).getTime())-timeWindow;    //Get start time
    view.end = (new Date()).getTime();       //Get end time
    
    $.ajax({ url: path+"vis/multigraph/get.json", data: "&id="+mid, dataType: 'json', async: true,
        success: function(data)
        {
            if (data['feedlist'] != undefined) multigraph_feedlist = data['feedlist'];
            $("#multigraph_name").replaceWith(data['name']);
            //console.log(multigraph_feedlist.length);
            vis_feed_data();
        }
    });
    
    //calculate absolute humidity using temperature and relative humidity
    /* temp in °C, humidity in % */
    function habs(temp,humidity) {
  	    return (0.622*humidity*10*Math.pow(10,2.7877+(7.625*temp)/(241.6+temp)))/101325; 
    }
    
    //equation of the line between (xmin,ymin) and (xmax,ymax)
    function line(xmin,xmax,ymin,ymax,x) {
        return ymin + (x-xmin)*(ymax-ymin)/(xmax-xmin);
    }
    
    //CLASSIC STYLE
    //the blue zone - confort polygon
    var zone4 = new Zone(17,26,function(x){return line(17,26,habs(17,40),habs(26,30),x);},
      function(x){return line(17,26,habs(17,80),habs(26,50),x);}
    );
    //zone 1 - green
    var zone1 = new Zone(0,27,function(){return 0;},function(x){return habs(x,40);});
    //zone 2 - yellow 
    var zone2 = new Zone(0,24,function(x){return habs(x,80);},function(x){return habs(x,100);});
    //zone 3 - green
    var zone3 = new Zone(24,27,function(x){return habs(x,80);},function(x){return habs(x,100);});
    
    //GIVONI STYLE
    if(givoni==1){
        var givoni1 = new Zone(20,27,function(x){return habs(x,20);},
            function(x){if (x<24) return habs(x,80);if (x>=24) return line(24,27,habs(24,80),habs(27,48),x);}
        );
        var givoni2 = new Zone(20,27,
            function(x){
              if (x<24) return habs(x,80);
              if (x>=24 && x<=27) return line(24,27,habs(24,80),habs(27,48),x);
            },
            function(x){return habs(x,95);}
        );
        var givoni3 = new Zone(27,32.5,
            function(x){return habs(x,20);},
            function(x){if (x<27.5) return habs(x,95);if (x>=27.5) return line(27.5,32.5,habs(27.5,95),habs(32.5,48),x);}
        );   
    }
    
    //XY scatterplots
    var feedXY=[];
    
    //create dataplot for 10 isoHR curves
    function isohr() {
        var hum = 10;
        var ten = []; 
        var plotiso = [];
        for (j=0; j < 10; j++) /* 10 isoHR curves (10% to 100%) */
	        { 
	        var T = -15;
		    ten[j] = [];
	        for (var i=0; i < 71; i++) /* Temp from -15 to 55 */
	            {
	    	    ten[j][i] = [];
	    	    ten[j][i][0] = T; /* x-axis */
	    	    ten[j][i][1] = habs(T,hum); /* y-axis */
	    	    T++;
	            }
	        hum = hum + 10;
            plotiso[j] = {color:"#C4C6C8",data: ten[j],lines: { show: true}};
	    }
        return plotiso;
    }
    
    function clear_confort_stats() {
        $("#psychrotext").replaceWith("<div id='psychrotext' style='width:100%; height:200px; position: relative;'></div>");
    }
    
    function calc_confort_stats() {
        nbclouds=Math.floor(multigraph_feedlist.length/2);
        inzone1=[];inzone2=[];inzone3=[];inzone4=[];out=[];
        for (var i=0; i<nbclouds; i++) {
            inzone1[i]=0;inzone2[i]=0;inzone3[i]=0;inzone4[i]=0;out[i]=0;
            for (z in feedXY[i]) {
                if(zone4.includes(feedXY[i][z][0],feedXY[i][z][1])) inzone4[i]+=1;
                else if(zone1.includes(feedXY[i][z][0],feedXY[i][z][1])) inzone1[i]+=1;
                else if(zone2.includes(feedXY[i][z][0],feedXY[i][z][1])) inzone2[i]+=1;
                else if(zone3.includes(feedXY[i][z][0],feedXY[i][z][1])) inzone3[i]+=1;
                else out[i]+=1;
            }
        }
        var text = "<div id='psychrotext' style='width:100%; height:200px; position: relative;'>";
        text += "<table class=table><thead><tr bgcolor=#EEEEEE><th>"+hrtohabs+"</th>";
        text += "<th>confort</th>";
        text += "<th>bacteria/fungus/<br>house dust mites</th>";
        text += "<th>bacteria/fungus</th>";
        text += "<th>too dry</th>";
        text += "<th>other</th>";
        text += "</tr></thead>";
        for (var i=0; i<nbclouds; i++) {
            text += "<tr><td>"+multigraph_feedlist[2*i]['tag']+"</td>";
            text += "<td>"+Math.floor(inzone4[i]*1000/feedXY[i].length)/10+"%</td>";
            text += "<td>"+Math.floor(inzone3[i]*1000/feedXY[i].length)/10+"%</td>";
            text += "<td>"+Math.floor(inzone2[i]*1000/feedXY[i].length)/10+"%</td>";
            text += "<td>"+Math.floor(inzone1[i]*1000/feedXY[i].length)/10+"%</td>";
            text += "<td>"+Math.floor(out[i]*1000/feedXY[i].length)/10+"%</td>";
            text += "</tr>";
        }
        text += "</table></div>";
        $("#psychrotext").replaceWith(text);
    }
    
    function vis_feed_data() {
        view.calc_interval(800);
        
        feeddata = [];
        linedata = [];
        plotcolor = [];
        plotdata = [];
        givonidata = [];
        plotiso = [];

        // we fetch all feeds defined in the multigraph and the associated colors if any         
        for (var i=0; i<multigraph_feedlist.length; i++) {
            feeddata[i]=feed.getdata(multigraph_feedlist[i]["id"],view.start,view.end,view.interval,0,0,1,1);
            if (multigraph_feedlist[i]['lineColour']) plotcolor[i]="#"+multigraph_feedlist[i]['lineColour']; 
            else plotcolor[i]=2;
            linedata[i]={color: plotcolor[i], data: feeddata[i], lines: {show: true}};
        }

        //Zone 1 - green
        plotdata[0]={color:3,data: zone1.outline(),lines: { show: true, fill: true  }, label: 'too dry'};
        //zone 2 - yellow 
        plotdata[1]={color:5,data: zone2.outline(),lines: { show: true, fill: true  }, label: 'bacteria/fungus problems'};
        //zone 3 - red
        plotdata[2]={color:2,data: zone3.outline(),lines: { show: true, fill: true  }, label: 'bacteria/fungus/house dust mites problems'};
        if(givoni==1) {
            givonidata[0]={color:"#0044FF",data: givoni1.outline(),lines: { show: true, fill: true  }, label: 'confort'};
            givonidata[1]={color:5,data: givoni2.outline(),lines: { show: true, fill: true  }, label: 'no confort 1' };
            givonidata[2]={color:5,data: givoni3.outline(),lines: { show: true, fill: true  }, label: 'no confort 2' };
        }

        // XY diagrams creation
        // X is feeddata[2*i] and Y is feeddata[2*i+1]
        // X is temperature and Y is absolute humidity
        var nbclouds=Math.floor(multigraph_feedlist.length/2);
        classic_indice=plotdata.length;
        if (givoni==1) givoni_indice=givonidata.length;
        for (var i=0; i<nbclouds; i++) {
            feedXY[i]=[];
            for (z in feeddata[2*i]) {
                if (feeddata[2*i+1][z]!=undefined) {
                    feedXY[i][z]= [];
                    feedXY[i][z][0] = feeddata[2*i][z][1];
                    if (hrtohabs==1) {
                        feedXY[i][z][1]=habs(feeddata[2*i][z][1],feeddata[2*i+1][z][1]);
                    } else {
                        feedXY[i][z][1] = feeddata[2*i+1][z][1];
                    }
                }
            }
            //we create the label by using the tag field of the feed 2*i
            //the label will be used for the legend generation
            var label = multigraph_feedlist[2*i]['tag'];
            plotdata[classic_indice+i]={color: plotcolor[2*i], data: feedXY[i], points: { show: true }, label: label };
            if (givoni==1) givonidata[givoni_indice+i]={color: plotcolor[2*i], data: feedXY[i], points: { show: true }, label: label };
            
        }
        classic_indice=plotdata.length;
        if (givoni==1) givoni_indice=givonidata.length;
        plotiso=isohr();
        //console.log(plotdata);
        for (var i=0; i<10; i++) {
            plotdata[classic_indice+i]=plotiso[i];
            if (givoni==1) givonidata[givoni_indice+i]=plotiso[i];
        }
        
        //zone 4 - blue - confort polygon
        plotdata[classic_indice+10]={color:"#0044FF",data: zone4.outline(),lines: { show: true, fill: true  } };
        
        Tmin = $("#Tmin").val();
        Tmax = $("#Tmax").val();
        habs_min = $("#habs_min").val();
        habs_max = $("#habs_max").val();
        //graph rendering
        clear_confort_stats();
        var plot = $.plot($("#multigraph"), linedata, {
               canvas: true,
               grid: { show: true, hoverable: true, clickable: true },
               xaxis: { mode: "time", timezone: "browser", min: view.start, max: view.end },
               selection: { mode: "x" },
               touch: { pan: "x", scale: "x" }
            });
        
        var plot = $.plot($("#psychrograph"), plotdata, {
                canvas: true,
                grid: { show: true, hoverable: true },
                legend: { show: true, position: "nw", toggle: true },
                xaxis: { min: Tmin, max: Tmax},
                yaxis: { min: habs_min, max: habs_max},
                touch: { pan: "xy", scale: "xy", delayTouchEnded: 0}
            });
        if (givoni==1) {        
            var plot = $.plot($("#givonigraph"), givonidata, {
                canvas: true,
                grid: { show: true, hoverable: true },
                legend: { show: true, position: "nw", toggle: true },
                xaxis: { min: Tmin, max: Tmax},
                yaxis: { min: habs_min, max: habs_max},
                touch: { pan: "xy", scale: "xy", delayTouchEnded: 0}
            });
        }
    }
    
    $("#clear").click(function () {clear_confort_stats();});
    $("#calc").click(function () {calc_confort_stats();});
    //----------------------------------------------------------------------------------------------
    // Operate buttons : D W M Y + - < >
    //----------------------------------------------------------------------------------------------      
    $("#zoomout").click(function () {view.zoomout(); vis_feed_data();});
    $("#zoomin").click(function () {view.zoomin(); vis_feed_data();});
    $('#right').click(function () {view.panright(); vis_feed_data();});
    $('#left').click(function () {view.panleft(); vis_feed_data();});  
    //actions for D,W,M,Y buttons
    $('.graph-time').click(function () {view.timewindow($(this).attr("time")); vis_feed_data();});  
      
    //--------------------------------------------------------------------------------------
    // Graph zooming with zone selection via mouse
    //--------------------------------------------------------------------------------------
    $("#multigraph").bind("plotselected", function (event, ranges) { 
        view.start = ranges.xaxis.from; 
        view.end = ranges.xaxis.to; 
        vis_feed_data(); 
    });
    
    //--------------------------------------------------------------------------------------
    // time calendar functions
    //--------------------------------------------------------------------------------------
    //parse_timepicker_time is a function included in the vis_helper.js
    $('.graph-timewindow-set').click(function () {
        var timewindow_start = parse_timepicker_time($("#timewindow-start").val());
        var timewindow_end = parse_timepicker_time($("#timewindow-end").val());
        if (!timewindow_start) {alert("Please enter a valid start date."); return false; }
        if (!timewindow_end) {alert("Please enter a valid end date."); return false; }
        if (timewindow_start>=timewindow_end) {alert("Start date must be further back in time than end date."); return false; }

        view.start = timewindow_start*1000;
        view.end = timewindow_end*1000;
        vis_feed_data();
    });
  
    $('#datetimepicker1').datetimepicker({
        language: 'en-EN'
    });

    $('#datetimepicker2').datetimepicker({
        language: 'en-EN',
        useCurrent: false //Important! See issue #1075
    });

    $('#datetimepicker1').on("changeDate", function (e) {
        if (datetimepicker_previous == null) datetimepicker_previous = view.start;
        if (Math.abs(datetimepicker_previous - e.date.getTime()) > 1000*60*60*24) {
            var d = new Date(e.date.getFullYear(), e.date.getMonth(), e.date.getDate());
            d.setTime( d.getTime() - e.date.getTimezoneOffset()*60*1000 );
            var out = d;    
		    $('#datetimepicker1').data("datetimepicker").setDate(out);
        } else {
            var out = e.date;
        }
        datetimepicker_previous = e.date.getTime();
        $('#datetimepicker2').data("datetimepicker").setStartDate(out);
    });

    $('#datetimepicker2').on("changeDate", function (e) {
        if (datetimepicker_previous == null) datetimepicker_previous = view.end;
        if (Math.abs(datetimepicker_previous - e.date.getTime()) > 1000*60*60*24) {
            var d = new Date(e.date.getFullYear(), e.date.getMonth(), e.date.getDate());
            d.setTime( d.getTime() - e.date.getTimezoneOffset()*60*1000 );
            var out = d;    
		    $('#datetimepicker2').data("datetimepicker").setDate(out);
        } else {
            var out = e.date;
        }
        datetimepicker_previous = e.date.getTime();

        $('#datetimepicker1').data("datetimepicker").setEndDate(out);
    });

    datetimepicker1 = $('#datetimepicker1').data('datetimepicker');
    datetimepicker2 = $('#datetimepicker2').data('datetimepicker');  
    
</script>

