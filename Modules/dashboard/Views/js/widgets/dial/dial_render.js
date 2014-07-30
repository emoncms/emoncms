/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    Author: Trystan Lea: trystan.lea@googlemail.com
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
 */

// Convenience function for shoving things into the widget object
// I'm not sure about calling optionKey "optionKey", but I don't want to just use "options" (because that's what this whole function returns), and it's confusing enough as it is.
function addOption(widget, optionKey, optionType, optionName, optionHint, optionData)
{

  widget["options"    ].push(optionKey);
  widget["optionstype"].push(optionType);
  widget["optionsname"].push(optionName);
  widget["optionshint"].push(optionHint);
  widget["optionsdata"].push(optionData);


}
function dial_widgetlist()
{
  var widgets =
  {
    "dial":
    {
      "offsetx":-80,"offsety":-80,"width":160,"height":160,
      "menu":"Widgets",
      "options":    [],
      "optionstype":[],
      "optionsname":[],
      "optionshint":[],
      "optionsdata":[]

    }
  };


  var typeDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
          [0,    "Light <-> dark green, Zero at left"],
          [1,    "Red <-> Green, Zero at center"],
          [2,    "Green <-> Red, Zero at left"],
          [3,    "Green <-> Red, Zero at center"],
          [4,    "Red <-> Green, Zero at left"],
          [5,    "Red <-> Green, Zero at center"],
          [6,    "Green center <-> orange edges, Zero at center "],
          [7,    "Light <-> Dark blue, Zero at left"],
          [8,    "Light blue <-> Red, Zero at mid-left"],
          [9,    "Red <-> Dark Red, Zero at left"],
          [10,   "Black <-> White, Zero at left"]
        ];

  var graduationDropBoxOptions = [
          [1, "Yes"],
          [0, "No"]
        ]

  addOption(widgets["dial"], "feed",        "feed",    _Tr("Feed"),        _Tr("Feed value"),                                                            []);
  addOption(widgets["dial"], "max",         "value",   _Tr("Max value"),   _Tr("Max value to show"),                                                     []);
  addOption(widgets["dial"], "scale",       "value",   _Tr("Scale"),       _Tr("Value is multiplied by scale before display"),                           []);
  addOption(widgets["dial"], "units",       "value",   _Tr("Units"),       _Tr("Units to show"),                                                         []);
  addOption(widgets["dial"], "offset",      "value",   _Tr("Offset"),      _Tr("Static offset. Subtracted from value before computing needle position"), []);
  addOption(widgets["dial"], "type",        "dropbox", _Tr("Type"),        _Tr("Type to show"),                                                          typeDropBoxOptions);
  addOption(widgets["dial"], "graduations", "dropbox", _Tr("Graduations"), _Tr("Should the graduation limits be shown"),                                 graduationDropBoxOptions);



  return widgets;
}

function dial_init()
{
  setup_widget_canvas('dial');
}

function dial_draw()
{
  $('.dial').each(function(index)
  {
    var feed = $(this).attr("feed");
    var val = curve_value(feed,dialrate);
    // ONLY UPDATE ON CHANGE
    if ((val * 1).toFixed(2) != (assoc[feed] * 1).toFixed(2) || redraw == 1)
    {
      var id = "can-"+$(this).attr("id");
      var scale = 1*$(this).attr("scale") || 1;
      draw_gauge(widgetcanvas[id],
                 0,
                 0,
                 $(this).width(),
                 $(this).height(),val*scale,
                 $(this).attr("max"),
                 $(this).attr("units"),
                 $(this).attr("type"),
                 $(this).attr("offset"),
                 $(this).attr("graduations"));
    }
  });
}

function dial_slowupdate()
{

}

function dial_fastupdate()
{
  dial_draw();
}

function deg_to_radians(deg)
{
  return deg * (Math.PI / 180)
}
function polar_to_cart(mag, ang, xOff, yOff)
{
  ang = deg_to_radians(ang);
  var x = mag * Math.cos(ang) + xOff;
  var y = mag * Math.sin(ang) + yOff;
  return [x, y];
}

// X, Y are the center coordinates of the canvas
function draw_gauge(ctx,x,y,width,height,position,maxvalue,units,type, offset, graduationBool)
{
  if (!ctx) return;

  // if (1 * maxvalue) == false: 3000. Else 1 * maxvalue
  maxvalue = 1 * maxvalue || 3000;
  // if units == false: "". Else units
  units = units || "";
  offset = 1*offset || 0;
  var val = position
  position = position-offset

  var size = 0;
  if (width<height) size = width/2;
  else size = height/2;
  size = size - (size*0.058/2);

  x = width/2;
  y = height/2;

  ctx.clearRect(0,0,200,200);

  if (!position)
    position = 0;

  var angleOffset = 0;
  var segment = ["#c0e392","#9dc965","#87c03f","#70ac21","#378d42","#046b34"];

  type = type || 0;

  if (type == 0)
  {
    if (position<0)
      position = 0;
  }
  else if (type == 1)
  {
    angleOffset = -0.75;
    segment = ["#e61703","#ff6254","#ffa29a","#70ac21","#378d42","#046b34"];
  }
  else if (type == 2)
  {
    if (position<0)
      position = 0;
    segment = ["#046b34","#378d42","#87c03f","#f8a01b","#f46722","#bf2025"];
  }
  else if (type == 3)
  {
    angleOffset = -0.75;
    segment = ["#046b34","#378d42","#87c03f","#f8a01b","#f46722","#bf2025"];
  }
  else if (type == 4)
  {
    if (position<0)
      position = 0;
    segment = ["#bf2025","#f46722","#f8a01b","#87c03f","#378d42","#046b34"];
  }
  else if (type == 5)
  {
    angleOffset = -0.75;
    segment = ["#bf2025","#f46722","#f8a01b","#87c03f","#378d42","#046b34"];
  }
  else if (type == 6)
  {
    angleOffset = -0.75;
    segment = ["#f46722","#f8a01b","#87c03f","#87c03f","#f8a01b","#f46722"];
  }
  else if (type == 7)
  {
    if (position<0)
      position = 0;
    segment = ["#a7cbe2","#68b7eb","#0d97f3","#0f81d0","#0c6dae","#08578e"];
  }
  else if (type == 8)  //temperature dial blue-red, first segment blue should mean below freezing C
  {
    angleOffset = -0.25;
    segment = ["#b7beff","#ffd9d9","#ffbebe","#ff9c9c","#ff6e6e","#ff3d3d"];
  }
  else if (type == 9)  //temperature dial blue-red, first segment blue should mean below freezing C
  {
    angleOffset = 0;
    segment = ["#e94937","#da4130","#c43626","#ad2b1c","#992113","#86170a"];
  }
  else if (type == 10) //light: from dark grey to white
  {
    if (position<0)
      position = 0;
    segment = ["#202020","#4D4D4D","#7D7D7D","#EEF0F3","#F7F7F7", "#FFFFFF"];
  }

  if (position>maxvalue)
    position = maxvalue;

  var a = 1.75 - ((position/maxvalue) * 1.5) + angleOffset;

  width = 0.785;
  var c=3*0.785;
  var pos = 0;
  var inner = size * 0.48;

  // Segments
  for (var z in segment)
  {
    ctx.fillStyle = segment[z];
    ctx.beginPath();
    ctx.arc(x,y,size,c+pos,c+pos+width+0.01,false);
    ctx.lineTo(x,y);
    ctx.closePath();
    ctx.fill();
    pos += width;
  }
  pos -= width;
  ctx.lineWidth = (size*0.058).toFixed(0);
  pos += width;
  ctx.strokeStyle = "#fff";
  ctx.beginPath();
  ctx.arc(x,y,size,c,c+pos,false);
  ctx.lineTo(x,y);
  ctx.closePath();
  ctx.stroke();

  ctx.fillStyle = "#666867";
  ctx.beginPath();
  ctx.arc(x,y,inner,0,Math.PI*2,true);
  ctx.closePath();
  ctx.fill();

  ctx.lineWidth = (size*0.052).toFixed(0);
  //---------------------------------------------------------------
  ctx.beginPath();
  ctx.moveTo(x+Math.sin(Math.PI*a-0.2)*inner,y+Math.cos(Math.PI*a-0.2)*inner);
  ctx.lineTo(x+Math.sin(Math.PI*a)*size,y+Math.cos(Math.PI*a)*size);
  ctx.lineTo(x+Math.sin(Math.PI*a+0.2)*inner,y+Math.cos(Math.PI*a+0.2)*inner);
  ctx.arc(x,y,inner,1-(Math.PI*a-0.2),1-(Math.PI*a+5.4),true);
  ctx.closePath();
  ctx.fill();
  ctx.stroke();

  //---------------------------------------------------------------
  
    if (isNaN(val))  val = 0;

    else if (val>=100)
        val = val.toFixed(0);
    else if (val>=10)
        val = val.toFixed(1);
    else if (val<=-100)
        val = val.toFixed(0);
    else if (val<=-10)
        val = val.toFixed(1);
    else
        val = val.toFixed(2);
    
  var dialtext = val+units;
  var textsize = (size / (dialtext.length+2)) * 6;
  
  ctx.fillStyle = "#fff";
  ctx.textAlign    = "center";
  ctx.font = "bold "+(textsize*0.26)+"px arial";
  
  ctx.fillText(val+units,x,y+(textsize*0.125));


  ctx.fillStyle = "#000";
  var spreadAngle = 32;


  if (graduationBool == 1)
  {

    ctx.font = "bold "+(size*0.22)+"px arial";

    var posStrt = polar_to_cart(size/1.15, 90+spreadAngle, x, y);
    var posStop = polar_to_cart(size/1.15, 90-spreadAngle, x, y);

    ctx.save()
    ctx.translate(posStrt[0], posStrt[1]);
    ctx.rotate(deg_to_radians(-45));
    ctx.fillText(""+offset+units, 0, 0);        // Since we've translated the entire context, the coords we want to draw at are now at [0,0]
    ctx.restore();

    ctx.save(); // each ctx.save is only good for one restore, apparently.
    ctx.translate(posStop[0], posStop[1]);
    ctx.rotate(deg_to_radians(45));
    ctx.fillText(""+(offset+maxvalue)+units, 0, 0);
    ctx.restore();
  }

}


