  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  */
function get_range(data,start,end)
{
  var gdata = [];
  var index = 0;
  for (var z in data)                     //for all variables
  {
    if (data[z][0] >= start && data[z][0] < end) {gdata[index] = data[z]; index ++;}

  }
  return gdata;
}

function get_days_month(data,month,year)
{
  return get_range(data,Date.UTC(year,month,0),Date.UTC(year,month+1,0));
}

function get_last_30days(data)
{
  var d = new Date();
  var s = d - (3600000*24*30);
  return get_range(data,s,d);
}


function get_months(data)
{
  var gdata = [];
  gdata.data = [];
  gdata.days =[];

  var sum=0, s=0, i=0;
  var lmonth=0,month=0,year;
  var tmp = []
  var d = new Date();

  for (var z in data)
  {
    lmonth = month;

    d.setTime(data[z][0]);
    month = d.getMonth();
    year = d.getFullYear();

   if (month!=lmonth && z!=0)
    {
      var tmp = [];
      tmp[0] = Date.UTC(year,month-1,1);
      tmp[1] = sum; ///daysInMonth(month-1, year);

      gdata.data[i] = tmp;
      gdata.days[i] = s;
      i++;
      sum = 0; s = 0;
    }

    sum += parseFloat(data[z][1]);
    s++;

   }

  var tmp = [];
  tmp[0] = Date.UTC(year,month,1);
  tmp[1] = sum; ///daysInMonth(month, year);

  gdata.data[i] = tmp;
  gdata.days[i] = s;

  return gdata;
}

function get_months_year(data,year)
{
  data = get_range(data,Date.UTC(year,0,1),Date.UTC(year+1,0,1));
  return get_months(data);
}

function get_years(data)
{
  var years = [];
  years.data = [];
  years.days =[];

  var sum=0, s=0, i=0;
  var lyear=0,year=0;
  var tmp = []
  var d = new Date();

  for (var z in data)
  {
    if (data[z][0]>1000000){
    lyear = year;

    d.setTime(data[z][0]);		// Get the date of the day
    year = d.getFullYear();		// Get the year of the day
    if (year!=lyear && z!=0)		// We sum all days until we find a new year
    {
      years.data[i] = [Date.UTC(year-1,0,1), sum];
      years.days[i] = s;
      i++;
      sum = 0; s = 0;
    }

    sum += parseFloat(data[z][1]);	// Add the day kwh/d value to the sum
    s++;				// Sum count
    }

  }
  
  years.data[i] = [Date.UTC(year,0,1), sum];
  years.days[i] = s;

  return years;
}

function daysInMonth(iMonth, iYear)
{
  return 32 - new Date(iYear, iMonth, 32).getDate();
}
