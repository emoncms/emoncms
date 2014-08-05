function SSMultiDisplay_widgetlist()
{
  var widgets = {
    "SSMultiDisplay":
    {
      "offsetx"	   :0,"offsety":0,"width":164,"height":80,
      "menu"	   :"Widgets",
      "options"	   :["feed","altfeed","unitString","headerString", "detailString", "LcdColor","LcdDecimals"],
      "optionstype":["feed","feed","value","value","value","LcdColor","value"],
      "optionsname":[_Tr("Feed"),_Tr("Alternative Feed"),_Tr("UnitString"),_Tr("Header"),_Tr("Detail"), _Tr("LcdColor"), _Tr("LcdDecimals")],
      "optionshint":[_Tr("Feed"),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr(""),_Tr(""),]
    }
  }
  return widgets;
}

function SSMultiDisplay_init()
{
  setup_widget_canvas('SSMultiDisplay');//add init
  setup_steelseries_object('SSMultiDisplay');
}



function SSMultiDisplay_draw()
{
	$('.SSMultiDisplay').each(function(index){
	
	var feed = $(this).attr("feed");
		if (feed==undefined) feed = 0;
	var val = assoc[feed];
	//console.log("feed1="+val);
	
	var feed2 = $(this).attr("altfeed");
		if (feed2==undefined) feed2 = 0;
	var val2 = assoc[feed2];
	//console.log("feed2="+val2);
	//Revise
    if (feed==undefined) val = 3;
    if (val==undefined) val = 3;
	if (feed2==undefined) val2 = 3;
    if (val2==undefined) val2 = 3;


	SteelseriesObjects[$(this).attr("id")].setValue(val);
	SteelseriesObjects[$(this).attr("id")].setAltValue(val2);
	
    });
}

function draw_SSMultiDisplay(){

}

function SSMultiDisplay_slowupdate()
{
	
}

function SSMultiDisplay_fastupdate()
{
  SSMultiDisplay_draw();
}