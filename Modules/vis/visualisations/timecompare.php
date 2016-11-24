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
    $npoints = intval(get("npoints"));

    if (!isset($feedidname)) $feedidname = "";
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.touch.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.togglelegend.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.canvas.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/base64.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/canvas2image.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/jquery.flot.saveAsImage.js"></script>

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
    var npoints = "<?php echo $npoints; ?>";
    
    var initzoom = urlParams.initzoom;
    if (initzoom==undefined || initzoom=='' || initzoom < 1) initzoom = '168'; // Initial zoom 7*24=168 hours  (1 week)

    $("#timecompare_title").replaceWith('<?php echo _("Time Compare: " . $feedidname); ?>');
    timecompare_init("#timecompare");
    vis_feed_data();

</script>

