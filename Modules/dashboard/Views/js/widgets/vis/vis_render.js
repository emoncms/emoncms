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
      "optionsname":[LANG_JS["Feed"]],
      "optionshint":[LANG_JS["Feed source"]], 
      "html":""
    },

    "rawdata": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","fill","units"],
      "optionstype":["feedid","value","value"],
      "optionsname":[LANG_JS["Feed"],LANG_JS["Fill"],LANG_JS["Units"]],
      "optionshint":[LANG_JS["Feed source"],LANG_JS["Fill value"],LANG_JS["Units to show"]],       
      "html":""
    },

    "bargraph": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feedid"],
      "optionsname":[LANG_JS["Feed"]],
      "optionshint":[LANG_JS["Feed source"]],       
      "html":""
    },

    "zoom": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["power","kwhd","currency","pricekwh"],
      "optionstype":["feedid","feedid","value","value"],
      "optionsname":[LANG_JS["Power"],LANG_JS["kwhd"],LANG_JS["Currency"],LANG_JS["Kwh price"]],
      "optionshint":[LANG_JS["Power to show"],LANG_JS["kwhd source"],LANG_JS["Currency to show"],LANG_JS["Set kwh price"]], 
      "html":""
    },

    "simplezoom": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["power","kwhd"],
      "optionstype":["feedid","feedid"],
      "optionsname":[LANG_JS["Power"],LANG_JS["kwhd"]],
      "optionshint":[LANG_JS["Power to show"],LANG_JS["kwhd source"]], 
      "html":""
    },

    "histgraph": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feedid"],
      "optionsname":[LANG_JS["Feed"]],
      "optionshint":[LANG_JS["Feed source"]], 
      "html":""
    },

    "threshold": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","thresholdA","thresholdB"],
      "optionstype":["feedid","value","value"],
      "optionsname":[LANG_JS["Feed"],LANG_JS["Threshold A"],LANG_JS["Threshold B"]],
      "optionshint":[LANG_JS["Feed source"],LANG_JS["Threshold A used"],LANG_JS["Threshold B used"]], 
      "html":""
    },

    "orderthreshold": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","power","thresholdA","thresholdB"],
      "optionstype":["feedid","feedid","value","value"],
      "optionsname":[LANG_JS["Feed"],LANG_JS["Power"],LANG_JS["Threshold A"],LANG_JS["Threshold B"]],
      "optionshint":[LANG_JS["Feed source"],LANG_JS["Power"],LANG_JS["Threshold A used"],LANG_JS["Threshold B used"]],       
      "html":""
    },

    "orderbars": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feedid"],
      "optionsname":[LANG_JS["Feed"]],
      "optionshint":[LANG_JS["Feed source"]],       
      "html":""
    },

    "stacked": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["bottom","top"],
      "optionstype":["feedid","feedid"],
      "optionsname":[LANG_JS["Bottom"],LANG_JS["Top"]],
      "optionshint":[LANG_JS["Bottom feed value"],LANG_JS["Top feed value"]],       
      "html":""
    },

    "stackedsolar": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["solar","consumption"],
      "optionstype":["feedid","feedid"],
      "optionsname":[LANG_JS["Solar"],LANG_JS["Consumption"]],
      "optionshint":[LANG_JS["Solar feed value"],LANG_JS["Consumption feed value"]], 
      "html":""
    },

    "smoothie": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","ufac"],
      "optionstype":["feedid","value"],
      "optionsname":[LANG_JS["Feed"],LANG_JS["Ufac"]],
      "optionshint":[LANG_JS["Feed source"],LANG_JS["Ufac value"]],       
      "html":""
    },

    "multigraph": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["mid"],
      "optionstype":["multigraph"],
      "optionsname":[LANG_JS["Mid"]],
      "optionshint":[LANG_JS["Mid value"]],       
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


