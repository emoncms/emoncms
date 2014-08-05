function SSSingleDisplay_widgetlist()
{
  var widgets = {
    "SSSingleDisplay":
    {
      "offsetx":0,"offsety":0,"width":164,"height":80,
      "menu":"Widgets",
      "options"		:["feed","LcdColor", "unit" , "lcdDecimals"	,"headerString"],
      "optionstype" :["feed","LcdColor", "value", "value"		,"value"],
      "optionsname" :[ _Tr("Feed"), _Tr("LcdColor"),_Tr("Units"),_Tr("lcdDecimals"),_Tr("HeaderString")],
      "optionshint" :[_Tr("Feed") , _Tr("LcdColor"),_Tr("Units(Use blank spaces to centre number)"),_Tr("Decimals Points Between 0-20"),_Tr("Header String")] 
    }
  }
  return widgets;
}

function SSSingleDisplay_init()
{
  setup_widget_canvas('SSSingleDisplay');//add init
  setup_steelseries_object('SSSingleDisplay');
}



function SSSingleDisplay_draw()
{
	$('.SSSingleDisplay').each(function(index){
	
		var feed = $(this).attr("feed");
		if (feed==undefined) feed = $(this).attr("feed");
		var val = assoc[feed];
		
		if (feed==undefined) val = 0;
		if (val==undefined) val = 0;
	
	SteelseriesObjects[$(this).attr("id")].setValue(val);
		//
    });
}

function draw_SSSingleDisplay(){

}

function SSSingleDisplay_slowupdate()
{
	
}

function SSSingleDisplay_fastupdate()
{
  SSSingleDisplay_draw();
}