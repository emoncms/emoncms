
// Get filter element
var filter = document.getElementById("filter");

// auto refresh
// update_feed_list();
setTimeout(update_feed_list,1);
setInterval(update_feed_list,5000);
filter.oninput = update_feed_list;

var firstLoad = true;
function update_feed_list() {
    var public_username_str = "";
    if (public_userid) public_username_str = public_username+"/";
    var requestTime = (new Date()).getTime();

    $.ajax({ url: path+public_username_str+"feed/list.json?meta=1", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
        if( typeof app !== 'undefined') app.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
        if (data.message!=undefined && data.message=="Username or password empty") {
            window.location.href = "/";
            return false;
        }
    
        // Show/hide no feeds alert
        $('#feed-loader').hide();
        if (data.length == 0){
            if (public_userid) {
                $("#public-feeds-none").show();
            } else {
                $("#feed-none").show();
            }
            // Clear Vue data
            if (typeof feedApp !== 'undefined') {
                feedApp.nodes = {};
                feedApp.feeds = {};
            }
        } else {
            $("#feed-none").hide();
            $("#public-feeds-none").hide();
        }
        
        // Filter feeds
        feeds = {};
        filterText = filter.value.toLowerCase()
        for (var z in data) {
            if (filterText == '' || data[z].name.toLowerCase().includes(filterText)) {
                feeds[data[z].id] = data[z];
            }
        }
        
        // Group feeds by node
        nodes = {};
        for (var z in feeds) {
            var node = feeds[z].tag;
            if (nodes[node]==undefined) nodes[node] = [];

            if (nodes_display[node]==undefined) nodes_display[node] = true;
            nodes[node].push(feeds[z]);
        }
        
        // Auto-collapse logic for first load
        if (firstLoad && Object.keys(nodes).length > 1 && Object.keys(nodes_display).length == 0) {
            for (var node in nodes) {
                nodes_display[node] = false;
            }
        }
        firstLoad = false;
        
        // Update Vue.js data
        if (typeof feedApp !== 'undefined') {
            feedApp.nodes = Object.assign({}, nodes);
            feedApp.feeds = Object.assign({}, feeds);
            feedApp.nodesDisplay = Object.assign({}, nodes_display);
            
            // Preserve existing selections
            var newSelectedFeeds = {};
            for (var feedid in feeds) {
                newSelectedFeeds[feedid] = selected_feeds[feedid] || false;
            }
            feedApp.selectedFeeds = newSelectedFeeds;
        }
        
    }}); // end of ajax callback
}// end of update_feed_list() function

// Updated event handlers for Vue.js integration

// Handle feed graph clicks via custom event  
document.addEventListener('feedGraphClick', function(e) {
    var feedid = e.detail.feedid;
    var public_username_str = "";
    if (public_userid) public_username_str = public_username+"/";
    
    window.location = path+public_username_str+feedviewpath+feedid;
});

// Handle public toggle clicks via custom event  
document.addEventListener('feedPublicClick', function(e) {
    var feedid = e.detail.feedid;
    e.stopPropagation();
    // Existing functionality preserved - can be extended here
});

// Legacy jQuery handlers for elements not in Vue
$("#table").on("click select",".feed-select",function(e) {
    feed_selection();
});

// Keep existing graph button functionality
$(".feed-graph").click(function(){
    var graph_feeds = [];
    for (var feedid in selected_feeds) {
        if (selected_feeds[feedid]==true) graph_feeds.push(feedid);
    }

    var public_username_str = "";
    if (public_userid) public_username_str = public_username+"/";
    
    window.location = path+public_username_str+feedviewpath+graph_feeds.join(",");      
});

function buildFeedNodeList() {
    node_names = [];
    for (n in nodes) {
        let feed = nodes[n];
        node_names.push(feed[0].tag)
    }
    autocomplete(document.getElementById("feed-node"), node_names);
}


// ---------------------------------------------------------------------------------------------
// EDIT FEED
// ---------------------------------------------------------------------------------------------
$(".feed-edit").click(function() {
    $('#feedEditModal').modal('show');
    var edited_feeds = $.map(selected_feeds, function(val,key){ return val ? key: null });
    var feedid = 0;
    // Now allows for multiple feed selection
    for (var z in selected_feeds) {
        if (selected_feeds[z]){
            feedid = z;
            if (edited_feeds.length == 1) {
                $("#feed-name").prop('disabled',false).val(feeds[feedid].name);
                $("#edit-feed-name-div").show();               
            } else {
                $("#edit-feed-name-div").hide();
            }
            $("#feed-node").val(feeds[feedid].tag);
            var checked = false; if (feeds[feedid]['public']==1) checked = true;
            $("#feed-public")[0].checked = checked;
            
            // pre-select item if already set
            let $dropdown = $('#feed_unit_dropdown');
            $dropdown.val(feeds[feedid].unit);
            // set the dropdown to "other" if value not in list
            let options = [];
            $dropdown.find('option').each(function(key,elem){
                options.push(elem.value);
            })
            if (options.indexOf(feeds[feedid].unit) == -1) {
                $('#feed_unit_dropdown_other').val(feeds[feedid].unit);
                $dropdown.val('_other');
            }
            // show / hide "other" free text field on load and on change if "other" selected in dropdown
            if($dropdown.val()=='_other') {
                $dropdown.next('input').show();
            }else{
                $dropdown.next('input').hide();
            }
            $dropdown.change(function(event){
                if(event.target.value=='_other') {
                    $(event.target).next('input').show();
                }else{
                    $(event.target).next('input').hide();
                }
            });
        }
    }
    
    buildFeedNodeList();
});

$(".feed-node").on('input', function(event){
    $('#feed-node').val($(this).val());
});

$(".feed-edit-save").click(function() {
    var feedid = 0;
    var edited_feeds = $.map(selected_feeds, function(val,key){ return val ? key: null });
    
    var edit_field = $(this).attr("field");

    var error = false;

    for (var z in selected_feeds) {
        if (selected_feeds[z]) {
            feedid = z; 
            
            var fields = {}

            if (edit_field=="name" && edited_feeds.length==1) {
                fields.name = $("#feed-name").val();
            }
            
            if (edit_field=="node") {
                fields.tag = $("#feed-node").val();
            }
            
            if (edit_field=="public") {
                var publicfeed = 0;
                if ($("#feed-public")[0].checked) publicfeed = 1;  
                fields.public = publicfeed;
            }

            if (edit_field=="unit") {
                var unit = $('#feed_unit_dropdown').val();
                unit = unit == '_other' ? $('#feed_unit_dropdown_other').val() : unit;
                fields.unit = unit;
            }
            
            // only send changed values
            var data = {};
            for (f in fields) {
                // console.log(fields[f],feeds[feedid][f],{matched:fields[f]===feeds[feedid][f]})
                if (!(fields[f]===feeds[feedid][f])) data[f] = fields[f];
            }
            // console.log(Object.keys(data).length);
            // dont send ajax if nothing changed
            if (Object.keys(data).length==0) {
                $('#feedEditModal').modal('hide');
                return;
            }
            $('#feed-edit-save-message').text('').hide();
            $.ajax({ url: path+"feed/set.json?id="+feedid+"&fields="+JSON.stringify(data), dataType: 'json'})
            .done(function(response) {
                if(response.success !== true) {
                    // error
                    $('#feed-edit-save-message').text(response.message).fadeIn();
                    error = true;
                }
            })
        }
    }
    
    if (!error) {
        update_feed_list();
        $('#feedEditModal').modal('hide');
        $('#feed-edit-save-message').text('').hide();
    }
    
});

// ---------------------------------------------------------------------------------------------
// DELETE FEED
// ---------------------------------------------------------------------------------------------

/**
 * getFeedProcess
 * 
 * Scans all input processes and identifies which processes are linked to feeds.
 * Returns an object mapping feed IDs to their associated input, process definition, and feed ID.
 * Used to determine which feeds are referenced by input process lists for safe deletion and management.
 *
 * @returns {Object} feedInputs - Map of feed IDs to input/process details.
 */
function getFeedProcess() {
    const feedInputs = {};

    // 1. Build a map of processes that are linked to feeds
    const feedProcesses = {};
    for (const key in process_vue.processes_by_key) {
        const proc = process_vue.processes_by_key[key];

        // Cycle through args, check for feed args
        for (let i = 0; i < proc.args.length; i++) {
            const arg = proc.args[i];
            if (arg.type === 2) {
                // Could filter for set engines field here but think it's useful to flag all feed linked processes
                feedProcesses[key] = proc;
                break; // No need to check further args
            }
        }
    }

    // 2. For each input, decode its processList and check for feed linked processes
    for (const input of Object.values(process_vue.inputs)) {
        if (!input.processList.length) continue;
        const decodedList = process_api.decode(input.processList);

        decodedList.forEach(procItem => {
            const procDef = feedProcesses[procItem.fn];
            if (!procDef) return;
            procDef.args.forEach((arg, idx) => {
                if (arg.type === 2) {
                    const feedid = procItem.args[idx];
                    feedInputs[feedid] = {
                        input: {
                            nodeid: input.nodeid,
                            name: input.name,
                            inputid: input.id,
                            // processList: decodedList (Not currently used)
                        },
                        process: procDef,
                        feedid: feedid
                    };
                }
            });
        });
    }

    return feedInputs;
}

/**
 * output what feeds have been selected in the overlay modal box
 *
 * @return void
 */
function showSelectedFeeds(feed_inputs) {
    let total_selected = 0;
    let total_input_processes_linked = 0;
    let total_virtual_feed_processes_linked = 0;
    let feedListShort = '';

    for (const feedid in selected_feeds) {
        if (!selected_feeds[feedid]) continue;
        total_selected++;
        const feed = feeds[feedid];
        feedListShort += `[${feedid}] ${feed.tag}:${feed.name}, `;

        // Virtual feed: count its processList items
        if (parseInt(feed.engine) === 7 && feed.processList != "") {
            total_virtual_feed_processes_linked += process_api.decode(feed.processList).length;
        }
        // Non-virtual: count input processes referencing this feed
        else if (feed_inputs[feedid]) {
            if (Array.isArray(feed_inputs[feedid])) {
                total_input_processes_linked += feed_inputs[feedid].length;
            } else {
                total_input_processes_linked += 1;
            }
        }
    }

    // Remove trailing comma
    if (feedListShort.endsWith(', ')) feedListShort = feedListShort.slice(0, -2);

    // Build summary
    let total_summary = '<div id="deleteFeedModalSelectedItems">';
    let feedProcessList = '';

    if (total_selected === 1) {
        total_summary += `<h5>${feedListShort}</h5>`;
    } else {
        total_summary += `<h5 title="${feedListShort}"><?php echo tr('%s Feeds selected') ?> <i class="icon icon-question-sign"></i></h5>`.replace('%s', total_selected);
    }

    // Compose the combined message
    if (total_input_processes_linked > 0 || total_virtual_feed_processes_linked > 0) {
        let msg = '';
        if (total_input_processes_linked > 0) {
            msg += total_input_processes_linked + ' <?php echo tr("input processes") ?>';
        }
        if (total_virtual_feed_processes_linked > 0) {
            if (msg.length > 0) msg += ' and ';
            msg += total_virtual_feed_processes_linked + ' <?php echo tr("virtual feed processes") ?>';
        }
        msg += total_selected === 1
            ? ' <?php echo tr("associated with this feed") ?>'
            : ' <?php echo tr("associated with these feeds") ?>';

        feedProcessList = `<span class="badge badge-default" style="padding-left:4px;margin-right:6px"><i class="icon icon-white icon-exclamation-sign"></i> ${msg}</span>`;
    }

    total_summary += '</div>';

    $("#feeds-to-delete").html(total_summary);
    $("#feedProcessList").html(feedProcessList);
}

/**
 * show the trim start time in the date time picker and input field
 * 
 * will also highlight a button if it matches the currently selected timestamp
 *
 * @param int start_time unix timestamp (seconds)
 */
function showFeedStartDate(start_time){
    let startDate = start_time==0 ? new Date() : new Date(start_time*1000);
    $datetimepicker = $('#feed_trim_datetimepicker');
    $datetimepicker
        .datetimepicker({startDate: startDate}) // restrict calendar selection to the start time
        .datetimepicker('setValue', startDate) // set the date/time picker to the start time
        .on('changeDate', function(event){
            // mark any matching buttons as active
            $('[data-relative_time]').each(function(i,elem){
                if ($(elem).data('startdate') != event.date) {
                    $(this).removeClass('active')
                }
            });
        });
}

/**
 * Initialises the different events to enable the "relative date" selections below the date/time picker
 * 
 * Set the data property of each button to store correct Date() for each button. Each button must have a 
 * "data-relative_time" attribute with one of the following values:-
 *   "-2y", "-1y", "start" or "now" (default)
 *
 * Each button shows a formatted date in the input field and also sets the date time picker to the relevant position
 * @param int start_time the earliest possible timestamp for all the selected feeds - does not allow trimming beyond this point
 *
 * @return void
 */
function initRelativeStartDateButtons(start_time){
    let startDate = start_time>0 ? new Date(start_time*1000) : new Date();

    $('[data-relative_time]').each(function(i,v){
        $btn = $(this);
        // add more cases here for additional options (and also data-relative_time='xyz' in the html)
        // returns function so that the dates are calculated to when the user clicks the buttons
        switch ($btn.data('relative_time')) {
        case '-2y':
            relativeTime = (function(){ now = new Date(); return new Date(now.getFullYear()-2,now.getMonth(),now.getDate(),now.getHours(),now.getMinutes(),now.getSeconds(),now.getMilliseconds()) });
            break;
        case '-1y':
            relativeTime = (function(){ now = new Date(); return new Date(now.getFullYear()-1,now.getMonth(),now.getDate(),now.getHours(),now.getMinutes(),now.getSeconds(),now.getMilliseconds()) });
            break;
        case 'start':
            relativeTime = startDate;
            break;
        default:
            relativeTime = new Date();
        }
        relativeTime = typeof relativeTime === 'function' ? relativeTime() : relativeTime;
        // set the timestamp as a data property of the button so that it can be referenced on click
        $btn.data('startdate', relativeTime.valueOf() );
        // make sure the calculated date is not beyond the start date
        if (relativeTime < startDate) {
            $btn.hide() // hide button date is beyond start date
            $btn.css({'font-style':'italic', color:'#9a9eaa'});
            $btn.attr('title',$btn.attr('title')+' - [<?php echo tr('Out of range')?>]');
        }
    })
    // open date picker on input focus
    $('#trim_start_time').on('focus', function(event){ $datetimepicker.datetimepicker('show') });
    
    // alter the trim date / time picker on button presses
    $('[data-relative_time]').click(function(event){
        event.preventDefault();
        $btn = $(this);
        $btn.addClass('active').siblings().removeClass('active');
        $input = $('#trim_start_time');
        // get starttime from button's data
        date = new Date($btn.data('startdate'));
        // restrict selection to the earliest possible date
        if (date < startDate) {
            date = startDate;
        }
        // rebuild the date string from the new date object
        Y = date.getFullYear();
        m = (date.getMonth()+1).pad(2);
        d = date.getDate().pad(2);
        h = date.getHours().pad(2);
        i = date.getMinutes().pad(2);
        s = date.getSeconds().pad(2);
        
        // show date in input field - DD/MM/YYYY HH:MM:SS
        newDateString = [[d,m,Y].join('/'),[h,i,s].join(':')].join(' ');
        $input.val(newDateString);
    });
}

/**
 * compares all the selected feed start_times to see which is the best suited for the group 
 * @return int start_time timestamp
 */
function getEarliestStartTime() { 
    let start_time = 0;
    for (var feedid in selected_feeds) {
        if (selected_feeds[feedid] == true) {
            // record the earliest possible start_time for all the selected feeds
            start_time = feeds[feedid].start_time > start_time ? feeds[feedid].start_time : start_time;
        }
    }
    return start_time;
}

/**
 * mark button as selected if chosen date in date/time picker matches
 * jQuery Event handler for datetime picker's changeDate event
 */
$('#feed_trim_datetimepicker').on('changeDate',function(event){
    $('[data-relative_time]').each(function(){
        $btn = $(this);
        if ($btn.data('startdate') == event.date.valueOf()) {
            $btn.addClass('active').siblings().removeClass('active');
        }
    })
});

/**
 * returns true if trim function available for all the selected feed engine types
 *
 * @return boolean
 */
function isSelectionValidForTrim(){
    /*
        const MYSQL = 0;
        const TIMESTORE = 1;     // Depreciated
        const PHPTIMESERIES = 2;
        const GRAPHITE = 3;      // Not included in core
        const PHPTIMESTORE = 4;  // Depreciated
        const PHPFINA = 5;
        const VIRTUALFEED = 7;   // Virtual feed, on demand post processing
        const MYSQLMEMORY = 8;   // Mysql with MEMORY tables on RAM. All data is lost on shutdown 
        const REDISBUFFER = 9;   // (internal use only) Redis Read/Write buffer, for low write mode
        const CASSANDRA = 10;    // Cassandra
    */
    let allowed_engines = [0,2,5,8] // array of allowed storage engines
    for (var feedid in selected_feeds) {
        engineid = parseInt(feeds[feedid].engine); // convert string to number
        // if feed selected and engineid is NOT found in allowed_engines
        if (selected_feeds[feedid] == true && !isNaN(engineid) && allowed_engines.indexOf(engineid) == -1) {
            return false;
        }
    }
    return true;
}

/**
 * display a message to the user in the delete feed modal
 *
 * restores the original message after delay
 *
 * @param string message text to show to user
 */
function updateFeedDeleteModalMessage(response){
    let message = response.message;
    let success = response.success;
    let $msg = $('#feedDelete-message');
    let cssClassName = success ? 'label-success' : 'label-important';

    $msg.stop().fadeOut(function(){
        $(this).text(message).removeClass('label-warning').addClass(cssClassName).fadeIn();
    });
    setTimeout(function(){
        $msg.stop().fadeOut(function(){
            $msg.text($msg.data('default')).removeClass(cssClassName).addClass('label-warning').fadeIn();
        })
    }, 3800);
}

/**
 * function call queue - clears previous interval if interrupted
 */
var updater;
function updaterStart(func, interval){
    clearInterval(updater);
    updater = null;
    if (interval > 0) updater = setInterval(func, interval);
}

/**
 * Enables/Disables the feed trim() feature based on selected feeds
 *
 * @return void
 */
function initTrim(){
    // get the most suitable start_time for all selected feeds
    if (isSelectionValidForTrim()) {
        let start_time = getEarliestStartTime()
        enableTrim(start_time);
    } else {
        disableTrim();
    }
}

/**
 * Allows feed(s) to be trimmed to a new start_date
 *
 * @param int start_time new timestamp to trim to
 * @return void
 */
function enableTrim(start_time){
    // populate the trim() date input with the feed's current start date
    showFeedStartDate(start_time);
    // make buttons under the trim date input react on click
    initRelativeStartDateButtons(start_time);

    // remove any styling the disableTrim() function created
    $('#trimContainer').attr('title','').removeClass('muted')//.show()
        .find('h4').addClass('text-info').removeClass('muted').end()
        .find('button,input').removeClass('disabled')
        .find('input').val('');
    
    // enable the confirm trim button
    $('#feedTrim-confirm')
        .unbind('click')
        .click(function(){
            $modal = $('#feedDeleteModal');
            let $input = $modal.find("#trim_start_time");
            let input_date_string = $input.val();
            // dont submit if nothing selected
            // convert uk dd/mm/yyyy h:m:s to RFC2822 date
            let start_date = new Date(input_date_string.replace( /(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})/, "$3-$2-$1T$4:$5:$6"));
            let isValidDate = !isNaN(start_date.getTime()) && input_date_string != "";
            // exit if supplied date not valid
            if (!isValidDate) {
                $('#trim_start_time_container').addClass('error');
                $input.focus();
                return false;
            }else{
                if(confirm("<?php echo tr('This is a new feature. Consider backing up your data before you continue. OK to continue?') ?>") == true) {
                    $('#trim_start_time_container').removeClass('error');
                    // set to seconds from milliseconds
                    let start_time = start_date.getTime()/1000;
                    $("#feedDelete-loader").fadeIn();
                    // run the trim() function on all the selected feeds
                    for (let feedid in selected_feeds) {
                        if (selected_feeds[feedid]) {
                            let response = feed.trim(feedid, start_time);
                            updateFeedDeleteModalMessage(response);
                            if (!response.success) {
                                break;
                            }
                        }
                    }
                    $("#feedDelete-loader").stop().fadeOut();
                    update_feed_list();
                    updaterStart(update_feed_list, 5000);
                }
            }
        });
}

/**
 * hide the trim feature
 *
 * @return void
 */
function disableTrim(){
    $('#trimContainer').attr('title','<?php echo tr('"Trim" not available for this storage engine') ?>').addClass('muted')//.hide()
        .find('h4').removeClass('text-info').addClass('muted').end()
        .find('button,input').addClass('disabled')
        .find('input').val('');
    $('#feedTrim-confirm').unbind('click'); // remove previous click event (if it exists)
}

/**
 * trigger off the modal overlay to display delete options
 * 
 * jQuery Event handler for the delete feed button
 * also shows items selected as well as a processlist warning
 */
$(".feed-delete").click(function(){
    $('#feedDeleteModal #deleteFeedText').show();
    $('#feedDeleteModal #deleteVirtualFeedText').hide();
    $('#feedDeleteModal').modal('show'); //show the delete modal

    // get the list of input processlists that write to feeds
    let feed_processes = getFeedProcess();
    let selected_feeds_inputs = {};
    for (i in selected_feeds){
        // if a selected feed has an associated process id then save it into an array
        if (selected_feeds[i] && typeof feed_processes[i] != 'undefined') {
            selected_feeds_inputs[i] = feed_processes[i];
        }
    }

    // show the selected feeds and any associated processList
    showSelectedFeeds(selected_feeds_inputs);

    initTrim();
    initClear();
});

function isSelectionValidForClear(){
    /*
        const MYSQL = 0;
        const TIMESTORE = 1;     // Depreciated
        const PHPTIMESERIES = 2;
        const GRAPHITE = 3;      // Not included in core
        const PHPTIMESTORE = 4;  // Depreciated
        const PHPFINA = 5;
        const VIRTUALFEED = 7;   // Virtual feed, on demand post processing
        const MYSQLMEMORY = 8;   // Mysql with MEMORY tables on RAM. All data is lost on shutdown 
        const REDISBUFFER = 9;   // (internal use only) Redis Read/Write buffer, for low write mode
        const CASSANDRA = 10;    // Cassandra
    */
    let allowed_engines = [0,2,5,8]; // array of allowed storage engines 
    for (var feedid in selected_feeds) {
        engineid = parseInt(feeds[feedid].engine); // convert string to number
        // if feed selected and engineid is NOT found in allowed_engines
        if (selected_feeds[feedid] == true && !isNaN(engineid) && allowed_engines.indexOf(engineid) == -1) {
            return false;
        }
    }
    return true;
}

function initClear(){
    // get the most suitable start_time for all selected feeds
    if (isSelectionValidForClear()) {
        enableClear();
    } else {
        disableClear();
    }
}

function enableClear(){
    // remove any disable styling
    $('#clearContainer').attr('title','').removeClass('muted')//.show()
        .find('h4').addClass('text-info').removeClass('muted').end()
        .find('button').removeClass('disabled');

    $("#feedClear-confirm")
        .unbind('click')
        .click(function(){
            if( confirm("<?php echo tr('Are you sure you want to delete all the feeds data?') ?>") == true ){
                $modal = $('#feedDeleteModal');
                $("#feedDelete-loader").fadeIn();

                for (let feedid in selected_feeds) {
                    if (selected_feeds[feedid]) {
                        let response = feed.clear(feedid);
                        updateFeedDeleteModalMessage(response);
                        if (!response.success) {
                            break;
                        }
                    }
                }
                $("#feedDelete-loader").stop().fadeOut();
                update_feed_list();
                updaterStart(update_feed_list, 5000);
            }
        });
}

function disableClear(){
    $("#feedClear-confirm").unbind();

    $('#clearContainer').attr('title','<?php echo tr('"Clear" not available for this storage engine') ?>').addClass('muted')//.hide()
        .find('h4').removeClass('text-info').addClass('muted').end()
        .find('button').addClass('disabled');
}

$("#feedDelete-confirm").click(function(){
    if( confirm("<?php echo tr('Are you sure you want to delete?') ?>") == true) {
        for (let feedid in selected_feeds) {
            if (selected_feeds[feedid]) {
                let response = feed.remove(feedid);
                response = response ? response : {success:true, message: '<?php echo tr("Feeds Deleted") ?>'};
                updateFeedDeleteModalMessage(response);
            }
        }
        
        setTimeout(function() {
            update_feed_list();
            updaterStart(update_feed_list, 5000);
            $('#feedDeleteModal').modal('hide');
            feed_selection();
        }, 5000);
    }
});

$("#refreshfeedsize").click(function(){
    $.ajax({ url: path+"feed/updatesize.json", async: true, success: function(data){ update_feed_list(); alert('<?php echo addslashes(tr("Total size of used space for feeds:")); ?>' + list_format_size(data)); } });
});

// ---------------------------------------------------------------------------------------------
// ---------------------------------------------------------------------------------------------
function feed_selection() 
{
    selected_feeds = {};
    var num_selected = 0;
    var phpfina_selected = 0;
    $(".feed-seleMessage: SyntaxError: missing } after property list
Route: feed/list
Line: 1367
Column: 6ct").each(function(){
        var feedid = $(this).attr("feedid");
        selected_feeds[feedid] = $(this)[0].checked;
        if (selected_feeds[feedid]==true) {
            num_selected += 1;
            if (feeds[feedid].engine==5) phpfina_selected += 1;
        }
    });
    
    if (num_selected>0) {
        if (session_write) $(".feed-delete").show();
        $(".feed-download").show();
        $(".feed-graph").show();
        if (session_write) $(".feed-edit").show();
        $("#filter").hide();
    } else {
        $(".feed-delete").hide();
        $(".feed-download").hide();
        $(".feed-graph").hide();
        $(".feed-edit").hide();
        $("#filter").show();
    }
    
    if (phpfina_selected>0 && num_selected == phpfina_selected) {
        $(".feed-downsample").show();
    } else {
        $(".feed-downsample").hide();
    }

    // There should only ever be one feed that is selected here:
    var feedid = 0; for (var z in selected_feeds) { if (selected_feeds[z]) feedid = z; }
    // Only show feed process button for Virtual feeds
    if (feeds[feedid] && feeds[feedid].engine==7 && num_selected==1) {
        if (session_write) $(".feed-process").show(); 
    } else {
        $(".feed-process").hide();
    }
}

// -------------------------------------------------------------------------------------------------------
// Interface responsive
//
// The following implements the showing and hiding of the device fields depending on the available width
// of the container and the width of the individual fields themselves. It implements a level of responsivness
// that is one step more advanced than is possible using css alone.
// -------------------------------------------------------------------------------------------------------
watchResize(onResize, 20) // only call onResize() after 20ms of delay (similar to debounce)

// ---------------------------------------------------------------------------------------------
// Create new feed dialog
// ---------------------------------------------------------------------------------------------

for (var e in engines_hidden) {
    $('#newfeed-engine option[value='+engines_hidden[e]+']').hide();
}

$("#newfeed-save").click(function (){
    var name = $('#newfeed-name').val();
    var tag = $('#newfeed-tag').val();
    var engine = $('#newfeed-engine').val();
    
    var options = {};
    if (engine==5) {
        options.interval = $('#newfeed-interval').val();
    }
    
    var result = feed.create(tag,name,engine,options);
    feedid = result.feedid;

    if (!result.success || feedid<1) {
        alert('ERROR: Feed could not be created. '+result.message);
        return false;
    } else {
        update_feed_list(); 
        $('#newFeedNameModal').modal('hide');
    }
});

$('#newfeed-engine').change(function(){
    var engine = $(this).val();
    if (engine==5) {
        $('#newfeed-interval').show();
    } else {
        $('#newfeed-interval').hide();
    }
});

$(".feed-process").click(function() {
    // There should only ever be one feed that is selected here:
    var feedid = 0; for (var z in selected_feeds) { if (selected_feeds[z]) feedid = z; }
    var contextid = feedid;
    var contextname = "";
    if (feeds[feedid].name != "") contextname = feeds[feedid].tag + " : " + feeds[feedid].name;
    else contextname = feeds[feedid].tag + " : " + feeds[feedid].id;    
    process_vue.load(1, contextid, feeds[feedid].processList, contextname, null, null); // load configs
});

function save_processlist(feed_id, process_list) {
    var result = feed.set_process(feed_id, process_list);
    if (!result.success) {
        alert('ERROR: Could not save processlist. '+result.message); 
        return false;
    } else {
        update_feed_list();
        return true;
    }
}

// Translations
var downloadlimit = <?php echo $settings['feed']['csv_downloadlimit_mb']; ?>;
var str_enter_valid_start_date = "<?php echo tr('Please enter a valid start date.'); ?>";
var str_enter_valid_end_date = "<?php echo tr('Please enter a valid end date.'); ?>";
var str_start_before_end = "<?php echo tr('Start date must be further back in time than end date.'); ?>";
var str_interval_for_download = "<?php echo tr('Please select interval to download.'); ?>";
var str_large_download = "<?php echo tr('Estimated download file size is large.'); ?>\n<?php echo tr('Server could take a long time or abort depending on stored data size.'); ?>\n<?php echo tr('Limit is'); ?> "+downloadlimit+"MB.\n\n<?php echo tr('Try exporting anyway?'); ?>";

// Vue.js Feed List Application
var feedApp = new Vue({
    el: '#feed-app',
    data: {
        nodes: {},
        feeds: {},
        selectedFeeds: {},
        nodesDisplay: {}
    },
    methods: {
        getNodeColor: function(nodeFeeds) {
            var maxColorCode = 0;
            var nodeColor = '#999';
            
            for (var i = 0; i < nodeFeeds.length; i++) {
                var feed = nodeFeeds[i];
                if (feed.time != null && parseInt(feed.engine) !== 7) {
                    var fv = list_format_updated_obj(feed.time, feed.interval);
                    if (fv.color_code > maxColorCode) {
                        maxColorCode = fv.color_code;
                        nodeColor = fv.color;
                    }
                }
            }
            return nodeColor;
        },
        
        getNodeSize: function(nodeFeeds) {
            var totalSize = 0;
            for (var i = 0; i < nodeFeeds.length; i++) {
                totalSize += Number(nodeFeeds[i].size);
            }
            return list_format_size(totalSize);
        },
        
        getNodeTime: function(nodeFeeds) {
            var maxColorCode = 0;
            var nodeTime = '';
            
            for (var i = 0; i < nodeFeeds.length; i++) {
                var feed = nodeFeeds[i];
                if (feed.time != null && parseInt(feed.engine) !== 7) {
                    var fv = list_format_updated_obj(feed.time, feed.interval);
                    if (fv.color_code > maxColorCode) {
                        maxColorCode = fv.color_code;
                        nodeTime = fv.value;
                    }
                }
            }
            return nodeTime;
        },
        
        getFeedColor: function(feed) {
            var fv = list_format_updated_obj(feed.time, feed.interval);
            return fv.color;
        },
        
        getFeedTooltip: function(feed) {
            var titleLines = [feed.name,
                             '-----------------------',
                             tr('Tag') + ': ' + feed.tag,
                             tr('Feed ID') + ': ' + feed.id,
                             tr('Feed Engine') + ': ' + feed_engines[feed.engine]];
            
            if(feed.engine == 5) {
                titleLines.push(tr('Feed Interval')+": "+(feed.interval||'')+'s');
            } else {
                titleLines.push(tr('Feed Interval (approx)')+": "+(feed.interval||'')+'s');
            }
            
            if(feed.start_time > 0) {
                titleLines.push(tr('Feed Start Time')+": "+feed.start_time);
                titleLines.push(format_time(feed.start_time,'LL LTS')+" UTC");
            }

            if(feed.end_time > 0) {
                titleLines.push(tr('Feed End Time')+": "+feed.end_time);
                titleLines.push(format_time(feed.end_time,'LL LTS')+" UTC");
            }
            
            return titleLines.join("\n");
        },
        
        formatEngine: function(engine, interval) {
            var engineName = feed_engines[engine];
            if (engineName == "PHPFINA") engineName = "FIXED";
            else if (engineName == "PHPTIMESERIES") engineName = "VARIABLE";
            
            var intervalStr = "";
            if (engine == 5) {
                intervalStr = " (" + interval + "s)";
            }
            
            return engineName + intervalStr;
        },
        
        formatSize: function(size) {
            return list_format_size(size);
        },
        
        formatValue: function(value, unit) {
            if (unit == undefined) unit = "";
            return list_format_value(value) + ' ' + unit;
        },
        
        formatTime: function(time, interval) {
            return list_format_updated(time, interval);
        },
        
        openFeedGraph: function(feedid) {
            // Trigger the existing feed graph functionality
            var event = new CustomEvent('feedGraphClick', { detail: { feedid: feedid } });
            document.dispatchEvent(event);
        },
        
        toggleFeedPublic: function(feed) {
            // Trigger the existing public toggle functionality
            var event = new CustomEvent('feedPublicClick', { detail: { feedid: feed.id } });
            document.dispatchEvent(event);
        },
        
        onFeedSelectionChange: function() {
            // Update the global selected_feeds object
            selected_feeds = Object.assign({}, this.selectedFeeds);
            feed_selection();
        }
    },
    
    watch: {
        selectedFeeds: {
            handler: function(newVal) {
                selected_feeds = Object.assign({}, newVal);
                feed_selection();
            },
            deep: true
        },
        
        nodesDisplay: {
            handler: function(newVal) {
                // Update global nodes_display object
                nodes_display = Object.assign({}, newVal);
            },
            deep: true
        }
    }
});

// Vue-compatible expand/collapse and select-all functionality  
$(document).ready(function() {
    // Wait for Vue to initialize before overriding button handlers
    setTimeout(function() {
        // Override the expand-collapse button for Vue compatibility
        $("#expand-collapse-all").off('click').on('click', function() {
            if (typeof feedApp !== 'undefined' && feedApp.nodes) {
                // Determine if we should expand or collapse all
                var allExpanded = true;
                for (var node in feedApp.nodes) {
                    if (!feedApp.nodesDisplay[node]) {
                        allExpanded = false;
                        break;
                    }
                }
                
                // Set all nodes to opposite state
                var newState = {};
                for (var node in feedApp.nodes) {
                    newState[node] = !allExpanded;
                }
                feedApp.nodesDisplay = newState;
                
                // Update button state
                var $btn = $(this);
                var $icon = $btn.find('.icon');
                $icon.toggleClass('icon-resize-small', !allExpanded)
                     .toggleClass('icon-resize-full', allExpanded);
                
                if (!$btn.data('original-title')) $btn.data('original-title', $btn.attr('title'));
                $btn.attr('title', allExpanded ? $btn.data('alt-title') : $btn.data('original-title'));
            }
        });

        // Override the select-all button for Vue compatibility
        $("#select-all").off('click').on('click', function() {
            if (typeof feedApp !== 'undefined' && feedApp.feeds) {
                var $btn = $(this);
                var currentState = $btn.data('state') !== false;
                
                // Toggle all checkboxes
                var newSelections = {};
                for (var feedid in feedApp.feeds) {
                    newSelections[feedid] = currentState;
                }
                feedApp.selectedFeeds = newSelections;
                
                // Update button appearance
                $btn.find('.icon').toggleClass('icon-ban-circle', currentState)
                                  .toggleClass('icon-check', !currentState);
                
                if (!$btn.data('title-original')) {
                    $btn.data('title-original', $btn.attr('title'));
                }
                var title = currentState ? $btn.data('alt-title') : $btn.data('title-original');
                $btn.attr('title', title);
                $btn.data('state', !currentState);
                
                // Expand all if selecting all
                if (currentState) {
                    var expandedState = {};
                    for (var node in feedApp.nodes) {
                        expandedState[node] = true;
                    }
                    feedApp.nodesDisplay = expandedState;
                }
            }
        });
    }, 100);
});