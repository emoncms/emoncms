<!--
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
-->

<?php
  $apikey = get("apikey");
  global $path, $embed;

  $fill = get("fill")?get("fill"):true;
  $units = get("units")?get("units"):"W";
?>

 <!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
 <script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.min.js"></script>
 <script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/proc.js"></script>

<?php if (!$embed) { ?>
<h2>Raw data: <?php echo $feedname; ?></h2>
<?php } ?>

    <div id="graph_bound" style="width:100%; height:400px; position:relative; ">
      <div id="graph"></div>
      <div style="position:absolute; top:20px; left:40px;">

        <input class="time" type="button" value="D" time="1"/>
        <input class="time" type="button" value="W" time="7"/>
        <input class="time" type="button" value="M" time="30"/>
        <input class="time" type="button" value="Y" time="365"/> | 

        <input id="zoomin" type="button" value="+"/>
        <input id="zoomout" type="button" value="-"/>
        <input id="left" type="button" value="<"/>
        <input id="right" type="button" value=">"/>

      </div>

        <h3 style="position:absolute; top:20px; left:310px;"><span id="stats"></span></h3>
    </div>

<script id="source" language="javascript" type="text/javascript">

  var feedid = "<?php echo $feedid; ?>";
  var feedname = "<?php echo $feedname; ?>";
  var path = "<?php echo $path; ?>";
  var apikey = "<?php echo $apikey; ?>";

  var plotfill = <?php echo $fill; ?>;
  if (plotfill==1) plotfill = true; else plotfill = false;
  var units = "<?php echo $units; ?>";

  var embed = <?php echo $embed; ?>;
  $('#graph').width($('#graph_bound').width());
  $('#graph').height($('#graph_bound').height());
  if (embed) $('#graph').height($(window).height());

  var timeWindow = (3600000*24.0*7);				//Initial time window
  var start = ((new Date()).getTime())-timeWindow;		//Get start time
  var end = (new Date()).getTime();				//Get end time

  var graph_data = [];
  vis_feed_data();

  $(window).resize(function(){
    $('#graph').width($('#graph_bound').width());
    if (embed) $('#graph').height($(window).height());
    plot();
  });

  function vis_feed_data()
  {
    graph_data = get_feed_data(feedid,start,end,1000);
    var stats = power_stats(graph_data);
    var out = "Average: "+stats['average'].toFixed(0)+units;
    if (units=='W') out+= " | "+stats['kwh'].toFixed(2)+" kWh";
    $("#stats").html(out);   
    plot();
  }

  function plot()
  {
    var plot = $.plot($("#graph"), [{data: graph_data, lines: { show: true, fill: plotfill }}], {
      grid: { show: true, hoverable: true, clickable: true },
      xaxis: { mode: "time", localTimezone: true, min: start, max: end },
      selection: { mode: "xy" }
    });
  }

  //--------------------------------------------------------------------------------------
  // Graph zooming
  //--------------------------------------------------------------------------------------
  $("#graph").bind("plotselected", function (event, ranges) { start = ranges.xaxis.from; end = ranges.xaxis.to; vis_feed_data(); });
  //----------------------------------------------------------------------------------------------
  // Operate buttons
  //----------------------------------------------------------------------------------------------
  $("#zoomout").click(function () {inst_zoomout(); vis_feed_data();});
  $("#zoomin").click(function () {inst_zoomin(); vis_feed_data();});
  $('#right').click(function () {inst_panright(); vis_feed_data();});
  $('#left').click(function () {inst_panleft(); vis_feed_data();});
  $('.time').click(function () {inst_timewindow($(this).attr("time")); vis_feed_data();});
  //-----------------------------------------------------------------------------------------------
</script>

