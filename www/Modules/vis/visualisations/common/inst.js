  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  */


  function inst_zoomout()
  {
    var time_window = end - start;
    var middle = start + time_window / 2;
    time_window = time_window * 2;					// SCALE
    start = middle - (time_window/2);
    end = middle + (time_window/2);
    timeWindowChanged = 1;
  }


  function inst_zoomin()
  {
    var time_window = end - start;
    var middle = start + time_window / 2;
    time_window = time_window * 0.5;					// SCALE
    start = middle - (time_window/2);
    end = middle + (time_window/2);
    timeWindowChanged = 1;
  }

  function inst_panright()
  {
    var laststart = start; var lastend = end;
    var timeWindow = (end-start);
    var shiftsize = timeWindow * 0.2;
    start += shiftsize;
    end += shiftsize;
    timeWindowChanged = 1;
  }

  function inst_panleft()
  {
    var laststart = start; var lastend = end;
    var timeWindow = (end-start);
    var shiftsize = timeWindow * 0.2;
    start -= shiftsize;
    end -= shiftsize;
    timeWindowChanged = 1;
  }

  function inst_timewindow(time)
  {
    start = ((new Date()).getTime())-(3600000*24*time);			//Get start time
    end = (new Date()).getTime();					        //Get end time
    timeWindowChanged = 1;
    //movingtime = 1;
  }
