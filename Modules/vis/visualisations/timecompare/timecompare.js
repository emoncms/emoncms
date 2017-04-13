var plotdata = [];
var event_vis_feed_data;
var ajaxAsyncXdr = [];
var compare_unit = 0;
var xaxis_format = "";
  
function create_plotlist(feedid, fill, depth) {
  var plotlist = [];
  var unit = "";
  var unit_plural = "s";

  switch(compare_unit) {
    case 1000*60*60*24:
      unit = "Day";
      xaxis_format = "%H:%M";
      break;
    case 1000*60*60*24*7:
      unit = "Week";
      xaxis_format = "%a<br>%H:%M";
      break;
    case 1000*60*60*24*28:
      unit = "Month";
      xaxis_format = "%a<br>%H:%M";
      break;
    case 1000*60*60*24*365:
      unit = "Year";
      xaxis_format = "%m/%d<br>%a<br>%H:%M";
      break;
    default:
      unit = compare_unit / 1000 + " Seconds";
      unit_plural = "";
      xaxis_format = "%H:%M";
  }

  for (var i = 0; i < depth; i++) {
    var cur_depth = depth - i - 1;
    var label;

    if (0 == cur_depth) {
      label = "Current";
    } else if (1 == cur_depth) {
      label = cur_depth + " " + unit + " Prior";
    } else {
      label = cur_depth + " " + unit + unit_plural + " Prior";
    }

    plotlist[i] = {
      id: feedid,
      selected: 1,
      depth: cur_depth,
      plot: {
        adj: compare_unit * cur_depth,
        data: null,
        label: label,
        yaxis: 1,
        lines: {
          show: true,
          fill: fill
        }
      }

    };
  }

  return plotlist;
}

/*
 Handle Feeds
*/

// Ignore load request spurts
function vis_feed_data() {
  clearTimeout(event_vis_feed_data); // Cancel any pending events
  event_vis_feed_data = setTimeout(function() { vis_feed_data_delayed(); }, 500);
  plot();
}
  
// Load relevant feed data asynchronously
function vis_feed_data_delayed() {
  var plotlist;
  fill = fill > 0 ? true : false;
  if (depth <= 0) depth = 3;
  if (npoints <= 0) npoints = 800;
	
  plotlist = create_plotlist(feedid, fill, depth);
  for(var i in plotlist) {
    if (plotlist[i].selected) {
      if (!plotlist[i].plot.data) {
        var skipmissing = false;
        var plot_start = view.start - (compare_unit * plotlist[i].depth); // Need to take into account leapyear
        var plot_end = view.end - (compare_unit * plotlist[i].depth);
        interval = Math.round((view.end - view.start)/(npoints * 1000));

        if (plotdata[i] === undefined) plotdata[i] = [];

        if (typeof ajaxAsyncXdr[i] !== 'undefined') { 
          ajaxAsyncXdr[i].abort(); // Abort pending loads
          ajaxAsyncXdr[i] = undefined;
        }
        var context = {index:i, plotlist:plotlist[i]}; 
        ajaxAsyncXdr[i] = get_feed_data_async(vis_feed_data_callback, context, plotlist[i].id, plot_start, plot_end, interval,skipmissing, 1);
      }
    }
  }
}
  
// Asynchronous callback for loading data
function vis_feed_data_callback(context, data) {
  var i = context['index'];
  var depth = context['plotlist'].depth;

  for (var d in data) {
    data[d][0] = data[d][0] + (compare_unit * depth); // Adjust the old data to be visible on the current graph
  }

  context['plotlist'].plot.data = data;
  if (context['plotlist'].plot.data) {
    plotdata[i] = context['plotlist'].plot;
  }
  plot();
}

/*
 Graphing Functions
*/

function plot() {
  $.plot($("#graph"), plotdata, {
    canvas: true,
    grid: { show: true, hoverable: true, clickable: true },
    xaxis: { mode: "time", timezone: "browser", timeformat: xaxis_format, min: view.start, max: view.end },
    selection: { mode: "x" },
    legend: { position: "nw", toggle: true },
    toggle: { scale: "visible" },
    touch: { pan: "x", scale: "x" }
  });
}

function timecompare_init(element) {
  // Get start and end time of view based on default scale and current time
  var now = new Date().getTime();

  plotdata = [];
  compare_unit = (1000*60*60*initzoom);
  view.start = now - compare_unit;
  view.end = now;

  var out =
    "<div id='graph_bound' style='height:400px; width:100%; position:relative; '>"+
      "<div id='graph'></div>"+
      "<div id='graph-buttons' style='position:absolute; top:20px; right:30px; opacity:0.5; display: none;'>"+
        "<div class='input-prepend input-append' id='graph-tooltip' style='margin:0'>"+
        "<span class='add-on'>Tooltip:</span>"+
        "<span class='add-on'><input id='enableTooltip' type='checkbox' checked ></span>"+
        "</div> "+

        "<div class='btn-group'>"+
        "<button class='btn graph-time' type='button' time='1'>D</button>"+
        "<button class='btn graph-time' type='button' time='7'>W</button>"+
        "<button class='btn graph-time' type='button' time='28'>M</button>"+
        "<button class='btn graph-time' type='button' time='365'>Y</button></div>"+

        "<div class='btn-group' id='graph-navbar' style='display: none;'>"+
        "<button class='btn graph-nav' id='zoomin'>+</button>"+
        "<button class='btn graph-nav' id='zoomout'>-</button>"+
        "<button class='btn graph-nav' id='left'><</button>"+
        "<button class='btn graph-nav' id='right'>></button></div>"+
      "</div>"+
    "</div>"
  ;
  $(element).html(out);

  // Tool tip
  var previousPoint = null;
  $(element).bind("plothover", function (event, pos, item) {
    //$("#x").text(pos.x.toFixed(2));
    //$("#y").text(pos.y.toFixed(2));

    if ($("#enableTooltip:checked").length > 0) {
      if (item) {
        if (previousPoint != item.dataIndex) {
          previousPoint = item.dataIndex;

          $("#tooltip").remove();
          var x = item.datapoint[0].toFixed(2);
          var y = item.datapoint[1].toFixed(2);

          var pointDate = new Date(parseInt(x) - parseInt(item.series.adj));
          var tipText = $.plot.formatDate(pointDate, y + "<br>%a %b %d %Y<br>%H:%M:%S");

          tooltip(item.pageX, item.pageY, tipText, "#DDDDDD");
        }
      } else {
        $("#tooltip").remove();
        previousPoint = null;
      }
    }
  });

  $('#graph').width($('#graph_bound').width());
  $('#graph').height($('#graph_bound').height());
  if (embed) $('#graph').height($(window).height());

  $(window).resize(function() {
    $('#graph').width($('#graph_bound').width());
    if (embed) $('#graph').height($(window).height());
    plot();
  });

  // Graph selections
  $("#graph").bind("plotselected", function (event, ranges) {
     view.start = ranges.xaxis.from; 
     view.end = ranges.xaxis.to;
     vis_feed_data();
  });

  // Navigation actions
  $("#zoomout").click(function () {view.zoomout(); vis_feed_data();});
  $("#zoomin").click(function () {view.zoomin(); vis_feed_data();});
  $('#right').click(function () {view.panright(); vis_feed_data();});
  $('#left').click(function () {view.panleft(); vis_feed_data();});
  $('.graph-time').click(function () {
    view.timewindow($(this).attr("time"));
    compare_unit = view.end - view.start;
    vis_feed_data();
  });

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
    vis_feed_data();
  });
}
