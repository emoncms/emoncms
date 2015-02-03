<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
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
        <script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
        <script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>
        <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
    </head>
    <body>
    
    <!---------------------------------------------------------------------------------------------------
    // Time window buttons
    ---------------------------------------------------------------------------------------------------->
    <?php if (!$embed) { ?>
    <h2><?php echo _("Realtime data:"); ?> <?php echo $feedidname; ?></h2>
    <?php } ?>

    <div id="graph_bound" style="height:400px; width:100%; position:relative; ">
     <div id="graph"></div>
     <div style="position:absolute; top:20px; right:20px;  opacity:0.5;">
       <button class="viewWindow" time="3600">1 <?php echo _('hour') ?></button>
       <button class="viewWindow" time="1800">30 <?php echo _('min') ?></button>
       <button class="viewWindow" time="900">15 <?php echo _('min') ?></button>
       <button class="viewWindow" time="300">5 <?php echo _('min') ?></button>
       <button class="viewWindow" time="60">1 <?php echo _('min') ?></button>
     </div>
    </div>

    <script id="source" language="javascript" type="text/javascript">
    //--------------------------------------------------------------------------------------
    var feedid = <?php echo $feedid; ?>;				//Fetch table name
    var path = "<?php echo $path; ?>";
    var apikey = "<?php echo $apikey; ?>";	
    var embed = <?php echo $embed; ?>;
    //----------------------------------------------------------------------------------------
    // These start time and end time set the initial graph view window 
    //----------------------------------------------------------------------------------------
    var timeWindow = (3600000*0.1);				//Initial time window
    var start = ((new Date()).getTime())-timeWindow;		//Get start time
    var end = (new Date()).getTime();				//Get end time

    var graph_bound = $('#graph_bound'),
       graph = $("#graph");

    graph.width(graph_bound.width()).height(graph_bound.height());
    if (embed) graph.height($(window).height());

    var data = [];

    $.ajax({                                      
      url: path+'feed/data.json',                         
      data: "&apikey="+apikey+"&id="+feedid+"&start="+(start-20)+"&end="+(end+20)+"&dp="+1000,
      dataType: 'json',
      async: false,                      
      success: function(data_in) { data = data_in; } 
    });   
   
    setInterval(fast,250);
    
    setInterval(getdp,5000);

    function fast()
    {
      start = +new Date-timeWindow;		//Get start time
      end = +new Date;   				//Get end time
      plot();
    }

    $(window).resize(function(){
      graph.width(graph_bound.width());
      if (embed) graph.height($(window).height());
      plot();
    });
    
    function getdp()
    {
     var result = {};
      $.ajax({ url: path+"feed/timevalue.json", data: "id="+feedid, dataType: 'json', async: false, success: function(datain) {result = datain;} });

      var timestamp = new Date;
      
      if (data[data.length-1][0]!=result.time*1000) {
        data.push([result.time*1000,parseFloat(result.value)]);
      }
            
      if (data[1][0]<(start-20)) data.splice(0, 1);
	  data.sort();
    }
  
    function plot()
    {
      $.plot(graph,[{data: data, lines: { fill: true }}],
      {
        xaxis: { mode: "time", timezone: "browser", min: start, max: end },
        selection: { mode: "xy" }
      });
    }

    //----------------------------------------------------------------------------------------------
    // Operate buttons
    //----------------------------------------------------------------------------------------------
    $('.viewWindow').click(function () { 
      timeWindow = (1000 * $(this).attr("time") ); 
      
      start = end-timeWindow;		//Get start time
      
      $.ajax({                                      
        url: path+'feed/data.json',                         
        data: "&apikey="+apikey+"&id="+feedid+"&start="+(start-20)+"&end="+(end+20)+"&dp="+1000,
        dataType: 'json',
        async: false,                      
        success: function(data_in) { data = data_in; } 
      }); 
    });
    //-----------------------------------------------------------------------------------------------

    </script>

  </body>
</html>  
