function list_format_color(color_code) {
    var colours = [
        "rgb(60,135,170)",  // 0: blue
        "rgb(50,200,50)",   // 1: green
        "rgb(240,180,20)",  // 2: yellow
        "rgb(255,125,20)",  // 3: orange
        "rgb(255,0,0)",     // 4: red
        "rgb(150,150,150)", // 5: grey
    ];
    return colours[color_code];
}

function list_format_updated_obj(time, interval = 1) {
    var servertime = (new Date()).getTime() - app.timeServerLocalOffset;
    time = new Date(time * 1000);
    var update = time.getTime();

    var delta = servertime - update;
    var secs = Math.abs(delta) / 1000;
    var mins = secs / 60;
    var hour = secs / 3600;
    var day = hour / 24;

    var updated = secs.toFixed(0) + "s";
    if ((update == 0) || (!$.isNumeric(secs))) updated = "n/a";
    else if (secs.toFixed(0) == 0) updated = "now";
    else if (day > 365 && delta > 0) updated = time.toLocaleDateString("en-GB",{year:"numeric", month:"short"});
    else if (day > 31 && delta > 0) updated = time.toLocaleDateString("en-GB",{month:"short", day:"numeric"});
    else if (day > 2) updated = day.toFixed(0) + " days";
    else if (hour > 2) updated = hour.toFixed(0) + " hrs";
    else if (secs > 180) updated = mins.toFixed(0) + " mins";

    secs = Math.abs(secs);

    var color_code = 5;                                  // grey    - Inactive

    if (interval == 1) {                                 // => Variable Interval Feeds
        if (delta < 0) color_code = 0;                   // blue    - Ahead of time!
        else if (secs < 30) color_code = 1;              // green   - < 30s
        else if (secs < 60) color_code = 2;              // yellow  - < 2 min
        else if (secs < (60 * 60)) color_code = 3;       // orange  - < 1h
        else if (secs < (3600*24*31)) color_code = 4;    // red     - < 1 month
    }
    else {                                               // => Fixed Interval Feeds
        if (delta < 0) color_code = 0;                   // blue    - Ahead of time!
        else if (secs < interval*3) color_code = 1;      // green   - < 3x interval
        else if (secs < interval*6) color_code = 2;      // yellow  - < 6x interval
        else if (secs < interval*12) color_code = 3;     // orange  - < 12x interval
        else if (secs < (3600*24*31)) color_code = 4;    // red     - < 1 month
    }

    var color = list_format_color(color_code);

    return {color:color, color_code: color_code, value:updated};
}

// Number of ms from last update
function list_format_last_update(time) {
    time = time * 1000;
    var servertime = new Date().getTime() - app.timeServerLocalOffset;
    var update = new Date(time).getTime();
    var delta = servertime - update;
    return delta
}


// Format value dynamically
function list_format_value(value) {
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
