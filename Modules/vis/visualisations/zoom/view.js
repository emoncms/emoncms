  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  */

function set_daily_view()
{
  bargraph(days,3600*22,"day");
  $("#out").html(""); view = 2;
  $("#return").html("View: monthly view");
  $("#out2").html("Daily view");
  $('#axislabely').html("Energy (kWh)");
  $("#bot_out").html(bot_kwhd_text);
  $("#graph-return").show();
  $("#graph-navbar").hide();
  $('.graph-time').hide();
}

function set_monthly_view()
{
  bargraph(months.data,3600*24*20, "month");
  $("#out").html(""); view = 1;
  $("#return").html("View: annual view");
  $("#out2").html("Monthly view");
  $('#axislabely').html("Energy (kWh)");
  $("#graph-return").show();
  $("#graph-navbar").hide();
  $('.graph-time').hide();
}

function set_annual_view()
{
  bargraph(years.data,3600*24*330, "year");
  $("#out").html(""); view = 0;
  $("#out2").html("Annual view");
  $('#axislabely').html("Energy (kWh)");
  $("#graph-return").hide();
  $("#graph-navbar").hide();
  $('.graph-time').hide();
}

function set_last30days_view()
{
  bargraph(days,3600*22, "day");
  $("#out").html(""); view = 2;
  $("#return").html("View: monthly view");
  $("#out2").html("Last 30 days");
  $('#axislabely').html("Energy (kWh)");
  $("#bot_out").html(bot_kwhd_text);
  $("#graph-return").show();
  $("#graph-navbar").hide();
  $('.graph-time').hide();
}

//--------------------------------------------------------------------------
// Inst graphing
//--------------------------------------------------------------------------
function set_inst_view(day)
{
      start = day; end = day + 3600000 * 24;

      vis_feed_data();
      view = 3;
      $("#out2").html("Power view");
      $("#return").html("View: daily view");
      $('#axislabely').html("Power (Watts)");
	  $("#graph-return").show();
	  $("#graph-navbar").show();
	  $('.graph-time').show();
}
