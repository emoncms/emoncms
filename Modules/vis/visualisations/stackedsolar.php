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
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.stack.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/feed/feed.js?v=<?php echo $vis_version; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/daysmonthsyears.js"></script>

<?php if (!$embed) { ?>
<h2><?php echo _("Stacked"); ?></h2>
<?php } ?>

<div id="graph_bound" style="width:100%; height:400px; position:relative; ">
    <div id="graph"></div>
    <div id="loading" style="position:absolute; top:0px; left:0px; width:100%; height:100%; background-color: rgba(255,255,255,0.5);"></div>
    <h2 style="position:absolute; top:0px; left:40px;"><span id="out"></span></h2>
</div>

<script id="source" language="javascript" type="text/javascript">
var solar_id = <?php echo $solar; ?>;
var use_id = <?php echo $consumption; ?>;
var delta = <?php echo $delta; ?>;
var apikey = "<?php echo $apikey?>";
feed.apikey = apikey;

var timeWindow = (3600000*24.0*365*5); //Initial time window
var start = +new Date - timeWindow;    //Get start time
var end = +new Date; 

var d = new Date()
var n = d.getTimezoneOffset();
var offset = n / -60;
start = Math.floor(start / 86400000) * 86400000;
end = Math.floor(end / 86400000) * 86400000;
start -= offset * 3600000;
end -= offset * 3600000;

var solar_data = feed.getdata(solar_id,start,end,"daily",0,delta,0,0);
var use_data = feed.getdata(use_id,start,end,"daily",0,delta,0,0);

var import_data = []
var export_data = []

for (var z in solar_data) {
    if (solar_data[z][1]==null) solar_data[z][1] = 0;
    if (use_data[z][1]==null) use_data[z][1] = 0;
    
    let balance = use_data[z][1] - solar_data[z][1];
    let import_val = 0;
    let export_val = 0;
    
    if (balance>0) {
        import_val = balance;
    } else {
        export_val = balance*-1;
    }
    
    let time = solar_data[z][0];
    import_data.push([time,import_val])
    export_data.push([time,export_val])
}


var embed = <?php echo $embed; ?>;
$('#graph').width($('#graph_bound').width());
$('#graph').height($('#graph_bound').height());
if (embed) $('#graph').height($(window).height());

$('#loading').hide();
var view = 0;

var solar_days = [];
var import_days = [];
var export_days = [];

var solar_months = get_months(solar_data);
var import_months = get_months(import_data);
var export_months = get_months(export_data);

$(function() {
    $(document).on('window.resized hidden.sidebar.collapse shown.sidebar.collapse', vis_resize);
})

function vis_resize() {
    $('#graph').width($('#graph_bound').width());
    $('#graph').height($('#graph_bound').height());
    if (embed) $('#graph').height($(window).height());
    bargraph(solar_months.data,import_months.data,export_months.data,3600*24*20,"month");
}

bargraph(solar_months.data,import_months.data,export_months.data,3600*24*20,"month");

$("#graph").bind("plotclick", function (event, pos, item){
    if (item!=null) {
        if (view==1){    }
        if (view==0){
            var d = new Date();
            d.setTime(item.datapoint[0]);
            solar_days = get_days_month(solar_data,d.getMonth(),d.getFullYear());
            import_days = get_days_month(import_data,d.getMonth(),d.getFullYear());
            export_days = get_days_month(export_data,d.getMonth(),d.getFullYear());
            bargraph(solar_days,import_days,export_days,3600*22,"day");
            view = 1;
            $("#out").html("");
        }
    } else {
        if (view==1) { $("#out").html(""); view = 0; bargraph(solar_months.data,import_months.data,export_months.data,3600*24*20,"month"); }
        if (view==2) { $("#out").html(""); view = 1; bargraph(solar_days,import_days,export_days,3600*22,"day"); }
    }
});

$("#graph").bind("plothover", function (event, pos, item){
    if (item!=null){
        var d = new Date();
        d.setTime(item.datapoint[0]);
        var mdate = new Date(item.datapoint[0]);
        
        if (item.series.data[item.dataIndex]!=undefined) {
            var value = item.series.data[item.dataIndex][1];
            
            var type = "";
            if (item.seriesIndex == 0) type = "Solar";
            if (item.seriesIndex == 1) type = "Import"
            if (item.seriesIndex == 2) type = "Export";

            if (view==0) $("#out").html(type+' '+value.toFixed(1)+" kWh/month | "+mdate.format("mmm yyyy"));
            if (view==1) $("#out").html(type+' '+value.toFixed(1)+" kWh/d | "+mdate.format("dS mmm yyyy"));
        }
    }
});

function bargraph(solar_data,import_data,export_data,barwidth, mode){
    $.plot($("#graph"), [ {color: "#e0c21f", data:solar_data}, {color: "#4e9acf", data:import_data}, {color: "#AA96ff", data:export_data}], {
        canvas: true,
        series: {
            stack: true,
            bars: { show: true,align: "center",barWidth: (barwidth*1000),fill: true }
        },
        grid: { show: true, hoverable: true, clickable: true },
        xaxis: { mode: "time", timezone: "browser", minTickSize: [1, mode], tickLength: 1 },
        touch: { pan: "x", scale: "x" }
    });
}
</script>
