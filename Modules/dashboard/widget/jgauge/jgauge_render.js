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

// Global variables
var img = null,
  needle = null;

function jgauge_widgetlist()
{
  var widgets = {
    "jgauge":
    {
      "offsetx":-80,"offsety":-80,"width":160,"height":160,
      "menu":"Widgets",
      "options":["feed", "scale", "max", "units"],
      "optionstype":["feed","value","value","value"],
      "optionsname":[_Tr("Feed"),_Tr("Scale"),_Tr("Max value"),_Tr("Units")],
      "optionshint":[_Tr("Feed"),_Tr("Scale applied to value"),_Tr("Max value to show"),_Tr("Units to show")]

    }
  }
  return widgets;
}

function jgauge_init()
{
  setup_widget_canvas('jgauge');

  // Load the needle image
  needle = new Image();
  needle.src = path+'Modules/dashboard/widget/jgauge/needle2.png';

  // Load the jgauge image
  img = new Image();
  img.src = path+'Modules/dashboard/widget/jgauge/jgauge.png';
}

function jgauge_draw()
{
  $('.jgauge').each(function(index)
  {
    var feed = $(this).attr("feed");
    var val = curve_value(feed,dialrate);
    // ONLY UPDATE ON CHANGE
    if ((val * 1).toFixed(2) != (assoc[feed] * 1).toFixed(2) || redraw == 1)
    {
      var id = "can-"+$(this).attr("id");
      var scale = 1*$(this).attr("scale") || 1;
      draw_jgauge(widgetcanvas[id],0,0,$(this).width(),$(this).height(),val*scale,$(this).attr("max"),$(this).attr("units"));
    }
  });
}

function jgauge_slowupdate()
{

}

function jgauge_fastupdate()
{
  jgauge_draw();
}

function draw_jgauge(ctx,x,y,width,height,value,max,units)
{
  if (!max) max = 1000;
  if (!value) value = 0;
  if (!units) units = " ";
  var offset = 45;
  var position = ((value*270)/max);
    if (position > 270) {
    position = 270;
  }
  var size = 0;
  if (width>height) {
    size = height;
  } else {
    size = width;
  }
  if (size>170) size=170;
  if (size<120) size=120;

  decimalPlaces = 0;
  if (max <= 1.2)  decimalPlaces = 2;
  else if (max <= 12)  decimalPlaces = 1;
  
  ctx.clearRect(0,0,width,height);

  // Draw the jgauge onto the canvas
  ctx.drawImage(img, 0, 0, size, size);

  //ticks labels
  max = max/6;
  ctx.font = "8pt Arial";
  ctx.fillStyle = "rgb(34,198,252)";
  ctx.fillText(0, 28*(size/100), 70*(size/100)); // first tick
  ctx.fillText(Number((max*1).toFixed(decimalPlaces)), 20*(size/100), 52*(size/100)); // second tick
  ctx.fillText(Number((max*2).toFixed(decimalPlaces)), 25*(size/100), 33*(size/100)); // third tick
  ctx.fillText(Number((max*3).toFixed(decimalPlaces)), 45*(size/100), 22*(size/100)); // 4th tick
  ctx.fillText(Number((max*4).toFixed(decimalPlaces)), 65*(size/100), 33*(size/100)); // 5th tick
  ctx.fillStyle = "rgb(245,144,0)";
  ctx.fillText(Number((max*5).toFixed(decimalPlaces)), 75*(size/100), 52*(size/100)); // 6th tick
  ctx.fillStyle = "rgb(255,0,0)";
  ctx.fillText(Number((max*6).toFixed(decimalPlaces)), 65*(size/100), 70*(size/100)); // 7th tick

  // main label
  ctx.font = "14pt Calibri,Geneva,Arial";
  ctx.strokeStyle = "rgb(255,255,255)";
  ctx.fillStyle = "rgb(255,255,255)";
  value = Number(value.toFixed(decimalPlaces));
  len = value.toString().length;
  if (len < 2) {
    ctx.fillText(value+units, 43*(size/100), 85*(size/100));
  }
  else if ((len<3) && (len>2)) {
    ctx.fillText(value+units, 40*(size/100), 85*(size/100));
  }
  else {
    ctx.fillText(value+units, 37*(size/100), 85*(size/100));
  }

  // Save the current drawing state
  ctx.save();

  // move to the middle of the image
  ctx.translate((size/2), (size/2));

  // Rotate around this point
  ctx.rotate((position + offset) * (Math.PI / 180));

  // Draw the image back and up
  ctx.drawImage(needle, -(size/2), -(size/2), size, size);

  // Restore the previous drawing state
  ctx.restore();
}
