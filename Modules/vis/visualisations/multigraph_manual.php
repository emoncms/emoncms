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
    $path = "../../../";
    $embed = intval($_GET["embed"]);
    $apikey = $_GET["apikey"];
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.selection.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/inst.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/multigraph.js"></script>

<?php if (!$embed) { ?>
<h2>Multigraph</h2>
<?php } ?>

<div id="multigraph"></div>

<script id="source" language="javascript" type="text/javascript">
    var embed = <?php echo $embed; ?>;
    var path = "<?php echo $path; ?>";
    var apikey = "<?php echo $apikey; ?>";

    var multigraph = [];
    multigraph[0] = {id:1, 'name':'power', datatype:1, left:true, right:false, fill:true};
    multigraph[1] = {id:2, 'name':'kwhd', datatype:2, left:false, right:true, fill:true};

    var timeWindow = (3600000*24.0*7);				//Initial time window
    var start = ((new Date()).getTime())-timeWindow;		//Get start time
    var end = (new Date()).getTime();				//Get end time

    multigraph_init("#multigraph");
    vis_feed_data();
</script>

