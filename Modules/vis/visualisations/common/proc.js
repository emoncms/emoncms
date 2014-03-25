
function power_stats(data)
{
    if (data[0]!=undefined)
    {
        var npoints = 1;
        var stats = [];
        stats['average'] = 1*data[0][1];
        stats['kwh'] = 0;

        for (var i=1; i<data.length; i++)
        {
            var time_diff  = 1*data[i][0] - 1*data[i-1][0];	// Note: in milliseconds
            var joules_inc = 1*data[i][1] * (time_diff/1000);
            stats['kwh'] += (joules_inc/3600000);
            stats['average'] += 1*data[i][1];
            npoints++;
        }
        stats['average'] = stats['average'] / npoints;
    }
    else
    {
        var stats = [];
        stats['kwh'] = 0;
        stats['average'] = 0;
    }
      
    return stats;
}
