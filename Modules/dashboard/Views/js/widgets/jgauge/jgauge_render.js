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
      "options":["feed", "max", "units"],
      "optionstype":["feed","value","value"],
      "optionsname":[_Tr("Feed"),_Tr("Max value"),_Tr("Units")],
      "optionshint":[_Tr("Feed"),_Tr("Max value to show"),_Tr("Units to show")]

    }
  }
  return widgets;
}

function jgauge_init()
{
  setup_widget_canvas('jgauge');

  // Load the needle image
  needle = new Image();
  needle.src = path+'Modules/dashboard/Views/js/widgets/jgauge/needle2.png';

  // Load the jgauge image
  img = new Image();
  img.src = path+'Modules/dashboard/Views/js/widgets/jgauge/jgauge.png';
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

  ctx.clearRect(0,0,width,height);

  // Draw the jgauge onto the canvas
  ctx.drawImage(img, 0, 0, size, size);

  //ticks labels
  ctx.font = "8pt Arial";
  ctx.fillStyle = "rgb(34,198,252)";
  ctx.fillText(0, 28*(size/100), 70*(size/100)); // first tick
  ctx.fillText(Math.round(max/6)*1, 20*(size/100), 52*(size/100)); // second tick
  ctx.fillText(Math.round(max/6)*2, 25*(size/100), 33*(size/100)); // third tick
  ctx.fillText(Math.round(max/6)*3, 45*(size/100), 22*(size/100)); // 4th tick
  ctx.fillText(Math.round(max/6)*4, 65*(size/100), 33*(size/100)); // 5th tick
  ctx.fillStyle = "rgb(245,144,0)";
  ctx.fillText(Math.round(max/6)*5, 75*(size/100), 52*(size/100)); // 6th tick
  ctx.fillStyle = "rgb(255,0,0)";
  ctx.fillText(Math.round(max), 65*(size/100), 70*(size/100)); // 7th tick

  // main label
  ctx.font = "15pt Calibri,Geneva,Arial";
    ctx.strokeStyle = "rgb(255,255,255)";
    ctx.fillStyle = "rgb(255,255,255)";
  if (value<10) {
    ctx.fillText(Math.round(value)+units, 43*(size/100), 85*(size/100));
  }
  else if ((value<100) && (value>10)) {
    ctx.fillText(Math.round(value)+units, 40*(size/100), 85*(size/100));
  }
  else {
    ctx.fillText(Math.round(value)+units, 37*(size/100), 85*(size/100));
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
