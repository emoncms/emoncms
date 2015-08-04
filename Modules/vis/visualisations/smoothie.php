<?php
// Thanks to Shervin for contributing this visualisation, see forum thread here: http://openenergymonitor.org/emon/node/600
    global $path, $embed;
    $feedid2 =null;
?>

<style type="text/css">
  html, body { background-color:#000000;}
  bar { height: 32px; background: red; }
</style>

<script type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/smoothie/smoothie.js"></script>

<?php if (!$embed) { ?>
<h2 style="color:#888" ><?php echo _("Smoothie"); ?></h2>
<div style="width:100%; height:400px;">
<?php } ?>

<canvas id="mycanvas" style="width: 100%; height: 100%;"></canvas>
<?php if (!$embed) { ?> </div> <?php } ?>

<script id="source" language="javascript" type="text/javascript">
  var feedid = <?php echo $feedid; ?>;
  var path = "<?php echo $path; ?>";
  var apikey = "<?php echo $apikey; ?>";
  var ufac = "<?php echo $ufac; ?>";
  var feedid2 = "<?php echo $feedid2; ?>";

  var smoothie = new SmoothieChart( {fps:30}  );
  smoothie.streamTo(document.getElementById("mycanvas"), ufac);

  var now = new Date().getTime();
  var start = now - 10000;
  var end = now;

  var line1 = new TimeSeries();

  if (feedid2 != "") var line2 = new TimeSeries();

  // Used to filter out repeated data (Might be bad)
  var old = 0;
  var old1  = 0;

  var canvas = document.getElementById('mycanvas'),
  context = canvas.getContext('2d');
  window.addEventListener('resize', resizeCanvas, false);

  function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  }
  resizeCanvas();


  doSome();
  setInterval ( doSome, 2000 );

  function doSome(){
    var now = new Date().getTime();
    start = now - 10000;
    end = now;

    vis_feed_data(apikey,feedid,start,end,line1,0);
    if (feedid2 != "") vis_feed_data(apikey,feedid2,start,end,line2,1);
  }

  function vis_feed_data(apikey,feedid,start,end,line,oldref){
    $.ajax({
    url: path+'feed/data.json',
    data: "&apikey="+apikey+"&id="+feedid+"&start="+start+"&end="+end+"&dp=0",
    dataType: 'json',
    async: true, 
    success: function(data)
    {
      var prev;
      if (oldref == 0)
      prev = old;
      else
      prev = old1;

      if (data[1] != undefined && data[1][1] != prev)
      {
      line.append(new Date().getTime(), data[1][1]);
      if (oldref == 0)
        old = data[1][1];
      else
        old1 = data[1][1];
      }
    }
    });
  }

  smoothie.addTimeSeries(line1,
  { strokeStyle:'rgb(0, 255, 0)',
    fillStyle:'rgba(0, 255, 0, 0.4)', lineWidth:3 });

  if (feedid2 != ""){
    smoothie.addTimeSeries(line2,
    { strokeStyle:'rgb(255, 0, 0)',
      fillStyle:'rgba(255, 0, 0, 0.4)', lineWidth:3 });
  }

</script>
