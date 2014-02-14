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

	function showTooltip(x, y, contents, bgColour) {
		var offset = 15; // use higher values for a little spacing between `x,y` and tooltip
		var elem = $('<div id="tooltip">' + contents + '</div>').css({
      position: 'absolute',
      display: 'none',
			'font-weight':'bold',
			border: '1px solid rgb(255, 221, 221)',
      padding: '2px',
			'background-color': bgColour,
			opacity: '0.8'
    }).appendTo("body").fadeIn(200);
		//x = x - elem.width();
		elem.css({
			top: y - elem.height() - offset,
			left: x - elem.width() - offset,
		});
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
					// This implementation is clumsy, but the js native date.toTimeString() returns
					// strings like "08:53:35 GMT-0800", and there is no easy way to turn off the "GMT-xxxx" segment
					// blargh
          var date = new Date(parseInt(x));
          var hours = date.getHours();
          var minutes = date.getMinutes();
          var seconds = date.getSeconds();
					if (hours < 10)
						hours = "0"+hours;
					if (minutes < 10)
						minutes = "0"+minutes;
					if (seconds < 10)
						seconds = "0"+seconds;

          // will display time in 10:30:23 format
          var formattedTime = hours + ':' + minutes + ':' + seconds;

					// I'd like to eventually add colour hinting to the background of the tooltop.
					// This is why showTooltip has the bgColour parameter.
					showTooltip(item.pageX, item.pageY, item.series.label + " at " + formattedTime   + " = " + y, "#DDDDDD");
        }
      } else {
        $("#tooltip").remove();
        previousPoint = null;            
      }
    }
  });
 
</script>

