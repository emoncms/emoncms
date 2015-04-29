var app_graph = {

    feedname: 0,

    start:0,
    end:0,
    interval:0,
    npoints:600,
    skipmissing:1,
    limitinterval:1,
    showcsv:0,
    data:[],

    mean: 0,
    stdev: 0,
    minval: 0,
    maxval: 0,

    // Include required javascript libraries
    include: [
        "static/flot/jquery.flot.min.js",
        "static/flot/jquery.flot.time.min.js",
        "static/flot/jquery.flot.selection.min.js",
        "static/vis.helper.js",
        "static/flot/date.format.js"
    ],
    
    init: function()
    {
        var timeWindow = (3600000*24.0*7);
        var now = Math.round(+new Date * 0.001)*1000;
        app_graph.start = now - timeWindow;
        app_graph.end = now;
        app_graph.calc_interval();

        $("#graph_zoomout").click(function () {app_graph.zoomout(); app_graph.draw();});
        $("#graph_zoomin").click(function () {app_graph.zoomin(); app_graph.draw();});
        $('#graph_right').click(function () {app_graph.panright(); app_graph.draw();});
        $('#graph_left').click(function () {app_graph.panleft(); app_graph.draw();});
        $('.graph_time').click(function () {
            app_graph.timewindow($(this).attr("time")); app_graph.draw();
        });
        
        $('#placeholder').bind("plotselected", function (event, ranges)
        {
            app_graph.start = ranges.xaxis.from;
            app_graph.end = ranges.xaxis.to;
            app_graph.calc_interval();
            
            app_graph.draw();
        });
        
        $("#resend").click(function(){
            app_graph.start = $("#request-start").val()*1000;
            app_graph.end = $("#request-end").val()*1000;
            app_graph.interval = $("#request-interval").val();
            app_graph.skipmissing = $("#request-skipmissing")[0].checked*1;
            app_graph.limitinterval = $("#request-limitinterval")[0].checked*1;
            
            app_graph.draw();
        });
        
        $("#showcsv").click(function(){
            if ($("#showcsv").html()=="Show CSV Output") {
                app_graph.printcsv()
                $("#csv").show();
                app_graph.showcsv = 1;
                $("#showcsv").html("Hide CSV Output");
            } else {
                app_graph.showcsv = 0;
                $("#csv").hide();
                $("#showcsv").html("Show CSV Output");
            }
        });
    },
    
    show: function() 
    {
        var top_offset = 0;
        var placeholder_bound = $('#placeholder_bound');
        var placeholder = $('#placeholder');

        var width = placeholder_bound.width();
        var height = width * 0.5;

        placeholder.width(width);
        placeholder_bound.height(height);
        placeholder.height(height-top_offset);
        
        app_graph.draw();
        
        $("#info").show();
    },
    
    hide: function() 
    {
    
    },
    
    draw: function()
    {
        
        var request = path+"feed/data.json?id="+app_graph.feedname+"&start="+app_graph.start+"&end="+app_graph.end+"&interval="+app_graph.interval+"&skipmissing="+app_graph.skipmissing+"&limitinterval="+app_graph.limitinterval;
        
        $("#request-start").val(app_graph.start/1000);
        $("#request-end").val(app_graph.end/1000);
        $("#request-interval").val(app_graph.interval);
        $("#request-skipmissing").attr("checked",app_graph.skipmissing);
        $("#request-limitinterval").attr("checked",app_graph.limitinterval);
        $("#request-url").html(request);
        $("#request-url").attr("href",request);
        
        $.ajax({                                      
            url: request,
            async: false,
            dataType: "text",
            success: function(data_in) {
            
                // 1) Check validity of json data, or show error
                var valid = true;
                try {
                    app_graph.data = JSON.parse(data_in);
                    if (app_graph.data.success!=undefined) valid = false;
                    
                    $("#error").hide();     
                } catch (e) {
                    valid = false;
                }
                
                // 2) If valid 
                if (!valid)
                {
                    $("#error").html("<div class='alert alert-danger'><b>Request error</b> "+data_in+"</div>").show();
                }
                else
                { 
                    var options = {
                        lines: { fill: true },
                        xaxis: { 
                            mode: "time", timezone: "browser", 
                            min: app_graph.start, max: app_graph.end
                        },
                        grid: {hoverable: true, clickable: true},
                        selection: { mode: "x" }
                    }
                    
                    $.plot($('#placeholder'), [{data:app_graph.data}], options);
                    
                    if (app_graph.showcsv) app_graph.printcsv();
                    
                    app_graph.stats();
                    dp = 1;
                    units = "";
                    $("#stats-mean").html(app_graph.mean.toFixed(dp)+units);
                    $("#stats-max").html(app_graph.maxval.toFixed(dp)+units);
                    $("#stats-min").html(app_graph.minval.toFixed(dp)+units);
                    $("#stats-stdev").html(app_graph.stdev.toFixed(dp)+units);
                    $("#stats-npoints").html(app_graph.data.length);                 
                }
            } 
        });
    },
    
    printcsv: function()
    {
        var csvout = "";
        var start_time = app_graph.data[0][0];
        for (z in app_graph.data) {
            csvout += Math.round((app_graph.data[z][0]-start_time)/1000)+", "+app_graph.data[z][1]+"\n";
        }
        $("#csv").val(csvout);
    },
    
    // View functions
    
    'zoomout':function ()
    {
        var time_window = app_graph.end - app_graph.start;
        var middle = app_graph.start + time_window / 2;
        time_window = time_window * 2;
        app_graph.start = middle - (time_window/2);
        app_graph.end = middle + (time_window/2);
        app_graph.calc_interval();
    },

    'zoomin':function ()
    {
        var time_window = app_graph.end - app_graph.start;
        var middle = app_graph.start + time_window / 2;
        time_window = time_window * 0.5;
        app_graph.start = middle - (time_window/2);
        app_graph.end = middle + (time_window/2);
        app_graph.calc_interval();
    },

    'panright':function ()
    {
        var time_window = app_graph.end - app_graph.start;
        var shiftsize = time_window * 0.2;
        app_graph.start += shiftsize;
        app_graph.end += shiftsize;
        app_graph.calc_interval();
    },

    'panleft':function ()
    {
        var time_window = app_graph.end - app_graph.start;
        var shiftsize = time_window * 0.2;
        app_graph.start -= shiftsize;
        app_graph.end -= shiftsize;
        app_graph.calc_interval();
    },

    'timewindow':function(time)
    {
        app_graph.start = ((new Date()).getTime())-(3600000*24*time);	//Get start time
        app_graph.end = (new Date()).getTime();	//Get end time
        app_graph.calc_interval();
    },
    
    'calc_interval':function()
    {
        var interval = Math.round(((app_graph.end - app_graph.start)/app_graph.npoints)/1000);
        
        var outinterval = 5;
        if (interval>10) outinterval = 10;
        if (interval>15) outinterval = 15;
        if (interval>20) outinterval = 20;
        if (interval>30) outinterval = 30;
        if (interval>60) outinterval = 60;
        if (interval>120) outinterval = 120;
        if (interval>180) outinterval = 180;
        if (interval>300) outinterval = 300;
        if (interval>600) outinterval = 600;
        if (interval>900) outinterval = 900;
        if (interval>1200) outinterval = 1200;
        if (interval>1800) outinterval = 1800;
        if (interval>3600*1) outinterval = 3600*1;
        if (interval>3600*2) outinterval = 3600*2;
        if (interval>3600*3) outinterval = 3600*3;
        if (interval>3600*4) outinterval = 3600*4;
        if (interval>3600*5) outinterval = 3600*5;
        if (interval>3600*6) outinterval = 3600*6;
        if (interval>3600*12) outinterval = 3600*12;
        if (interval>3600*24) outinterval = 3600*24;
        
        app_graph.interval = outinterval;
        
        app_graph.start = Math.floor((app_graph.start/1000) / outinterval) * outinterval * 1000;
        app_graph.end = Math.ceil((app_graph.end/1000) / outinterval) * outinterval * 1000;
    },
    
    'stats':function()
    {
        var sum = 0, i=0;
        app_graph.minval = 0;
        app_graph.maxval = 0;
        for (z in app_graph.data)
        {
            var val = app_graph.data[z][1];
            if (val!=null) 
            {
                if (i==0) {
                    app_graph.maxval = val;
                    app_graph.minval = val;
                }
                if (val>app_graph.maxval) app_graph.maxval = val;
                if (val<app_graph.minval) app_graph.minval = val;
                sum += val;
                i++;
            }
        }
        app_graph.mean = sum / i;
        sum = 0, i=0;
        for (z in app_graph.data)
        {
            sum += (app_graph.data[z][1] - app_graph.mean) * (app_graph.data[z][1] - app_graph.mean);
            i++;
        }
        app_graph.stdev = Math.sqrt(sum / i);
    }
};
