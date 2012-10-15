<!--
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    The intention for this script is to allow a completely manual specification of a multigraph.

    SPECIFICATION
    You specify the multigraph you'd like to load using the feedlist:
    feedlist[0] = {id: 1, selected: 1, plot: {data: null, label: "power", lines: { show: true, fill: true } } };
    feedlist[1] = {id: 4, selected: 1, plot: {data: null, label: "temp", lines: { show: true, fill: false }, yaxis:2} };

    BROWSER URL
    To view the multigraph, load the script directly in your browser using:
    http://localhost/emoncms3/Views/vis/multigraph_manual.php?apikey=YOURAPIKEY

    EMBED AS AN IFRAME
    To embed the multigraph in an iframe use the following code:
    <iframe style="width:400px; height:300px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://localhost/emoncms3/Views/vis/multigraph_manual.php?embed=1"></iframe>

    DASHBOARDS
    You can include this in a dashboard by entering it in the html options box for the paragraph widget type.

    For further discussion on manual multigraph see this forum discussion:
    http://openenergymonitor.org/emon/node/968
-->

<?php
  $path = "../../";
  $embed = intval($_GET["embed"]);
  $apikey = $_GET["apikey"];
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.selection.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/inst.js"></script>
 
<?php if (!$embed) { ?>
<h2>Multigraph</h2>
<?php } ?>

    <div id="graph_bound" style="height:400px; width:100%; position:relative; ">
      <div id="graph"></div>
      <div style="position:absolute; top:20px; right:20px;">

        <input class="time" type="button" value="D" time="1"/>
        <input class="time" type="button" value="W" time="7"/>
        <input class="time" type="button" value="M" time="30"/>
        <input class="time" type="button" value="Y" time="365"/> | 

        <input id="zoomin" type="button" value="+"/>
        <input id="zoomout" type="button" value="-"/>
        <input id="left" type="button" value="<"/>
        <input id="right" type="button" value=">"/>

      </div>
    </div>

<script id="source" language="javascript" type="text/javascript">

  var embed = <?php echo $embed; ?>;
  $('#graph').width($('#graph_bound').width());
  $('#graph').height($('#graph_bound').height());
  if (embed) $('#graph').height($(window).height());

  var path = "<?php echo $path; ?>";
  var apikey = "<?php echo $apikey; ?>";

  var timeWindow = (3600000*24.0*7);				//Initial time window
  var start = ((new Date()).getTime())-timeWindow;		//Get start time
  var end = (new Date()).getTime();				//Get end time

  var timeWindowChanged = 0;

  var feedlist = [];
  feedlist[0] = {id: 1, selected: 1, plot: {data: null, label: "power", lines: { show: true, fill: true } } };
  feedlist[1] = {id: 4, selected: 1, plot: {data: null, label: "temp", lines: { show: true, fill: false }, yaxis:2} };


  var plotdata = [];
  vis_feed_data();

  $(window).resize(function(){
    $('#graph').width($('#graph_bound').width());
    if (embed) $('#graph').height($(window).height());
    plot();
  });

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
        if (!feedlist[i].plot.data) feedlist[i].plot.data = get_feed_data(feedlist[i].id,start,end,1000);
        if ( feedlist[i].plot.data) plotdata.push(feedlist[i].plot);
      }
    }

    plot();

    timeWindowChanged=0;
  }

  function plot()
  {
    $.plot($("#graph"), plotdata, {
      grid: { show: true, hoverable: true, clickable: true },
      xaxis: { mode: "time", localTimezone: true, min: start, max: end },
      selection: { mode: "xy" },
      legend: { position: "nw"}
    });
  }

  //--------------------------------------------------------------------------------------
  // Graph zooming
  //--------------------------------------------------------------------------------------
  $("#graph").bind("plotselected", function (event, ranges) 
  {
     start = ranges.xaxis.from; end = ranges.xaxis.to;
     timeWindowChanged = 1; vis_feed_data();
  });

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

