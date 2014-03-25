<!-- bring in the emoncms path variable which tells this script what the base URL of emoncms is -->
<?php global $path; 

$apikey = get('apikey');

?>

<!-- feed.js is the feed api helper library, it gives us nice functions to use within our program that
calls the feed API on the server via AJAX. -->
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

<!-- defenition of the style/look of the elements on our page (CSS stylesheet) -->
<style>

  .electric-title {
    font-weight:bold; 
    font-size:22px; 
    color:#aaa; 
    padding-top:50px
  }
  
  .power-value {
    font-weight:bold; 
    font-size:100px; 
    color:#0699fa; 
    padding-top:45px;
  }
  
  .kwh-value {
    font-weight:normal; 
    font-size:22px; 
    color:#0699fa; 
    padding-top:45px;
  }
  
</style>

<!-- The three elements: title, power value and kwhd value that makes up our page -->
<!-- margin: 0px auto; max-width:320px; aligns the elements to the middle of the page -->

<div id="config" style="margin: 0px auto; max-width:320px; display:none">
    
    <div class="electric-title">My Electric config</div>
    <br><br>
    
    <p><b style="color:#0699fa">Power feed:</b></p>
    <select id="powerfeed" style="width:290px"></select>
    <br><br>
    
    <p><b style="color:#0699fa">Wh feed:</b></p>
    <select id="kwhfeed" style="width:290px"></select>
    <br><br>
    
    <button id="configsave" class="btn btn-primary">Save</button>
    
</div>

<div id="powerblock">
    <div style="margin: 0px auto; max-width:320px;">
        <div class="electric-title">POWER NOW:</div>
        <div class="power-value"><span id="power"></span>W</div>
        <div class="kwh-value">USE TODAY: <b><span id="kwhd"></span> kWh</b></div>
    </div>
    <br><br>
    <div id="bound" style="width:100%; height:270px">
        <canvas id="myCanvas" ></canvas>
    </div>
</div>

<script>
    // The feed api library requires the emoncms path
    var path = "<?php echo $path; ?>";
    var apikey = "<?php echo $apikey; ?>"; 

    feed.apikey = apikey;

    // Set the background color to dark grey - looks nice on a mobile.
    $("body").css('background-color','#222');

    // Page and canvas widths
    var bound = {};
    bound.width = $("#bound").width();
    bound.height = $("#bound").height();

    $("#myCanvas").attr('width',bound.width);
    $("#myCanvas").attr('height',bound.height);

    $(window).resize(function(){
        bound.width = $("#bound").width();
        bound.height = $("#bound").height();

        $("#myCanvas").attr('width',bound.width);
        $("#myCanvas").attr('height',bound.height); 
        draw();
    });
    
    // Canvas for simple bar chart
    var c=document.getElementById("myCanvas");
    var ctx=c.getContext("2d");

    var timeWindow = (3600000*24*7);	//Initial time window
    var start = +new Date - timeWindow;	//Get start time
    var end = +new Date;				    //Get end time

    var d = new Date()
    var n = d.getTimezoneOffset();
    var offset = n / -60;

    var interval = 3600*24;
    var datastart = (Math.round((start/1000.0)/interval) * interval); //+3600*offset;

    var totalwh = [];

    // used for updating every 5 
    var updateinst = false;

    // Load Feeds used from myelectric table  
    var config = {};
    $.ajax({ url: path+"myelectric/get.json?apikey="+apikey, dataType: 'json', async: false, success: function(data) {config = data;} });

    if (!config) config = {powerfeed:0, kwhfeed:0};
    var powerfeed = parseInt(config.powerfeed); 
    var kwhfeed = parseInt(config.kwhfeed); 

    // If no config then show config interface
    if (powerfeed==0 || kwhfeed==0) 
    {
        // Populate config feed list selectors
        var feeds = feed.list();
        var out = ""; for (z in feeds) out +="<option value="+feeds[z].id+">"+feeds[z].name+"</option>";
        $("#powerfeed").html(out);
        $("#kwhfeed").html(out);

        $("#config").show();
        $("#powerblock").hide();
    } else {
        totalwh = feed.get_average(kwhfeed,datastart*1000,end+(interval*1000),interval);
        update();
        updateinst = setInterval(update,5000);
    }

    function update()
    {
        // Get latest feed values from the server (this returns the equivalent of what you see on the feed/list page)
        feeds = feed.list_by_id();

        // Make a copy of the last 7 days of kwh totals data so that we can calculate today's amount with out always adding a new entry
        var totalwhcopy = eval(JSON.stringify(totalwh));

        // Add today
        if (totalwh.length>0) {
            var lastday = totalwh[totalwh.length-1][0];
            totalwhcopy.push([lastday+24*3600*1000,feeds[kwhfeed]]);
        }

        daily = [];

        for (var z=1; z<totalwhcopy.length; z++)
        {
            daily.push([totalwhcopy[z][0],totalwhcopy[z][1] - totalwhcopy[z-1][1]]);
        }

        // Update the elements on the page with the latest power and energy values.
        $("#power").html(feeds[powerfeed]);
        if (daily.length>0) $("#kwhd").html((daily[daily.length-1][1]/1000.0).toFixed(1));

        draw();
    }


    function draw()
    {
        ctx.clearRect(0,0,bound.width,bound.height);
        ctx.strokeStyle = "#0699fa";
        ctx.fillStyle = "#0699fa";

        // Bar chart axes
        ctx.moveTo(0,0);
        ctx.lineTo(0,bound.height);
        ctx.lineTo(bound.width,bound.height);
        ctx.stroke();

        // Bar chart y-axis label
        ctx.textAlign    = "left";
        ctx.font = "16px arial";
        ctx.fillText('kWh',10,15);
        
        // Bar widths and height scale
        var scale = 0.5;
        var barwidth = (bound.width - 20 - 7*10)/7;
        if (barwidth>50 ) barwidth = 50;

        // Draw each individual bar
        for (var x=0; x<daily.length; x++)
        {
            ctx.fillStyle = "#0699fa";
            ctx.fillRect(10+x*(barwidth+10),(bound.height-(daily[x][1]*0.024)*scale)-10,barwidth,(daily[x][1]*0.024)*scale);

            // Text is too small if less than 2kWh
            if (daily[x][1]>2000) {
                ctx.textAlign    = "center";
                ctx.fillStyle = "#ccccff";
                ctx.fillText((daily[x][1]/1000).toFixed(0),10+x*(barwidth+10)+(barwidth/2),(bound.height-(daily[x][1]*0.024)*scale)+10);
            }
        }
    }

    $("#powerblock").click(function(){
    
        // Load feed list, populate feed selectors and select the selected feed
        var feeds = feed.list();
        var out = ""; for (z in feeds) out +="<option value="+feeds[z].id+">"+feeds[z].name+"</option>";
        $("#powerfeed").html(out);
        $("#powerfeed").val(powerfeed);
        $("#kwhfeed").html(out);
        $("#kwhfeed").val(kwhfeed);

        // Switch to the config interface
        $("#config").show();
        $("#powerblock").hide();
    });

    $("#configsave").click(function(){
    
        powerfeed = $("#powerfeed").val();
        kwhfeed = $("#kwhfeed").val();
        totalwh = feed.get_average(kwhfeed,datastart*1000,end+(interval*1000),interval);
        update();

        // Restart interface update
        if (updateinst) clearInterval(updateinst);
        updateinst = setInterval(update,5000);

        // Switch to main view 
        $("#config").hide();
        $("#powerblock").show();

        // Save config to db
        var config = {powerfeed: powerfeed, kwhfeed: kwhfeed};
        var result = {};
        $.ajax({ url: path+"myelectric/set.json", data: "data="+JSON.stringify(config), async: false, success: function(data){} });
    });
  
</script>
