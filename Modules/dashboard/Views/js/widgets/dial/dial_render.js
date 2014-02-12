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

function dial_widgetlist()
{
  var widgets =
  {
    "dial":
    {
      "offsetx":-80,"offsety":-80,"width":160,"height":160,
      "menu":"Widgets",
      "options":["feed","max","scale","units","type"],
      "optionstype":["feed","value","value","value","dropbox"],
      "optionsname":[_Tr("Feed"),_Tr("Max value"),_Tr("Scale"),_Tr("Units"),_Tr("Type")],
      "optionshint":[_Tr("Feed value"),_Tr("Max value to show"),_Tr("Scale to show"),_Tr("Units to show"),_Tr("Type to show")],
      "optionsdata":[
        [],
        [],
        [],
        [],
        [       // Options for the type combobox. Item is [typeID, "description"]
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
        ]
        ]

    }
  };
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
      draw_gauge(widgetcanvas[id],0,0,$(this).width(),$(this).height(),val*scale, $(this).attr("max"), $(this).attr("units"),$(this).attr("type"));
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



function draw_gauge(ctx,x,y,width,height,position,maxvalue,units,type)
{
  if (!ctx) return;

  // if (1 * maxvalue) == false: 3000. Else 1 * maxvalue
  maxvalue = 1 * maxvalue || 3000;
  // if units == false: "". Else units
  units = units || "";
  var size = 0;
  if (width<height) size = width/2;
  else size = height/2;
  size = size - (size*0.058/2);

  x = width/2;
  y = height/2;

  ctx.clearRect(0,0,200,200);

  if (!position)
    position = 0;

  var offset = 0;
  var segment = ["#c0e392","#9dc965","#87c03f","#70ac21","#378d42","#046b34"];

  type = type || 0;

  if (type == 0)
  {
    if (position<0) position = 0;
  }
  else if (type == 1)
  {
    offset = -0.75;
    segment = ["#e61703","#ff6254","#ffa29a","#70ac21","#378d42","#046b34"];
  }
  else if (type == 2)
  {
    segment = ["#046b34","#378d42","#87c03f","#f8a01b","#f46722","#bf2025"];
  }
  else if (type == 3)
  {
    offset = -0.75;
    segment = ["#046b34","#378d42","#87c03f","#f8a01b","#f46722","#bf2025"];
  }
  else if (type == 4)
  {
    segment = ["#bf2025","#f46722","#f8a01b","#87c03f","#378d42","#046b34"];
  }
  else if (type == 5)
  {
    offset = -0.75;
    segment = ["#bf2025","#f46722","#f8a01b","#87c03f","#378d42","#046b34"];
  }
  else if (type == 6)
  {
    offset = -0.75;
    segment = ["#f46722","#f8a01b","#87c03f","#87c03f","#f8a01b","#f46722"];
  }
  else if (type == 7)
  {
    offset = 0;
    segment = ["#a7cbe2","#68b7eb","#0d97f3","#0f81d0","#0c6dae","#08578e"];
  }
  else if (type == 8)  //temperature dial blue-red, first segment blue should mean below freezing C
  {
    offset = -0.25;
    segment = ["#b7beff","#ffd9d9","#ffbebe","#ff9c9c","#ff6e6e","#ff3d3d"];
  }
  else if (type == 9)  //temperature dial blue-red, first segment blue should mean below freezing C
  {
    offset = 0;
    segment = ["#e94937","#da4130","#c43626","#ad2b1c","#992113","#86170a"];
  }
  else if (type == 10) //light: from dark grey to white
  {
    segment = ["#202020","#4D4D4D","#7D7D7D","#EEF0F3","#F7F7F7", "#FFFFFF"];
  }

  if (position>maxvalue)
    position = maxvalue;

  var a = 1.75 - ((position/maxvalue) * 1.5) + offset;

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

  ctx.fillStyle = "#fff";
  ctx.textAlign    = "center";
  ctx.font = "bold "+(size*0.28)+"px arial";
  if (position>100)
  {
    position = position.toFixed(0);
  }
  else if (position>10)
  {
    position = position.toFixed(1);
  }
  else
  {
    position = position.toFixed(2);
  }
  ctx.fillText(position+units,x,y+(size*0.125));

}


