function SSLinear_widgetlist()
{
  var widgets = {
    "SSLinear":
    {
      "offsetx"		:-80,"offsety":-80,"width":140,"height":320,
      "menu"		:"Widgets",
	  "options"		:["feed", "LinearType", "framedesign", "backgroundcolour","pointercolour", "LcdColor", "LedColor", "title", "unit",  "MinValue", "MaxValue","threshold"],
      "optionstype"	:["feed", "LinearType", "framedesign", "backgroundcolour","pointercolour", "LcdColor", "LedColor", "value", "value", "value",	 "value",	"value"],
      "optionsname"	:[_Tr("Feed"),_Tr("LinearType"), _Tr("Frame Design"), _Tr("Background Colour"),_Tr("Pointer Colour"),_Tr("LCD Colour"),_Tr("LED Colour"),_Tr("Title"),_Tr("Units"),_Tr("MinValue"),_Tr("MaxValue"),_Tr("Threshold")],
      "optionshint"	:[_Tr("Feed"),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr("LED blinks when value is exceeded"),] 
    }
  }
  return widgets;
}

function SSLinear_init()
{
  setup_widget_canvas('SSLinear');//add init
  setup_steelseries_object('SSLinear');
}



function SSLinear_draw()
{
	$('.SSLinear').each(function(index){
	
	var feed = $(this).attr("feed");
	if (feed==undefined){feed=0;}
	var val = assoc[feed];
    if (val==undefined) val = 0;
	
	if (val != temp){//redraw?
		SteelseriesObjects[$(this).attr("id")].setValueAnimated(val);
		var temp =val;

		}
    });
}

function draw_SSLinear(){

}

function SSLinear_slowupdate()
{
	
}

function SSLinear_fastupdate()
{
  SSLinear_draw();
}