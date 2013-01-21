<!--
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
-->

<?php
  global $path, $embed;
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/date.format.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/proc.js"></script>
 
<?php if (!$embed) { ?>
<h2>Bar graph: <?php echo $feedidname; ?></h2>
<?php } ?>


    <div id="graph_bound" style="height:400px; width:100%; position:relative; ">
      <div id="graph" style="position:absolute; top:25px;"></div>
      <div style="position:absolute; top:0px; right:0px;">

        <input class="time" type="button" value="D" time="1"/>
        <input class="time" type="button" value="W" time="7"/>
        <input class="time" type="button" value="M" time="30"/>
        <input class="time" type="button" value="Y" time="365"/> | 

        <input id="zoomin" type="button" value="+"/>
        <input id="zoomout" type="button" value="-"/>
        <input id="left" type="button" value="<"/>
        <input id="right" type="button" value=">"/>

      </div>
      <div style="position:absolute; bottom:-50px; left:20px;">
        <!--<h2 style="position:absolute; bottom:0px; left:0px;">-->
        <h3><span id="totals"></span><span id="stats"></span><br></h3>
        <!--</h2>-->
        <!--<h3 style="position:absolute; bottom:15px; left:50px;"><span id="tots"></span></h3>-->
        </div>
      
        
    </div>
    

<script id="source" language="javascript" type="text/javascript">

  var feedid = "<?php echo $feedid; ?>";
  var cost = "<?php echo $cost; ?>";
  var rider = "<?php echo $rider; ?>";
  var billday = "<?php echo $billday; ?>";
  var feedname = "<?php echo $feedidname; ?>";
  var path = "<?php echo $path; ?>";
  var apikey = "<?php echo $apikey; ?>";
  var embed = <?php echo $embed; ?>;
  var valid = "<?php echo $valid; ?>";

  <!--var valid = "<?php echo $valid; ?>";-->
  
  var units = "$";
  
  var ctotal = 0.0;
  
  $('#graph').width($('#graph_bound').width());
  $('#graph').height($('#graph_bound').height());
  if (embed) $('#graph').height($(window).height()-50);

  var timeWindow = (3600000*24.0*30);  			//Initial time window
  var start = ((new Date()).getTime())-timeWindow;		//Get start time
  var end = (new Date()).getTime();				//Get end time

  var graph_data = [];
  var graph2_data = [];
  <!--var graph_data = get_feed_data(feedid,start,end,500);-->
  vis_feed_data();
  

  $(window).resize(function(){
    $('#graph').width($('#graph_bound').width());
    if (embed) $('#graph').height($(window).height()-50);
    plot();
  });

  function vis_feed_data()
  {
    ctotal = 0.0;
    if (valid) graph_data = get_feed_data(feedid,start,end,500);
    if (valid) graph2_data = get_feed_data(feedid,start,end,500);
    <!--Multiply cost per kwh by kwh/d value-->
    for(var i=0;i<graph_data.length;i++){
      graph_data[i][1] = ((graph_data[i][1] * cost) + (rider / 30));
      graph2_data[i][1] = (rider / 30);
      ctotal = (graph_data[i][1] + ctotal);
    }
    plot();
  }

  function plot()
  {
    var plot = $.plot($("#graph"), 
      [{data: graph_data},{data: graph2_data}], 
      {
      series: {
        stack: true,
        bars: { show: true,align: "center",barWidth: (3600*18*1000),fill: true }
      },
      grid: { show: true, hoverable: true, clickable: true },
      xaxis: { mode: "time", localTimezone: true, min: start, max: end },
      yaxis: {min: 0},
      selection: { mode: "x" }
    });
    $("#totals").html("Period Cost: "+units+ctotal.toFixed(2)+" ");
  }

  

  //--------------------------------------------------------------------------------------
  // Graph zooming
  //--------------------------------------------------------------------------------------
  $("#graph").bind("plotselected", function (event, ranges) { start = ranges.xaxis.from; end = ranges.xaxis.to; vis_feed_data(); });

  $("#graph").bind("plothover", function (event, pos, item) { 
    if (item)
    {
      var mdate = new Date(item.datapoint[0]);
      $("#stats").html(" | Daily: "+(item.datapoint[1]).toFixed(2)+units+" | "+mdate.format("ddd, mmm dS, yyyy"));
    }
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

