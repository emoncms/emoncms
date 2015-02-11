  var plotdata = [];
  var timeWindowChanged = 0;
  var multigraph_editmode = false;

  function convert_to_plotlist(multigraph_feedlist)
  {
    var plotlist = [];
    for (z in multigraph_feedlist)
    {
    if (multigraph_feedlist[z]['datatype']==1)
    {
      plotlist[z] = {
        id: multigraph_feedlist[z]['id'],
        selected: 1,
        plot:
        {
          data: null,
          label: multigraph_feedlist[z]['name'],
          lines:
          {
            show: true,
            fill: multigraph_feedlist[z]['fill']
          }
        }
      };
    }

    else if (multigraph_feedlist[z]['datatype']==2)
    {
      plotlist[z] = {
        id: multigraph_feedlist[z]['id'],
        selected: 1,
        plot:
        {
          data: null,
          label: multigraph_feedlist[z]['name'],
          bars:
          {
            show: true,
            align: "left", barWidth: 3600*24*1000, fill: multigraph_feedlist[z]['fill']
          }
        }
      };
    }
    else
    {
      console.log("ERROR: Unknown plot datatype! Datatype: ", multigraph_feedlist[z]['datatype']);
    }

    if (multigraph_feedlist[z]['left']==true)
    {
      plotlist[z].plot.yaxis = 1;
    }
    else if (multigraph_feedlist[z]['right']==true)
    {
      plotlist[z].plot.yaxis = 2;
    }
    else
    {
      console.log("ERROR: Unknown plot alignment! Alignment setting: ", multigraph_feedlist[z]['right']);
    }

    // Only set the plotcolour variable if we have a value to set it with
    if (multigraph_feedlist[z]["lineColour"])
    {
      // Some browsers really want the leading "#". It works without in chrome, not in IE and opera.
      // What the hell, people?
      if (multigraph_feedlist[z]["lineColour"].indexOf("#") == -1)
      {
        plotlist[z].plot.color = "#" + multigraph_feedlist[z]["lineColour"];
      }
      else
      {
        plotlist[z].plot.color = multigraph_feedlist[z]["lineColour"];
      }
    }


    if (multigraph_feedlist[z]['left']==false && multigraph_feedlist[z]['right']==false)
    {
      plotlist[z].selected = 0;
    }

    }
    return plotlist;
  }

  /*

  Handle_feeds

  For all feeds in the plotlist:
  - remove all plot data if the time window has changed
  - if the feed is selected load new data
  - add the feed to the multigraph plot
  - plot the multigraph

  */
  function vis_feed_data()
  {
    var plotlist = convert_to_plotlist(multigraph_feedlist);
    console.log(plotlist);
    plotdata = [];
    for(var i in plotlist) {
    if (timeWindowChanged)
    {
      plotlist[i].plot.data = null;
    }
      if (plotlist[i].selected) {
        if (!plotlist[i].plot.data)
        {
          
          var npoints = 400;
          interval = Math.round(((view.end - view.start)/npoints)/1000);

          $.ajax({                                      
              url: path+'feed/average.json',                         
              data: "id="+plotlist[i].id+"&start="+view.start+"&end="+view.end+"&interval="+interval,
              dataType: 'json',
              async: false,                      
              success: function(data_in) { plotlist[i].plot.data = data_in; } 
          });
        }

        if ( plotlist[i].plot.data)
        {
          plotdata.push(plotlist[i].plot);
        }
      }
    }

    plot();

    timeWindowChanged=0;

    if (multigraph_editmode==true)
    {
      //update_multigraph_feedlist();
    }
  }

  function plot()
  {
      $.plot($("#graph"), plotdata, {
      grid: { show: true, hoverable: true, clickable: true },
      xaxis: { mode: "time", timezone: "browser", min: view.start, max: view.end },
      selection: { mode: "x" },
      legend: { position: "nw",
	  hideable: true
	  }
    });
  }

function multigraph_init(element)
{
  // Get start and end time of multigraph view
  // end time and timewindow is stored in the first multigraph_feedlist item.
  // start time is calculated from end - timewindow
  
  var timeWindow = (3600000*24.0*7);
  view.start = +new Date - timeWindow;
  view.end = +new Date;
  
  if (multigraph_feedlist[0]!=undefined)
  {
    view.end = multigraph_feedlist[0].end;
    if (view.end==0) view.end = (new Date()).getTime();
    if (multigraph_feedlist[0].timeWindow) {
        view.start = view.end - multigraph_feedlist[0].timeWindow;
    }
  }

  var out =
    "<div id='graph_bound' style='height:400px; width:100%; position:relative; '>"+
      "<div id='graph'></div>"+
      "<div id='graph_buttons' style='position:absolute; top:20px; right:30px; opacity:0.5; display: none;'>"+


        "<div class='input-prepend input-append' style='margin:0'>"+
        "<span class='add-on'>Tooltip:</span>"+
        "<span class='add-on'><input id='enableTooltip' type='checkbox' checked ></span>"+
        "</div> | "+

        "<div class='btn-group'>"+
        "<button class='btn time' type='button' time='1'>D</button>"+
        "<button class='btn time' type='button' time='7'>W</button>"+
        "<button class='btn time' type='button' time='30'>M</button>"+
        "<button class='btn time' type='button' time='365'>Y</button></div> |"+

        "<div class='btn-group'>"+
        "<button id='zoomin' class='btn' >+</button>"+
        "<button id='zoomout' class='btn' >-</button>"+
        "<button id='left' class='btn' ><</button>"+
        "<button id='right' class='btn' >></button></div>"+
		
		"<div class='btn-group'>"+
        "<button id='save' class='btn' >Save</button></div>"+

      "</div>"+
    "</div>"
  ;
  $(element).html(out);

  $('#graph').width($('#graph_bound').width());
  $('#graph').height($('#graph_bound').height());
  if (embed) $('#graph').height($(window).height());

  $(window).resize(function(){
    $('#graph').width($('#graph_bound').width());
    if (embed) $('#graph').height($(window).height());
    plot();
  });


  // Fade in/out the control buttons on mouse-over the plot container
  $("#graph_bound").mouseenter(function(){
    $("#graph_buttons").stop().fadeIn();
  }).mouseleave(function(){
    $("#graph_buttons").stop().fadeOut();
  });

  //--------------------------------------------------------------------------------------
  // Graph zooming
  //--------------------------------------------------------------------------------------
  $("#graph").bind("plotselected", function (event, ranges)
  {
     view.start = ranges.xaxis.from; 
     view.end = ranges.xaxis.to;
     timeWindowChanged = 1; vis_feed_data();
  });

  //----------------------------------------------------------------------------------------------
  // Operate buttons
  //----------------------------------------------------------------------------------------------
  $("#zoomout").click(function () {view.zoomout(); vis_feed_data();});
  $("#zoomin").click(function () {view.zoomin(); vis_feed_data();});
  $('#right').click(function () {view.panright(); vis_feed_data();});
  $('#left').click(function () {view.panleft(); vis_feed_data();});
  $('.time').click(function () {view.timewindow($(this).attr("time")); vis_feed_data();});
  
  $('#save').click(function () {
		$("#graph_buttons").hide();
		var nodeToTransform = $("#graph");
		$("#graph").css( "backgroundColor", "white" );
		html2canvas(nodeToTransform ,
		{
			onrendered: function(canvas) {
				Canvas2Image.saveAsPNG(canvas)
		   }
		});
	});
  //-----------------------------------------------------------------------------------------------
}