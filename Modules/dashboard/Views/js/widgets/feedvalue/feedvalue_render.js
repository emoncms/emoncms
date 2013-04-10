
function feedvalue_widgetlist()
{
  var widgets = {
    "feedvalue": 
    {
      "offsetx":-40,"offsety":-30,"width":80,"height":60,
      "menu":"Widgets",
      "options":["feedname","units"],
      "optionstype":["feed","value"],
      "optionsname":["Feed","Value"],
      "optionshint":["Feed value","Value shown"] 
    }
  }
  return widgets;
}

function feedvalue_init()
{
  feedvalue_draw();
}

function feedvalue_draw()
{
  $('.feedvalue').each(function(index)
  {
    var feed = $(this).attr("feedname");
    if (feed==undefined) feed = $(this).attr("feed");

    var units = $(this).attr("units");
    var val = assoc[feed];

    if (feed==undefined) val = 0;
    if (units==undefined) units = '';
    if (val==undefined) val = 0;

    if (val < 100)
      val = val.toFixed(1);
    else
      val = val.toFixed(0);

    $(this).html(val+units);
  });
}

function feedvalue_slowupdate()
{
  feedvalue_draw();
}

function feedvalue_fastupdate()
{
}


