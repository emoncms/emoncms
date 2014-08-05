/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

  render.js goes through all the dashboard html elements that specify the dashboard widgets
  and inserts the dials, visualisations to be displayed inside the element.
  see designer.js for more information on the html element widget box model.

  render.js calls the render scripts of all the widgets which is where all the 
  individual widget render code is located.
*/

// Global page vars definition
var SteelseriesObjects = [];
// Array that holds ID's of feeds of associative key
var feedids = [];
// Array for exact values
var assoc = [];
// Array for smooth change values - creation of smooth dial widget
var assoc_curve = [];
var widgetcanvas = {};

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
  browserVersion = Browser.Version();
  if (browserVersion < 9) dialrate = 0.2;

  for (z in widget)
  {
    var fname = widget[z]+"_init";
    var fn = window[fname];
    fn();
  }

  update();
}

// update function
function update()
{
  var query = path + "feed/list.json?userid="+userid;
  $.ajax(
  {
    url : query,
    dataType : 'json',
    success : function(data)
    { 

      for (z in data)
      {
        var newstr = data[z]['name'].replace(/\s/g, '-');
        var value = parseFloat(data[z]['value']);
        $("." + newstr).html(value);
        assoc[newstr] = value * 1;
        feedids[newstr] = data[z]['id'];
      }

      for (z in widget)
      {
        var fname = widget[z]+"_slowupdate";
        var fn = window[fname];
        fn();
      }
    }
  });
}

function fast_update()
{
  if (redraw)
  { 
    for (z in widget)
    {
      var fname = widget[z]+"_init";
      var fn = window[fname];
      fn();
    }

  }

  for (z in widget)
  {
    var fname = widget[z]+"_fastupdate";
    var fn = window[fname];
    fn();
  }
    redraw = 0;
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

function setup_widget_canvas(elementclass)
{
  $('.'+elementclass).each(function(index)
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
//TODO
// Values, render only on change
//MIN MAX VALUES
//Single JS load for steelseries.js
//linear scale lock?

function setup_steelseries_object(elementclass)
{
	$('.'+elementclass).each(function(index)
  {
	var id = "can-"+$(this).attr("id"); //Canvas
	
	var title =$(this).attr("title");
	if (type == undefined){type = ""}
	
	var MinValue = new Number($(this).attr("MinValue"));
	if (MinValue == ""){MinValue = 0}
	
	var MaxValue = new Number($(this).attr("MaxValue"));
	if (MaxValue == ""){MaxValue = 100}

	var units =$(this).attr("unit");
	if (units == undefined){type = ""}
	
	var threshold = $(this).attr("threshold");
	if (threshold == undefined){threshold = 80}
	
	var type = $(this).attr("type");
	if (type == undefined){type = "TYPE4"}
		//set section colours :D
    var sections = [	steelseries.Section(0, 25, 'rgba(0, 0, 220, 0.3)'),
						steelseries.Section(25, 50, 'rgba(0, 220, 0, 0.3)'),
						steelseries.Section(50, 75, 'rgba(220, 220, 0, 0.3)') ];

	// Define one area colour :P
    var areas = Array(steelseries.Section(75, 100, 'rgba(220, 0, 0, 0.3)'));
	
	if (elementclass=="SSRadial"){
	//Checks Selection: "Radial","RadialBargraph","RadialVertical"
	var radialtype = $(this).attr("radialtype");
	if (radialtype == undefined){radialtype="Radial"};
	
		SteelseriesObjects[$(this).attr("id")] = new steelseries[radialtype](id, {
			gaugeType: steelseries.GaugeType[type],
				section: sections,
				size: $(this).width(),
				digitalFont: true,
				area: areas,
				titleString: title,
				unitString: units,
				threshold: threshold,
				lcdVisible: true,
				//fullScaleDeflectionTime: 0.5
				//minValue: $(this).attr("minvalue"),
				//maxValue:$(this).attr("maxvalue"),
				});
			
			//Pointer Exception Handle
			if (radialtype=="RadialBargraph"){
			
				var pointercolour = $(this).attr("pointercolour");
				if (pointercolour == undefined){pointercolour = "RED";}
				SteelseriesObjects[$(this).attr("id")].setValueColor(steelseries.ColorDef[pointercolour]);	
			}
			else {
				
				var PointerType = $(this).attr("pointertype");
				if (PointerType == undefined){PointerType = "TYPE1";}
				SteelseriesObjects[$(this).attr("id")].setPointerType(steelseries.PointerType[PointerType]);
				
				var pointercolour = $(this).attr("pointercolour");
				if (pointercolour == undefined){pointercolour = "RED";}
				SteelseriesObjects[$(this).attr("id")].setPointerColor(steelseries.ColorDef[pointercolour]);

				var ForegroundType = $(this).attr("ForegroundType");
				if (ForegroundType == undefined){ForegroundType = "TYPE1";}
				SteelseriesObjects[$(this).attr("id")].setForegroundType(steelseries.ForegroundType[ForegroundType]);
			}
			//End Exception Handle
			
			if (radialtype=="RadialVertical"){
				//Skip LCD Colour
				}
			else{	
				var LcdColor = $(this).attr("LcdColor");
				if (LcdColor == undefined){LcdColor = "STANDARD";}
				SteelseriesObjects[$(this).attr("id")].setLcdColor(steelseries.LcdColor[LcdColor]);
			}
	}
	
	//Start of SingleDispaly Object Init
	else if (elementclass=="SSSingleDisplay"){
	
	var unitstringbool=true;
	var unitstring = $(this).attr("unit");
	if (unitstring == undefined||unitstring ==""){unitstring = "";unitstringbool=false}
	
	var headerStringbool=true;
	var headerString = $(this).attr("headerString");
	if (headerString == undefined||headerString==""){headerString = "";headerStringbool=false}
	
	
	//decimal checks?
	var lcdDecimals = $(this).attr("lcdDecimals");
	if (lcdDecimals == undefined || lcdDecimals == ""||lcdDecimals>10||lcdDecimals<0){lcdDecimals = 2;}
	
	
	 SteelseriesObjects[$(this).attr("id")] = new steelseries.DisplaySingle(id, {
                            width: $(this).width(),
                            unitStringVisible: 	unitstringbool,
                            unitString: 		unitstring,
							headerString: 		headerString,
                            headerStringVisible:headerStringbool,
                            valuesNumeric: 		true,
                            digitalFont: 		true,
							lcdDecimals: 		lcdDecimals
                            });
	}
	//End of SingleDisplay Object Init
	
	//Start of MultiDisplay Object Init
	else if (elementclass=="SSMultiDisplay"){
		
		var unitstringbool=true;
		var unitstring = $(this).attr("unitString");
		if (unitstring == undefined||unitstring ==""){unitstring = "";unitstringbool=false}
		
		var headerStringbool=true;
		var headerString = $(this).attr("headerString");
		if (headerString == undefined||headerString==""){headerString = "";headerStringbool=false}
		
		var detailStringbool=true;
		var detailString = $(this).attr("detailString");
		if (detailString == undefined ||detailString ==""){detailString = "";detailStringbool=false}
		
		
		//decimal checks?
		var lcdDecimals = $(this).attr("lcdDecimals");
		if (lcdDecimals == undefined || lcdDecimals == ""||lcdDecimals>10||lcdDecimals<0){lcdDecimals = 2;}
	
	 SteelseriesObjects[$(this).attr("id")] = new steelseries.DisplayMulti(id, {
                            width: $(this).width(),
                            unitStringVisible: 	unitstringbool,
                            unitString: 		unitstring,
							headerString: 		headerString,
                            headerStringVisible:headerStringbool,
                            detailString: 		detailString,
                            detailStringVisible:detailStringbool,
                            valuesNumeric: 		true,
                            digitalFont: 		true,
							linkAltValue: 		false,
							lcdDecimals:		lcdDecimals
                            });
		
	
	}
	//End of MultiDisplay Object Init
	
	//Start of SSLinear Object Init
	else if (elementclass=="SSLinear"){
	
	var LinearTypeSelector = $(this).attr("LinearType");
	if (LinearTypeSelector == undefined){LinearTypeSelector = "Linear";}
	
	if (LinearTypeSelector=="Linear"){
	 SteelseriesObjects[$(this).attr("id")] = new steelseries.Linear(id, {
                            titleString: title,
							unitString: units,
							width: $(this).width(),
							height: $(this).height(),
                            unitString: units,
                            lcdVisible: true,
							threshold: threshold
                            });
		}else if (LinearTypeSelector=="LinearBargraph"){
			 SteelseriesObjects[$(this).attr("id")] = new steelseries.LinearBargraph(id, {
                            titleString: title,
							unitString: units,
							width: $(this).width(),
							height: $(this).height(),
                            unitString: units,
                            lcdVisible: true,
							threshold: threshold
                            });
			}else if (LinearTypeSelector=="LinearThermoStat"){
						SteelseriesObjects[$(this).attr("id")] = new steelseries.Linear(id, {
							gaugeType: steelseries.GaugeType.TYPE2, //ThermoStat Property
							unitString: units,
                            titleString: title,
							width: $(this).width(),
							height: $(this).height(),
                            unitString: units,
                            lcdVisible: true,
							threshold: threshold,
							minValue: MinValue,
							maxValue:MaxValue,
							fullScaleDeflectionTime: 0.8
                            });
						}
						
	var pointercolour = $(this).attr("pointercolour");
	if (pointercolour == undefined){pointercolour = "RED";}
	SteelseriesObjects[$(this).attr("id")].setValueColor(steelseries.ColorDef[pointercolour]);
	}
	//End of SSLinear Object Init
	if (elementclass=="SSSingleDisplay"||elementclass=="SSMultiDisplay"){
		var LcdColor = $(this).attr("LcdColor");
		if (LcdColor == undefined){LcdColor = "STANDARD";}
		SteelseriesObjects[$(this).attr("id")].setLcdColor(steelseries.LcdColor[LcdColor]);
		}
	
	if (elementclass=="SSLinear" || elementclass=="SSRadial"){
	var framedesign = $(this).attr("framedesign");
	if (framedesign == undefined){framedesign = "METAL";}
	SteelseriesObjects[$(this).attr("id")].setFrameDesign(steelseries.FrameDesign[framedesign]);
	
	var backgroundcolour = $(this).attr("backgroundcolour");
	if (backgroundcolour == undefined){backgroundcolour = "DARK_GRAY";}
	SteelseriesObjects[$(this).attr("id")].setBackgroundColor(steelseries.BackgroundColor[backgroundcolour]);
	
	var LedColor = $(this).attr("LedColor");
	if (LedColor == undefined){LedColor = "RED_LED";
	SteelseriesObjects[$(this).attr("id")].setLedColor(steelseries.LedColor[LedColor]);}
	else{
	SteelseriesObjects[$(this).attr("id")].setLedColor(steelseries.LedColor[LedColor+"_LED"]);
	}
	}
	
  });
}