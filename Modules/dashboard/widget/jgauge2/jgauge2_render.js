/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.
   Part of the OpenEnergyMonitor project:  http://openenergymonitor.org
 */

// Global variables
var img = null,
  needle = null,
  needle2 = null;

function jgauge2_widgetlist()
{
  var widgets = {
    "jgauge2":
    {
      "offsetx":-80,"offsety":-80,"width":160,"height":160,
      "menu":"Widgets",
      "options":["feedid", "feedid2", "scale", "max", "min", "units"],
      "optionstype":["feedid","feedid","value","value","value"],
      "optionsname":[_Tr("Feed 1"),_Tr("Feed 2"),_Tr("Scale"),_Tr("Max value"),_Tr("Min value"),_Tr("Units")],
      "optionshint":[_Tr("Feed 1"),_Tr("Feed 2 (Min/Max for example)"),_Tr("Scale applied to value"),_Tr("Max value to show"),_Tr("Min value to show"),_Tr("Units to show")]

    }
  }
  return widgets;
}

function jgauge2_init()
{
  setup_widget_canvas('jgauge2');

  // Load the needle image
  needle_jgauge2 = new Image();
  needle_jgauge2.src = path+'Modules/dashboard/widget/jgauge2/needle2.png';
  
  needle2_jgauge2 = new Image();
  needle2_jgauge2.src = path+'Modules/dashboard/widget/jgauge2/needle.png';

  // Load the jgauge2 image
  img_jgauge2 = new Image();
  img_jgauge2.src = path+'Modules/dashboard/widget/jgauge2/jgauge2.png';
}

function jgauge2_draw()
{
  $('.jgauge2').each(function(index)
  {
    var feedid = $(this).attr("feedid");
    var feedid2 = $(this).attr("feedid2");
    if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    if (associd[feedid2] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var val = curve_value(feedid,dialrate);
    var val2 = curve_value(feedid2, dialrate);
    // ONLY UPDATE ON CHANGE
    if ((val * 1).toFixed(2) != (associd[feedid]['value'] * 1).toFixed(2) || 
        (val2 * 1).toFixed(2) != (associd[feedid2]['value'] * 1).toFixed(2) ||
        redraw == 1)
    {
      var id = "can-"+$(this).attr("id");
      var scale = 1*$(this).attr("scale") || 1;
      draw_jgauge2(widgetcanvas[id],0,0,$(this).width(),$(this).height(),val*scale,val2*scale,$(this).attr("max"),$(this).attr("min"),$(this).attr("units"));
    }
  });
}

function jgauge2_slowupdate()
{

}

function jgauge2_fastupdate()
{
  jgauge2_draw();
}

function draw_jgauge2(ctx,x,y,width,height,value,value2,max,min,units)
{
  if (!max) max = 1000;
  if (!min) min = 0;
  min = Number(min);
  max = Number(max);
  if (!value) value = 0;
  if (!value2) value2 = 0;
  if (!units) units = " ";
  var offset = 45;
  var position = (((value-min)*270)/(max - min));
  if (position > 270) {
    position = 270;
  }
  if (position < 0) {
    position = 0;
  }

  var position2 = (((value2-min)*270)/(max - min));
  if (position2 > 270) {
    position2 = 270;
  }
  if (position2 < 0) {
    position2 = 0;
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
  if (max > min) {
    if ((max - min) <= 1.2)  decimalPlaces = 2;
    else if ((max - min) <= 12)  decimalPlaces = 1;
  } else {
    if ((min - max) <= 1.2)  decimalPlaces = 2;
    else if ((min - max) <= 12)  decimalPlaces = 1;
  }
  
  ctx.clearRect(0,0,width,height);

  // Draw the jgauge2 onto the canvas
  ctx.drawImage(img_jgauge2, 0, 0, size, size);

  //ticks labels
  var step = ((max - min)/6);
  ctx.textAlign="center"; 
  ctx.font = "8pt Arial";
  ctx.fillStyle = "rgb(34,198,252)";
  ctx.fillText((Number(min + (step*0)).toFixed(decimalPlaces)), 30*(size/100), 72*(size/100)); // 1st tick
  ctx.fillText((Number(min + (step*1)).toFixed(decimalPlaces)), 25*(size/100), 52*(size/100)); // 2nd tick
  ctx.fillText((Number(min + (step*2)).toFixed(decimalPlaces)), 30*(size/100), 32*(size/100)); // 3rd tick
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
  ctx.fillText(value+units, 50*(size/100), 88*(size/100));

  // max label
  ctx.font = "10pt Calibri,Geneva,Arial";
  ctx.strokeStyle = "rgb(255,255,255)";
  ctx.fillStyle = "rgb(255,1,1)";
  value = Number(value2.toFixed(decimalPlaces));
  ctx.fillText(value+units, 50*(size/100), 75*(size/100));


  // Save the current drawing state
  ctx.save();
  // move to the middle of the image
  ctx.translate((size/2), (size/2));
  // Rotate around this point
  ctx.rotate((position + offset) * (Math.PI / 180));
  // Draw the image back and up
  ctx.drawImage(needle_jgauge2, -(size/2), -(size/2), size, size);
  // Restore the previous drawing state
  ctx.restore(); 

  // Save the current drawing state
  ctx.save();
  // move to the middle of the image
  ctx.translate((size/2), (size/2));
  // Rotate around this point
  ctx.rotate((position2 + offset) * (Math.PI / 180));
  // Draw the image back and up
  ctx.drawImage(needle2_jgauge2, -(size/2), -(size/2), size, size);
  // Restore the previous drawing state
  ctx.restore();
}