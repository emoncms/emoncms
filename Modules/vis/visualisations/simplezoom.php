<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/
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
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/proc.js"></script>

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

            </div>
            <h3 style="position:absolute; top:0px; left:32px;"><span id="stats"></span></h3>
        </div>

<script id="source" language="javascript" type="text/javascript">

  var embed = <?php echo $embed; ?>;

  $('#graph').width($('#graph_bound').width());
  $('#graph').height($('#graph_bound').height());
  if (embed) $('#graph').height($(window).height());

  var path = "<?php echo $path; ?>";
  var apikey = "<?php echo $apikey; ?>";

  var power = "<?php echo $power; ?>";
  var kwhd = "<?php echo $kwhd; ?>";
  var delta = "<?php echo $delta; ?>";
  
  var timeWindow = (3600000*24.0*30);         //Initial time window
  var start = ((new Date()).getTime())-timeWindow;    //Get start time
  var end = (new Date()).getTime();       //Get end time

  var kwhd_start = start; var kwhd_end = end;
  var panning = false;

  var timeWindowChanged = 0;

  var plotdata = [];

  var feedlist = [];
  feedlist[0] = {id: power, selected: 0, plot: {data: null, lines: { show: true, fill: true } } };
  feedlist[1] = {id: kwhd, mode:"day", delta: delta, interval:86400, selected: 1, plot: {data: null, bars: { show: true, align: "center", barWidth: 3600*18*1000, fill: true}, yaxis:2} };

  $(window).resize(function(){
    $('#graph').width($('#graph_bound').width());
    $('#graph').height($('#graph_bound').height());
    if (embed) $('#graph').height($(window).height());
    plot();
  });

  vis_feed_data();

  /*

  Handle_feeds

  For all feeds in the feedlist:
  - remove all plot data if the time window has changed
  - if the feed is selected load new data
  - add the feed to the multigraph plot
  - plot the multigraph

  */
  function vis_feed_data()
  {
    plotdata = [];
    for(var i in feedlist) {
      if (timeWindowChanged) feedlist[i].plot.data = null;
      if (feedlist[i].selected) {
        
        if (!feedlist[i].plot.data) {
          //feedlist[i].plot.data = get_feed_data(feedlist[i].id,start,end,500);
          
          if (feedlist[i].interval!=undefined && feedlist[i].interval>0)
          {
            interval = feedlist[i].interval;
            intervalms = interval * 1000;
            
            var d = new Date()
            var n = d.getTimezoneOffset();
            var offset = n / -60;

            var datastart = Math.floor(start / intervalms) * intervalms;
            var dataend = Math.ceil(end / intervalms) * intervalms;
            datastart -= offset * 3600000;
            dataend -= offset * 3600000;
          } else {
            datastart = start;
            dataend = end;
            interval = Math.round(((end-start)/500)*0.001);
          }
          if (feedlist[i].mode==undefined) {
              feedlist[i].plot.data = get_feed_data(feedlist[i].id,datastart,dataend,interval,1,1);
          } else {
              feedlist[i].plot.data = get_feed_data_DMY(feedlist[i].id,datastart,dataend,feedlist[i].mode);
          }
          
          if (feedlist[i].delta==1 && i==1) {
              var tmp = [];
              for (var n=1; n<feedlist[i].plot.data.length; n++) {
                  var delta = feedlist[i].plot.data[n][1] - feedlist[i].plot.data[n-1][1];
                  tmp.push([feedlist[i].plot.data[n-1][0],delta]);
              }
              feedlist[i].plot.data = tmp;
          }
        }
        
        if ( feedlist[i].plot.data) plotdata.push(feedlist[i].plot);
      }
    }

    if (feedlist[0].selected) {
      var stats = power_stats(feedlist[0].plot.data);
      $("#stats").html("Average: "+stats['average'].toFixed(0)+"W | "+stats['kwh'].toFixed(2)+" kWh");
    } else { $("#stats").html(""); }

    plot();

    timeWindowChanged=0;
  }

  function plot()
  {
    var plot = $.plot($("#graph"), plotdata, {
      canvas: true,
      selection: { mode: "x" },
      grid: { show: true, clickable: true, hoverable: true },
      xaxis: { mode: "time", timezone: "browser", min: start, max: end },
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
  $("#graph").bind("plotselected", function (event, ranges)
  {
     start = ranges.xaxis.from; end = ranges.xaxis.to;
     timeWindowChanged = 1; vis_feed_data();
     panning = true; setTimeout(function() {panning = false; }, 100);
  });

  // Graph click
  $("#graph").bind("plotclick", function (event, pos, item)
  {
    if (item!=null && feedlist[0].selected == 0 && !panning)
    {
      kwhd_start = start; kwhd_end = end;
      start = item.datapoint[0]; end = item.datapoint[0] + (3600000*24.0);
      timeWindowChanged = 1;
      feedlist[0].selected = 1;
      feedlist[1].selected = 0;
      $('#mode').html("kwhd");
      vis_feed_data();
    }
  });

  // Operate buttons
  $("#zoomout").click(function () {inst_zoomout(); vis_feed_data();});
  $("#zoomin").click(function () {inst_zoomin(); vis_feed_data();});
  $('#right').click(function () {inst_panright(); vis_feed_data();});
  $('#left').click(function () {inst_panleft(); vis_feed_data();});
  $('.graph-time').click(function () {inst_timewindow($(this).attr("time")); vis_feed_data();});

  $('#mode').click(function ()
  {
    if ($(this).html() == "kwhd") {
      start = kwhd_start; end = kwhd_end; timeWindowChanged = 1;
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
  $("#graph").mouseenter(function(){
    $("#graph-navbar").show();
    $("#graph-buttons").stop().fadeIn();
    $("#stats").stop().fadeIn();
  });
  $("#graph-bound").mouseleave(function(){
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
    start = ranges.xaxis.from; end = ranges.xaxis.to;
    timeWindowChanged = 1; vis_feed_data();
    panning = true; setTimeout(function() {panning = false; }, 100);
  });
  
</script>

