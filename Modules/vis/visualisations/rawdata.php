<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
		<head>
		
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
				<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>
				<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>

				<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
				<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/inst.js"></script>
				<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/proc.js"></script>
		</head>
		<body>
<?php if (!$embed) { ?>
<h2>Raw data: <?php echo $feedidname; ?></h2>
<?php } ?>

		<div id="graph_bound" style="width:100%; height:400px; position:relative; ">
			<div id="graph"></div>
			<div style="position:absolute; top:20px; left:40px; opacity:0.5;">

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

<script id="source" language="javascript" type="text/javascript">

	var feedid = "<?php echo $feedid; ?>";
	var feedname = "<?php echo $feedidname; ?>";
	var path = "<?php echo $path; ?>";
	var apikey = "<?php echo $apikey; ?>";
	var valid = "<?php echo $valid; ?>";


	var plotfill = <?php echo $fill; ?> == 1;
	var units = "<?php echo $units; ?>";
	var embed = <?php echo $embed; ?>;
	
	// Eventually We can store the plot colors in the DB, and use a php command to stick it here
	var plotColour = "<?php echo $colour; ?>";
	
	var $graph_bound = $('#graph_bound');
	var $graph = $('#graph').width($graph_bound.width()).height($('#graph_bound').height());

	if (embed) $graph.height($(window).height());

	var timeWindow = (3600000*24.0*7);	//Initial time window
	var start = +new Date - timeWindow;	//Get start time
	var end = +new Date;				    //Get end time

	var graph_data = [];
	vis_feed_data();

	$(window).resize(function(){
		$graph.width($graph_bound.width());
		if (embed) $graph.height($(window).height());
		plot();
	});

 
	function vis_feed_data()
	{
		if (valid) {
				get_feed_data_async(feedid,start,end,1000, function(response){
						graph_data = response;
						var stats = power_stats(graph_data);
						var out = "Average: "+stats['average'].toFixed(1)+units;
						if (units=='W') out+= " | "+stats['kwh'].toFixed(2)+" kWh";
						$("#stats").html(out);   
						plot();
				});
		}
	}

	function plot()
	{
		var plot = $.plot($graph, 
			[{data: graph_data, lines: { show: true, fill: plotfill }, color: plotColour}], 
			{
				grid: { show: true, hoverable: true, clickable: true },
				xaxis: { mode: "time", timezone: "browser", min: start, max: end },
				selection: { mode: "x" }
			});
	}

	//--------------------------------------------------------------------------------------
	// Graph zooming
	//--------------------------------------------------------------------------------------
	$graph.bind("plotselected", function (event, ranges) { start = ranges.xaxis.from; end = ranges.xaxis.to; vis_feed_data(); });
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

		</body>
</html>
