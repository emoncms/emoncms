function SSRadial_widgetlist()
{
  var widgets = {
    "SSRadial":
    {
      "offsetx":-80,"offsety":-80,"width":200,"height":200,
      "menu":"Widgets",
      "options"		:["feed", "radialtype", "type","framedesign", "backgroundcolour","pointercolour","PointerType","LcdColor","LedColor","ForegroundType","title", "unit", "threshold","sections","areas","minvalue","maxvalue"],
      "optionstype"	:["feed","radialtype", "type", "framedesign", "backgroundcolour","pointercolour","PointerType","LcdColor","LedColor","ForegroundType","value","value","value", "sections", "areas","value","value"],
      "optionsname"	:[ _Tr("Feed"),_Tr("Radialtype Selector"),  _Tr("Type Selector"), _Tr("Frame Design"), _Tr("Backgroundcolour"), _Tr("Pointercolour"), _Tr("PointerType"), _Tr("LcdColor"), _Tr("LedColor"),_Tr("ForegroundType"),_Tr("Title"),_Tr("Units"),_Tr("Threshold"),_Tr("Sections"),_Tr("Areas"),_Tr("Min Value"),_Tr("Max Value")],
      "optionshint"	:[_Tr(""),_Tr(""),_Tr("1/4, 1/2, 3/4, Full"),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr("Title"),_Tr("Units to show"),_Tr("Led will Blink if Exceeded"),_Tr("Define section colours"),_Tr("Define area colours"),_Tr(""),_Tr(""),] 
    }
  }
  return widgets;
}

function SSRadial_init()
{
  setup_widget_canvas('SSRadial');//add init
  setup_steelseries_object('SSRadial');
}



function SSRadial_draw()
{
	$('.SSRadial').each(function(index){
	//REVISE
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

function draw_SSRadial(){

}

function SSRadial_slowupdate()
{
	
}

function SSRadial_fastupdate()
{
  SSRadial_draw();
}