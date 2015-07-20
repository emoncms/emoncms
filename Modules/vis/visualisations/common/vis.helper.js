var view =
{
  'start':0,
  'end':0,

  'zoomout':function ()
  {
    var time_window = this.end - this.start;
    var middle = this.start + time_window / 2;
    time_window = time_window * 2;
    this.start = middle - (time_window/2);
    this.end = middle + (time_window/2);
  },

  'zoomin':function ()
  {
    var time_window = this.end - this.start;
    var middle = this.start + time_window / 2;
    time_window = time_window * 0.5;
    this.start = middle - (time_window/2);
    this.end = middle + (time_window/2);
  },

  'panright':function ()
  {
    var time_window = this.end - this.start;
    var shiftsize = time_window * 0.2;
    this.start += shiftsize;
    this.end += shiftsize;
  },

  'panleft':function ()
  {
    var time_window = this.end - this.start;
    var shiftsize = time_window * 0.2;
    this.start -= shiftsize;
    this.end -= shiftsize;
  },

  'timewindow':function(time)
  {
    this.start = ((new Date()).getTime())-(3600000*24*time);	//Get start time
    this.end = (new Date()).getTime();	//Get end time
  }
}

var stats = {

  'min': 0,
  'max': 0,
  'mean': 0,
  'stdev': 0,

  'calc':function(data)
  {
    var sum = 0, i=0;
    stats.min = 0;
    stats.max = 0;
    for (z in data)
    {
      if (i==0) {
        stats.max = data[z][1];
        stats.min = data[z][1];
      }
    
      if (data[z][1]>stats.max) stats.max = data[z][1];
      if (data[z][1]<stats.min) stats.min = data[z][1];
      sum +=data[z][1];
      i++;
    }
    
    stats.mean = sum / i;

    sum = 0, i=0;
    for (z in data)
    {
      sum += (data[z][1] - stats.mean) * (data[z][1] - stats.mean);
      i++;
    }
    
    stats.stdev = Math.sqrt(sum / i);
    
  }

}

// http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values/901144#901144
var urlParams;
(window.onpopstate = function () {
    var match,
        pl = /\+/g, // Regex for replacing addition symbol with a space
        search = /([^&=]+)=?([^&]*)/g,
        decode = function (s) { return decodeURIComponent(s.replace(pl, " ")); },
        query = window.location.search.substring(1);

    urlParams = {};
    while (match = search.exec(query))
       urlParams[decode(match[1])] = decode(match[2]);
})();

function tooltip(x, y, contents, bgColour)
{

    var offset = 15; // use higher values for a little spacing between `x,y` and tooltip
    var elem = $('<div id="tooltip">' + contents + '</div>').css({
        position: 'absolute',
        display: 'none',
        'font-weight':'bold',
        border: '1px solid rgb(255, 221, 221)',
        padding: '2px',
        'background-color': bgColour,
        opacity: '0.8'
    }).appendTo("body").fadeIn(200);
    //x = x - elem.width();
    //x = x - elem.width();
    elem.css({
        top: y - elem.height() - offset,
        left: x - elem.width() - offset,
    });
};
