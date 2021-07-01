//get_feed_data_async is defined in /Modules/vis/visualisations/common/api.js
/*global get_feed_data_async */
//view, tooltip and parse_timepicker_time are defined in Modules/vis/visualisations/common/vis.helper.js
/*global tooltip */
/*global parse_timepicker_time */
/*global view */
//multigraphFeedlist and embed are defined in the script part of multigraph.php
/*global multigraphFeedlist */
/*global embed*/
/*eslint no-undef: "error"*/
/*eslint no-console: ["error", { allow: ["warn", "error"] }] */

var plotdata = [];
var timeWindowChanged = 0;
var ajaxAsyncXdr = [];
var eventVisFeedData;
var eventRefresh;
var showlegend = true;
var backgroundColour = "ffffff";
var datetimepicker1;
var datetimepicker2;
var datatype;
var graphtype;

function convertToPlotlist(multigraphFeedlist) {
  if (multigraphFeedlist==undefined) return;
  if (!multigraphFeedlist[0]) return;
  var plotlist = [];

  var showtag = (typeof multigraphFeedlist[0]["showtag"] !== "undefined" ? multigraphFeedlist[0]["showtag"] : true);
  showlegend = (typeof multigraphFeedlist[0]["showlegend"] === "undefined" || multigraphFeedlist[0]["showlegend"]);
  var barwidth = 1;

  view.ymin = (typeof multigraphFeedlist[0]["ymin"] !== "undefined" ? multigraphFeedlist[0]["ymin"] : null);
  view.ymax = (typeof multigraphFeedlist[0]["ymax"] !== "undefined" ? multigraphFeedlist[0]["ymax"] : null);
  view.y2min = (typeof multigraphFeedlist[0]["y2min"] !== "undefined" ? multigraphFeedlist[0]["y2min"] : null);
  view.y2max = (typeof multigraphFeedlist[0]["y2max"] !== "undefined" ? multigraphFeedlist[0]["y2max"] : null);

  backgroundColour = (typeof multigraphFeedlist[0]["backgroundColour"] !== "undefined" ? multigraphFeedlist[0]["backgroundColour"] : "ffffff");
  $("body").css("background-color","#"+backgroundColour);
  
  datatype=1;

  for (var z in multigraphFeedlist) {
    var currentFeed=multigraphFeedlist[parseInt(z,10)];
    var tag = (showtag && typeof currentFeed["tag"] !== "undefined" && currentFeed["tag"] !== "" ? currentFeed["tag"]+": " : "");
    var stacked = (typeof currentFeed["stacked"] !== "undefined" && currentFeed["stacked"]);
    barwidth = typeof currentFeed["barwidth"] === "undefined" ? 1 : currentFeed["barwidth"];

    if ( typeof currentFeed["graphtype"] === "undefined" ) {
      currentFeed["datatype"] === "1" ? graphtype="lines" : graphtype="bars";
    } else {
      graphtype=currentFeed["graphtype"];
    }

    if (currentFeed["datatype"] === "2") {
      datatype=2;
    }

    if (graphtype.substring(0, 5) === "lines") {
      plotlist[parseInt(z,10)] = {
        id: currentFeed["id"],
        selected: 1,
        plot: {
          data: null,
          label: tag + currentFeed["name"],
          stack: stacked,
          points: {
            show: true,
            radius: 0,
            lineWidth: 1, // in pixels
            fill: false
          },
          lines: {
            show: true,
            fill: currentFeed["fill"] ? (stacked ? 1.0 : 0.5) : 0.0,
            steps: graphtype === "lineswithsteps" ? true : false
          }
        }
      };
    }

    else if (graphtype === "bars") {
      plotlist[parseInt(z,10)] = {
        id: currentFeed["id"],
        selected: 1,
        plot: {
          data: null,
          label: tag + currentFeed["name"],
          stack: stacked,
          bars: {
            show: true,
            align: "center", barWidth: 3600*24*1000*barwidth, fill: currentFeed["fill"] ? (stacked ? 1.0 : 0.5) : 0.0
          }
        }
      };
    } else {
      // custom console
      console.error("ERROR: Unknown plot graphtype! Graphtype: ", currentFeed["graphtype"]);
    }

    if (currentFeed["left"] === true) {
      plotlist[parseInt(z,10)].plot.yaxis = 1;
    } else if (currentFeed["right"] === true) {
      plotlist[parseInt(z,10)].plot.yaxis = 2;
    } else {
      // custom console
      console.error("ERROR: Unknown plot alignment! Alignment setting: ", currentFeed["right"]);
    }

    // Only set the plotcolour variable if we have a value to set it with
    if (currentFeed["lineColour"]) {
      // Some browsers really want the leading "#". It works without in chrome, not in IE and opera.
      // What the hell, people?
      if (currentFeed["lineColour"].indexOf("#") === -1) {
        plotlist[parseInt(z,10)].plot.color = "#" + currentFeed["lineColour"];
      } else {
        plotlist[parseInt(z,10)].plot.color = currentFeed["lineColour"];
      }
    }

    if (currentFeed["left"] === false && currentFeed["right"] === false) {
      plotlist[parseInt(z,10)].selected = 0;
    }
  }
  return plotlist;
}



/*
 Handle Feeds
*/
function visFeedData() {
    if (typeof multigraphFeedlist !== "undefined" && typeof multigraphFeedlist[0] !== "undefined" && typeof multigraphFeedlist[0]["autorefresh"] !== "undefined") {
        var now = new Date().getTime();
        var timeWindow = view.end - view.start;
        if (now - view.end < 2000 * multigraphFeedlist[0]["autorefresh"]) {
        view.end = now;
        view.start = view.end - timeWindow;
            visFeedDataOri();
            clearTimeout(eventRefresh); // Cancel any pending event
            eventRefresh = setTimeout(visFeedData, 1000 * multigraphFeedlist[0]["autorefresh"]);
        } else {
            visFeedDataOri();
        }
    } else {
        visFeedDataOri();
    }
}


// Ignore load request spurts
function visFeedDataOri() {
  datetimepicker1.setLocalDate(new Date(view.start));
  datetimepicker2.setLocalDate(new Date(view.end));
  datetimepicker1.setEndDate(new Date(view.end));
  datetimepicker2.setStartDate(new Date(view.start));

  clearTimeout(eventVisFeedData); // Cancel any pending events
  eventVisFeedData = setTimeout(function() { visFeedDataDelayed(); }, 500);
  if (typeof multigraphFeedlist !== "undefined" && multigraphFeedlist.length !== plotdata.length) {plotdata = [];}
  plot();
}



// Load relevant feed data asynchronously
function visFeedDataDelayed() {
  var plotlist = convertToPlotlist(multigraphFeedlist);
  var npoints = 800;
  var interval = Math.round(((view.end - view.start)/npoints)/1000);

  for(var i in plotlist) {
    if (plotlist[parseInt(i,10)].selected) {
      if (!plotlist[parseInt(i,10)].plot.data) {
        var skipmissing = 0; if (multigraphFeedlist[parseInt(i,10)]["skipmissing"]) {skipmissing = 1;}

        if (typeof plotdata[parseInt(i,10)] === "undefined") {plotdata[parseInt(i,10)] = [];}

        if (typeof ajaxAsyncXdr[parseInt(i,10)] !== "undefined") {
          ajaxAsyncXdr[parseInt(i,10)].abort(); // Abort pending loads
          ajaxAsyncXdr[parseInt(i,10)]="undefined";
        }
        var context = {index:i, plotlist:plotlist[parseInt(i,10)]};
        ajaxAsyncXdr[parseInt(i,10)] = get_feed_data_async(visFeedDataCallback,context,plotlist[parseInt(i,10)].id,view.start,view.end,interval,skipmissing,1);
      }
    }
  }
}

//load feed data to multigraph plot
function visFeedDataCallback(context,data) {
  var i = context["index"];
  context["plotlist"].plot.data = data;
  if (context["plotlist"].plot.data) {
    plotdata[parseInt(i,10)] = context["plotlist"].plot;
  }
  plot();
}

function plot() {
  $.plot($("#graph"), plotdata, {
    canvas: true,
    grid: { show: true, hoverable: true, clickable: true },
    xaxis: { mode: "time", timezone: "browser", min: view.start, max: view.end },
    selection: { mode: "x" },
    legend: { show: showlegend, position: "nw", toggle: true },
    toggle: { scale: "visible" },
    touch: { pan: "x", scale: "x" },
    yaxis: { min: view.ymin , max: view.ymax},
    y2axis: { min: view.y2min , max: view.y2max}
  });
}


function multigraphInit(element) {
  // Get start and end time of multigraph view
  // end time and timewindow is stored in the first multigraphFeedlist item.
  // start time is calculated from end - timewindow
  plotdata = [];
  var timeWindow = (3600000*24.0*7);
  var now = new Date().getTime();
  view.start = now - timeWindow;
  view.end = now;

  if (typeof multigraphFeedlist !== "undefined" && typeof multigraphFeedlist[0] !== "undefined") {
    view.end = multigraphFeedlist[0].end;
    if (view.end === 0) {view.end = now;}
    if (multigraphFeedlist[0].timeWindow) {
        view.start = view.end - multigraphFeedlist[0].timeWindow;
    }
  }

  var out =
    "<div id='graph_bound' style='height:400px; width:100%; position:relative; '>"+
      "<div id='graph'></div>"+

      "<div id='graph-buttons-timemanual' style='position:absolute; top:15px; right:35px; opacity:0.5; display: none;'>"+
        "<div class='input-prepend input-append'>"+
            "<span class='add-on'>Select time window</span>"+

            "<span class='add-on'>Start:</span>"+
            "<span id='datetimepicker1'>"+
                "<input id='timewindow-start' data-format='dd/MM/yyyy hh:mm:ss' type='text' style='width:140px'/>"+
                "<span class='add-on'><i data-time-icon='icon-time' data-date-icon='icon-calendar'></i></span>"+
            "</span> "+

            "<span class='add-on'>End:</span>"+
            "<span id='datetimepicker2'>"+
                "<input id='timewindow-end' data-format='dd/MM/yyyy hh:mm:ss' type='text' style='width:140px'/>"+
                "<span class='add-on'><i data-time-icon='icon-time' data-date-icon='icon-calendar'></i></span>"+
            "</span> "+

            "<button class='btn graph-timewindow-set' type='button'><i class='icon-ok'></i></button>"+
        "</div> "+
      "</div>"+

      "<div id='graph-buttons' style='position:absolute; top:15px; right:35px; opacity:0.5; display: none;'>"+
        "<div id='graph-buttons-normal'>"+
            "<div class='input-prepend input-append' id='graph-tooltip' style='margin:0'>"+
             "<span class='add-on'>Tooltip:</span>"+
             "<span class='add-on'><input id='enableTooltip' type='checkbox' checked ></span>"+
            "</div> "+

            "<div class='btn-group'>"+
             "<button class='btn graph-time' type='button' time='1'>D</button>"+
             "<button class='btn graph-time' type='button' time='7'>W</button>"+
             "<button class='btn graph-time' type='button' time='30'>M</button>"+
             "<button class='btn graph-time' type='button' time='365'>Y</button>"+
             "<button class='btn graph-timewindow' type='button'><i class='icon-resize-horizontal'></i></button></div>"+

            "<div class='btn-group' id='graph-navbar' style='display: none;'>"+
             "<button class='btn graph-nav' id='zoomin'>+</button>"+
             "<button class='btn graph-nav' id='zoomout'>-</button>"+
             "<button class='btn graph-nav' id='left'><</button>"+
             "<button class='btn graph-nav' id='right'>></button></div>"+

        "</div>"+
      "</div>"+
    "</div>";
  $(element).html(out);

  // Tool tip
  var previousPoint = null;
  var previousSeries = null;
  $(element).bind("plothover", function (event, pos, item) {
    //$("#x").text(pos.x.toFixed(2));
    //$("#y").text(pos.y.toFixed(2));

    if ($("#enableTooltip:checked").length > 0) {
      if (item) {
        if (previousPoint !== item.dataIndex || previousSeries !== item.seriesIndex) {
          previousPoint = item.dataIndex;
          previousSeries = item.seriesIndex;

          $("#tooltip").remove();
          var x = item.datapoint[0].toFixed(2);
          var y;
          var options;
          if (typeof(item.datapoint[2])==="undefined") {
            y=Number(item.datapoint[1].toFixed(2));
          } else {
            y=Number((item.datapoint[1]-item.datapoint[2]).toFixed(2));
          }

          if (datatype === 1) {
            options = { month:"short", day:"2-digit", hour:"2-digit", minute:"2-digit", second:"2-digit"};
          } else {
            options = { month:"short", day:"2-digit"};
          }

          var formattedTime=new Date(parseInt(x,10));

          // I'd like to eventually add colour hinting to the background of the tooltop.
          // This is why showTooltip has the bgColour parameter.
          tooltip(item.pageX, item.pageY, item.series.label + " at " + formattedTime.toLocaleDateString("en-GB",options) + " = " + y, "#DDDDDD");
        }
      } else {
        $("#tooltip").remove();
        previousPoint = null;
      }
    }
  });

  function visResize() {
    var width = $("#graph_bound").width();
    $("#graph").width(width);
    var height = width * 0.5;

    if (embed) {
        $("#graph").height($(window).height());
    } else {
        $("#graph").height(height);
    }
    plot();
  }

  visResize();

  $(document).on("window.resized hidden.sidebar.collapse shown.sidebar.collapse",visResize);

  // Graph selections
  $("#graph").bind("plotselected", function (event, ranges) {
     view.start = ranges.xaxis.from;
     view.end = ranges.xaxis.to;
     visFeedData();
  });

  // Navigation actions
  $("#zoomout").click(function () {view.zoomout(); visFeedData();});
  $("#zoomin").click(function () {view.zoomin(); visFeedData();});
  $("#right").click(function () {view.panright(); visFeedData();});
  $("#left").click(function () {view.panleft(); visFeedData();});
  $(".graph-time").click(function () {view.timewindow($(this).attr("time")); visFeedData();});

  $(".graph-timewindow").click(function () {
     $("#graph-buttons-timemanual").show();
     $("#graph-buttons-normal").hide();
  });

  $(".graph-timewindow-set").click(function () {
    var timewindowStart = parse_timepicker_time($("#timewindow-start").val());
    var timewindowEnd = parse_timepicker_time($("#timewindow-end").val());
    if (!timewindowStart) {alert("Please enter a valid start date."); return false; }
    if (!timewindowEnd) {alert("Please enter a valid end date."); return false; }
    if (timewindowStart>=timewindowEnd) {alert("Start date must be further back in time than end date."); return false; }

    $("#graph-buttons-timemanual").hide();
    $("#graph-buttons-normal").show();
    view.start = timewindowStart * 1000;
    view.end = timewindowEnd *1000;
    visFeedData();
  });

  $("#datetimepicker1").datetimepicker({
    language: "en-EN"
  });

  $("#datetimepicker2").datetimepicker({
    language: "en-EN",
    useCurrent: false //Important! See issue #1075
  });

  $("#datetimepicker1").on("changeDate", function (e) {
    if (view.datetimepicker_previous == null) {view.datetimepicker_previous = view.start;}
    if (Math.abs(view.datetimepicker_previous - e.date.getTime()) > 1000*60*60*24)
    {
        var d = new Date(e.date.getFullYear(), e.date.getMonth(), e.date.getDate());
        d.setTime( d.getTime() - e.date.getTimezoneOffset()*60*1000 );
        out = d;
		$("#datetimepicker1").data("datetimepicker").setDate(out);
    } else {
        out = e.date;
    }
    view.datetimepicker_previous = e.date.getTime();

    $("#datetimepicker2").data("datetimepicker").setStartDate(out);
  });

  $("#datetimepicker2").on("changeDate", function (e) {
    if (view.datetimepicker_previous === null) {view.datetimepicker_previous = view.end;}
    if (Math.abs(view.datetimepicker_previous - e.date.getTime()) > 1000*60*60*24)
    {
        var d = new Date(e.date.getFullYear(), e.date.getMonth(), e.date.getDate());
        d.setTime( d.getTime() - e.date.getTimezoneOffset()*60*1000 );
        out = d;
		$("#datetimepicker2").data("datetimepicker").setDate(out);
    } else {
        out = e.date;
    }
    view.datetimepicker_previous = e.date.getTime();

    $("#datetimepicker1").data("datetimepicker").setEndDate(out);
  });

  datetimepicker1 = $("#datetimepicker1").data("datetimepicker");
  datetimepicker2 = $("#datetimepicker2").data("datetimepicker");

  // Navigation and zooming buttons for mouse and touch
  $("#graph").mouseenter(function() {
    $("#graph-navbar").show();
    $("#graph-tooltip").show();
    $("#graph-buttons").stop().fadeIn();
    $("#stats").stop().fadeIn();
  });
  $("#graph_bound").mouseleave(function() {
    $("#graph-buttons").stop().fadeOut();
    $("#stats").stop().fadeOut();
  });
  $("#graph").bind("touchstarted", function (event, pos) {
    $("#graph-navbar").hide();
    $("#graph-tooltip").hide();
    $("#graph-buttons").stop().fadeOut();
    $("#stats").stop().fadeOut();
  });
  $("#graph").bind("touchended", function (event, ranges) {
    $("#graph-buttons").stop().fadeIn();
    $("#stats").stop().fadeIn();
    view.start = ranges.xaxis.from;
    view.end = ranges.xaxis.to;
    visFeedData();
  });
}
