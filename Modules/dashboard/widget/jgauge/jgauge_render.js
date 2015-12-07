/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.
   Part of the OpenEnergyMonitor project:  http://openenergymonitor.org
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
      "options":["feedid", "scale", "max", "min", "units"],
      "optionstype":["feedid","value","value","value","value"],
      "optionsname":[_Tr("Feed"),_Tr("Scale"),_Tr("Max value"),_Tr("Min value"),_Tr("Units")],
      "optionshint":[_Tr("Feed"),_Tr("Scale applied to value"),_Tr("Max value to show"),_Tr("Min value to show"),_Tr("Units to show")]

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
    var feedid = $(this).attr("feedid");
    if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var val = curve_value(feedid,dialrate);
    // ONLY UPDATE ON CHANGE
    if ((val * 1).toFixed(2) != (associd[feedid]['value'] * 1).toFixed(2) || redraw == 1)
    {
      var id = "can-"+$(this).attr("id");
      var scale = 1*$(this).attr("scale") || 1;
      draw_jgauge(widgetcanvas[id],0,0,$(this).width(),$(this).height(),val*scale,$(this).attr("max"),$(this).attr("min"),$(this).attr("units"));
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

function draw_jgauge(ctx,x,y,width,height,value,max,min,units)
{
  if (!max) max = 1000;
  if (!min || min > max) min = 0;
  min = Number(min);
  max = Number(max);
  if (!value) value = 0;
  if (!units) units = " ";
  var offset = 45;
  var position = (((value-min)*270)/(max - min));
  if (position > 270) {
    position = 270;
  }
  if (position < 0) {
    position = 0;
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
  if ((max - min) <= 1.2)  decimalPlaces = 2;
  else if ((max - min) <= 12)  decimalPlaces = 1;
  
  ctx.clearRect(0,0,width,height);

  // Draw the jgauge onto the canvas
  ctx.drawImage(img, 0, 0, size, size);

  //ticks labels
  var step = ((max - min)/6);
  ctx.textAlign="center"; 
  ctx.font = "8pt Arial";
  ctx.fillStyle = "rgb(34,198,252)";
  ctx.fillText((Number(min + (step*0)).toFixed(decimalPlaces)), 30*(size/100), 72*(size/100)); // first tick
  ctx.fillText((Number(min + (step*1)).toFixed(decimalPlaces)), 25*(size/100), 52*(size/100)); // second tick
  ctx.fillText((Number(min + (step*2)).toFixed(decimalPlaces)), 30*(size/100), 32*(size/100)); // third tick
  ctx.fillText((Number(min + (step*3)).toFixed(decimalPlaces)), 50*(size/100), 27*(size/100)); // 4th tick
  ctx.fillText((Number(min + (step*4)).toFixed(decimalPlaces)), 70*(size/100), 32*(size/100)); // 5th tick
  ctx.fillStyle = "rgb(245,144,0)";
  ctx.fillText((Number(min + (step*5)).toFixed(decimalPlaces)), 75*(size/100), 52*(size/100)); // 6th tick
  ctx.fillStyle = "rgb(255,0,0)";
  ctx.fillText((Number(min + (step*6)).toFixed(decimalPlaces)), 70*(size/100), 72*(size/100)); // 7th tick

  // main label
  ctx.font = "14pt Calibri,Geneva,Arial";
  ctx.strokeStyle = "rgb(255,255,255)";
  ctx.fillStyle = "rgb(255,255,255)";
  value = Number(value.toFixed(decimalPlaces));
  ctx.fillText(value+units, 50*(size/100), 85*(size/100));

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
