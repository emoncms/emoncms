// ---------------------------------------------------------------------------------------------
// Export feature
// ---------------------------------------------------------------------------------------------
function isNumeric(value) {
    return value !== null && value !== '' && Number.isFinite(Number(value));
}

function openFeedExportModal(){
    $("#export-average").parent().hide();
    $("#export-average").data("enabled",0);
    
    var ids = [];
    for (var feedid in feedApp.selectedFeeds) {
        if (feedApp.selectedFeeds[feedid]==true) {
            ids.push(parseInt(feedid));
        }
    }
    
    var selected_interval = $('#export-interval').val();
     
    if (ids.length>1) {
        $("#export-interval").children('option[value="original"]').hide();
        if (selected_interval=="original") {
            $("#export-interval").val(10);
        }
    } else {
        $("#export-interval").children('option[value="original"]').show();
    }
    
    // Enable averaging checkbox for single feed selection, phpfina & phptimeseries
    var engine = feeds[ids[0]].engine;
    if (ids.length==1 && (engine==2 || engine==5)) {
        $("#export-average").data("enabled",1);
        if (selected_interval!="original") {
            $("#export-average").parent().show();
        }
    }
    
    $("#export").attr('feedcount',ids.length);
    calculate_download_size(ids.length);
    
    emoncmsModal.open('feedExportModal');
}

function getExportDate($input) {
    return ecDateTime.parseYmdHms($input.val());
}

function setExportDate($input, date) {
    $input.val(ecDateTime.formatYmdHms(date));
}

var now = new Date();
var today = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0);
setExportDate($('#export-start'), today);
setExportDate($('#export-end'), today);

$('#export-start').on('change input', function() {
    var startDate = getExportDate($('#export-start'));
    var endDate = getExportDate($('#export-end'));

    if (startDate && endDate && startDate > endDate) {
        setExportDate($('#export-end'), startDate);
    }
    calculate_download_size($("#export").attr('feedcount'));
});

$('#export-end').on('change input', function() {
    var startDate = getExportDate($('#export-start'));
    var endDate = getExportDate($('#export-end'));

    if (startDate && endDate && endDate < startDate) {
        setExportDate($('#export-start'), endDate);
    }
    calculate_download_size($("#export").attr('feedcount'));
});

$('#export-interval').on('change', function(e) {
    if ($("#export-average").data("enabled")) {
        if ($(this).val()=="original") {
            $("#export-average").parent().hide();
        } else {
            $("#export-average").parent().show();
        }
    }
});

$('#export-interval, #export-timeformat').on('change', function(e) {
    calculate_download_size($("#export").attr('feedcount')); 
});

$("#export").on('click', function()
{
    var ids = [];
    for (var feedid in feedApp.selectedFeeds) {
        if (feedApp.selectedFeeds[feedid]==true) ids.push(parseInt(feedid));
    }

    var export_start = parse_timepicker_time($("#export-start").val());
    var export_end = parse_timepicker_time($("#export-end").val());
    var export_interval = $("#export-interval").val();
    var export_timeformat = $("#export-timeformat").val();
    
    if (!export_start) {alert(str_enter_valid_start_date); return false; }
    if (!export_end) {alert(str_enter_valid_end_date); return false; }
    if (export_start>=export_end) {alert(str_start_before_end); return false; }
    if (export_interval=="") {alert(str_interval_for_download); return false; }
    
    var downloadsize = calculate_download_size(ids.length);
    
    var enable_average = $("#export-average")[0].checked*1;
    var average_str = "&average="+enable_average;
    
    var params = {
        ids: ids.join(","),
        start: export_start*1000,
        end: export_end*1000,
        interval: export_interval,
        average: enable_average,
        timeformat: export_timeformat,
        csv: 1,
        skipmissing: 0,
        limitinterval: 0
    }
    
    var param_parts = [];
    for (var z in params) {
        param_parts.push(z+"="+params[z]);
    }
    
    var url = path+"feed/data.json?"+param_parts.join("&");
    
    if (downloadsize>(downloadlimit*1048576)) {
        var r = confirm(str_large_download);
        if (!r) return false;
    }
    window.open(url);
});

function calculate_download_size(feedcount){

    var export_start = parse_timepicker_time($("#export-start").val());
    var export_end = parse_timepicker_time($("#export-end").val());
    var export_interval = $("#export-interval").val();
    
    if (export_interval=="daily") {
        export_interval = 86400;
    } else if (export_interval=="weekly") {
        export_interval = 86400*7;
    } else if (export_interval=="monthly") {
        export_interval = 86400*30; 
    } else if (export_interval=="annual") {
        export_interval = 86400*365;  
    } else if (export_interval=="original") {
        // Get interval from meta data if available
        var feedid = false;
        for (feedid in feedApp.selectedFeeds) {
            if (feedApp.selectedFeeds[feedid]==true) break;
        }
        if (feeds[feedid].interval!=undefined) {
            export_interval = feeds[feedid].interval;
        } else {
            export_interval = 10;
        }
    }
    
    var export_timeformat_size = ($("#export-timeformat").prop('checked') ? 20 : 11); // bytes per timestamp
    var export_data_size = 7;                                                         // avg bytes per data
    
    var downloadsize = 0;
    if (!(!isNumeric(export_start) || !isNumeric(export_end) || !isNumeric(export_interval) || export_start > export_end )) {
        downloadsize = ((export_end - export_start) / export_interval) * (export_timeformat_size + export_data_size) * feedcount; 
    }
    
    if (downloadsize<1024) {
        $("#downloadsize").html((downloadsize).toFixed(0)+" bytes");
    } else if (downloadsize<(1024*1024)) {
        $("#downloadsize").html((downloadsize / 1024).toFixed(2)+" kB");
    } else {
        $("#downloadsize").html((downloadsize / 1024 / 1024).toFixed(2)+" MB");
    }
    
    $("#downloadsizeplaceholder").css('color', (downloadsize == 0 || downloadsize > (downloadlimit*1048576) ? 'red' : ''));
    
    return downloadsize;
}

function parse_timepicker_time(timestr){
    return ecDateTime.toUnixSeconds(timestr);
}
