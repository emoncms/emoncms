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

	if (isset($_GET['powerx'])) $powerx = $_GET['powerx']; else $powerx = 0;
	if (isset($_GET['powery'])) $powery = $_GET['powery']; else $powery = 0;
?>

 <!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/proc.js"></script>

<?php if (!$embed) { ?>
<h2>Feed calibration and comparison tool</h2>
<p>Use this tool to compare two feeds: PowerX and PowerY. Designed for calibration and comparison of different emontx versions (to see if feeds match up in parallel tests). Enter feed ids for comparison below. If there is a difference between feed values adjust the calibration to see if the difference can be removed.</p>

<div class="input-prepend">
	<span class="add-on">PowerX Feed ID</span>
	<input id="powerx" type="text"  style="width:100px">
</div><br>

<div class="input-prepend input-append">
	<span class="add-on">PowerY Feed ID</span>
	<input id="powery" type="text"  style="width:100px">
	<button id="load" class="btn btn-info">Load</button>
</div><br>

<div class="input-prepend input-append">
	<span class="add-on">PowerY Calibration</span>
	<input id="calibration" type="text"  style="width:100px" value="1.0">
	<button id="update" class="btn btn-info">Update</button>
</div>

<?php } ?>

<div id="graph_bound" style="width:100%; height:400px; position:relative; ">
	<div id="power"></div>
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

		<h3 style="position:absolute; top:0px; left:410px;"><span id="stats"></span></h3>
</div>

<h3>Difference between feeds (powerY calibration applied - powerX)</h3>
<div id="diff" style="width:100%; height:400px; "></div>

<h3>PowerX vs PowerY</h3>
<p>Relationship should be linear if measurements are the same</p>
<div id="line" style="width:100%; height:400px; "></div>

<script id="source" language="javascript" type="text/javascript">

	var path = "<?php echo $path; ?>";
	var apikey = "<?php echo $apikey; ?>";
	var valid = "<?php echo $valid; ?>";

	var embed = <?php echo $embed; ?>;
	$('#power').width($('#graph_bound').width());
	$('#power').height($('#graph_bound').height());
	if (embed) $('#graph').height($(window).height());

	var timeWindow = (3600000*24.0*7);				//Initial time window
	var start = ((new Date()).getTime())-timeWindow;		//Get start time
	var end = (new Date()).getTime();				//Get end time

	var powerxid = <?php echo $powerx; ?>;
	var poweryid = <?php echo $powery; ?>;

	$("#powerx").val(powerxid);
	$("#powery").val(poweryid);

	var calibration = 1;

	var powerX = [];
	var powerY = [];
	var powerY_cal = [];
	var diff = [];

	var powerXY = [];
	var line_data = [];

	var calibration_update = false;

	vis_feed_data();

	$(window).resize(function(){
		$('#graph').width($('#graph_bound').width());
		if (embed) $('#graph').height($(window).height());
		plot();
	});

	function vis_feed_data()
	{

		powerY_cal = [];
		diff = [];

		powerXY = [];
		line_data = [];

		if (powerxid>0 && poweryid>0 && calibration_update != true) {
			powerX = [];
			powerY = [];
			powerX = get_feed_data(powerxid,start,end,800);
			powerY = get_feed_data(poweryid,start,end,800);
		}

		var sumX=0,sumY=0,sumXY=0,sumX2=0,n=0;
		for (z in  powerY)
		{
			// Create calibrated B
			powerY_cal[z] = [];
			powerY_cal[z][0] = powerY[z][0];
			powerY_cal[z][1] = calibration * powerY[z][1];

			if (powerX[z]!=undefined)
			{
				// Calculate line of best fit variables
				var XY = 1.0*powerX[z][1] * powerY[z][1];
				var X2 = 1.0*powerX[z][1] * powerX[z][1];

				sumX += 1.0*powerX[z][1];
				sumY += 1.0*powerY[z][1];

				sumXY += XY;
				sumX2 += X2;

				n++;
			}
		}

		var slope = ((n * sumXY - (sumX*sumY)) / (n * sumX2 - (sumX*sumX)));
		var intercept = (sumY - slope*sumX) / n;
		console.log(slope);

		line_data[0] = [];
		line_data[0][0] = 0;
		line_data[0][1] = slope * line_data[0][0] + intercept;

		line_data[1] = [];
		line_data[1][0] = 9000;
		line_data[1][1] = slope * line_data[1][0] + intercept;

		for (z in powerX)
		{
			if (powerY_cal[z]!=undefined) {
				diff[z] = [];
				diff[z][0] = 1.0*powerX[z][0];
				diff[z][1] = 1.0*powerY_cal[z][1] - 1.0*powerX[z][1];

				powerXY[z] = [];
				powerXY[z][0] = powerX[z][1];
				powerXY[z][1] = powerY_cal[z][1];
			}
		}

		plot();
		calibration_update = false;
	}

	function plot()
	{

		var plot = $.plot($("#power"), [
		{data: powerX, lines: { show: true }},
		{data: powerY_cal, lines: { show: true }}], {
			grid: { show: true, hoverable: true, clickable: true },
			xaxis: { mode: "time", timezone: "browser", min: start, max: end },
			selection: { mode: "xy" }
		});

		var plot = $.plot($("#diff"), [{color:2, data: diff, lines: { show: true }}], {
			grid: { show: true, hoverable: true },
			xaxis: { mode: "time", timezone: "browser", min: start, max: end }
		});

		var plot = $.plot($("#line"), [
			{color:2,data: powerXY, points: { show: true }},
			{color: "#000",data: line_data,lines: { show: true, fill: false }}],{
				grid: { show: true, hoverable: true },
				xaxis: { min: 0, max: 500 },
				yaxis: { min: 0, max: 500 }
			});
	}

	//--------------------------------------------------------------------------------------
	// Graph zooming
	//--------------------------------------------------------------------------------------
	$("#power").bind("plotselected", function (event, ranges) { start = ranges.xaxis.from; end = ranges.xaxis.to; vis_feed_data(); });
	//----------------------------------------------------------------------------------------------
	// Operate buttons
	//----------------------------------------------------------------------------------------------
	$("#zoomout").click(function () {inst_zoomout(); vis_feed_data();});
	$("#zoomin").click(function () {inst_zoomin(); vis_feed_data();});
	$('#right').click(function () {inst_panright(); vis_feed_data();});
	$('#left').click(function () {inst_panleft(); vis_feed_data();});
	$('.time').click(function () {inst_timewindow($(this).attr("time")); vis_feed_data();});
	//-----------------------------------------------------------------------------------------------

	$("#load").click(function () {
		powerxid = $("#powerx").val();
		poweryid = $("#powery").val();
		vis_feed_data();
	});

	$("#update").click(function () {
		calibration = 1.0 * $("#calibration").val();
		calibration_update = true;
		vis_feed_data();
	});
</script>

