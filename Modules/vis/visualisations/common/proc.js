
function power_stats(data)
{
  var npoints = 0;

  var stats = [];
  stats['average'] = 0;
  stats['kwh'] = 0;

  for (var i=1; i<data.length; i++)
  {
    var time_diff  = 1*data[i][0] - 1*data[i-1][0];	// Note: in milliseconds
    var joules_inc = 1*data[i][1] * (time_diff/1000);
    stats['kwh'] += (joules_inc/3600000);
    stats['average'] += 1*data[i][1];
    npoints++;
  }
  stats['kwh'] = Math.abs(stats['kwh']);
  stats['average'] = stats['average'] / npoints;

  return stats;
}
