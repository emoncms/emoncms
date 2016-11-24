<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/
    global $path, $embed;
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.touch.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/date.format.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.canvas.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/base64.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/canvas2image.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/jquery.flot.saveAsImage.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/daysmonthsyears.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/zoom/view.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/zoom/graphs.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/proc.js"></script>

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
  var path = "<?php echo $path; ?>";
  var apikey = "<?php echo $apikey; ?>";
  var embed = <?php echo $embed; ?>;
  var delta = <?php echo $delta; ?>;
  
  var timeWindow = (3600000*24.0*365*5);   //Initial time window
  var start = +new Date - timeWindow;  //Get start time
  var end = +new Date; 

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
  
  var view = 0;

  // Global instantaneous graph variables
  var feedid = power;

  var price = <?php echo $pricekwh ?>;
  var currency = "<?php echo $currency ?>";
  var currency_after_val = "<?php echo $currency_after_val ?>";

  var bot_kwhd_text = "";

  var d = new Date()
  var n = d.getTimezoneOffset();
  var offset = n / -60;
  start = Math.floor(start / 86400000) * 86400000;
  end = Math.floor(end / 86400000) * 86400000;
  start -= offset * 3600000;
  end -= offset * 3600000;
  get_feed_data_DMY_async(vis_feed_kwh_data_callback,null,kwhd,start,end,"day"); // get 5 years of daily kw_data
  
  //load feed kwh_data
  function vis_feed_kwh_data_callback(context,data){
  
    if (window.delta==1) {
        var tmp = [];
        for (var n=1; n<data.length; n++) {
            tmp.push([data[n-1][0], data[n][1]-data[n-1][1]]);
        }
        data = tmp;
    }
  
    kwh_data = data;
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
  }

  // Rounds value and adds currency after or before the value
  function add_currency(value, decimal_places){
    var val = value.toFixed(decimal_places);

    if (currency_after_val == '1') {
      return val + currency;
    } else {
      return currency + val;
    }
  }

  function vis_feed_data(){
    clearTimeout(event_vis_feed_data); // cancel pending event
    event_vis_feed_data = setTimeout(function(){ vis_feed_data_delayed(); }, 500);
    instgraph(power_data);
    $("#out").html("");
    $("#bot_out").html("Loading...");
  }
  
  function vis_feed_data_delayed(){
    var interval = Math.round(((end - start)*0.001) / 800);
    if (typeof ajaxAsyncXdr !== 'undefined') { 
      ajaxAsyncXdr.abort(); // abort pending requests
      ajaxAsyncXdr=undefined;
    }
    ajaxAsyncXdr=get_feed_data_async(vis_feed_data_callback,null,feedid,start,end,interval,1,1);
  }
  
  //load feed data
  function vis_feed_data_callback(context,data){
    power_data=data;
    var stats = power_stats(power_data);
    instgraph(power_data);

    var datetext = "";
    if ((end-start)<3600000*25) { var mdate = new Date(start); datetext = mdate.format("dd mmm yyyy") + ": "; }
    $("#bot_out").html(datetext+"<?php echo _("Average:"); ?> "+stats['average'].toFixed(0)+"W | "+stats['kwh'].toFixed(2)+" <?php echo _("kWh"); ?> | "+add_currency(stats['kwh']*price, 2));
  }

  // Zoom in on bar click
  $("#placeholder").bind("plotclick", function (event, pos, item){
    if (item!=null){
      if (view==2) set_inst_view(item.datapoint[0]);

      if (view==1) {
        var d = new Date(); d.setTime(item.datapoint[0]);
        days = get_days_month(kwh_data,d.getMonth(),d.getFullYear());
        set_daily_view();
      }
      if (view==0) {
        var d = new Date(); d.setTime(item.datapoint[0]);
        months = get_months_year(kwh_data,d.getFullYear());
        set_monthly_view();
      }
    }
  });

  // Return button click
  $("#return").click(function (){
    if (view==1) set_annual_view();
    if (view==2) set_monthly_view();
    if (view==3) set_daily_view();
  });

  // Info label
  $("#placeholder").bind("plothover", function (event, pos, item){
    if (item!=null) {
      var d = new Date();
      d.setTime(item.datapoint[0]);
      var mdate = new Date(item.datapoint[0]);
      if (view==0) $("#out").html(" : "+mdate.format("yyyy")+" | "+item.datapoint[1].toFixed(0)+" kWh | "+(item.datapoint[1]/years.days[item.dataIndex]).toFixed(1)+" kWh/d");
      if (view==1) $("#out").html(" : "+mdate.format("mmm yyyy")+" | "+item.datapoint[1].toFixed(0)+" kWh | "+(item.datapoint[1]/months.days[item.dataIndex]).toFixed(1)+" kWh/d ");
      if (view==2) $("#out").html(" : "+mdate.format("dd mmm yyyy")+" | "+item.datapoint[1].toFixed(1)+" kWh | "+add_currency(item.datapoint[1]*price, 2)+" | "+add_currency(item.datapoint[1]*price*365, 0)+"/y");
      if (view==3) $("#out").html(" : "+item.datapoint[1].toFixed(0)+" W");
    }
  });


  // Graph zooming
  $("#placeholder").bind("plotselected", function (event, ranges){
     start = ranges.xaxis.from; end = ranges.xaxis.to; vis_feed_data();
  });

  // Operate buttons
  $("#zoomout").click(function () {inst_zoomout(); vis_feed_data();});
  $("#zoomin").click(function () {inst_zoomin(); vis_feed_data();});
  $('#right').click(function () {inst_panright(); vis_feed_data();});
  $('#left').click(function () {inst_panleft(); vis_feed_data();});
  $('.graph-time').click(function () {inst_timewindow($(this).attr("time")); vis_feed_data();});

  $(window).resize(function(){
    $('#placeholder').width($('#placeholder_bound').width());
    $('#placeholder').height($('#placeholder_bound').height()-80);
    if (embed) $('#placeholder').height($(window).height()-80);
    if (view==0) set_annual_view();
    if (view==1) set_monthly_view();
    if (view==2) set_daily_view();
    if (view==3) vis_feed_data();
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
  $("#placeholder").bind("touchstarted", function (event, pos){
    $("#graph-navbar").hide();
    $("#graph-buttons").stop().fadeOut();
    $("#stats").stop().fadeOut();
  });
  $("#placeholder").bind("touchended", function (event, ranges){
    $("#graph-buttons").stop().fadeIn();
    $("#stats").stop().fadeIn();
    if (view==3) {
      start = ranges.xaxis.from; 
      end = ranges.xaxis.to;
      vis_feed_data();
    }
  });
</script>
