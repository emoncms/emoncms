
function vis_widgetlist()
{
  var widgets = {
    "realtime": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feedid"],
      "html":""
    },

    "rawdata": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","fill","units"],
      "optionstype":["feedid","value","value"],
      "html":""
    },

    "bargraph": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feedid"],
      "html":""
    },

    "zoom": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["power","kwhd","currency","pricekwh"],
      "optionstype":["feedid","feedid","value","value"],
      "html":""
    },

    "simplezoom": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["power","kwhd"],
      "optionstype":["feedid","feedid"],
      "html":""
    },

    "histgraph": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feedid"],
      "html":""
    },

    "threshold": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","thresholdA","thresholdB"],
      "optionstype":["feedid","value","value"],
      "html":""
    },

    "orderthreshold": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","power","thresholdA","thresholdB"],
      "optionstype":["feedid","feedid","value","value"],
      "html":""
    },

    "orderbars": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feedid"],
      "html":""
    },

    "stacked": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["bottom","top"],
      "optionstype":["feedid","feedid"],
      "html":""
    },

    "stackedsolar": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["solar","consumption"],
      "optionstype":["feedid","feedid"],
      "html":""
    },

    "smoothie": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","ufac"],
      "optionstype":["feedid","value"],
      "html":""
    },

    "multigraph": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["mid"],
      "optionstype":["multigraph"],
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


