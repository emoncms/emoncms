  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  */

function plotChart(container, id, month) {
  //----------------------------------------------------------------------------------------
  // These start time and end time set the initial graph view window
  //----------------------------------------------------------------------------------------
  var endDate = new Date(year, month, 31, 0, 0, 0, 0);

  var startDate = new Date();
  var startDate = new Date(year, month, 1, 0, 0, 0, 0);

  //--------------------------------------------------------------
  // 1) GET ALL KWHD DATA
  //--------------------------------------------------------------
  $.ajax({
    url: path+"feed/data.json",
    data: "&apikey=" + apikey + "&id=" + kwhd + "&start=" + (startDate.getTime() - 100)  +"&end=" + endDate.getTime() + "&dp=30", // - 100 is to get the day before the first
    dataType: 'json',
    success: function(data_in)
    {
      data = data_in;
      var w = 500,
        h = 200,
        p = 30;

      var ymax = 0;

      // find ymax :
      for(var i in data) {
        if( data[i][1] > ymax ) {
          ymax = data[i][1];
        }
      }


      // Scales and axes. Note the inverted domain for the y-scale: bigger is up!
      var x = d3.time.scale().domain([startDate, endDate]).range([0, w]),
        y = d3.scale.linear().domain([0, ymax]).range([h, 0])

        xAxis = d3.svg.axis().scale(x).tickSubdivide(true).orient("bottom").ticks(6).tickFormat(d3.time.format("%d %B")),
        yAxis = d3.svg.axis().scale(y).ticks(10).orient("left");

      var prev = container.append("a")
        .attr("id", "prev"+id)
        .attr("href", "#")
        .attr("style", "margin: 120px 0 0 10px; background-image: url(\"../../Views/theme/wp/prev.png\"); display: block; height: 24px; position: absolute; width: 24px; z-index: 10;");

      $("#prev"+id).click(function () {
        container.html("");
        plotChart(container, id, month-1, year);

      });

      var next = container.append("a")
        .attr("id", "next"+id)
        .attr("href", "#")
        .attr("style", "margin: 120px 0 0 570px; background-image: url(\"../../Views/theme/wp/next.png\"); display: block; height: 24px; position: absolute; width: 24px; z-index: 10;");

      $("#next"+id).click(function () {
        container.html("");
        plotChart(container, id, month+1);
      });

      var vis = container
        .append("svg")
        .data([data])
        .attr("id", "#chart"+id)
        .attr("class", "chart")
        .attr("width", w + p * 2)
        .attr("height", h + p * 2)
        .append("g")
        .attr("transform", "translate(" + p + "," + p + ")");

      var rules = vis.selectAll("g.rule")
      .data(y.ticks(10))
      .enter().append("g")
        .attr("class", "rule");

      var bars = vis.selectAll("rect")
        .data(data)
        .enter().append("rect")
          .attr("x", function(d) { return x(d[0]); })
          .attr("y", function(d) { return y(d[1]); })
          .attr("id", function(d) { return "index-" + d[0]; })
          .attr("width", 10)
          .attr("height", function(d) { return h - y(d[1]); })
          .on("mouseover", fade(0.6))
          .on("mouseout", fade(0.4))
          .on("click",  function(d) { render_daily_information(id, d[0], d[1]); });

      vis.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + h + ")")
        .call(xAxis.tickSubdivide(0).tickSize(0));

      // Add the y-axis.
      vis.append("g")
        .attr("class", "y axis")
        .call(yAxis.tickSize(0));

      rules.append("line")
        .attr("class", function(d) { return d ? null : "axis"; })
        .attr("y1", y)
        .attr("y2", y)
        .attr("x1", 0)
        .attr("x2", w + 1);

      /** Returns an event handler for fading a given chord group. */
      function fade(opacity) {
        return function(d, i) {
          vis.selectAll("#index-" + d[0])
            .transition()
            .style("fill-opacity", opacity);
         };
      }
    }
  });
}

function render_daily_information(id, timestamp, kwhd) {
  var d = new Date(timestamp);
  var out = '<table style="text-align:left; margin: auto;">'
  out += '<tr><th>Date :</th><td id="date">' + d.toDateString()  + '</td></tr>'
  out += '<tr><th>Energy :</th><td id="kwhd">' + parseFloat(kwhd).toFixed(2)  + ' kWh/d</td></tr>';
  out += '<tr><th>Cost :</th><td id="costd">'+ parseFloat(kwhd * price).toFixed(2) + currency + '/d, ' + parseFloat(kwhd * price * 365).toFixed(0) + currency + '/y</td></tr>';
  out += '</table>';

  $('#day' + id).each(function(index)
  {
    $(this).hide().html(out).fadeIn();
  });

  var orange = "#FF7D14"
    green = "#C0E392";

  if (id == 1)
    kwhd1 = kwhd;
  else
    kwhd2 = kwhd;

  color = green;

  if(kwhd1 != 0 && kwhd2 != 0) {
    var result = kwhd2 - kwhd1;
    var result2;
    if (result > 0) {
      result = "+" +  parseFloat(result).toFixed(2);
      result2 = "+" + parseFloat(result * price).toFixed(2);
      color = orange;
    }
    else {
      result = parseFloat(result).toFixed(2);
      result2 = parseFloat(result * price).toFixed(2);

    }
    out = '<div class="comparison"><h1>Comparison</h1>';
    out += '<h2 style="color:' + color + ';\">' + result + 'kWh</h2>';
    out += '<h2 style ="color :#33A4D9;">' + result2 + currency + '/d (';
    out += parseFloat(result2 * 365).toFixed(0) + currency + '/y)</h2></div>';

    $('#comparisonbox').each(function(index) {
      $(this).html(out);
    });
  }
}


