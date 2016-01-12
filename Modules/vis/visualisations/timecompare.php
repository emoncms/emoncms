<!--All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
-->

<?php
    global $path;
    $embed = intval(get("embed"));
    $feedid = intval(get("feedid"));
    $fill = intval(get("fill"));
    $depth = intval(get("depth"));

    if (!isset($feedidname)) $feedidname = "";
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.touch.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/vis.helper.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/timecompare/timecompare.js"></script>

<?php if (!$embed) { ?>
<h2><div id="timecompare_title"></div></h2>
<?php } ?>

<div id="timecompare"></div>

<script id="source" language="javascript" type="text/javascript">

    var path = "<?php echo $path; ?>";
    var embed = <?php echo $embed; ?>;
    var apikey = "<?php echo $apikey; ?>";
    var feedid = "<?php echo $feedid; ?>";
    var fill = "<?php echo $fill; ?>";
    var depth = "<?php echo $depth; ?>";

    $("#timecompare_title").replaceWith('<?php echo _("Time Compare: " . $feedidname); ?>');
    timecompare_init("#timecompare");
    vis_feed_data();
    
    var previousPoint = null;
    $("#timecompare").bind("plothover", function (event, pos, item) {
        //$("#x").text(pos.x.toFixed(2));
        //$("#y").text(pos.y.toFixed(2));

        if ($("#enableTooltip:checked").length > 0) {
            if (item) {
                if (previousPoint != item.dataIndex) {
                    previousPoint = item.dataIndex;

                    $("#tooltip").remove();
                    var x = item.datapoint[0].toFixed(2);
                    var y = item.datapoint[1].toFixed(2);

                    var pointDate = new Date(parseInt(x) - parseInt(item.series.adj));
                    var tipText = $.plot.formatDate(pointDate, y + "<br>%a %b %d %Y<br>%H:%M:%S");

                    tooltip(item.pageX, item.pageY, tipText, "#DDDDDD");
                }
            } else {
                $("#tooltip").remove();
                previousPoint = null;
            }
        }
    });

</script>

