<html>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
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

    ?>

        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.touch.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/date.format.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/daysmonthsyears.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/zoom/view.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/zoom/graphs.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/proc.js"></script>

<?php if (!$embed) { ?>
<h2><?php echo _("kWh/d Zoomer"); ?></h2>
<?php } ?>

<div id="placeholder_bound" style="height:500px; width:100%; position:relative; ">
    <div style="position:absolute; top:10px; left:0px; font-size:18px;">
        <b><span id="out2"></span></b><?php echo _(": Hover on bar for info, press to zoom in"); ?>
    </div>
    <div id="placeholder" style="font-family: arial; position:absolute; top: 40px; left:0px;"></div>
    
    <b><p style="position:absolute; bottom: 50px; left:10px;"><span id="axislabely"></span></p></b>
    <h2 style="position:absolute; top:40px; left:32px;"><span id="out"></span></h2>
    <p id="bot_out" style="position:absolute; bottom:-10px; font-size:18px; font-weight:bold;"></p>

    

    <div id="graph-buttons" style="position:absolute; top:55px; right:32px; opacity:0.5;">
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
    var path = "<?php echo $path; ?>";
    var apikey = "<?php echo $apikey; ?>";
    var embed = <?php echo $embed; ?>;
    
    var timeWindow = (3600000*24.0*365*5);   //Initial time window
    var start = +new Date - timeWindow;  //Get start time
    var end = +new Date; 

    $('#placeholder').width($('#placeholder_bound').width());
    $('#placeholder').height($('#placeholder_bound').height()-120);
    if (embed) $('#placeholder').height($(window).height()-120);

    var data = [];
    var days = [];
    var months = [];
    var years = [];

    var view = 0;

    // Global instantaneous graph variables
    var feedid = power;

    var price = <?php echo $pricekwh ?>;
    var currency = "<?php echo $currency ?>";
    var currency_after_val = "<?php echo $currency_after_val ?>";

    var bot_kwhd_text = "";

    var kwh_data = get_feed_data(kwhd,start,end,3600*24,1,1);

    var total = 0, ndays=0;
    for (z in kwh_data) {
        total += parseFloat(kwh_data[z][1]); ndays++;
    }

    bot_kwhd_text = "<?php echo _("Total:"); ?> "+(total).toFixed(0)+" <?php echo _("kWh"); ?> : "+add_currency((total*price), 0)+" | <?php echo _("Average:"); ?> "+(total/ndays).toFixed(1)+" <?php echo _("kWh"); ?> : "+add_currency((total/ndays)*price, 2)+" | "+add_currency((total/ndays)*price*7, 0)+" <?php echo _("a week"); ?>, "+add_currency((total/ndays)*price*365, 0)+" <?php echo _("a year"); ?> | <?php echo _("Unit price:"); ?> "+add_currency(price, 2);

    years = get_years(kwh_data);
    //set_annual_view();

    months = get_months_year(kwh_data,new Date().getFullYear());
    //set_monthly_view();

    days = get_last_30days(kwh_data);
    set_last30days_view();

    // Rounds value and adds currency after or before the value
    function add_currency(value, decimal_places)
    {
        var val = value.toFixed(decimal_places);

        if (currency_after_val == '1') {
            return val + currency;
        } else {
            return currency + val;
        }
    }

    function vis_feed_data()
    {
        var power_data = [];
        var interval = Math.round(((end - start)*0.001) / 800);
        var power_data = get_feed_data(feedid,start,end,interval,1,1);
        var stats = power_stats(power_data);
        instgraph(power_data);

        $("#out").html("");

        var datetext = "";
        if ((end-start)<3600000*25) {var mdate = new Date(start); datetext = mdate.format("dS mmm yyyy")}

        $("#bot_out").html(datetext+": <?php echo _("Average:"); ?> "+stats['average'].toFixed(0)+"W | "+stats['kwh'].toFixed(2)+" <?php echo _("kWh"); ?> | "+add_currency(stats['kwh']*price, 2));
    }

    //--------------------------------------------------------------
    // Zoom in on bar click
    //--------------------------------------------------------------
    $("#placeholder").bind("plotclick", function (event, pos, item)
    {
        if (item!=null)
        {
            if (view==2) set_inst_view(item.datapoint[0]);

            if (view==1)
            {
                var d = new Date(); d.setTime(item.datapoint[0]);
                days = get_days_month(kwh_data,d.getMonth(),d.getFullYear());
                set_daily_view();
            }

            if (view==0)
            {
                var d = new Date(); d.setTime(item.datapoint[0]);
                months = get_months_year(kwh_data,d.getFullYear());
                set_monthly_view();
            }
        }
    });

    //--------------------------------------------------------------
    // Return button click
    //--------------------------------------------------------------
    $("#return").click(function (){
        if (view==1) set_annual_view();
        if (view==2) set_monthly_view();
        if (view==3) set_daily_view();
    });

    //--------------------------------------------------------------
    // Info label
    //--------------------------------------------------------------
    $("#placeholder").bind("plothover", function (event, pos, item)
    {
        if (item!=null)
        {
            var d = new Date();
            d.setTime(item.datapoint[0]);
            var mdate = new Date(item.datapoint[0]);

            if (view==0) $("#out").html(item.datapoint[1].toFixed(0)+" kWh | "+mdate.format("yyyy")+" | "+(item.datapoint[1]/years.days[item.dataIndex]).toFixed(1)+" kWh/d");
            if (view==1) $("#out").html(item.datapoint[1].toFixed(0)+" kWh | "+mdate.format("mmm yyyy")+" | "+(item.datapoint[1]/months.days[item.dataIndex]).toFixed(1)+" kWh/d ");
            if (view==2) $("#out").html(item.datapoint[1].toFixed(1)+" kWh | "+add_currency(item.datapoint[1]*price, 2)+" | "+add_currency(item.datapoint[1]*price*365, 0)+"/y | "+mdate.format("dS mmm yyyy"));
            if (view==3) $("#out").html(item.datapoint[1].toFixed(0)+" W");
        }
    });


    //--------------------------------------------------------------------------------------
    // Graph zooming
    //--------------------------------------------------------------------------------------
    $("#placeholder").bind("plotselected", function (event, ranges)
    {
         start = ranges.xaxis.from; end = ranges.xaxis.to; vis_feed_data();
    });

    //----------------------------------------------------------------------------------------------
    // Operate buttons
    //----------------------------------------------------------------------------------------------
    $("#zoomout").click(function () {inst_zoomout(); vis_feed_data();});
    $("#zoomin").click(function () {inst_zoomin(); vis_feed_data();});
    $('#right').click(function () {inst_panright(); vis_feed_data();});
    $('#left').click(function () {inst_panleft(); vis_feed_data();});
    $('.graph-time').click(function () {inst_timewindow($(this).attr("time")); vis_feed_data();});
    //-----------------------------------------------------------------------------------------------

  $(window).resize(function(){
    $('#placeholder').width($('#placeholder_bound').width());
    $('#placeholder').height($('#placeholder_bound').height()-120);
    if (embed) $('#placeholder').height($(window).height()-120);
    
    if (view==0) set_annual_view();
    if (view==1) set_monthly_view();
    if (view==2) set_daily_view();
    if (view==3) vis_feed_data();
    //plot();
  });
    
// Graph buttons and navigation efects for mouse and touch
    $("#placeholder").mouseenter(function(){
        if (view==3) {
            $("#graph-navbar").show();
        }
        $("#graph-buttons").stop().fadeIn();
        $("#stats").stop().fadeIn();
    });
    $("#placeholder_bound").mouseleave(function(){
        $("#graph-buttons").stop().fadeOut();
        $("#stats").stop().fadeOut();
    });
    $("#placeholder").bind("touchstarted", function (event, pos)
    {
        $("#graph-navbar").hide();
        $("#graph-buttons").stop().fadeOut();
        $("#stats").stop().fadeOut();
    });
    
    $("#placeholder").bind("touchended", function (event, ranges)
    {
        $("#graph-buttons").stop().fadeIn();
        $("#stats").stop().fadeIn();
        if (view==3) {
            start = ranges.xaxis.from; 
            end = ranges.xaxis.to;
            vis_feed_data();
        }
    });
    
    
</script>
