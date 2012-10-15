/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Global page vars definition
var feedids = [];
// Array that holds ID's of feeds of associative key
var assoc = [];
// Array for exact values
var assoc_curve = [];
// Array for smooth change values - creation of smooth dial widget

var widgetcanvas = {};

var firstdraw = 1;

var dialrate = 0.02;
var browserVersion = 999;

var Browser =
{
  Version : function()
  {
    var version = 999;
    if (navigator.appVersion.indexOf("MSIE") != -1)
      version = parseFloat(navigator.appVersion.split("MSIE")[1]);
    return version;
  }
}

function show_dashboard()
{
  update(apikey_read);
  slow_update(apikey_read);
}


function onetime(apikey_read)
{
  $('.rawdata,.bargraph,.zoom,.realtime,.simplezoom,.threshold,.orderthreshold,.orderbars,.stacked,.multigraph,.histgraph,.smoothie').each(function(index)
  {

    var id = $(this).attr("id");
    var feed = $(this).attr("feed") || 0;
    var width = $(this).width();
    var height = $(this).height();

    var attrstring = "";
    var target = $(this).get(0);
    var l = target.attributes.length
    for (var i=0; i<l; i++)
    {
      var attr = target.attributes[i].name;
      if (attr!="id" && attr!="class" && attr!="style")
      {
        console.log(attr);
        attrstring += "&"+attr+"="+target.attributes[i].value;
      }
    }

    if (!$(this).html() || reloadiframe==id){
      $(this).html('<iframe style="width:'+width+'px; height:'+height+'px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+path+'vis/'+$(this).attr("class")+'?apikey='+apikey_read+'&embed=1'+attrstring+'"></iframe>');
    }

    var iframe = $(this).children('iframe');
    iframe.width(width);
    iframe.height(height);

  });

  reloadiframe = 0;
}

// update function
function update(apikey_read)
{
  browserVersion = Browser.Version();
  if (browserVersion < 9)
    dialrate = 0.2;

  $.ajax(
  {
    url : path + "feed/list.json?apikey=" + apikey_read,
    dataType : 'json',
    success : function(data)
    { 
      for (z in data)
      {
        var newstr = data[z]['name'].replace(/\s/g, '-');
        var value = parseFloat(data[z]['value']);
        
        if (value < 100)
          value = value.toFixed(1);
        else
          value = value.toFixed(0);

        $("." + newstr).html(value);
        assoc[newstr] = value * 1;
        feedids[newstr] = data[z]['id'];
      }

      // Calls specific page javascript update function for any in page javascript
      if ( typeof page_js_update == 'function')
      {
        page_js_update(assoc);
      }
      //--------------------------------------------------------------------------

    } // End of data return function
  });
  // End of AJAX function

  $('.cylinder').each(function(index)
  {
    var cyl_top = assoc[$(this).attr("topfeed")]*1;
    var cyl_bot = assoc[$(this).attr("botfeed")]*1;

    var id = "can-"+$(this).attr("id");
    draw_cylinder(widgetcanvas[id],cyl_bot,cyl_top,$(this).width(),$(this).height());
  });

  $('.led').each(function(index)
  {
    var feed = $(this).attr("feed");
    var val = assoc[feed];
    var id = "can-"+$(this).attr("id");
    if (browserVersion < 9)
      draw_led_ie8(widgetcanvas[id], val);
    else
      draw_led(widgetcanvas[id], val);
  });

  $('.feedvalue').each(function(index)
  {
    var feed = $(this).attr("feedname");
    var units = $(this).attr("units");
    var val = assoc[feed];

    if (feed==undefined) val = 0;
    if (units==undefined) units = '';

    $(this).html(val+units);
  });
}

function fast_update(apikey_read)
{
  if (redraw)
  { 
    setup_widget_canvas();
    update(apikey_read);
    onetime(apikey_read);
  }
  draw_dials();
  //draw_leds();
  redraw = 0;
}

function slow_update()
{
}



function draw_dials()
{

  $('.dial').each(function(index)
  {
    var feed = $(this).attr("feed");
    var val = curve_value(feed,dialrate);
    // ONLY UPDATE ON CHANGE
    if ((val * 1).toFixed(1) != (assoc[feed] * 1).toFixed(1) || redraw == 1)
    {
      var id = "can-"+$(this).attr("id");
      var scale = 1*$(this).attr("scale") || 1;
      draw_gauge(widgetcanvas[id],0,0,$(this).width(),$(this).height(),val*scale, $(this).attr("max"), $(this).attr("units"),$(this).attr("type"));
    }
  });
 
}




function curve_value(feed,rate)
{
  var val = 0;
  if (feed) {
    if (!assoc_curve[feed]) assoc_curve[feed] = 0;
    assoc_curve[feed] = assoc_curve[feed] + ((parseFloat(assoc[feed]) - assoc_curve[feed]) * rate);
    val = assoc_curve[feed] * 1;
  }
  return val;
}

function setup_widget_canvas()
{
  $('.dial,.cylinder,.led').each(function(index)
  {
    var widgetId = $(this).attr("id");

    var width = $(this).width();
    var height = $(this).height();
    var canvas = $(this).children('canvas');

    var canvasid = "can-"+widgetId;
    // 1) Create canvas if it does not exist
    if (!canvas[0])
    {
      $(this).html('<canvas id="'+canvasid+'"></canvas>');
    }

    // 2) Resize canvas if it needs resizing
    if (canvas.attr("width") != width) canvas.attr("width", width);
    if (canvas.attr("height") != height) canvas.attr("height", height);

    var canvas = document.getElementById(canvasid);
    if (browserVersion != 999)
    {
      canvas.setAttribute('width', width);
      canvas.setAttribute('height', height);
      if ( typeof G_vmlCanvasManager != "undefined") G_vmlCanvasManager.initElement(canvas);
    }
    // 3) Get and store the canvas context
    widgetcanvas[canvasid] = canvas.getContext("2d");
  });
}
