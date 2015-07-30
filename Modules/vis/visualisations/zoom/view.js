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
  $("#return").html("View Monthly");
  $("#out2").html("Daily");
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
  $("#return").html("View Annual");
  $("#out2").html("Monthly");
  $('#axislabely').html("Energy (kWh)");
  $("#graph-return").show();
  $("#graph-navbar").hide();
  $('.graph-time').hide();
}

function set_annual_view()
{
  bargraph(years.data,3600*24*330, "year");
  $("#out").html(""); view = 0;
  $("#out2").html("Annual");
  $('#axislabely').html("Energy (kWh)");
  $("#graph-return").hide();
  $("#graph-navbar").hide();
  $('.graph-time').hide();
}

function set_last30days_view()
{
  bargraph(days,3600*22, "day");
  $("#out").html(""); view = 2;
  $("#return").html("View monthly");
  $("#out2").html("Last 30 days. Daily");
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
      $("#out2").html("Power");
      $("#return").html("View Daily");
      $('#axislabely').html("Power (Watts)");
	  $("#graph-return").show();
	  $("#graph-navbar").show();
	  $('.graph-time').show();
}
