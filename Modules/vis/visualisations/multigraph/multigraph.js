var plotdata = [];
var timeWindowChanged = 0;
var ajaxAsyncXdr = [];
var event_vis_feed_data;
var showlegend = true;
  
function convert_to_plotlist(multigraph_feedlist) {
  var plotlist = [];
  var showtag = (multigraph_feedlist[0]['showtag'] != undefined ? multigraph_feedlist[0]['showtag'] : true);
  showlegend = (multigraph_feedlist[0]['showlegend']==undefined || multigraph_feedlist[0]['showlegend']);
  for (z in multigraph_feedlist) {
    var tag = (showtag && multigraph_feedlist[z]['tag']!=undefined && multigraph_feedlist[z]['tag']!="" ? multigraph_feedlist[z]['tag']+": " : "");
    var stacked = (multigraph_feedlist[z]['stacked']!=undefined && multigraph_feedlist[z]['stacked']);
    if (multigraph_feedlist[z]['datatype']==1) {
      plotlist[z] = {
        id: multigraph_feedlist[z]['id'],
        selected: 1,
        plot: {
          data: null,
          label: tag + multigraph_feedlist[z]['name'],
 		  stack: stacked,
          points: { 
            show: true,
            radius: 0,
            lineWidth: 1, // in pixels
            fill: false
          },
          lines: {
            show: true,
            fill: multigraph_feedlist[z]['fill'] ? (stacked ? 1.0 : 0.5) : 0.0
          }
        }
      };
    }

    else if (multigraph_feedlist[z]['datatype']==2) {
      plotlist[z] = {
        id: multigraph_feedlist[z]['id'],
        selected: 1,
        plot: {
          data: null,
          label: tag + multigraph_feedlist[z]['name'],
		  stack: stacked,
          bars: {
            show: true,
            align: "left", barWidth: 3600*24*1000,
			fill: multigraph_feedlist[z]['fill'] ? (stacked ? 1.0 : 0.5) : 0.0
          }
        }
      };
    } else {
      console.log("ERROR: Unknown plot datatype! Datatype: ", multigraph_feedlist[z]['datatype']);
    }

    if (multigraph_feedlist[z]['left']==true) {
      plotlist[z].plot.yaxis = 1;
    } else if (multigraph_feedlist[z]['right']==true) {
      plotlist[z].plot.yaxis = 2;
    } else {
      console.log("ERROR: Unknown plot alignment! Alignment setting: ", multigraph_feedlist[z]['right']);
    }

    // Only set the plotcolour variable if we have a value to set it with
    if (multigraph_feedlist[z]["lineColour"]) {
      // Some browsers really want the leading "#". It works without in chrome, not in IE and opera.
      // What the hell, people?
      if (multigraph_feedlist[z]["lineColour"].indexOf("#") == -1) {
        plotlist[z].plot.color = "#" + multigraph_feedlist[z]["lineColour"];
      } else {
        plotlist[z].plot.color = multigraph_feedlist[z]["lineColour"];
      }
    }

    if (multigraph_feedlist[z]['left']==false && multigraph_feedlist[z]['right']==false) {
      plotlist[z].selected = 0;
    }
  }
  return plotlist;
}

/*
 Handle Feeds
*/

function vis_feed_data() {
	if (multigraph_feedlist !== undefined && multigraph_feedlist[0] != undefined && multigraph_feedlist[0]['autorefresh'] != undefined) {
	  var now = new Date().getTime();
	  var timeWindow = view.end - view.start;
	  if (now - view.end < 2000 * multigraph_feedlist[0]['autorefresh']) {
	    view.end = now;
	    view.start = view.end - timeWindow;
        vis_feed_data1();
	    setTimeout(vis_feed_data, 1000 * multigraph_feedlist[0]['autorefresh']);
      } else{		
        vis_feed_data1();
	  }
    } else{		
      vis_feed_data1();
	}
 }
// Ignore load request spurts
function vis_feed_data1() {
   clearTimeout(event_vis_feed_data); // Cancel any pending events
   event_vis_feed_data = setTimeout(function() { vis_feed_data_delayed(); }, 500);
   if (multigraph_feedlist !== undefined && multigraph_feedlist.length != plotdata.length) plotdata = [];
   plot();
}
  
// Load relevant feed data asynchronously
function vis_feed_data_delayed() {
  var plotlist = convert_to_plotlist(multigraph_feedlist);
  for(var i in plotlist) {
    if (plotlist[i].selected) {
      if (!plotlist[i].plot.data) {
      
        var skipmissing = 0; if (multigraph_feedlist[i]['skipmissing']) skipmissing = 1;

        if (plotdata[i] === undefined) plotdata[i] = [];

        if (typeof ajaxAsyncXdr[i] !== 'undefined') { 
          ajaxAsyncXdr[i].abort(); // Abort pending loads
          ajaxAsyncXdr[i]=undefined;
        }
        var context = {index:i, plotlist:plotlist[i]}; 
        
        
        var npoints = 800;
        interval = Math.round(((view.end - view.start)/npoints)/1000);
          
        // Round to more common and useful intervals:
        interval = view.round_interval(interval);
         
        var intervalms = interval*1000;
        view.start = Math.round(view.start/intervalms)*intervalms;
        view.end = Math.round(view.end/intervalms)*intervalms;
        
        ajaxAsyncXdr[i] = get_feed_data_async(vis_feed_data_callback,context,plotlist[i].id,view.start,view.end,interval,skipmissing,1);
      }
    }
  }
}
  
//load feed data to multigraph plot
function vis_feed_data_callback(context,data) {
  var i = context['index'];
  context['plotlist'].plot.data = data;
  if (context['plotlist'].plot.data) {
    plotdata[i] = context['plotlist'].plot;
  }
  plot();
}

function plot() {
  $.plot($("#graph"), plotdata, {
    grid: { show: true, hoverable: true, clickable: true },
    xaxis: { mode: "time", timezone: "browser", min: view.start, max: view.end },
    selection: { mode: "x" },
    legend: { show: showlegend, position: "nw", toggle: true },
    toggle: { scale: "visible" },
    touch: { pan: "x", scale: "x" }
  });
}

function multigraph_init(element) {
  // Get start and end time of multigraph view
  // end time and timewindow is stored in the first multigraph_feedlist item.
  // start time is calculated from end - timewindow
  plotdata = [];
  var timeWindow = (3600000*24.0*7);
  var now = new Date().getTime();
  view.start = now - timeWindow;
  view.end = now;

  if (multigraph_feedlist !== undefined && multigraph_feedlist[0] != undefined) {
    view.end = multigraph_feedlist[0].end;
    if (view.end==0) view.end = now;
    if (multigraph_feedlist[0].timeWindow) {
        view.start = view.end - multigraph_feedlist[0].timeWindow;
    }
  }

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
        "<button class='btn graph-time' type='button' time='30'>M</button>"+
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
          var x = item.datapoint[0].toFixed(2),
          y = item.datapoint[1].toFixed(2);

          // create a new javascript Date object based on the timestamp
          // This implementation is clumsy, but the js native date.toTimeString() returns
          // strings like "08:53:35 GMT-0800", and there is no easy way to turn off the "GMT-xxxx" segment
          // blargh
          var date = new Date(parseInt(x));
          var hours = date.getHours();
          var minutes = date.getMinutes();
          var seconds = date.getSeconds();
          if (hours < 10)
            hours = "0"+hours;
          if (minutes < 10)
            minutes = "0"+minutes;
          if (seconds < 10)
            seconds = "0"+seconds;

          // will display time in 10:30:23 format
          var formattedTime = hours + ':' + minutes + ':' + seconds;

          // I'd like to eventually add colour hinting to the background of the tooltop.
          // This is why showTooltip has the bgColour parameter.
          tooltip(item.pageX, item.pageY, item.series.label + " at " + formattedTime   + " = " + y, "#DDDDDD");
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
  $('.graph-time').click(function () {view.timewindow($(this).attr("time")); vis_feed_data();});

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
