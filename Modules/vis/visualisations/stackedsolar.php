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
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.stack.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.time.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/date.format.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.canvas.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/base64.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/lib/canvas2image.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/plugin/saveAsImage/jquery.flot.saveAsImage.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/api.js"></script>
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
  var kwhdA = <?php echo $solar; ?>;
  var kwhdB = <?php echo $consumption; ?>;
  var delta = <?php echo $delta; ?>;
  
  var path = "<?php echo $path; ?>";
  var apikey = "<?php echo $apikey?>";

  var timeWindow = (3600000*24.0*365*5);   //Initial time window
  var start = +new Date - timeWindow;  //Get start time
  var end = +new Date; 
  
  var d = new Date()
  var n = d.getTimezoneOffset();
  var offset = n / -60;
  start = Math.floor(start / 86400000) * 86400000;
  end = Math.floor(end / 86400000) * 86400000;
  start -= offset * 3600000;
  end -= offset * 3600000;
  
  var dataA = get_feed_data_DMY(kwhdA,start,end,"daily");
  var dataB = get_feed_data_DMY(kwhdB,start,end,"daily");

  if (delta==1) {
      var tmpA = [];
      var tmpB = [];
      for (var n=1; n<dataA.length; n++) {
          tmpA.push([dataA[n-1][0], dataA[n][1]-dataA[n-1][1]]);
          tmpB.push([dataB[n-1][0], dataB[n][1]-dataB[n-1][1]]);
      }
      dataA = tmpA;
      dataB = tmpB;
  }

  var dataC = [];

  for (z in dataA) {
    dataC[z]=[dataA[z][0],0];
    var a=0, b = 0;
    if (dataB[z]==undefined) {dataB[z] = [dataA[z][0],0];}
    if (dataA[z]==undefined) {dataA[z] = [dataB[z][0],0];}
    dataB[z][1] = dataB[z][1] - dataA[z][1];
    if (dataB[z][1]<0) {dataC[z][1] = dataB[z][1]*-1; dataB[z][1] = 0;}
  }

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
  var daysC = [];
  var monthsC = [];

  monthsA = get_months(dataA);
  monthsB = get_months(dataB);
  monthsC = get_months(dataC);

  $(window).resize(function(){
    $('#graph').width($('#graph_bound').width());
    $('#graph').height($('#graph_bound').height());
    if (embed) $('#graph').height($(window).height());
    bargraph(monthsA.data,monthsB.data,monthsC.data,3600*24*20,"month");
  });

  bargraph(monthsA.data,monthsB.data,monthsC.data,3600*24*20,"month");

  $("#graph").bind("plotclick", function (event, pos, item){
    if (item!=null) {
      if (view==1){  }
      if (view==0){
        var d = new Date();
        d.setTime(item.datapoint[0]);
        daysA = get_days_month(dataA,d.getMonth(),d.getFullYear());
        daysB = get_days_month(dataB,d.getMonth(),d.getFullYear());
        daysC = get_days_month(dataC,d.getMonth(),d.getFullYear());
        bargraph(daysA,daysB,daysC,3600*22,"day");
        view = 1;
        $("#out").html("");
      }
    } else {
      if (view==1) { $("#out").html(""); view = 0; bargraph(monthsA.data,monthsB.data,monthsC.data,3600*24*20,"month"); }
      if (view==2) { $("#out").html(""); view = 1; bargraph(daysA,daysB,daysC,3600*22,"day"); }
    }
  });

  $("#graph").bind("plothover", function (event, pos, item){
    if (item!=null){
      var d = new Date();
      d.setTime(item.datapoint[0]);
      var mdate = new Date(item.datapoint[0]);

      var type = "", value = 0;
      if (item.seriesIndex == 0 && view==0) {type = "Solar"; value = monthsA.data[item.dataIndex][1];};
      if (item.seriesIndex == 1 && view==0) {type = "Import"; value = monthsB.data[item.dataIndex][1];};
      if (item.seriesIndex == 2 && view==0) {type = "Export"; value = monthsC.data[item.dataIndex][1];};

      if (item.seriesIndex == 0 && view==1) {type = "Solar"; value = 1*daysA[item.dataIndex][1];};
      if (item.seriesIndex == 1 && view==1) {type = "Import"; value = 1*daysB[item.dataIndex][1];};
      if (item.seriesIndex == 2 && view==1) {type = "Export"; value = 1*daysC[item.dataIndex][1];};

      if (view==0) $("#out").html(type+' '+value.toFixed(1)+" kWh/d | "+mdate.format("mmm yyyy"));
      if (view==1) $("#out").html(type+' '+value.toFixed(1)+" kWh/d | "+mdate.format("dS mmm yyyy"));
    }
  });

  function bargraph(dataA,dataB,dataC,barwidth, mode){
    $.plot($("#graph"), [ {color: "#e0c21f", data:dataA}, {color: "#4e9acf", data:dataB}, {color: "#AA96ff", data:dataC}], {
      canvas: true,
      series: {
        stack: true,
        bars: { show: true,align: "center",barWidth: (barwidth*1000),fill: true }
      },
      grid: { show: true, hoverable: true, clickable: true },
      xaxis: { mode: "time", timezone: "browser", minTickSize: [1, mode], tickLength: 1 }
    });
  }
</script>
