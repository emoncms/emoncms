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

    $type = 1;
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
    <button id="okb" class="btn btn-info"><?php echo _('Save'); ?></button>
    <button id="delete-button" class="btn btn-danger"><i class="icon-trash"></i><?php echo _('Delete data in window'); ?></button>
</div>

<div style="width:100% height:50px; background-color:#ddd; padding:10px; margin:10px;">
    Multiply data in window by: <input type="text" id="multiplyvalue" style="width:150px;" value="" />
    <button id="multiply-submit" class="btn btn-info"><?php echo _('Save'); ?></button>
</div>

<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="false">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel"><?php echo _('WARNING deleting feed data is permanent'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Are you sure you want to delete the data in this window?'); ?></p>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="confirmdelete" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
    </div>
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

    $('#multiply-submit').click(function () {

        var multiplyvalue = $("#multiplyvalue").val();

        $.ajax({
            url: path+'feed/scalerange.json',
            data: "&apikey="+apikey+"&id="+feedid+"&start="+start+"&end="+end+"&value="+multiplyvalue,
            dataType: 'json',
            async: false,
            success: function() {}
        });
        vis_feed_data();
    });

    $('#delete-button').click(function () {
        $('#myModal').modal('show');
    });

    $("#confirmdelete").click(function()
    {
        $.ajax({
            url: path+'feed/deletedatarange.json',
            data: "&apikey="+apikey+"&id="+feedid+"&start="+start+"&end="+end,
            dataType: 'json',
            async: false,
            success: function() {}
        });
        vis_feed_data();
        $('#myModal').modal('hide');
    });
</script>

