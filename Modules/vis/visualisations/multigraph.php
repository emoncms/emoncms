<!--
	 All Emoncms code is released under the GNU Affero General Public License.
	 See COPYRIGHT.txt and LICENSE.txt.

	---------------------------------------------------------------------
	Emoncms - open source energy visualisation
	Part of the OpenEnergyMonitor project:
	http://openenergymonitor.org
-->

<?php
	global $path;
	$embed = intval(get("embed"));
	$mid = intval(get("mid"));
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/inst.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/multigraph.js"></script>

<?php if (!$embed) { ?>
<h2>Multigraph</h2>
<?php } ?>


<div id="multigraph"></div>

<script id="source" language="javascript" type="text/javascript">

	var mid = <?php echo $mid; ?>;
	var path = "<?php echo $path; ?>";
	var embed = <?php echo $embed; ?>;
	var apikey = "<?php echo $apikey; ?>";
	var multigraph_feedlist = {};

	$.ajax({ url: path+"vis/multigraph/get.json", data: "&id="+mid, dataType: 'json', async: true,
		success: function(data)
		{
			multigraph_feedlist = data;

			var timeWindow = (3600000*24.0*7);				//Initial time window
			var start = ((new Date()).getTime())-timeWindow;		//Get start time
			var end = (new Date()).getTime();				//Get end time

			multigraph_init("#multigraph");
			vis_feed_data();
		}
	});

	function showTooltip(x, y, contents) {
		$('<div id="tooltip">' + contents + '</div>').css( {
			position: 'absolute',
			display: 'none',
			top: y + 5,
			left: x + 5,
			border: '1px solid #fdd',
			padding: '2px',
			'background-color': '#fee',
			opacity: 0.80
		}).appendTo("body").fadeIn(200);
	}

	var previousPoint = null;
	$("#multigraph").bind("plothover", function (event, pos, item)
	{
		$("#x").text(pos.x.toFixed(2));
		$("#y").text(pos.y2.toFixed(2));

		if ($("#enableTooltip:checked").length > 0) {
			if (item) {
				if (previousPoint != item.dataIndex) {
					previousPoint = item.dataIndex;

					$("#tooltip").remove();
					var x = item.datapoint[0].toFixed(2),
					y = item.datapoint[1].toFixed(2);

					// create a new javascript Date object based on the timestamp
					var date = new Date(parseInt(x));
					// hours part from the timestamp
					var hours = date.getHours();
					// minutes part from the timestamp
					var minutes = date.getMinutes();
					// seconds part from the timestamp
					var seconds = date.getSeconds();
					if (hours < 10)
					{
						hours = "0"+hours;
					}
					if (minutes < 10)
					{
						minutes = "0"+minutes;
					}
					if (seconds < 10)
					{
						seconds = "0"+seconds;
					}
					// will display time in 10:30:23 format
					var formattedTime = hours + ':' + minutes + ':' + seconds;

					showTooltip(item.pageX, item.pageY, item.series.label + " at " + formattedTime   + " = " + y);
				}
			} else {
				$("#tooltip").remove();
				previousPoint = null;
			}
		}
	});

</script>

