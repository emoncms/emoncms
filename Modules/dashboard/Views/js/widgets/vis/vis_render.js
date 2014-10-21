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

function vis_widgetlist()
{
  var widgets = {
    "realtime":
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feedid"],
      "optionsname":[_Tr("Feed")],
      "optionshint":[_Tr("Feed source")],
      "html":""
    },

    "rawdata":
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",  
      "options":["feedid","colour","units","dp","scale","fill"],
      "optionstype":["feedid","colour_picker","value","value","value","value"],
      "optionsname":[_Tr("Feed"),_Tr("Colour"),_Tr("units"),_Tr("dp"),_Tr("scale"),_Tr("Fill")],
      "optionshint":[_Tr("Feed source"),_Tr("Line colour in hex. Blank is use default."),_Tr("units"),_Tr("Decimal points"),_Tr("Scale by"),_Tr("Fill value")],
      
      "html":""
    },
    
    "bargraph":
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","colour","interval","units","dp","scale","delta"],
      "optionstype":["feedid","colour_picker","value","value","value","value","value"],
      "optionsname":[_Tr("Feed"),_Tr("Colour"),_Tr("interval"),_Tr("units"),_Tr("dp"),_Tr("scale"),_Tr("delta")],
      "optionshint":[_Tr("Feed source"),_Tr("Line colour in hex. Blank is use default."),_Tr("Interval (seconds)-you can set \"d\" for day, \"m\" for month, or \"y\" for year"),_Tr("Units"),_Tr("Decimal points"),_Tr("Scale by"),_Tr("St to \"1\" to show diff between each bar. It displays an ever-increasing Wh feed as a daily\/montly\/yeayly kWh feed (set interval to \"d\", or \"m\", or \"y\")")],
      "html":""
    },

    "multigraph":
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["mid"],
      "optionstype":["multigraph"],
      "optionsname":[_Tr("Mid")],
      "optionshint":[_Tr("Mid value")],
      "html":""
    }
  }

  // Gets multigraphs from vis_widget.php public multigraphs variable

  return widgets;
}

function vis_init()
{
  vis_draw();
}

function vis_draw()
{
  var vislist = vis_widgetlist();

  var visclasslist = '';
  for (z in vislist) { visclasslist += '.'+z+','; }

  visclasslist = visclasslist.slice(0, -1)

  $(visclasslist).each(function()
  {
    var id = $(this).attr("id");
    var feed = $(this).attr("feed") || 0;
    var width = $(this).width();
    var height = $(this).height();

    var attrstring = "";
    var target = $(this).get(0);
    var l = target.attributes.length
    for (var i=0; i<l; i++)
    {
      var attr = target.attributes[i].name;
      if (attr!="id" && attr!="class" && attr!="style")
      {
        attrstring += "&"+attr+"="+target.attributes[i].value;
      }
    }

    var apikey_string = "";
    if (apikey) apikey_string = "&apikey="+apikey;
    if (!$(this).html() || reloadiframe==id || apikey){
      $(this).html('<iframe style="width:'+width+'px; height:'+height+'px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+path+'vis/'+$(this).attr("class")+'?embed=1'+attrstring+apikey_string+'"></iframe>');
    }

    var iframe = $(this).children('iframe');
    iframe.width(width);
    iframe.height(height);

  });
reloadiframe = 0;
}

function vis_slowupdate()
{
  // Are these supposed to be empty?
}

function vis_fastupdate()
{

}



