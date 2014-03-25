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

    $type = 2;
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/proc.js"></script>

<?php if (!$embed) { ?>
<h2>Datapoint editor: <?php echo $feedidname; ?></h2>
<p>Click on a datapoint to select, then in the edit box below the graph enter in the new value. You can also add another datapoint by changing the time to a point in time that does not yet have a datapoint.</p>
<?php } ?>

<div id="graph_bound" style="height:350px; width:100%; position:relative; ">
    <div id="graph"></div>
    <div style="position:absolute; top:20px; right:20px;">

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

<div style="width:100% height:50px; background-color:#ddd; padding:10px; margin:10px;">
    Edit feed_<?php echo $feedid; ?> @ time: <input type="text" id="time" style="width:150px;" value="" /> new value:
    <input type="text" id="newvalue" style="width:150px;" value="" />
    <input id="okb" type="submit" value="ok" class="button05"/>
    <input id="delb" type="submit" value="Delete" class="button05"/>
</div>

<script id="source" language="javascript" type="text/javascript">

    $('#graph').width($('#graph_bound').width());
    $('#graph').height($('#graph_bound').height());

    var feedid = "<?php echo $feedid; ?>";
    var feedname = "<?php echo $feedidname; ?>";
    var type = "<?php echo $type; ?>";
    var path = "<?php echo $path; ?>";
    var apikey = "<?php echo $write_apikey; ?>";

    var timeWindow = (3600000*24.0*7);                //Initial time window
    var start = ((new Date()).getTime())-timeWindow;      //Get start time
    var end = (new Date()).getTime();             //Get end time

    vis_feed_data();

    function vis_feed_data()
    {
        var graph_data = get_feed_data(feedid,start,end,1000);
        var stats = power_stats(graph_data);
        //$("#stats").html("Average: "+stats['average'].toFixed(0)+"W | "+stats['kwh'].toFixed(2)+" kWh");

        var plotdata = {data: graph_data, lines: { show: true, fill: true }};
        if (type == 2) plotdata = {data: graph_data, bars: { show: true, align: "center", barWidth: 3600*18*1000, fill: true}};

        var plot = $.plot($("#graph"), [plotdata], {
            grid: { show: true, clickable: true},
            xaxis: { mode: "time", timezone: "browser", min: start, max: end },
            selection: { mode: "x" }
        });

    }

    $("#graph").bind("plotclick", function (event, pos, item) {
        $("#time").val(item.datapoint[0]/1000);
        $("#newvalue").val(item.datapoint[1]);
        //$("#stats").html("Value: "+item.datapoint[1]);
    });

    //--------------------------------------------------------------------------------------
    // Graph zooming
    //--------------------------------------------------------------------------------------
    $("#graph").bind("plotselected", function (event, ranges) { start = ranges.xaxis.from; end = ranges.xaxis.to; vis_feed_data(); });
    //----------------------------------------------------------------------------------------------
    // Operate buttons
    //----------------------------------------------------------------------------------------------
    $("#zoomout").click(function () {inst_zoomout(); vis_feed_data();});
    $("#zoomin").click(function () {inst_zoomin(); vis_feed_data();});
    $('#right').click(function () {inst_panright(); vis_feed_data();});
    $('#left').click(function () {inst_panleft(); vis_feed_data();});
    $('.time').click(function () {inst_timewindow($(this).attr("time")); vis_feed_data();});
    //-----------------------------------------------------------------------------------------------

    $('#okb').click(function () {
        var time = $("#time").val();
        var newvalue = $("#newvalue").val();

        $.ajax({
            url: path+'feed/update.json',
            data: "&apikey="+apikey+"&id="+feedid+"&time="+time+"&value="+newvalue,
            dataType: 'json',
            async: false,
            success: function() {}
        });
        vis_feed_data();
    });

    $('#delb').click(function () {
        var time = $("#time").val();

        $.ajax({
            url: path+'feed/update.json',
            data: "&apikey="+apikey+"&id="+feedid+"&time="+time+"&delete=1",
            dataType: 'json',
            async: false,
            success: function() {}
        });
        vis_feed_data();
    });
</script>

