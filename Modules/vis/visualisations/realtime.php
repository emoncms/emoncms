 <?php
/*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

   Emoncms - open source energy visualisation
   Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/
    global $path, $embed;
 ?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.time.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.canvas.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/base64.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/canvas2image.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/jquery.flot.saveAsImage.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/vis.helper.js"></script>
    
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
    var feedid = <?php echo $feedid; ?>;                //Fetch table name
    var path = "<?php echo $path; ?>";
    var apikey = "<?php echo $apikey; ?>";  
    var embed = <?php echo $embed; ?>;
    var data = [];
    var timerget;
    var fast_update_fps = 10;

    var plotColour = urlParams.colour;
    if (plotColour==undefined || plotColour=='') plotColour = "EDC240";
    if (plotColour.indexOf("#") == -1) {
        plotColour = "#" + plotColour;
    }

    var initzoom = urlParams.initzoom;
    if (initzoom==undefined || initzoom=='' || initzoom < 1) initzoom = '15'; // Initial zoom default to 15 mins

    var timeWindow = (60*1000*initzoom);        //Initial time window

    var graph_bound = $('#graph_bound'),
    graph = $("#graph");
    graph.width(graph_bound.width()).height(graph_bound.height());
    if (embed) graph.height($(window).height());

    var now = (new Date()).getTime();
    var start = now-timeWindow;        // start time
    var end = now;                     // end time
    var interval = parseInt(((end*0.001+10) - (start*0.001-10)) / 800);
    data = get_feed_data(feedid,(start-10000),(end+10000),interval,1,1);
    
    timerget = setInterval(getdp,7500);
    gpu_fast();
    //setInterval(fast,150);
    
    // GPU friendly fast update loop
    function gpu_fast() { 
      setTimeout( 
       function() {
          window.requestAnimationFrame(gpu_fast);
          fast();
        }
      , 1000/fast_update_fps);
    };

    function fast() {
      var now = (new Date()).getTime();
      start = now-timeWindow;     // start time
      end = now;                  // end time
      plot();
    }

    $(window).resize(function(){
      graph.width(graph_bound.width());
      if (embed) graph.height($(window).height());
      window.requestAnimationFrame(plot);
    });

    function getdp(){
      $.ajax({ url: 
        path+"feed/timevalue.json", 
        data: "id="+feedid, 
        dataType: 'json', 
        async: true, 
        success: function(result) {
          if (data.length==0 || data[data.length-1][0]!=result.time*1000) {
            data.push([result.time*1000,parseFloat(result.value)]);
          }
          if (data.length>0 && data[1][0]<(start)) data.splice(0, 1);
          data.sort();
        }
     });
    }

    function plot(){
      $.plot(graph,[{data: data, color: plotColour}],
      {
        canvas: true,
        lines: { fill: true },
        series: { shadowSize: 0 },
        xaxis: { tickLength:10, mode: "time", timezone: "browser", min: start, max: end }
      });
    }

    // Operate buttons
    $('.viewWindow').click(function () { 
      timeWindow = (1000 * $(this).attr("time") ); 
      start = end-timeWindow;            //Get start time

      var rate = 0;
      if (timeWindow > 300*1000){ // > 5m
        rate = timeWindow/120; 
        fast_update_fps = 10;
      } else { 
        rate = timeWindow/60;
        fast_update_fps = 20;
      }
      if (rate < 1800) rate = 1800; // limit max rate
      clearInterval(timerget);
      timerget = setInterval(getdp,rate); // change refresh rate
      console.log("realtime timewindow " +timeWindow/1000 + "s get rate "+rate/1000 + "s");

      interval = parseInt(((end*0.001+10) - (start*0.001-10)) / 800);
      data = get_feed_data(feedid,(start-10000),(end+10000),interval,1,1);
    });
    </script>
