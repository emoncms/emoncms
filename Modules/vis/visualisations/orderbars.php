<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/
    defined('EMONCMS_EXEC') or die('Restricted access');
    global $path, $embed, $vis_version;
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.merged.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js?v=<?php echo $vis_version; ?>"></script>

<?php if (!$embed) { ?>
<h2><?php echo _("Bar graph (ordered by height):"); ?> <?php echo $feedidname; ?></h2>
<?php } ?>

<div id="graph_bound" style="height:400px; width:100%; position:relative; ">
    <div id="graph"></div>
    <h3 style="position:absolute; top:00px; left:50px;"><span id="stats"></span></h3>
</div>

<script id="source" language="javascript" type="text/javascript">
var embed = <?php echo $embed; ?>;
$('#graph').width($('#graph_bound').width());
$('#graph').height($('#graph_bound').height());
if (embed) $('#graph').height($(window).height());

var feedid = "<?php echo $feedid; ?>";
var feedname = "<?php echo $feedidname; ?>";
var apikey = "<?php echo $apikey; ?>";
var delta = <?php echo $delta; ?>;

feed.apikey = apikey;

var timeWindow = (3600000 * 24.0 * 365 * 5); //Initial time window
var start = +new Date - timeWindow; //Get start time
var end = +new Date;

var graph_data = [];
vis_feed_data();

$(document).on('window.resized hidden.sidebar.collapse shown.sidebar.collapse', vis_resize);

function vis_resize() {
    $('#graph').width($('#graph_bound').width());
    $('#graph').height($('#graph_bound').height());
    if (embed) $('#graph').height($(window).height());
    plot();
}

function vis_feed_data() {
    graph_data = feed.getdata(feedid, start, end, "daily", 0, window.delta, 1, 0);

    for (x = 0; x < graph_data.length; x++) {
        for (y = 0; y < (graph_data.length - 1); y++) {
            if (graph_data[y][1] * 1 < graph_data[y + 1][1] * 1) {
                holder = graph_data[y + 1];
                graph_data[y + 1] = graph_data[y];
                graph_data[y] = holder;
            }
        }
    }

    for (x = 0; x < graph_data.length; x++) graph_data[x][0] = x;

    plot();
}

function plot() {
    var plot = $.plot($("#graph"), [{
        data: graph_data,
        bars: {
            show: true,
            align: "center",
            fill: true
        }
    }], {
        canvas: true,
        grid: {
            show: true,
            hoverable: true
        },
        yaxis: {
            min: 0
        },
        touch: {
            pan: "x",
            scale: "x"
        }
    });
}
</script>

