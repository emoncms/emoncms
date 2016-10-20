  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  */

//--------------------------------------------------------------
// Bar graph
//--------------------------------------------------------------
function bargraph(data,barwidth,mode)
{
  $.plot($("#placeholder"), [{color: "#0096ff", data: data}],
  {
    canvas: true,
    bars: { show: true,align: "center",barWidth: (barwidth*1000),fill: true },
    grid: { show: true, hoverable: true, clickable: true },
    xaxis: { mode: "time", timezone: "browser", minTickSize: [1, mode], tickLength: 1}
  });
}

function instgraph(data)
{
  $.plot($("#placeholder"), [{color: "#0096ff", data: data}],
  {
    lines: { show: true, fill: true },
    grid: { show: true, hoverable: true },
    xaxis: { mode: "time" , timezone: "browser", min: start, max: end },
    selection: { mode: "x"}
  });
}

function plotHistogram(data, start, end)
{
  barwidth = 50;
  $.plot(placeholder,[
  {
      color: "#0096ff",
      data: data ,				//data
      //lines: { show: true, fill: true }		//style
      bars: {
        show: true,
        align: "center",
        barWidth: barwidth,
        fill: true
      }
  }], {
    xaxis: { mode: null },
    grid: { show: true, hoverable: true, clickable: true },
    selection: { mode: "x" }
  } );
}







