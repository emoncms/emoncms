/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    Author: Trystan Lea: trystan.lea@googlemail.com
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
	
	Compass Widget to visualize wind or flow direction
 */

// Global variables
var img = null,
  needle = null;
  
function compass_widgetlist()
{
  var widgets = {
    "compass":
    {
      "offsetx":-80,"offsety":-80,"width":160,"height":160,
      "menu":"Widgets",
      "options":["feedid", "units"],
      "optionstype":["feedid","value"],
      "optionsname":[_Tr("Feed"),_Tr("Units")],
      "optionshint":[_Tr("Feed"),_Tr("Units to show")]

    }
  }
  return widgets;
}

function compass_init()
{
  setup_widget_canvas('compass');

  // Load the compass image
  background = new Image();
  background.src = path+'Modules/dashboard/widget/compass/windrose.png';
  
  // Load the needle image
  direction = new Image();
  direction.src = path+'Modules/dashboard/widget/compass/needle3.png';
  
}

function compass_draw() {

//compassbase()
	
$('.compass').each(function(index)
	{
	var id = "can-"+$(this).attr("id");
	var scale = 1*$(this).attr("scale") || 1;
	draw_compassbase(widgetcanvas[id],0,0,$(this).width(),$(this).height());	
	});


	//compass_Get1()
	{
		$('.compass').each(function(index)
		{
		var feedid = $(this).attr("feedid");
		if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
		var val = curve_value(feedid, dialrate);
		// ONLY UPDATE ON CHANGE
		if ((val * 1).toFixed(2) != (associd[feedid]['value'] * 1).toFixed(2) || redraw == 1);
		var id = "can-"+$(this).attr("id");
		var scale = 1*$(this).attr("scale") || 1;
		draw_compass(widgetcanvas[id],0,0,$(this).width(),$(this).height(),val*scale,$(this).attr("max"),$(this).attr("units"));
		});
	}
	

}

function compass_slowupdate()
{

}

function compass_fastupdate()
{
  compass_draw();
}

function draw_compassbase(ctx,x,y,width,height)
{
  
  var offset = 45;
  var size = 0;
  if (width>height) {
    size = height;
  } else {
    size = width;
  }
  if (size>170) size=170;
  if (size<120) size=120;
   
  ctx.clearRect(0,0,width,height);

  // Draw the compass onto the canvas
  ctx.drawImage(background, 0, 0, size, size);
  }

function draw_compass(ctx,x,y,width,height,value,max,units)
{
 
  if (!max) max = 360;
  if (!value) value = 0;
  if (!units) units = " ";
  var offset = 180;
  var position = ((value*360)/max);
    if (position > 360) {
    position = 360;

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
     
  //ticks labels
  max = max/8;
  ctx.font = "8pt Arial";
  ctx.fillStyle = "rgb(34,198,252)";
  ctx.fillText("225°", 25*(size/100), 70*(size/100)); // first tick
  ctx.fillText("270°", 18*(size/100), 52*(size/100)); // second tick
  ctx.fillText("315°", 25*(size/100), 33*(size/100)); // third tick
  ctx.fillText("360°", 45*(size/100), 25*(size/100)); // 4th tick
  ctx.fillText("45°", 65*(size/100), 33*(size/100)); // 5th tick
  //ctx.fillStyle = "rgb(245,144,0)";
  ctx.fillText("90°",75*(size/100), 52*(size/100)); // 6th tick
 // ctx.fillStyle = "rgb(255,0,0)";
  ctx.fillText("135°", 65*(size/100), 70*(size/100)); // 7th tick
  ctx.fillText("180°", 45*(size/100), 78*(size/100)); // 7th tick
  
  // main label
  ctx.font = "14pt Calibri,Geneva,Arial";
  ctx.strokeStyle = "rgb(255,255,255)";
  ctx.fillStyle = "rgb(255,255,255)";
  value = Number(value.toFixed(decimalPlaces));
  len = value.toString().length;
  if (len < 2) {
 //   ctx.fillText(value+units, 43*(size/100), 85*(size/100));
  }
  else if ((len<3) && (len>2)) {
   // ctx.fillText(value+units, 40*(size/100), 85*(size/100));
  }
  else {
   // ctx.fillText(value+units, 37*(size/100), 85*(size/100));
	
  }	 
	 
   
  // Save the current drawing state
  ctx.save();

  // move to the middle of the image
  ctx.translate((size/2), (size/2));

  // Rotate around this point
  ctx.rotate((position + offset) * (Math.PI / 180));

  // Draw the image back and up
  ctx.drawImage(direction, -(size/2), -(size/2), size, size);
  
  // Restore the previous drawing state
  ctx.restore(); 
  }