<html>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<!----------------------------------------------------------------------------------------------------
  
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

-------------------------------------------------------------------------------------->

 <?php
  global $path, $embed;
 ?>
    
 <!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
 <script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.min.js"></script>
 <script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
 <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>

 <!---------------------------------------------------------------------------------------------------
 // Time window buttons
 ---------------------------------------------------------------------------------------------------->
<?php if (!$embed) { ?>
<h2>Realtime data: <?php echo $feedidname; ?></h2>
<?php } ?>

 <div id="graph_bound" style="height:400px; width:100%; position:relative; ">
   <div id="graph" style="position:absolute; top:25px; right:0px;"></div>
   <div style="position:absolute; top:0px; right:0px;">
     <button class="viewWindow" time="1.0">1 hr</button>
     <button class="viewWindow" time="0.50">30 min</button>
     <button class="viewWindow" time="0.25">15 min</button>
   </div>
 </div>

 <script id="source" language="javascript" type="text/javascript">
   //--------------------------------------------------------------------------------------
   var feedid = <?php echo $feedid; ?>;				//Fetch table name
   var feedid2 = <?php echo $feedid2; ?>;  			//Fetch table name
   var path = "<?php echo $path; ?>";
   var apikey = "<?php echo $apikey; ?>";	
   var embed = <?php echo $embed; ?>;
   //----------------------------------------------------------------------------------------
   // These start time and end time set the initial graph view window 
   //----------------------------------------------------------------------------------------
   var timeWindow = (3600000*0.5);				//Initial time window
   var start = ((new Date()).getTime())-timeWindow;		//Get start time
   var end = (new Date()).getTime();				//Get end time

   $('#graph').width($('#graph_bound').width());
   $('#graph').height($('#graph_bound').height());
   if (embed) $('#graph').height($(window).height()-25);

   var data = [];

   loop();
   setInterval ( loop, 2000 );

   function loop()
   {
     start = ((new Date()).getTime())-timeWindow;		//Get start time
     end = (new Date()).getTime();				//Get end time
     vis_feed_data();
   }

  $(window).resize(function(){
    $('#graph').width($('#graph_bound').width());
    if (embed) $('#graph').height($(window).height());
    plot();
  });

   function vis_feed_data()
   {
     data = get_feed_data(feedid,start,end,2);
     data2 = get_feed_data(feedid2,start,end,2);
     plot();
   }
  
   function plot()
   {
     $.plot($("#graph"), 
       //[{data: data, lines: { fill: true }},{data: data2, lines: { fill: false }}],
       [{color: "#c1a81f", data:data}, {color: "#dec225", data:data2}],
       {
       series: { stack: true},
       xaxis: { mode: "time", localTimezone: true},
       //grid: { show: true, hoverable: true, clickable: true },
       selection: { mode: "xy" }
       });
     });
   }

   //----------------------------------------------------------------------------------------------
   // Operate buttons
   //----------------------------------------------------------------------------------------------
   $('.viewWindow').click(function () { timeWindow = (3600000* $(this).attr("time") ); });
   //-----------------------------------------------------------------------------------------------

  </script>

  </body>
</html>  
