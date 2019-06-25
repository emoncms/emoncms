/**
 * mark toggle button as opened if all accordions are opened
 * 
 * @parm {bool} state == true if all visible
 */
function setExpandButtonState(state){
    if(typeof state == 'undefined') return
    $container = $('#table')
    $btn_expand = $('#expand-collapse-all')
    // set the icon and button title based on state (true == open)
    $icon_expand = $btn_expand.find('.icon')
    $icon_expand
      .toggleClass('icon-resize-small', state)
      .toggleClass('icon-resize-full', !state)
    if(!$btn_expand.data('original-title')) $btn_expand.data('original-title', $btn_expand.attr('title'))
    $btn_expand.attr('title', !state ? $btn_expand.data('alt-title') : $btn_expand.data('original-title'))
}
function expandAllNodes(state){
    $container = $('#table')
    // true if all expanded
    if (typeof state == 'undefined') state = $container.find('.collapsed').length == 0
    $container.find('.collapse').collapse(state ? 'hide':'show')
}

$(function() {
    // toggle-able show/hide button to expand/hide all the collapsable items
    $container = $('#table')
    $btn_expand = $('#expand-collapse-all')
    $icon_expand = $btn_expand.find('.icon')

    // collapse or expand all nodes
    $btn_expand.on('click', function(){
        expandAllNodes()
    })

    // once accordion has finished opening or closing check if all are open and change the button
    // saves the state to local storage to recall on next page load
    save_node_state_timeout = null
    // store the state once animation finished
    //data-toggle="collapse"

    $(document).on("shown hidden", '#table .tbody.collapse', function(e){
    	$header = $('[data-target="#'+e.target.id+'"]')
        if (e.type == 'hidden'){
            $header.addClass('collapsed')
            $header.find('.icon-indicator').removeClass('icon-chevron-down').addClass('icon-chevron-right')
        } else {
            $header.removeClass('collapsed')
            $header.find('.icon-indicator').removeClass('icon-chevron-right').addClass('icon-chevron-down')
        }
        state = $container.find('.collapsed').length == 0
        setExpandButtonState(state)

        // debounce the call to save the current state to a local storage (cookie)
        clearTimeout(save_node_state_timeout)
        save_node_state_timeout = setTimeout(function(){
            docCookies.setItem(local_cache_key, JSON.stringify(nodes_display));
        },100)
    })
    // record the state change before animation starts
    $(document).on('hide show', '#table .tbody.collapse', function(event){
        nodes_display[event.target.dataset.node] = event.type == 'show'
    })
    // select/de-select all node checkboxes
    $('#select-all').on('click',function(){
        // state = true if all selected
        $this = $(this)
        state = $this.data('state') != false
        // remember the original title
        $this.data('title-original', $this.data('title-original') ? $this.data('title-original') : $this.attr('title'))
        // show different icon on state change
        $this.find('.icon').toggleClass('icon-ban-circle', state)
        $this.find('.icon').toggleClass('icon-check', !state)
        // make the selection with custom event handler
        $("#table .select input[type='checkbox']").prop('checked', state).trigger('select').trigger('change')
        // set the title
        title = state ? $this.data('alt-title') : $this.data('title-original')
        $this.attr('title', title)
        // flip the toggle state
        $this.data('state',!state)
        // expand all accordions if chosen to select all
        if (state===true) expandAllNodes(false)
    })
    
    // stop accordion from collapsing if any feeds selected within the node
    $('#table').change('.feed-select', function(e) {
        let $parent = $(e.target).parents('.collapse')
        let id = $parent.attr('id')
        let $collapse = $('.accordion-toggle[data-target="#'+id+'"]')
        checked_checkboxes = $parent.find(':checkbox:checked').length;
        if(checked_checkboxes>0){
            $collapse.attr('data-toggle',false)
        } else {
            $collapse.attr('data-toggle','collapse')
        }
    })

    // @todo: not yet implemented. ui element not chosen on to trigger this action
        // select or deselect all the checkboxes for a node
        function selectAllInNode(e){
            e.preventDefault()
            e.stopPropagation()
            $container = $(e.target).parents('.accordion').first()
            $container.find('.collapse').collapse('show')
            $inputs = $container.find(':checkbox')
            $selected = $container.find(':checkbox:checked')
            // use a custom trigger so not to confuse with the click event
            // if all selected de-select else select all
            $inputs.prop('checked', $inputs.length != $selected.length).trigger('select')
        }
        // check / clear all selection
        // $(document).on('click','.input-list .has-indicator', selectAllInNode)
        
        // feed list view already makes use of the click event
        // $(document).on('mouseup','.feed-list .has-indicator', selectAllInNode)
});


// Calculate and color updated time
function list_format_updated(time) {
    var fv = list_format_updated_obj(time);
    return "<span class='last-update' style='color:" + fv.color + ";'>" + fv.value + "</span>";
}

function list_format_updated_obj(time) {
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
    var color = "rgb(255,0,0)";
    if (delta < 0) color = "rgb(60,135,170)"
    else if (secs < 25) color = "rgb(50,200,50)"
    else if (secs < 60) color = "rgb(240,180,20)"; 
    else if (secs < (3600*2)) color = "rgb(255,125,20)"
    
    return {color:color,value:updated};
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
    // resize columns based on columns in tbody and thead
    $container.find("[data-col]").each(function() {
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
        $container.find('[data-col="' + col + '"]').width(r-10);
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
