<?php

  // All Emoncms code is released under the GNU Affero General Public License.
  // See COPYRIGHT.txt and LICENSE.txt.
  // ---------------------------------------------------------------------
  // Emoncms - open source energy visualisation
  // Part of the OpenEnergyMonitor project:
  // http://openenergymonitor.org

  global $path, $embed;

  
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/vis.helper.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/date.format.js"></script>

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
<div style="position:absolute; top:18px; left:32px;">

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
<!--
<div class='btn-group'>
<button id='csv' class='btn' >csv</button>
<button id='info' class='btn' >i</button>
</div>
-->

</div>

<h3 style="position:absolute; top:0px; right:25px;"><span id="stats"></span></h3>
</div>

<script id="source" language="javascript" type="text/javascript">

var feedid = <?php echo $feedid; ?>;
var feedname = "<?php echo $feedidname; ?>";
var units = "<?php echo $units; ?>";
var embed = <?php echo $embed; ?>;

var interval = 3600*24;

//$("#placeholder").css('top',18);
//$("#placeholder").css('left',32);

var top_offset = 0;

var path = "<?php echo $path; ?>";
var apikey = "";

// var feedid = urlParams['feedid'];
// var embed = urlParams['embed'] || false;

var placeholder_bound = $('#placeholder_bound');
var placeholder = $('#placeholder').width(placeholder_bound.width()).height($('#placeholder_bound').height()-top_offset);
if (embed) placeholder.height($(window).height()-top_offset);

$(window).resize(function(){
  placeholder.width(placeholder_bound.width());
  if (embed) placeholder.height($(window).height()-top_offset);
  
  var options = {
    //points: {show:true},
    bars: { show: true, align: "center", barWidth: 0.75*interval*1000, fill: true},
    xaxis: { mode: "time", min: view.start, max: view.end, minTickSize: [interval, "second"] },
    grid: {hoverable: true, clickable: true},
    selection: { mode: "x" }
  }

  $.plot(placeholder, [data], options);
});

var timeWindow = (3600000*24.0*7);
view.start = +new Date - timeWindow;
view.end = +new Date;

var data = [];

$(function() {

  if (embed==false) $("#vis-title").html("<br>Timestore Daily: "+feedname+"<br><br>");
  draw();

  $("#zoomout").click(function () {view.zoomout(); draw();});
  $("#zoomin").click(function () {view.zoomin(); draw();});
  $('#right').click(function () {view.panright(); draw();});
  $('#left').click(function () {view.panleft(); draw();});
  $('.time').click(function () {view.timewindow($(this).attr("time")); draw();});

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
    data = [];
    
    var d = new Date()
    var n = d.getTimezoneOffset();
    var offset = n / -60;
    
    var datastart = (Math.round((view.start/1000.0)/interval) * interval)+3600*offset;
    data = feed.get_timestore_average(feedid,datastart*1000,view.end+(interval*1000),interval);

    if (units=='kWh') {
      for (z in data)
      {
        data[z][1] = data[z][1] * 0.024;
        data[z][0] = data[z][0] - 3600000*offset;
      }
    }

    stats.calc(data);
    console.log(stats.mean);

    var options = {
      //points: {show:true},
      bars: { show: true, align: "center", barWidth: 0.75*interval*1000, fill: true},
      xaxis: { mode: "time", min: view.start, max: view.end, minTickSize: [interval, "second"] },
      grid: {hoverable: true, clickable: true},
      selection: { mode: "x" }
    }

    $.plot(placeholder, [data], options);
  }
});

</script>
