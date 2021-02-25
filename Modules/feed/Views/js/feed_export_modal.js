// ---------------------------------------------------------------------------------------------
// Export feature
// ---------------------------------------------------------------------------------------------
$(".feed-download").click(function(){
    var ids = [];
    for (var feedid in selected_feeds) {
        if (selected_feeds[feedid]==true) ids.push(parseInt(feedid));
    }

    $("#export").attr('feedcount',ids.length);
    calculate_download_size(ids.length);

    if ($("#export-timezone-offset").val()=="") {   
        var timezoneoffset = user.timezoneoffset();
        if (timezoneoffset==null) timezoneoffset = 0;
        $("#export-timezone-offset").val(parseInt(timezoneoffset));
    }
    
    $('#feedExportModal').modal('show');
});

$('#datetimepicker1').datetimepicker({
    language: 'en-EN'
});

$('#datetimepicker2').datetimepicker({
    language: 'en-EN',
    useCurrent: false //Important! See issue #1075
});

now = new Date();
today = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 00, 00);
var picker1 = $('#datetimepicker1').data('datetimepicker');
var picker2 = $('#datetimepicker2').data('datetimepicker');
picker1.setLocalDate(today);
picker2.setLocalDate(today);
picker1.setEndDate(today);
picker2.setStartDate(today);

$('#datetimepicker1').on("changeDate", function (e) {
    $('#datetimepicker2').data("datetimepicker").setStartDate(e.date);
});

$('#datetimepicker2').on("changeDate", function (e) {
    $('#datetimepicker1').data("datetimepicker").setEndDate(e.date);
});

$('#export-interval, #export-timeformat').on('change', function(e) {
    $("#export-timezone-offset").prop("disabled", $("#export-timeformat").prop('checked'));
    calculate_download_size($("#export").attr('feedcount')); 
});

$('#datetimepicker1, #datetimepicker2').on('changeDate', function(e) {
    calculate_download_size($("#export").attr('feedcount')); 
});



function parse_timepicker_time(timestr){
    var tmp = timestr.split(" ");
    if (tmp.length!=2) return false;

    var date = tmp[0].split("/");
    if (date.length!=3) return false;

    var time = tmp[1].split(":");
    if (time.length!=3) return false;

    return new Date(date[2],date[1]-1,date[0],time[0],time[1],time[2],0).getTime() / 1000;
}

$("#export").click(function()
{
    var ids = [];
    for (var feedid in selected_feeds) {
        if (selected_feeds[feedid]==true) ids.push(parseInt(feedid));
    }

    var export_start = parse_timepicker_time($("#export-start").val());
    var export_end = parse_timepicker_time($("#export-end").val());
    var export_interval = $("#export-interval").val();
    var export_timezone_offset = parseInt($("#export-timezone-offset").val());
    var export_timeformat = ($("#export-timeformat").prop('checked') ? 1 : 0);
    if (export_timeformat) { export_timezone_offset = 0; }

    if (!export_start) {alert("Please enter a valid start date."); return false; }
    if (!export_end) {alert("Please enter a valid end date."); return false; }
    if (export_start>=export_end) {alert("Start date must be further back in time than end date."); return false; }
    if (export_interval=="") {alert("Please select interval to download."); return false; }
    
    var downloadsize = calculate_download_size(ids.length);
    
    if (ids.length>1) {
        url = path+"feed/csvexport.json?ids="+ids.join(",")+"&start="+(export_start+(export_timezone_offset))+"&end="+(export_end+(export_timezone_offset))+"&interval="+export_interval+"&timeformat="+export_timeformat+"&name="+ids.join("_");
    } else {
        url = path+"feed/csvexport.json?id="+ids.join(",")+"&start="+(export_start+(export_timezone_offset))+"&end="+(export_end+(export_timezone_offset))+"&interval="+export_interval+"&timeformat="+export_timeformat+"&name="+ids.join("_");
    }

    if (downloadsize>(downloadlimit*1048576)) {
        var r = confirm("Estimated download file size is large.\nServer could take a long time or abort depending on stored data size.\nLimit is "+downloadlimit+"MB.\n\nTry exporting anyway?");
        if (!r) return false;
    }
    window.open(url);
});

function calculate_download_size(feedcount){

    var export_start = parse_timepicker_time($("#export-start").val());
    var export_end = parse_timepicker_time($("#export-end").val());
    var export_interval = $("#export-interval").val();
    var export_timeformat_size = ($("#export-timeformat").prop('checked') ? 20 : 11); // bytes per timestamp
    var export_data_size = 7;                                                         // avg bytes per data
    
    var downloadsize = 0;
    if (!(!$.isNumeric(export_start) || !$.isNumeric(export_end) || !$.isNumeric(export_interval) || export_start > export_end )) { 
        downloadsize = ((export_end - export_start) / export_interval) * (export_timeformat_size + export_data_size) * feedcount; 
    }
    $("#downloadsize").html((downloadsize / 1024 / 1024).toFixed(2));

    $("#downloadsizeplaceholder").css('color', (downloadsize == 0 || downloadsize > (downloadlimit*1048576) ? 'red' : ''));
    
    return downloadsize;
}
