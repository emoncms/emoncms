$(function() {
    // hide expanded groups if the switch is set to expanded false
    $(".expand-all").on("click", function(event) {
        let $btn = $(this);
        let $container = $($btn.data("target"));
        let $collapsableToggles = $container.find('[data-toggle="collapse"]');
        let $collapsables = $(".node .collapse");
        $collapsables.stop(true, true);
        if ($btn.is(".in")) {
            $collapsables.collapse("hide");
            $collapsableToggles.addClass("collapsed");
        } else {
            $collapsables.collapse("show");
            $collapsableToggles.removeClass("collapsed");
        }
    });
    
    // on collapsed or shown alter the toggle button
    $(document).on("shown hidden", ".node .collapse", function(e) {
        let $btn = $(".expand-all");
        let $container = $($btn.data("target"));
        let total_groups = $container.find(".collapse").length;
        let total_visible = $container.find(".collapse.in").length;
        let title = "";
        if (total_visible == total_groups) {
            //toggle switch to shrink
            $btn.addClass("in");
            title = $btn.data("title-reduced");
        } else if (total_visible == 0) {
            //toggle switch to expand
            $btn.removeClass("in");
            title = $btn.data("title-expanded");
        }
        $btn.attr("title", title);
    });
});

// Calculate and color updated time
function list_format_updated(time) {
    time = time * 1000;
    var servertime = new Date().getTime(); // - table.timeServerLocalOffset;
    var update = new Date(time).getTime();
    
    var secs = (servertime - update) / 1000;
    var mins = secs / 60;
    var hour = secs / 3600;
    var day = hour / 24;
    
    var updated = secs.toFixed(0) + "s";
    if (update == 0 || !$.isNumeric(secs)) updated = "n/a";
    else if (secs < 0) updated = secs.toFixed(0) + "s";
    // update time ahead of server date is signal of slow network
    else if (secs.toFixed(0) == 0) updated = "now";
    else if (day > 7) updated = "inactive";
    else if (day > 2) updated = day.toFixed(1) + " days";
    else if (hour > 2) updated = hour.toFixed(0) + " hrs";
    else if (secs > 180) updated = mins.toFixed(0) + " mins";
    
    secs = Math.abs(secs);
    var color = "rgb(255,0,0)";
    if (secs < 25) color = "rgb(50,200,50)";
    else if (secs < 60) color = "rgb(240,180,20)";
    else if (secs < 3600 * 2) color = "rgb(255,125,20)";
    
    return "<span style='color:" + color + ";'>" + updated + "</span>";
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

/**
* Resize all the columns to the same width
*
* add data-col-padding="[width]" to any element you want additional padding to
* add data-col-width="[width]" to any element to fix it's column's width
*/
function autowidth($container) {
    let widths = {}, // only store widest column values against each selector
    default_padding = 20;
    // tbody
    $container.find(".tbody [data-col]").each(function() {
        let $this = $(this),
        padding = $this.data("col-padding") || default_padding,
        width = $this.width() + padding,
        col = $this.data("col");
        
        // save the col and largest width for all the columns
        widths[col] = widths[col] || 0;
        
        if (width > widths[col] || width == "auto") {
            widths[col] = width;
        }
    });
    
    // thead
    // set a fix width column if [data-col-width] is set in the first row
    $container.find(".thead [data-col]").each(function() {
        let $this = $(this),
        col = $this.data("col");
        // "auto" uses up remainder of space
        // @see: onResize()
        if ($this.data("col-width")) {
            // overwrite list of widths
            widths[col] = $this.data("col-width");
        }
    });
    
    // resize each column to the largest width
    for (col in widths) {
        if (widths[col] != "auto") {
            $('[data-col="' + col + '"]').width(widths[col]);
        }
    }
    onResize(); // redraw columns if they overlap
}

/**
* responsive show/hide columns on window resize
*
* requires that all the columns are already set to the same width
* @see autowidth()
*
* read all columns with a data-col attribute
* sorts the value a-z
* hides the columns if not enough room (oldest first)
*/
function onResize() {
    // only take the first row for comparison as autowidth() should have already resized the columns
    let $container = $("#table"),
        $row = $container.find(".thead:first"),
        rowWidth = $row.width(), // total available space
        columnsWidth = 0, // increments for each column
        hidden = {},
        cols = {},
        min_auto_width = 200
    
    // get a list of all the columns
    $row.find("[data-col]").each(function() {
        let $col = $(this);
        cols[$col.data("col")] = $col;
    });
    // sort columns in order to hide first
    keys = Object.keys(cols).sort();
    
    // columnsWidth += remainder
    // check if each column fits the width, add to hidden list if not
    for (k in keys) {
        // let $col = order[keys[s]]
        let $col = cols[keys[k]];
        if ($col.data("col-width") != "auto") {
            columnsWidth += $col.outerWidth(); // includes padding
        }
        // if this column is too wide add to hidden list
        if (keys[k] !== keys[0]) hidden[keys[k]] = columnsWidth > rowWidth;
    }
    // resize all divs in the "auto" column
    var remainder = rowWidth - columnsWidth,
        numberOfNodes = $container.find(".node").length,
        numberOfAutocolumns = parseInt(
            $('.thead [data-col-width="auto"]').length / numberOfNodes
        ),
        r = parseInt(remainder / numberOfAutocolumns); // allow for multiple "auto" columns
    
    // if "auto" column les than min width hide it
    $container.find('.thead [data-col-width="auto"]').each(function() {
        let col = $(this).data("col")
        $container.find('[data-col="' + col + '"]').width(r);
        hidden[col] = remainder < min_auto_width;
    });
    
    //show all, then hide relevant columns
    $("[data-col]").show();
    for (key in hidden) {
        if (hidden[key]) $('[data-col="' + key + '"]').hide();
    }
}
/**
*
* @param {Function} callback function to call after callback delay
* @param {int} timeout ms to wait
*/
function watchResize(callback, timeout) {
    // exit if callback not a function
    if (typeof callback == "undefined" || !(callback instanceof Function)) return;
    timeout = timeout || 50; // defaults to 50ms
    
    var resizeTimer;
    // debounce (ish) script to improve performance
    $(window).on("resize", function(e) {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // resize the columns to fit the view
            callback();
        });
    });
}

function list_format_size(bytes) {
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

/**
* alter the Number primitive to include a new method to pad out numbers with zeros
* @param int size - number of characters to fill with zeros
* @return string
*/
Number.prototype.pad = function(size) {
    var s = String(this);
    while (s.length < (size || 2)) {
        s = "0" + s;
    }
    return s;
};
