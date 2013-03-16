/*

  1) Rendering a multigraph usually starts by recieving a multigraph_feedlist from the server
  this details the feeds that need to be visualised, feednames, datatypes and axis orientation.

    multigraph_feedlist:

    0: Object
      id: "21"
      name: "Collector"
      datatype: "1"
      left: true
      end: 1354569971668
      timeWindow: 86400000

    1: Object
      datatype: "1"
      id: "22"
      left: true
      name: "Cylinder Bottom"

    2: Object
      datatype: "1"
      id: "23"
      left: true
      name: "Cylinder Top"

  2) This descriptor object is then used to build a plotlist object which is the object we pass to the flot 
  library minus the actual feed data converting the above object into:

    0: Object
      id: "21"
      plot: Object
        data: null
        label: "Collector"
        lines: Object
        yaxis: 1
      selected: 1

    1: Object
      id: "22"
      plot: Object
        data: null
        label: "Cylinder Bottom"
        lines: Object
        yaxis: 1
      selected: 1

    2: Object
      id: "23"
      plot: Object
        data: null
        label: "Cylinder Top"
        lines: Object
        yaxis: 1
      selected: 1

*/


var multigraph = {

  'inc':0,
  'element':null,
  'data':null,

  'start':null,
  'end':null,
  'timeWindowChanged':0,

  'feedlist':[],  //  feedlist object mirrors database
  'plotlist':[],  //  plotlist object is in flot library structure
  'plotdata':[],  //  plotdata object is in flot library structure

  'init':function()
  {

    var out = 
      "<div id='graph_bound' style='height:400px; width:100%; position:relative; '>"+
        "<div id='graph'></div>"+
        "<div style='position:absolute; top:20px; right:20px;'>"+

          "<input class='time' type='button' value='D' time='1'/>"+
          "<input class='time' type='button' value='W' time='7'/>"+
          "<input class='time' type='button' value='M' time='30'/>"+
          "<input class='time' type='button' value='Y' time='365'/> |"+ 

          "<input id='zoomin' type='button' value='+'/>"+
          "<input id='zoomout' type='button' value='-'/>"+
          "<input id='left' type='button' value='<'/>"+
          "<input id='right' type='button' value='>'/>"+

        "</div>"+
      "</div>"
    ;
    $(multigraph.element).html(out);

    $('#graph').width($('#graph_bound').width());
    $('#graph').height($('#graph_bound').height());

    //if (multigraph.embed) $('#graph').height($(window).height());

    for (z in multigraph.feedlist)
    {
      // Line chart
      if (multigraph.feedlist[z]['datatype']==1) {
        multigraph.plotlist[z] = {
          id: multigraph.feedlist[z]['id'], selected: 1, 
          plot: {
            data: null, 
            label: multigraph.feedlist[z]['name'], 
            lines: { show: true, fill: multigraph.feedlist[z]['fill'] } 
          }
        } 
      }

      // Bar chart
      if (multigraph.feedlist[z]['datatype']==2) {
        multigraph.plotlist[z] = {
          id: multigraph.feedlist[z]['id'], selected: 1, 
          plot: {
            data: null, 
            label: multigraph.feedlist[z]['name'], 
            bars: { show: true, align: "left", barWidth: 3600*24*1000, fill: multigraph.feedlist[z]['fill']} 
          } 
        }
      }

      // Axis and visibility
      if (multigraph.feedlist[z]['left']==true) multigraph.plotlist[z].plot.yaxis = 1;
      if (multigraph.feedlist[z]['right']==true) multigraph.plotlist[z].plot.yaxis = 2;
      if (multigraph.feedlist[z]['left']==false && multigraph.feedlist[z]['right']==false) multigraph.plotlist[z].selected = 0;
    }

    $(window).resize(function(){
      $('#graph').width($('#graph_bound').width());
      if (embed) $('#graph').height($(window).height());
      multigraph.plot();
    });

    //--------------------------------------------------------------------------------------
    // Graph zooming
    //--------------------------------------------------------------------------------------
    $("#graph").bind("plotselected", function (event, ranges) 
    {  
       multigraph.start = ranges.xaxis.from; multigraph.end = ranges.xaxis.to;
       multigraph.timeWindowChanged = 1; multigraph.compile();
    });

    //----------------------------------------------------------------------------------------------
    // Operate buttons
    //----------------------------------------------------------------------------------------------
    $("#zoomout").click(function () {
      var time_window = multigraph.end - multigraph.start;
      var middle = multigraph.start + time_window / 2;
      time_window = time_window * 2;					// SCALE
      multigraph.start = middle - (time_window/2);
      multigraph.end = middle + (time_window/2);
      multigraph.timeWindowChanged = 1;

      multigraph.compile();
    });

    $("#zoomin").click(function () {
      var time_window = multigraph.end - multigraph.start;
      var middle = multigraph.start + time_window / 2;
      time_window = time_window * 0.5;					// SCALE
      multigraph.start = middle - (time_window/2);
      multigraph.end = middle + (time_window/2);
      multigraph.timeWindowChanged = 1;
      multigraph.compile();
    });

    $('#right').click(function () {
      var timeWindow = (multigraph.end-multigraph.start);
      var shiftsize = timeWindow * 0.2;
      multigraph.start += shiftsize;
      multigraph.end += shiftsize;
      multigraph.timeWindowChanged = 1;
      multigraph.compile();
    });

    $('#left').click(function () {
      var timeWindow = (multigraph.end-multigraph.start);
      var shiftsize = timeWindow * 0.2;
      multigraph.start -= shiftsize;
      multigraph.end -= shiftsize;
      multigraph.timeWindowChanged = 1;
      multigraph.compile();
    });

    $('.time').click(function () {
      multigraph.start = ((new Date()).getTime())-(3600000*24*$(this).attr("time"));			//Get start time
      multigraph.end = (new Date()).getTime();					        //Get end time
      multigraph.timeWindowChanged = 1;
      multigraph.compile();
    });
    //-----------------------------------------------------------------------------------------------
  },

  'compile':function()
  {
    multigraph.plotdata = eval(JSON.stringify(multigraph.plotdata));
    multigraph.plotlist = eval(JSON.stringify(multigraph.plotlist));

    multigraph.plot();

    for (var i in multigraph.plotlist) {
      console.log(i);
      if (multigraph.timeWindowChanged) multigraph.plotlist[i].plot.data = null;
      if (multigraph.plotlist[i].selected) {
 
        if (!multigraph.plotlist[i].plot.data) {
          multigraph.fetchdata(i,400); 
        } else {
          console.log("compile push "+i);
        }

      }
    } 


    multigraph.timeWindowChanged=0;
  },

  'addfeed':function()
  {
    // add feed to plotlist and feedlist
  },

  'plot':function()
  {
    $.plot($("#graph"), multigraph.plotdata, {
      grid: { show: true, hoverable: true, clickable: true },
      xaxis: { mode: "time", localTimezone: true, min: multigraph.start, max: multigraph.end },
      selection: { mode: "xy" },
      legend: { position: "nw"}
    });
  },

  'fetchdata':function(i,dp)
  {
    multigraph.plotdata = eval(JSON.stringify(multigraph.plotdata));

    $.ajax({                                    
      url: path+'feed/data.json',                         
      data: "&id="+multigraph.plotlist[i].id+"&start="+multigraph.start+"&end="+multigraph.end+"&dp="+dp,  
      dataType: 'json',                           
      success: function(datain)
      { 
        multigraph.plotlist[i].plot.data = datain;
  
        var exists = false;
        for (z in multigraph.plotdata)
        {
          if (multigraph.plotlist[i].plot.label == multigraph.plotdata[z]['label']) {
            multigraph.plotdata[z] = multigraph.plotlist[i].plot;
            exists = true;
          }
        }

        if (exists == false) multigraph.plotdata.push(multigraph.plotlist[i].plot);

        multigraph.plot();
      }
    });
  }


}
