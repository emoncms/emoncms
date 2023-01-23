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

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/daysmonthsyears.js?v=3"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/feed/feed.js?v=<?php echo $vis_version; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/vis.helper.js?v=<?php echo $vis_version; ?>"></script>

<?php if (!$embed) { ?>
<h2><?php echo _("kWh/d Zoomer"); ?></h2>
<?php } ?>

<div id="placeholder_bound" style="width:100%; height:400px; position:relative; ">
    <div style="position:absolute; top:10px; left:0px; width:100%;">
        &nbsp;&nbsp;<span id="out2">Loading...</span><span id="out"></span>
    </div>
    
    <div id="placeholder" style="top: 30px; left:0px;"></div>
    
    <div style="position:relative; width:100%; bottom:-30px;">&nbsp;&nbsp;&nbsp;&nbsp;
        <small id="axislabely"></small><br>&nbsp;&nbsp;<span id="bot_out"></span>
    </div>
    
    <div id="graph-buttons" style="position:absolute; top:45px; right:32px; opacity:0.5; display: none;">
        <div class='btn-group' id="graph-return">
            <button class='btn graph-return' id="return">Back</button>
        </div>
        
        <div class='btn-group'>
            <button class='btn graph-time' time='1'>D</button>
            <button class='btn graph-time' time='7'>W</button>
            <button class='btn graph-time' time='30'>M</button>
            <button class='btn graph-time' time='365'>Y</button>
        </div>
        
        <div class='btn-group' id='graph-navbar' style='display: none;'>
            <button class='btn graph-nav' id='zoomin'>+</button>
            <button class='btn graph-nav' id='zoomout'>-</button>
            <button class='btn graph-nav' id='left'><</button>
            <button class='btn graph-nav' id='right'>></button>
        </div>
    </div>

</div>

<script id="source" language="javascript" type="text/javascript">
var kwhd = <?php echo $kwhd; ?>;
var power = <?php echo $power; ?>;
var apikey = "<?php echo $apikey; ?>";
feed.apikey = apikey;
var embed = <?php echo $embed; ?>;
var delta = <?php echo $delta; ?>;

var timeWindow = (3600000*24.0*365*10);   //Initial time window 10 years
view.start = +new Date - timeWindow;  //Get start time
view.end = +new Date; 

$('#placeholder').width($('#placeholder_bound').width());
$('#placeholder').height($('#placeholder_bound').height()-80);
if (embed) $('#placeholder').height($(window).height()-80);

var event_vis_feed_data;
var ajaxAsyncXdr;

var kwh_data = [];
var days = [];
var months = [];
var years = [];
var power_data = [];

var view_mode = 0;

// Global instantaneous graph variables
var feedid = power;

var price = <?php echo $pricekwh ?>;
var currency = "<?php echo $currency ?>";
var currency_after_val = "<?php echo $currency_after_val ?>";

var bot_kwhd_text = "";

feed.getdata(kwhd,view.start,view.end,"daily",0,delta,0,0,vis_feed_kwh_data_callback); // get 5 years of daily kw_data

//load feed kwh_data
function vis_feed_kwh_data_callback(data) {

    kwh_data = data;
    var total = 0, ndays=0;
    for (var z in kwh_data) {
        total += kwh_data[z][1]; 
        ndays++;
    }

    bot_kwhd_text = "<?php echo _("Total:"); ?> "+(total).toFixed(0)+" <?php echo _("kWh"); ?> : "+add_currency((total*price), 0)+" | <?php echo _("Average:"); ?> "+(total/ndays).toFixed(1)+" <?php echo _("kWh"); ?> : "+add_currency((total/ndays)*price, 2)+" | "+add_currency((total/ndays)*price*7, 0)+" <?php echo _("a week"); ?>, "+add_currency((total/ndays)*price*365, 0)+" <?php echo _("a year"); ?> | <?php echo _("Unit price:"); ?> "+add_currency(price, 2);

    years = get_years(kwh_data);
    //set_annual_view();

    months = get_months_year(kwh_data,new Date().getFullYear());
    //set_monthly_view();

    days = get_last_30days(kwh_data);
    set_last30days_view();
}

// Rounds value and adds currency after or before the value
function add_currency(value, decimal_places) {
    var val = value.toFixed(decimal_places);

    if (currency_after_val == '1') {
        return val + currency;
    } else {
        return currency + val;
    }
}

function vis_feed_data() {
    clearTimeout(event_vis_feed_data); // cancel pending event
    event_vis_feed_data = setTimeout(function() {
        vis_feed_data_delayed();
    }, 500);
    instgraph(power_data);
    $("#out").html("");
    $("#bot_out").html("Loading...");
}

function vis_feed_data_delayed() {
    view.calc_interval(800);
    if (typeof ajaxAsyncXdr !== 'undefined') {
        ajaxAsyncXdr.abort(); // abort pending requests
        ajaxAsyncXdr = undefined;
    }
    ajaxAsyncXdr = feed.getdata(feedid, view.start, view.end, view.interval, 0, 0, 1, 1, vis_feed_data_callback);
}

//load feed data
function vis_feed_data_callback(data){
    power_data=data;
    var st = stats(power_data);
    instgraph(power_data);

    var datetext = "";
    if ((view.end-view.start)<3600000*25) { var mdate = new Date(view.start); datetext = mdate.format("dd mmm yyyy") + ": "; }
    $("#bot_out").html(datetext+"<?php echo _("Average:"); ?> "+st['mean'].toFixed(0)+"W | "+st['kwh'].toFixed(2)+" <?php echo _("kWh"); ?> | "+add_currency(st['kwh']*price, 2));
}

// Zoom in on bar click
$("#placeholder").bind("plotclick", function(event, pos, item) {
    if (item != null) {
        if (view_mode == 2) set_inst_view(item.datapoint[0]);

        if (view_mode == 1) {
            var d = new Date();
            d.setTime(item.datapoint[0]);
            days = get_days_month(kwh_data, d.getMonth(), d.getFullYear());
            set_daily_view();
        }
        if (view_mode == 0) {
            var d = new Date();
            d.setTime(item.datapoint[0]);
            months = get_months_year(kwh_data, d.getFullYear());
            set_monthly_view();
        }
    }
});

// Return button click
$("#return").click(function() {
    if (view_mode == 1) set_annual_view();
    if (view_mode == 2) set_monthly_view();
    if (view_mode == 3) set_daily_view();
});

// Info label
$("#placeholder").bind("plothover", function (event, pos, item){
    if (item!=null) {
        var d = new Date();
        d.setTime(item.datapoint[0]);
        var mdate = new Date(item.datapoint[0]);
        if (view_mode==0) $("#out").html(" : "+mdate.format("yyyy")+" | "+item.datapoint[1].toFixed(0)+" kWh : " + add_currency(item.datapoint[1]*price, 2) +" | Average: "+(item.datapoint[1]/years.days[item.dataIndex]).toFixed(1)+" kWh/d, "+(item.datapoint[1]/years.days[item.dataIndex]*12).toFixed(0)+" kWh/m : "+add_currency(item.datapoint[1]/years.days[item.dataIndex]*price, 2)+"/d, "+add_currency(item.datapoint[1]/12*price, 0)+"/m");
        else if (view_mode==1) $("#out").html(" : "+mdate.format("mmm yyyy")+" | "+item.datapoint[1].toFixed(0)+" kWh : " + add_currency(item.datapoint[1]*price, 2)+" | Average: "+(item.datapoint[1]/months.days[item.dataIndex]).toFixed(1)+" kWh/d, "+(item.datapoint[1]*12).toFixed(0)+" kWh/y : "+add_currency(item.datapoint[1]/months.days[item.dataIndex]*price, 2)+"/d, "+add_currency(item.datapoint[1]*12*price, 0)+"/y");
        else if (view_mode==2) $("#out").html(" : "+mdate.format("dd mmm yyyy")+" | "+item.datapoint[1].toFixed(1)+" kWh : "+add_currency(item.datapoint[1]*price, 2)+" | Average: "+(item.datapoint[1]*30).toFixed(0)+" kWh/m, "+(item.datapoint[1]*365).toFixed(0)+" kWh/y : "+add_currency(item.datapoint[1]*30*price, 0)+"/m, "+add_currency(item.datapoint[1]*price*365, 0)+"/y");
        else if (view_mode==3) $("#out").html(" : "+item.datapoint[1].toFixed(0)+" W");
    }
});

// Graph zooming
$("#placeholder").bind("plotselected", function(event, ranges) {
    view.start = ranges.xaxis.from;
    view.end = ranges.xaxis.to;
    vis_feed_data();
});

// Operate buttons
$("#zoomout").click(function() {
    view.zoomout();
    vis_feed_data();
});
$("#zoomin").click(function() {
    view.zoomin();
    vis_feed_data();
});
$('#right').click(function() {
    view.panright();
    vis_feed_data();
});
$('#left').click(function() {
    view.panleft();
    vis_feed_data();
});
$('.graph-time').click(function() {
    view.timewindow($(this).attr("time"));
    vis_feed_data();
});

$(document).on('window.resized hidden.sidebar.collapse shown.sidebar.collapse', vis_resize);

function vis_resize() {
    $('#placeholder').width($('#placeholder_bound').width());
    $('#placeholder').height($('#placeholder_bound').height() - 80);
    if (embed) $('#placeholder').height($(window).height() - 80);
    if (view_mode == 0) set_annual_view();
    if (view_mode == 1) set_monthly_view();
    if (view_mode == 2) set_daily_view();
    if (view_mode == 3) vis_feed_data();
}

// Graph buttons and navigation efects for mouse and touch
$("#placeholder").mouseenter(function() {
    if (view_mode == 3) {
        $("#graph-navbar").show();
    }
    $("#graph-buttons").stop().fadeIn();
    $("#stats").stop().fadeIn();
});
$("#placeholder_bound").mouseleave(function() {
    $("#graph-buttons").stop().fadeOut();
    $("#stats").stop().fadeOut();
});
$("#placeholder").bind("touchstarted", function(event, pos) {
    $("#graph-navbar").hide();
    $("#graph-buttons").stop().fadeOut();
    $("#stats").stop().fadeOut();
});
$("#placeholder").bind("touchended", function(event, ranges) {
    $("#graph-buttons").stop().fadeIn();
    $("#stats").stop().fadeIn();
    if (view_mode == 3) {
        view.start = ranges.xaxis.from;
        view.end = ranges.xaxis.to;
        vis_feed_data();
    }
});

function bargraph(data, barwidth, mode) {
    $.plot($("#placeholder"), [{
        color: "#0096ff",
        data: data
    }], {
        canvas: true,
        bars: {
            show: true,
            align: "center",
            barWidth: (barwidth * 1000),
            fill: true
        },
        grid: {
            show: true,
            hoverable: true,
            clickable: true
        },
        xaxis: {
            mode: "time",
            timezone: "browser",
            minTickSize: [1, mode],
            tickLength: 1
        },
        touch: {
            pan: "",
            scale: "",
            callback: function() {}
        }
    });
}

function instgraph(data) {
    $.plot($("#placeholder"), [{
        color: "#0096ff",
        data: data
    }], {
        lines: {
            show: true,
            fill: true
        },
        grid: {
            show: true,
            hoverable: true
        },
        xaxis: {
            mode: "time",
            timezone: "browser",
            min: view.start,
            max: view.end
        },
        selection: {
            mode: "x"
        },
        touch: {
            pan: "x",
            scale: "x"
        }
    });
}

function set_daily_view() {
    bargraph(days, 3600 * 22, "day");
    $("#out").html("");
    view_mode = 2;
    $("#return").html("View Monthly");
    $("#out2").html("Daily");
    $('#axislabely').html("Energy (kWh)");
    $("#bot_out").html(bot_kwhd_text);
    $("#graph-return").show();
    $("#graph-navbar").hide();
    $('.graph-time').hide();
}

function set_monthly_view() {
    bargraph(months.data, 3600 * 24 * 20, "month");
    $("#out").html("");
    view_mode = 1;
    $("#return").html("View Annual");
    $("#out2").html("Monthly");
    $('#axislabely').html("Energy (kWh)");
    $("#graph-return").show();
    $("#graph-navbar").hide();
    $('.graph-time').hide();
}

function set_annual_view() {
    bargraph(years.data, 3600 * 24 * 330, "year");
    $("#out").html("");
    view_mode = 0;
    $("#out2").html("Annual");
    $('#axislabely').html("Energy (kWh)");
    $("#graph-return").hide();
    $("#graph-navbar").hide();
    $('.graph-time').hide();
}

function set_last30days_view() {
    bargraph(days, 3600 * 22, "day");
    $("#out").html("");
    view_mode = 2;
    $("#return").html("View monthly");
    $("#out2").html("Last 30 days. Daily");
    $('#axislabely').html("Energy (kWh)");
    $("#bot_out").html(bot_kwhd_text);
    $("#graph-return").show();
    $("#graph-navbar").hide();
    $('.graph-time').hide();
}

function set_inst_view(day) {
    view.start = day;
    view.end = day + 3600000 * 24;

    vis_feed_data();
    view_mode = 3;
    $("#out2").html("Power");
    $("#return").html("View Daily");
    $('#axislabely').html("Power (Watts)");
    $("#graph-return").show();
    $("#graph-navbar").show();
    $('.graph-time').show();
}
</script>
