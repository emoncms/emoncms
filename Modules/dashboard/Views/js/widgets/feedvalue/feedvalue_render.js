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

function feedvalue_widgetlist()
{
  var widgets = {
    "feedvalue":
    {
      "offsetx":-40,"offsety":-30,"width":80,"height":60,
      "menu":"Widgets",
      "options":["feedname","units","decimals"],
      "optionstype":["feed","value","decimals"],
      "optionsname":[_Tr("Feed"),_Tr("Units"),_Tr("Decimals")],
      "optionshint":[_Tr("Feed value"),_Tr("Units to show"),_Tr("Decimals to show (-1 for automatic)")]
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
    var decimals = $(this).attr("decimals");

    if (feed==undefined) val = 0;
    if (units==undefined) units = '';
    if (val==undefined) val = 0;
    if (decimals==undefined) decimals = -1;
    
    if (isNaN(val))  val = 0;
    
    if (decimals==<0)
    {

      if (val>=100)
          val = (val*1).toFixed(0);
      else if (val>=10)
          val = (val*1).toFixed(1);
      else if (val<=-100)
          val = (val*1).toFixed(0);
      else if (val<=-10)
          val = (val*1).toFixed(1);
      else
          val = (val*1).toFixed(2);
    }
    else 
    {
      val = val.toFixed(decimals);
    }

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


