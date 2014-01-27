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
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/date.format.js"></script>
 <script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/proc.js"></script>

<?php if (!$embed) { ?>
<h2>Simpler kWh/d zoomer</h2>
<?php } ?>

<div id="graph_bound" style="height:400px; width:100%; position:relative; ">
	<div id="graph"></div>
	<div style="position:absolute; top:20px; right:20px; opacity:0.5;">

		<input id="mode" type="button" value="power" /> |

		<input class="time" type="button" value="D" time="1"/>
		<input class="time" type="button" value="W" time="7"/>
		<input class="time" type="button" value="M" time="30"/>
		<input class="time" type="button" value="Y" time="365"/> |

		<input id="zoomin" type="button" value="+"/>
		<input id="zoomout" type="button" value="-"/>
		<input id="left" type="button" value="<"/>
		<input id="right" type="button" value=">"/>

	</div>
		<h3 style="position:absolute; top:00px; left:50px;"><span id="stats"></span></h3>
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

	var timeWindow = (3600000*24.0*30);				//Initial time window
	var start = ((new Date()).getTime())-timeWindow;		//Get start time
	var end = (new Date()).getTime();				//Get end time

	var kwhd_start = start; var kwhd_end = end;
	var panning = false;

	var timeWindowChanged = 0;

	var plotdata = [];

	var feedlist = [];
	feedlist[0] = {id: power, selected: 0, plot: {data: null, lines: { show: true, fill: true } } };
	feedlist[1] = {id: kwhd, selected: 1, plot: {data: null, bars: { show: true, align: "center", barWidth: 3600*18*1000, fill: true}, yaxis:2} };

	$(window).resize(function(){
		$('#graph').width($('#graph_bound').width());
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
				if (!feedlist[i].plot.data) feedlist[i].plot.data = get_feed_data(feedlist[i].id,start,end,500);
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
			selection: { mode: "x" },
			grid: { show: true, clickable: true, hoverable: true },
			xaxis: { mode: "time", timezone: "browser", min: start, max: end }
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

	//--------------------------------------------------------------------------------------
	// Graph zooming
	//--------------------------------------------------------------------------------------
	$("#graph").bind("plotselected", function (event, ranges)
	{
		 start = ranges.xaxis.from; end = ranges.xaxis.to;
		 timeWindowChanged = 1; vis_feed_data();
		 panning = true; setTimeout(function() {panning = false; }, 100);
	});

	//--------------------------------------------------------------------------------------
	// Graph click
	//--------------------------------------------------------------------------------------
	$("#graph").bind("plotclick", function (event, pos, item)
	{
		if (item!=null && feedlist[0].selected == 0 && !panning)
		{
			kwhd_start = start; kwhd_end = end;
			start = item.datapoint[0]; end = item.datapoint[0] + (3600000*24.0);
			timeWindowChanged = 1;
			feedlist[0].selected = 1;
			feedlist[1].selected = 0;
			$('#mode').val("kwhd");
			vis_feed_data();
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

	$('#mode').click(function ()
	{
		if ($(this).val() == "kwhd") {
			start = kwhd_start; end = kwhd_end; timeWindowChanged = 1;
			feedlist[0].selected = 0;
			feedlist[1].selected = 1;
			$('#mode').val("power");
		} else if ($(this).val() == "power") {
			feedlist[0].selected = 1;
			feedlist[1].selected = 0;
			$('#mode').val("kwhd");
		}
		vis_feed_data();
	});

</script>

