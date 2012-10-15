<!--
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
-->

<?php
  global $session, $path, $embed;
  $clear = get("clear");
  $apikey = get("apikey");
  $showoptions = get("showoptions")?get("showoptions"):0;

  // Show options if not embeded
  if (!$embed) $showoptions = 1;
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

    <div id="graph_bound" style="height:380px; width:100%; position:relative; ">
      <div id="graph"></div>
      <div style="position:absolute; top:20px; right:20px;">

        <input class="time" type="button" value="1H" time="0.04"/>
        <input class="time" type="button" value="6H" time="0.25"/>
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
  <br/><div id="choices"></div>
  <?php if ($session['write']) { ?>
  <p><input id="save" type="button" class="button05" value="Save current configuration"/></p>
  <?php } ?>

<script id="source" language="javascript" type="text/javascript">

  var showoptions = <?php echo $showoptions; ?>;

  var embed = <?php echo $embed; ?>;
  $('#graph').width($('#graph_bound').width());
  $('#graph').height($('#graph_bound').height());
  if (embed && showoptions==0) $('#graph').height($(window).height());

  var clear = "<?php echo $clear; ?>";
  var path = "<?php echo $path; ?>";
  var apikey = "<?php echo $apikey; ?>";
  var write_apikey = "<?php echo $write_apikey; ?>";

  var movingtime = 0;
  var timeWindow = (3600000*24.0);				//Initial time window
  var start = ((new Date()).getTime())-timeWindow;		//Get start time
  var end = (new Date()).getTime();				//Get end time

  var timeWindowChanged = 0;

    var plotdata = [];

  // Load list of feeds from server

  var feedlist = get_multigraph(apikey);

  if (feedlist && feedlist[0] && !clear) {
    end = feedlist[0].end;
    if (end==0) end = (new Date()).getTime();
    if (feedlist[0].timeWindow) start = end - feedlist[0].timeWindow;
  } else {
    feedlist = load_feedlist(apikey);
  }

  // Draw feed selector
  var out = "<table class='catlist' style='width:500px'>";
  out += "<tr><th>Select Feeds</th><th width=60px>Left</th><th width=60px>Right</th><th width=60px>Fill</th></tr>";

  for(var i in feedlist) {
    var checkedA = '',checkedB = '',checkedC = '';
    if (feedlist[i].selected)
    {
      if (feedlist[i].plot.yaxis==1) checkedA = 'checked="checked"';
      if (feedlist[i].plot.yaxis==2) checkedB = 'checked="checked"';
      var test = feedlist[i].plot.bars;
      if (test) { if (test.fill==true) checkedC = 'checked="checked"'; }
      var test = feedlist[i].plot.lines;
      if (test) { if (test.fill==true) checkedC = 'checked="checked"'; }
    }

    out += "<tr  class='d"+(i & 1)+"' ><td><label>" + feedlist[i].plot.label + '</label></td>';
    out += '<td><input type="checkbox" id="' + feedlist[i].id + '"' + checkedB + 'axis="2" ></td>';
    out += '<td><input type="checkbox" id="' + feedlist[i].id + '"' + checkedA + 'axis="1" ></td>';
    out += '<td><input type="checkbox" id="' + feedlist[i].id + '"' + checkedC + 'name="fill" ></td></tr>';
  }
  out += "</table>";
  if (showoptions==1) $("#choices").html(out);

  $("#choices").find("input[type='checkbox'][name!='fill']").click(function() {
    var id = $(this).attr("id");
    var axis = $(this).attr("axis");
    var checked = $(this).attr("checked");

    if (axis==1 && checked==true) $("#choices").find("input[id='"+id+"'][axis='2']").removeAttr("checked");
    if (axis==2 && checked==true) $("#choices").find("input[id='"+id+"'][axis='1']").removeAttr("checked");

    for(var i in feedlist) {
      if (feedlist[i].id==id && checked==true) {feedlist[i].selected = 1; feedlist[i].plot.yaxis = Number(axis);}
      if (feedlist[i].id==id && checked==false) feedlist[i].selected = 0;
    }
    timeWindowChanged = 0;
    vis_feed_data();
  });

  $("#choices").find("input[type='checkbox'][name='fill']").click(function() {
    var id = $(this).attr("id");
    var checked = $(this).attr("checked");

    for(var i in feedlist) {
      if (feedlist[i].id==id && feedlist[i].plot.lines) feedlist[i].plot.lines.fill = checked;
      if (feedlist[i].id==id && feedlist[i].plot.bars) feedlist[i].plot.bars.fill = checked;
    }
    timeWindowChanged = 0;
    vis_feed_data();
  });

  vis_feed_data();

  $(window).resize(function(){
    $('#graph').width($('#graph_bound').width());
    if (embed && showoptions==0) $('#graph').height($(window).height());
    if (embed && showoptions==1) $('#graph').height(400);
    plot();
  });


  function load_feedlist(apikey)
  {
    var feedlist = [];
    var feedin = get_feed_list(apikey);
    var i =0 ;
    for (z in feedin) {
      if (feedin[z]['datatype']!=3) {
        feedlist[i] = {id: feedin[z]['id'], selected: 0, plot: {data: null, label: feedin[z]['name']} };

        if (feedin[z]['datatype']==1 || feedin[z]['datatype']==0) feedlist[i].plot.lines = { show: true, fill: false };
        if (feedin[z]['datatype']==2) feedlist[i].plot.bars = { show: true, align: "left", barWidth: 3600*24*1000, fill: false};
        i++;
      }
    }
    return feedlist;
  }

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

  $("#save").click(function (){
    feedlist[0].timeWindow = end - start;
    if (movingtime) feedlist[0].end = 0; else feedlist[0].end = end;
    movingtime = 0;
    save_multigraph(write_apikey,feedlist);
  });

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

