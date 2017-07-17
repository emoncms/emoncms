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
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/date.format.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.canvas.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/base64.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/canvas2image.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/jquery.flot.saveAsImage.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/proc.js"></script>

<?php if (!$embed) { ?>
<h2><?php echo _("Threshold Order"); ?></h2>
<?php } ?>

    <div id="graph_bound" style="height:400px; width:100%; position:relative; ">
        <div id="graph"></div>
        <div id="graph-buttons" style="position:absolute; top:18px; right:32px; opacity:0.5;">
            <div class='btn-group'>
                <button class='btn graph-back' type='button' time='1'><?php echo _("Reload"); ?></button>
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
  var powerfeed = <?php echo $power; ?>;

  var thresholdA = <?php echo $thresholdA; ?>;
  var thresholdB = <?php echo $thresholdB; ?>;

  var path = "<?php echo $path; ?>";
  var apikey = "<?php echo $apikey; ?>";

  var timeWindow = (3600000*24.0*7);        //Initial time window
  var start = ((new Date()).getTime())-timeWindow;    //Get start time
  var end = (new Date()).getTime();       //Get end time

  // First we load the kwh data at given power ranges:
  var dataA = get_kwhatpower(feedid,0,thresholdA);
  var dataB = get_kwhatpower(feedid,thresholdA+1,thresholdB);
  var dataC = get_kwhatpower(feedid,thresholdB+1,20000);

  // Next we merge all the above bars into an array that shares the same timestamp
  var data = {};
  for (z in dataA){
    if (!data[dataA[z][0]]) data[dataA[z][0]] = [];
    data[dataA[z][0]][0] = 1*dataA[z][1];
    data[dataA[z][0]][1] = 0;
    data[dataA[z][0]][2] = 0;
  }
  for (z in dataB)
  {
    if (!data[dataB[z][0]]) data[dataB[z][0]] = [];
    data[dataB[z][0]][1] = 1*dataB[z][1];
  }

  for (z in dataC)
  {
    if (!data[dataC[z][0]]) data[dataC[z][0]] = [];
    data[dataC[z][0]][2] = 1*dataC[z][1];
  }

  // Remove the timestamp replacing it with an index - we still include timestamp for reference
  var data2 = []; var i = 0;
  for (z in data) {data2[i] = data[z]; data2[i][3] = z; i++;}

  // Sort.
  for(x = 0; x < data2.length; x++) {
    for(y = 0; y < (data2.length-1); y++) {
      var sumA = data2[y][0]+data2[y][1]+data2[y][2];
      var sumB = data2[y+1][0]+data2[y+1][1]+data2[y+1][2];

      if(sumA < sumB) {
        holder = data2[y+1];
        data2[y+1] = data2[y];
        data2[y] = holder;
      }
    }
  }

  // Seperate the sorted array out again into 3 seperate arrays
  dataA = []; dataB = []; dataC = []; var timedata = []; i = 0;
  for (z in data2) {

    dataA[i] = []; dataB[i] = []; dataC[i] = [];
    dataA[i][1] = data2[z][0];
    dataB[i][1] = data2[z][1];
    dataC[i][1] = data2[z][2];
    dataA[i][0] = i;
    dataB[i][0] = i;
    dataC[i][0] = i;
    timedata[i] = data2[z][3];  // Copy timestamp for reference

    i++;
  }

  $(window).resize(function(){
    $('#graph').width($('#graph_bound').width());
    if (embed) $('#graph').height($(window).height());
    draw_ordered_kwhd_histogram();
  });

  draw_ordered_kwhd_histogram();

  function draw_ordered_kwhd_histogram()
  {
    // Draw the plot
    $.plot($("#graph"), [{color: "#c1a81f", data:dataA}, {color: "#dec225", data:dataB}, {color: "#deb368", data:dataC}],
    {
      canvas: true,
      series: {
        stack: true,
        bars: { show: true,align: "center",fill: true }
      },
      grid: { show: true, hoverable: true, clickable: true },
      legend: { position: "nw"},
      touch: { pan: "x", scale: "x" ,delayTouchEnded: 0}
    });
  }

  $("#graph").bind("plothover", function (event, pos, item) {
    //var mdate = new Date(item.datapoint[0]);
    if (item) {
      if (item.seriesIndex == 0) val = dataA[item.dataIndex][1];
      if (item.seriesIndex == 1) val = dataB[item.dataIndex][1];
      if (item.seriesIndex == 2) val = dataC[item.dataIndex][1];
      var total = dataA[item.dataIndex][1] + dataB[item.dataIndex][1] + dataC[item.dataIndex][1];
      var mdate = new Date(1*timedata[item.dataIndex]);
      $("#stats").html(val.toFixed(1)+"kWh of "+total.toFixed(1)+"kWh | "+mdate.format("ddd, mmm dS, yyyy"));
    }
  });

  $("#graph").bind("plotclick", function (event, pos, item)
  {
    if (item!=null)
    {
      var start = 1*timedata[item.dataIndex];
      var end = start + (3600000*24.0);
      var power_data = get_feed_data(powerfeed,start,end,500);

      $.plot($("#graph"), [{data: power_data, lines: { show: true, fill: true }}], {
        grid: { show: true, hoverable: false, clickable: false },
        xaxis: { mode: "time", timezone: "browser", min: start, max: end },
        //selection: { mode: "x" }
        touch: { pan: "x", scale: "x" ,delayTouchEnded: 0}
      });
    }
  });
  $('.graph-back').click(function () { draw_ordered_kwhd_histogram(); });

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
    //start = ranges.xaxis.from; end = ranges.xaxis.to;
    //vis_feed_data();
  });
</script>

