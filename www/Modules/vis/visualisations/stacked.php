<!--
     All Emoncms code is released under the GNU General Public License v3.
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
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.stack.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/date.format.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/daysmonthsyears.js"></script>
<?php if (!$embed) { ?>
<h2>Stacked</h2>
<?php } ?>

<div id="graph_bound" style="width:100%; height:400px; position:relative; ">
    <div id="graph"></div>
    <div id="loading" style="position:absolute; top:0px; left:0px; width:100%; height:100%; background-color: rgba(255,255,255,0.5);"></div>
    <h2 style="position:absolute; top:0px; left:40px;"><span id="out"></span></h2>
</div>

<script id="source" language="javascript" type="text/javascript">
    var kwhdA = <?php echo $bottom; ?>;
    var kwhdB = <?php echo $top; ?>;

    var path = "<?php echo $path; ?>";

    // API key
    var apikey = "<?php echo $apikey?>";

    var dataA = get_feed_data(kwhdA,0,0,0);
    var dataB = get_feed_data(kwhdB,0,0,0);


    var embed = <?php echo $embed; ?>;
    $('#graph').width($('#graph_bound').width());
    $('#graph').height($('#graph_bound').height());
    if (embed) $('#graph').height($(window).height());

    $('#loading').hide();
    var view = 0;

    var daysA = [];
    var monthsA = [];
    var daysB = [];
    var monthsB = [];

    monthsA = get_months(dataA);
    monthsB = get_months(dataB);

    $(window).resize(function(){
        $('#graph').width($('#graph_bound').width());
        if (embed) $('#graph').height($(window).height());
        bargraph(monthsA.data,monthsB.data,3600*24*20,"month");
    });

    bargraph(monthsA.data,monthsB.data,3600*24*20,"month");



    $("#graph").bind("plotclick", function (event, pos, item)
    {
        if (item!=null)
        {
            if (view==1)
            {

            }
            if (view==0)
            {
                var d = new Date();
                d.setTime(item.datapoint[0]);
                daysA = get_days_month(dataA,d.getMonth(),d.getFullYear());
                daysB = get_days_month(dataB,d.getMonth(),d.getFullYear());
                bargraph(daysA,daysB,3600*22,"day");
                view = 1;
                $("#out").html("");
            }
        }
        else
        {

            if (view==1) { $("#out").html(""); view = 0; bargraph(monthsA.data,monthsB.data,3600*24*20,"month"); }
            if (view==2) { $("#out").html(""); view = 1; bargraph(daysA,daysB,3600*22,"day"); }
        }
    });

    $("#graph").bind("plothover", function (event, pos, item)
    {
        if (item!=null)
        {
            var d = new Date();
            d.setTime(item.datapoint[0]);
            var mdate = new Date(item.datapoint[0]);

            console.log(item);
            console.log(daysA);
            console.log(daysB);
            var type = "", value = 0;
            if (item.seriesIndex == 0 && view==0) {value = monthsA.data[item.dataIndex][1];};
            if (item.seriesIndex == 1 && view==0) {value = monthsB.data[item.dataIndex][1];};

            if (item.seriesIndex == 0 && view==1) {value = 1*daysA[item.dataIndex][1];};
            if (item.seriesIndex == 1 && view==1) {value = 1*daysB[item.dataIndex][1];};

            if (view==0) $("#out").html(type+' '+value.toFixed(1)+" kWh/d | "+mdate.format("mmm yyyy"));
            if (view==1) $("#out").html(type+' '+value.toFixed(1)+" kWh/d | "+mdate.format("dS mmm yyyy"));
        }
    });

    function bargraph(dataA,dataB,barwidth, mode)
    {
        $.plot($("#graph"), [ {color: "#0096ff", data:dataA}, {color: "#7cc9ff", data:dataB}],
        {
            series: {
            stack: true,
            bars: { show: true,align: "center",barWidth: (barwidth*1000),fill: true }
            },
    grid: { show: true, hoverable: true, clickable: true },
            xaxis: { mode: "time", timezone: "browser", minTickSize: [1, mode],
                     tickLength: 1 }
        });
    }
</script>
