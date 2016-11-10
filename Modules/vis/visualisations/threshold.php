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
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.stack.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.time.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.canvas.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/base64.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/canvas2image.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/jquery.flot.saveAsImage.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/vis.helper.js"></script>

<?php if (!$embed) { ?>
<h2><?php echo _("Threshold"); ?></h2>
<?php } ?>

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

            </div>
            <h3 style="position:absolute; top:0px; left:32px;"><span id="stats"></span></h3>
        </div>

<script id="source" language="javascript" type="text/javascript">
  var embed = <?php echo $embed; ?>;

  $('#graph').width($('#graph_bound').width());
  $('#graph').height($('#graph_bound').height());
  if (embed) $('#graph').height($(window).height());

  var feedid = <?php echo $feedid; ?>;
  var thresholdA = <?php echo $thresholdA; ?>;
  var thresholdB = <?php echo $thresholdB; ?>;

  var path = "<?php echo $path; ?>";
  var apikey = "<?php echo $apikey; ?>";
  
  var initzoom = urlParams.initzoom;
  if (initzoom==undefined || initzoom=='' || initzoom < 1) initzoom = '7'; // Initial zoom default to 7 days (1 week)

  var timeWindow = (3600000*24.0*initzoom);        //Initial time window
  var start = ((new Date()).getTime())-timeWindow;    //Get start time
  var end = (new Date()).getTime();       //Get end time

  var dataA = get_kwhatpower(feedid,0,thresholdA);
  var dataB = get_kwhatpower(feedid,thresholdA+1,thresholdB);
  var dataC = get_kwhatpower(feedid,thresholdB+1,20000);

  vis_feed_data();

  $(window).resize(function(){
    $('#graph').width($('#graph_bound').width());
    if (embed) $('#graph').height($(window).height());
    vis_feed_data();
  });

  function vis_feed_data()
  {

    $.plot($("#graph"), [{color: "#c1a81f", data:dataA}, {color: "#dec225", data:dataB}, {color: "#deb368", data:dataC}],
    {
      canvas: true,
      series: {
        stack: true,
        bars: { show: true,align: "center",barWidth: (3600*18*1000),fill: true }
      },
      grid: { show: true, hoverable: true, clickable: true },
      xaxis: { mode: "time", timezone: "browser", min: start, max: end, minTickSize: [1, "day"], tickLength: 1 },
      selection: { mode: "x" },
      legend: { position: "nw"},
      touch: { pan: "x", scale: "x" ,delayTouchEnded: 0}
    });
  }

  $("#graph").bind("plothover", function (event, pos, item) {
    //var mdate = new Date(item.datapoint[0]);
    if (item) {
      if (item.seriesIndex == 0) val = parseFloat(dataA[item.dataIndex][1]);
      if (item.seriesIndex == 1) val = parseFloat(dataB[item.dataIndex][1]);
      if (item.seriesIndex == 2) val = parseFloat(dataC[item.dataIndex][1]);
      $("#stats").html(val.toFixed(1));
    }
  });

  // Graph zooming
  $("#graph").bind("plotselected", function (event, ranges)
  {
     start = ranges.xaxis.from; end = ranges.xaxis.to;
     vis_feed_data();
  });

  // Operate buttons
  $("#zoomout").click(function () {inst_zoomout(); vis_feed_data();});
  $("#zoomin").click(function () {inst_zoomin(); vis_feed_data();});
  $('#right').click(function () {inst_panright(); vis_feed_data();});
  $('#left').click(function () {inst_panleft(); vis_feed_data();});
  $('.graph-time').click(function () {inst_timewindow($(this).attr("time")); vis_feed_data();});
  
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
    start = ranges.xaxis.from; end = ranges.xaxis.to;
    vis_feed_data();
  });
</script>

