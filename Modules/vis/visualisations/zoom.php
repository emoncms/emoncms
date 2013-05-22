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
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/date.format.js"></script>
 <script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/daysmonthsyears.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/zoom/view.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/zoom/graphs.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/proc.js"></script>

<?php if (!$embed) { ?>
<h2>kWh/d Zoomer</h2>
<?php } ?>

    <div id="test" style="height:500px; width:100%; position:relative; ">
      <div id="placeholder" style="font-family: arial; position:absolute; top: 40px; left:60px;"></div>

      <div style="position:absolute; top:10px; left:65px; font-size:18px;"><b><span id="out2"></span></b>: Hover on bar for info, press to zoom in</div>
      <h2 style="position:absolute; top:40px; left:80px;"><span id="out"></span></h2>
      <p id="bot_out" style="position:absolute; bottom:-10px; left:65px; font-size:18px; font-weight:bold;"></p>

      <b><p style="position:absolute; top: 200px; left:0px;"><span id="axislabely"></span></p>
     <p style="position:absolute; bottom: 40px; left:450px;">Date / Time</p></b>

   <div id="return_ctr" style="position:absolute; top:0px; right:10px;">
        <input id="return" type="button" value="Back" style="font-size:18px; height:40px;"/>
   </div>

      <div id="inst-buttons" style="position:absolute; top:55px; right:20px;">
        <input class="time" type="button" value="D" time="1"/>
        <input class="time" type="button" value="W" time="7"/>
        <input class="time" type="button" value="M" time="30"/>
        <input class="time" type="button" value="Y" time="365"/> | 
        <input id="zoomin" type="button" value="+"/>
        <input id="zoomout" type="button" value="-"/>
        <input id="left" type="button" value="<"/>
        <input id="right" type="button" value=">"/>
      </div>

    </div>

    <script id="source" language="javascript" type="text/javascript">  
      var kwhd = <?php echo $kwhd; ?>;   
      var power = <?php echo $power; ?>;    
      var path = "<?php echo $path; ?>";  
      var apikey = "<?php echo $apikey; ?>";  
      var embed = <?php echo $embed; ?>;
	  
      $('#placeholder').width($('#test').width()-60);
      $('#placeholder').height($('#test').height()-120);
    //  if (embed) $('#graph').height($(window).height());
      $('#inst-buttons').hide();

      var data = [];
      var days = [];
      var months = [];
      var years = [];

      var view = 0;

      // Global instantaneous graph variables
      var start, end;
      var feedid = power;

      var price = <?php echo $pricekwh ?>;
      var currency = "<?php echo $currency ?>";
	  var currency_after_val = "<?php echo $currency_after_val ?>";

      var bot_kwhd_text = "";

      var kwh_data = get_feed_data(kwhd,0,0,0);
      
      var total = 0, ndays=0;
      for (z in kwh_data) {
        total += parseFloat(kwh_data[z][1]); ndays++;
      }

      bot_kwhd_text = "Total: "+(total).toFixed(0)+" kWh : "+add_currency((total*price), 0)+" | Average: "+(total/ndays).toFixed(1)+" kWh : "+add_currency((total/ndays)*price, 2)+" | "+add_currency((total/ndays)*price*7, 0)+" a week, "+add_currency((total/ndays)*price*365, 0)+" a year | Unit price: "+add_currency(price, 2);

      years = get_years(kwh_data);
      //set_annual_view();

      months = get_months_year(kwh_data,2012);
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
	    var power_data = get_feed_data(feedid,start,end,500);
	    var stats = power_stats(power_data);
	    instgraph(power_data);

	    $("#out").html("");

	    var datetext = "";
	    if ((end-start)<3600000*25) {var mdate = new Date(start); datetext = mdate.format("dS mmm yyyy")}
            
	    $("#bot_out").html(datetext+": Average: "+stats['average'].toFixed(0)+"W | "+stats['kwh'].toFixed(2)+" kWh | "+add_currency(stats['kwh']*price, 2));
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
  $('.time').click(function () {inst_timewindow($(this).attr("time")); vis_feed_data();});
  //-----------------------------------------------------------------------------------------------

</script>
