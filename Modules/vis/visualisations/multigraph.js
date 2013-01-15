  var plotdata = [];
  var timeWindowChanged = 0;
  var multigraph_editmode = false;

  function convert_to_plotlist(multigraph_feedlist)
  {
    var plotlist = [];
    for (z in multigraph_feedlist)
    {
      if (multigraph_feedlist[z]['datatype']==1) plotlist[z] = {id: multigraph_feedlist[z]['id'], selected: 1, plot: {data: null, label: multigraph_feedlist[z]['name'], lines: { show: true, fill: multigraph_feedlist[z]['fill'] } } };

      if (multigraph_feedlist[z]['datatype']==2) plotlist[z] = {id: multigraph_feedlist[z]['id'], selected: 1, plot: {data: null, label: multigraph_feedlist[z]['name'], bars: { show: true, align: "left", barWidth: 3600*24*1000, fill: multigraph_feedlist[z]['fill']} } };

      if (multigraph_feedlist[z]['left']==true) plotlist[z].plot.yaxis = 1;
      if (multigraph_feedlist[z]['right']==true) plotlist[z].plot.yaxis = 2;

      if (multigraph_feedlist[z]['left']==false && multigraph_feedlist[z]['right']==false) plotlist[z].selected = 0;

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
    plotdata = [];
    for(var i in plotlist) {
      if (timeWindowChanged) plotlist[i].plot.data = null;
      if (plotlist[i].selected) {        
        if (!plotlist[i].plot.data) plotlist[i].plot.data = get_feed_data(plotlist[i].id,start,end,400);
        if ( plotlist[i].plot.data) plotdata.push(plotlist[i].plot);
      }
    }

    plot();

    timeWindowChanged=0;

    if (multigraph_editmode==true)
    {
      update_multigraph_feedlist();
    }
  }

  function plot()
  {
    $.plot($("#graph"), plotdata, {
      grid: { show: true, hoverable: true, clickable: true },
      xaxis: { mode: "time", localTimezone: true, min: start, max: end },
      selection: { mode: "xy" },
      legend: { position: "nw"}
    });
  }

function multigraph_init(element)
{
  // Get start and end time of multigraph view
  // end time and timewindow is stored in the first multigraph_feedlist item.
  // start time is calculated from end - timewindow
  if (multigraph_feedlist[0]!=undefined)
  {
    //end = multigraph_feedlist[0].end;
    //if (end==0) 
    end = (new Date()).getTime();
    if (multigraph_feedlist[0].timeWindow) start = end - multigraph_feedlist[0].timeWindow;
  }

  var out = 
    "<div id='graph_bound' style='height:400px; width:100%; position:relative; '>"+
      "<div id='graph' style='position:absolute; top:25px;'></div>"+
      "<div style='position:absolute; top:0px; right:0px;'>"+

        "<input class='time' type='button' value='12 Hr' time='0.5'/>"+
        "<input class='time' type='button' value='D' time='1'/>"+
        "<input class='time' type='button' value='W' time='7'/>"+
        "<input class='time' type='button' value='M' time='30'/>"+
        "<input class='time' type='button' value='Y' time='365'/> |"+ 

        "<input id='zoomin' type='button' value='+'/>"+
        "<input id='zoomout' type='button' value='-'/>"+
        "<input id='left' type='button' value='<'/>"+
        "<input id='right' type='button' value='>'/>"+
        "<input id='enableTooltip' type='checkbox' checked >"+

      "</div>"+
    "</div>"
  ;
  $(element).html(out);

  $('#graph').width($('#graph_bound').width());
  $('#graph').height($('#graph_bound').height());
  if (embed) $('#graph').height($(window).height()-25);

  $(window).resize(function(){
    $('#graph').width($('#graph_bound').width());
    if (embed) $('#graph').height($(window).height()-25);
    plot();
  });

  //--------------------------------------------------------------------------------------
  // Graph zooming
  //--------------------------------------------------------------------------------------
  $("#graph").bind("plotselected", function (event, ranges) 
  {
     start = ranges.xaxis.from; end = ranges.xaxis.to;
     timeWindowChanged = 1; vis_feed_data();
  });

  //----------------------------------------------------------------------------------------------
  // Operate buttons
  //----------------------------------------------------------------------------------------------
  $("#zoomout").click(function () {inst_zoomout(); vis_feed_data();});
  $("#zoomin").click(function () {inst_zoomin(); vis_feed_data();});
  $('#right').click(function () {inst_panright(); vis_feed_data();});
  $('#left').click(function () {inst_panleft(); vis_feed_data();});
  $('.time').click(function () {inst_timewindow($(this).attr("time")); vis_feed_data();});
  //-----------------------------------------------------------------------------------------------
}
