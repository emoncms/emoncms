// -------------------------------------------------------------------------------
// CSV import tool
// -------------------------------------------------------------------------------
var import_data = [];

$("#importDataModal").on('shown', function(){
    draw_import_feed_select();
    
    for (var e in engines_hidden) {
        $('#import-feed-engine option[value='+engines_hidden[e]+']').hide();
    }
});


// Validate pasted data for import
$("#import-textarea").change(function() {

    $("#import-alert").html("").hide();

    var content = $(this).val();
    if (content=="") return false;
    
    var lines = content.split("\n");
    
    import_data = [];
    
    // 1. Check format of pasted data
    for (var i=0; i<lines.length; i++) {
        var time_value_str = lines[i].split(",");
        if (time_value_str.length==2) {
            var time = time_value_str[0].trim();
            var value = time_value_str[1].trim();
            if (value=='null') value = null;
            
            if (isNaN(time)) {
                $("#import-alert").html("<b>Error:</b> invalid time on line "+i).show();
                return false;
            } else {
                time = parseInt(time);
            }

            if (isNaN(value)) {
                $("#import-alert").html("<b>Error:</b> invalid value on line "+i).show();
                return false;
            } else {
                value = parseFloat(value);
            }
            
            import_data.push([time,value]);
        }
    }

    if (import_data.length==1) {
        $("#import-alert").html("<b>Format valid:</b> 1 data point").removeClass("alert-danger").addClass("alert-success").show();
    }
    
    else if (import_data.length>1) {
        
        // 2. Check interval of pasted data
        var interval = false;
        var fixed_interval = true;
        for (var i=1; i<import_data.length; i++) {
            this_interval = import_data[i][0] - import_data[i-1][0]
            if (this_interval>0) {
                if (interval!=false && this_interval!=interval) {
                    fixed_interval = false;
                    break;
                }
                interval = this_interval;
            } else {
                if (this_interval<0) {
                    $("#import-alert").html("<b>Error:</b> datapoint "+import_data[i][0]+" is older than previous data point").show();
                    return false;
                }
            }
        }
        
        if (!fixed_interval && import_data.length>2) {
            interval = (import_data[import_data.length-1][0] - import_data[0][0])/(import_data.length+1);
        }
        
        var message = "<b>Format valid:</b> "+import_data.length+" data points";
        if (interval!==false) message += ", "+(fixed_interval?"fixed interval "+interval+"s":"variable interval, average: "+(interval.toFixed(1))+"s");
        $("#import-alert").html(message).removeClass("alert-danger").addClass("alert-success").show();
        
        if (available_intervals.indexOf(parseInt(interval))!=-1) {
            $("#import-feed-interval").val(parseInt(interval));
        }
    }

});

var import_select_drawn = false;

function draw_import_feed_select() {
    if (!import_select_drawn) {
        import_select_drawn = true;
        
        var out = "<option value=-1>CREATE NEW:</option>";
        for (var n in nodes) {
          out += "<optgroup label='"+n+"'>";
          for (var f in nodes[n]) {
              out += "<option value="+nodes[n][f]['id']+">"+nodes[n][f].name+"</option>";
          }
          out += "</optgroup>";
        }
        $("#import-feed-select").html(out);
    }
}

$("#import-feed-select").change(function(){
    var feedid = $(this).val();
    console.log(feedid);
    if (feedid==-1) {
        $(".import-new-feed").show();
    } else {
        $(".import-new-feed").hide();
    }
});

$("#import-feed-engine").change(function(){
    var engine = $(this).val();
    if (engine==5) {
        $("#import-feed-interval").show();
    } else {
        $("#import-feed-interval").hide();
    }
});

$("#importData").click(function() {    
    var feedid = $("#import-feed-select").val();
    if (feedid==-1) {
        var tag = $("#import-feed-tag").val();
        var name = $("#import-feed-name").val();
        var engine = $("#import-feed-engine").val();
        var interval = $("#import-feed-interval").val();      
        
        var options = {};
        if (engine==5) {
            options.interval = $('#import-feed-interval').val();
        }
        
        var result = feed.create(tag,name,engine,options);
        feedid = parseInt(result.feedid);
    
        if (!result.success || feedid<1) {
            alert('ERROR: Feed could not be created. '+result.message);
            return false;
        }
    }
    
    $.ajax({ 
        type: 'POST', 
        url: path+"feed/post.json?id="+feedid,
        data: "data="+JSON.stringify(import_data),
        async: true, 
        dataType: 'json',
        success: function(result) {
            if (result.success!=undefined) {
                if (result.success) {
                    alert('Success: Data import complete');
                    
                    $.ajax({ url: path+"feed/updatesize.json", async: true, success: function(data){ update_feed_list(); }});
                    
                } else {
                    alert('ERROR: '+result.message);
                }
            }
            $('#importDataModal').modal('hide');
        }
    });
});
