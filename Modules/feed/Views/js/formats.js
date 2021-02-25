function format_time(time) {
    time = time * 1000;
    var servertime = new Date().getTime(); // - table.timeServerLocalOffset;
    var update = new Date(time).getTime();
    
    var delta = servertime - update;
    var secs = Math.abs(delta) / 1000;
    var mins = secs / 60;
    var hour = secs / 3600;
    var day = hour / 24;
    
    var updated = secs.toFixed(0) + "s";
    if ((update == 0) || (!$.isNumeric(secs))) updated = "n/a";
    else if (secs.toFixed(0) == 0) updated = "now";
    else if (day > 7 && delta > 0) updated = "inactive";
    else if (day > 2) updated = day.toFixed(1) + " days";
    else if (hour > 2) updated = hour.toFixed(0) + " hrs";
    else if (secs > 180) updated = mins.toFixed(0) + " mins";
    
    secs = Math.abs(secs);
    var color = "#DC3545";
    if (delta < 0) color = "rgb(60,135,170)"
    else if (secs < 25) color = "rgb(50,200,50)"
    else if (secs < 60) color = "rgb(240,180,20)"; 
    else if (secs < (3600*2)) color = "rgb(255,125,20)"
    
    return {color:color,value:updated};
}

function format_size(bytes) {
    if (!$.isNumeric(bytes)) {
        return "n/a";
    } else if (bytes < 1024) {
        return bytes + "B";
    } else if (bytes < 1024 * 100) {
        return (bytes / 1024).toFixed(1) + "KB";
    } else if (bytes < 1024 * 1024) {
        return Math.round(bytes / 1024) + "KB";
    } else if (bytes <= 1024 * 1024 * 1024) {
        return Math.round(bytes / (1024 * 1024)) + "MB";
    } else {
        return (bytes / (1024 * 1024 * 1024)).toFixed(1) + "GB";
    }
}

function format_value(value) {
    if (value == null) return "NULL";
    value = parseFloat(value);
    if (value >= 1000) value = parseFloat(value.toFixed(0));
    else if (value >= 100) value = parseFloat(value.toFixed(1));
    else if (value >= 10) value = parseFloat(value.toFixed(2));
    else if (value <= -1000) value = parseFloat(value.toFixed(0));
    else if (value <= -100) value = parseFloat(value.toFixed(1));
    else if (value < 10) value = parseFloat(value.toFixed(2));
    return value;
}
