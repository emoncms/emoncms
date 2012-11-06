
function vis_widgetlist()
{
  var widgets = {
    "realtime": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feed-inst"],
      "html":""
    },

    "rawdata": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","fill","units"],
      "optionstype":["feed-inst","value","value"],
      "html":""
    },

    "bargraph": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feed-daily"],
      "html":""
    },

    "zoom": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["power","kwhd","currency","pricekwh"],
      "optionstype":["feed-inst","feed-daily","value","value"],
      "html":""
    },

    "simplezoom": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["power","kwhd"],
      "optionstype":["feed-inst","feed-daily"],
      "html":""
    },

    "histgraph": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feed-hist"],
      "html":""
    },

    "threshold": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","thresholdA","thresholdB"],
      "optionstype":["feed-hist","value","value"],
      "html":""
    },

    "orderthreshold": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","power","thresholdA","thresholdB"],
      "optionstype":["feed-hist","feed-inst","value","value"],
      "html":""
    },

    "orderbars": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid"],
      "optionstype":["feed-daily"],
      "html":""
    },

    "stacked": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["kwhdA","kwhdB"],
      "optionstype":["feed-daily","feed-daily"],
      "html":""
    },

    "multigraph": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["clear","showoptions"],
      "optionstype":["value","value"],
      "html":""
    },

    "smoothie": 
    {
      "offsetx":0,"offsety":0,"width":400,"height":300,
      "menu":"Visualisations",
      "options":["feedid","ufac"],
      "optionstype":["feed-inst","value"],
      "html":""
    }
  }
  return widgets;
}

function vis_init()
{
  vis_draw();
}

function vis_draw()
{
$('.rawdata,.bargraph,.zoom,.realtime,.simplezoom,.threshold,.orderthreshold,.orderbars,.stacked,.multigraph,.histgraph,.smoothie').each(function(index)
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
        console.log(attr);
        attrstring += "&"+attr+"="+target.attributes[i].value;
      }
    }

    if (!$(this).html() || reloadiframe==id){
      $(this).html('<iframe style="width:'+width+'px; height:'+height+'px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+path+'vis/'+$(this).attr("class")+'?apikey='+apikey_read+'&embed=1'+attrstring+'"></iframe>');
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


