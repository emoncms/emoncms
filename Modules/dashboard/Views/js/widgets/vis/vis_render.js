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
      "options":["feedid","fill","units"],
      "optionstype":["feedid","value","value"],
      "optionsname":[_Tr("Feed"),_Tr("Fill"),_Tr("Units")],
      "optionshint":[_Tr("Feed source"),_Tr("Fill value"),_Tr("Units to show")],       
      "html":""
    },

    "bargraph": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feedid"],
      "optionsname":[_Tr("Feed")],
      "optionshint":[_Tr("Feed source")],       
      "html":""
    },
    
    "timestoredaily": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","units"],
      "optionstype":["feedid","value"],
      "optionsname":[_Tr("Feed"),_Tr("Units")],
      "optionshint":[_Tr("Feed source"),_Tr("Units to show")],       
      "html":""
    },

    "zoom": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["power","kwhd","currency","currency_after_val","pricekwh"],
      "optionstype":["feedid","feedid","value","value","value"],
      "optionsname":[_Tr("Power"),_Tr("kwhd"),_Tr("Currency"),_Tr("Currency position"),_Tr("Kwh price")],
      "optionshint":[_Tr("Power to show"),_Tr("kwhd source"),_Tr("Currency to show"),_Tr("0 = before value, 1 = after value"),_Tr("Set kwh price")],
      "html":""
    },

    "simplezoom": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["power","kwhd"],
      "optionstype":["feedid","feedid"],
      "optionsname":[_Tr("Power"),_Tr("kwhd")],
      "optionshint":[_Tr("Power to show"),_Tr("kwhd source")], 
      "html":""
    },

    "histgraph": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feedid"],
      "optionsname":[_Tr("Feed")],
      "optionshint":[_Tr("Feed source")], 
      "html":""
    },

    "threshold": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","thresholdA","thresholdB"],
      "optionstype":["feedid","value","value"],
      "optionsname":[_Tr("Feed"),_Tr("Threshold A"),_Tr("Threshold B")],
      "optionshint":[_Tr("Feed source"),_Tr("Threshold A used"),_Tr("Threshold B used")], 
      "html":""
    },

    "orderthreshold": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","power","thresholdA","thresholdB"],
      "optionstype":["feedid","feedid","value","value"],
      "optionsname":[_Tr("Feed"),_Tr("Power"),_Tr("Threshold A"),_Tr("Threshold B")],
      "optionshint":[_Tr("Feed source"),_Tr("Power"),_Tr("Threshold A used"),_Tr("Threshold B used")],       
      "html":""
    },

    "orderbars": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feedid"],
      "optionsname":[_Tr("Feed")],
      "optionshint":[_Tr("Feed source")],       
      "html":""
    },

    "stacked": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["bottom","top"],
      "optionstype":["feedid","feedid"],
      "optionsname":[_Tr("Bottom"),_Tr("Top")],
      "optionshint":[_Tr("Bottom feed value"),_Tr("Top feed value")],       
      "html":""
    },

    "stackedsolar": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["solar","consumption"],
      "optionstype":["feedid","feedid"],
      "optionsname":[_Tr("Solar"),_Tr("Consumption")],
      "optionshint":[_Tr("Solar feed value"),_Tr("Consumption feed value")], 
      "html":""
    },

    "smoothie": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","ufac"],
      "optionstype":["feedid","value"],
      "optionsname":[_Tr("Feed"),_Tr("Ufac")],
      "optionshint":[_Tr("Feed source"),_Tr("Ufac value")],       
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

}

function vis_fastupdate()
{

}


