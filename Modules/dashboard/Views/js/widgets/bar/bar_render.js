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
function bar_widgetlist()
{
	var widgets =
	{
		"bar":
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

	addOption(widgets["bar"], "feed",        "feed",          _Tr("Feed"),        _Tr("Feed value"),                                                            []);
	addOption(widgets["bar"], "max",         "value",         _Tr("Max value"),   _Tr("Max value to show"),                                                     []);
	addOption(widgets["bar"], "scale",       "value",         _Tr("Scale"),       _Tr("Value is multiplied by scale before display"),                           []);
	addOption(widgets["bar"], "units",       "value",         _Tr("Units"),       _Tr("Units to show"),                                                         []);
	addOption(widgets["bar"], "offset",      "value",         _Tr("Offset"),      _Tr("Static offset. Subtracted from value before computing needle position"), []);
	addOption(widgets["bar"], "colour",      "colour_picker", _Tr("Colour"),      _Tr("Colour to draw bar in"),                                                 []);
	addOption(widgets["bar"], "graduations", "dropbox",       _Tr("Graduations"), _Tr("Should the graduation limits be shown"),                                 graduationDropBoxOptions);



	return widgets;
}

function bar_init()
{
	setup_widget_canvas('bar');
}

function bar_draw()
{
	$('.bar').each(function(index)
	{
		var feed = $(this).attr("feed");
		var val = curve_value(feed,dialrate);
		// ONLY UPDATE ON CHANGE
		if ((val * 1).toFixed(2) != (assoc[feed] * 1).toFixed(2) || redraw == 1)
		{
			var id = "can-"+$(this).attr("id");
			var scale = 1*$(this).attr("scale") || 1;
			draw_bar(widgetcanvas[id],
								 0,
								 0,
								 $(this).width(),
								 $(this).height(),val*scale,
								 $(this).attr("max"),
								 $(this).attr("units"),
								 $(this).attr("colour"),
								 $(this).attr("offset"),
								 $(this).attr("graduations"));
		}
	});
}

function bar_slowupdate()
{

}

function bar_fastupdate()
{
	bar_draw();
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


function draw_bar(context,
				x_pos,				// these x and y coords seem unused?
				y_pos,
				width,
				height,
				raw_value,
				max_value,
				units_string,
				display_colour,
				static_offset,
				graduationBool)
{
	if (!context)
		return;

	// if (1 * max_value) == false: 3000. Else 1 * max_value
	max_value = 1 * max_value || 3000;
	// if units_string == false: "". Else units_string
	units_string = units_string || "";

	static_offset = 1*static_offset || 0;
	var display_value = raw_value
	display_value = display_value-static_offset

	var scaled_value = (display_value/max_value);    // Produce a scaled 0-1 value corresponding to min-max

	var size = 0;
	if (width<height)
		size = width/2;
	else
		size = height/2;

	size = size - (size*0.058/2);

	var half_width = width/2;
	var half_height = height/2;

	context.clearRect(0,0,width,height); // Clear old drawing

	if (!display_value)
		display_value = 0;

	context.lineWidth = 1;
	context.strokeStyle = "#000";
	var border_space = 5;
	context.strokeRect(border_space,
					border_space,
					width-(border_space*2),
					height-(border_space*2));


	context.lineWidth = 0;
	if (display_colour.indexOf("#") == -1)			// Fix missing "#" on colour if needed
		display_colour = "#" + display_colour;

	context.fillStyle = display_colour;

	border_space = 10;

	var bar_top = height - (scaled_value * (height - (border_space)));

	context.fillRect(border_space,
					bar_top,
					width-(border_space*2),
					(height-border_space) - bar_top );

	// var a = 1.75 - ( * 1.5);

	// width = 0.785;
	// var c=3*0.785;
	// var pos = 0;
	// var inner = size * 0.48;

	// pos -= width;
	// context.lineWidth = (size*0.058).toFixed(0);
	// pos += width;
	// context.strokeStyle = "#fff";
	// context.beginPath();
	// context.arc(half_width,half_height,size,c,c+pos,false);
	// context.lineTo(half_width,half_height);
	// context.closePath();
	// context.stroke();

	// context.fillStyle = "#666867";
	// context.beginPath();
	// context.arc(half_width,half_height,inner,0,Math.PI*2,true);
	// context.closePath();
	// context.fill();

	// context.lineWidth = (size*0.052).toFixed(0);
	// //---------------------------------------------------------------
	// context.beginPath();
	// context.moveTo(half_width+Math.sin(Math.PI*a-0.2)*inner,half_height+Math.cos(Math.PI*a-0.2)*inner);
	// context.lineTo(half_width+Math.sin(Math.PI*a)*size,half_height+Math.cos(Math.PI*a)*size);
	// context.lineTo(half_width+Math.sin(Math.PI*a+0.2)*inner,half_height+Math.cos(Math.PI*a+0.2)*inner);
	// context.arc(half_width,half_height,inner,1-(Math.PI*a-0.2),1-(Math.PI*a+5.4),true);
	// context.closePath();
	// context.fill();
	// context.stroke();

	//---------------------------------------------------------------



	context.fillStyle = "#000";
	context.textAlign    = "center";
	context.font = "bold "+(size*0.32)+"px arial";
	if (raw_value>100)
	{
		raw_value = raw_value.toFixed(0);
	}
	else if (raw_value>10)
	{
		raw_value = raw_value.toFixed(1);
	}
	else
	{
		raw_value = raw_value.toFixed(2);
	}
	context.fillText(raw_value+units_string, half_width, half_height+(size*0.125));


	context.fillStyle = "#000";
	var spreadAngle = 32;


	if (graduationBool == 1)
	{

		// context.font = "bold "+(size*0.22)+"px arial";

		// var posStrt = polar_to_cart(size/1.15, 90+spreadAngle, half_width, half_height);
		// var posStop = polar_to_cart(size/1.15, 90-spreadAngle, half_width, half_height);

		// context.save()
		// context.translate(posStrt[0], posStrt[1]);
		// context.rotate(deg_to_radians(-45));
		// context.fillText(""+static_offset+units_string, 0, 0);        // Since we've translated the entire context, the coords we want to draw at are now at [0,0]
		// context.restore();

		// context.save(); // each context.save is only good for one restore, apparently.
		// context.translate(posStop[0], posStop[1]);
		// context.rotate(deg_to_radians(45));
		// context.fillText(""+(static_offset+max_value)+units_string, 0, 0);
		// context.restore();
	}

}


