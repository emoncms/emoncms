

// ---------------------------------------------------------------------------------------------
// DELETE FEED
// ---------------------------------------------------------------------------------------------

/**
 * find which inputs and processess write to a feed
 *
 * the returned object is a list of arrays that store the process/input pairs that make up the specific output to feed
 *   obj[feedid][0].input.nodeid --- will get the nodeid for the first input that outputs to the given feed
 *   obj[feedid][0].process.short --- will get the short name for the first process that outputs to the given feed
 *
 * @return object
 */
function getFeedProcess(){
    let inputs = {}, // list of inputs and their processes
        feedProcesses = {}, // list of process that write to feeds
        let_feeds = {}; // list of feeds and their accociated processes

    // create a list of all inputs that have processes
    for (inputid in processlist_ui.inputlist) {
        let input = processlist_ui.inputlist[inputid];
        if (input.processList.length>0) {
            inputs[inputid] = {
                processList: processlist_ui.decode(input.processList),
                nodeid: input.nodeid,
                name: input.name,
                inputid: inputid
            };
        }
    }
    // get all the processes that write to a feed - list them by numeric key (if available)
    for (processid in processlist_ui.processlist) {
        let process = processlist_ui.processlist[processid];
        if (process.feedwrite) {
            key = process.hasOwnProperty('id_num') ? process.id_num : processid;
            feedProcesses[key] = processid;
        }
    }

    // go through all the input processes and get all the feeds they output to
    for (inputid in inputs) {
        let input = inputs[inputid];
        // loop through the key / value pairs of each input processlist
        for (item in input.processList) {
            let processid = input.processList[item][0];
            let processval = input.processList[item][1] || null;
            if(feedProcesses[processid]){
                //this process writes to feed
                let_feeds[processval] = let_feeds[processval] || [];
                let_feeds[processval].push({
                    process: processlist_ui.processlist[feedProcesses[processid]],
                    input: input,
                    feedid: processval
                });
            }
        }
    }
    return let_feeds;
}

/**
 * output what feeds have been selected in the overlay modal box
 *
 * @return void
 */
function showSelectedFeeds(feed_inputs) {
    // loop through selection 
    let selected = [];
    for (var feedid in selected_feeds) {
        if (selected_feeds[feedid] == true) {
            selected[feedid] = feeds[feedid];
            if (feed_inputs[feedid]) {
                if (Array.isArray(feed_inputs[feedid])) {
                    selected[feedid].input = [];
                    selected[feedid].process = [];
                    for (f in feed_inputs[feedid]) {
                        selected[feedid].input.push(feed_inputs[feedid][f].input);
                        selected[feedid].process.push(feed_inputs[feedid][f].process);
                    }
                } else {
                    selected[feedid].input = [feed_inputs[feedid].input];
                    selected[feedid].process = [feed_inputs[feedid].process];
                }
            }
        }
    }

    // count the number of processess associated with the selected feeds
    let list='',titles={},linked=[],total_linked = 0;
    for(s in selected) {
        titles[s] = selected[s].tag+":"+selected[s].name;
        // virtual feed processes
        if ( selected[s].hasOwnProperty('processList') && selected[s].processList && selected[s].processList.length > 0 ) {
            linked.push(selected[s]);
            let virtualProcesses = processlist_ui.decode(selected[s].processList);
            for(p in virtualProcesses) {
                total_linked++;
            }
        }
        // feed's linked/parent process
        if ( selected[s].hasOwnProperty('process') && selected[s].process && selected[s].process.length > 0 ) {
            linked.push(selected[s]);
            for(i=0;i<selected[s].process.length;i++) {
                total_linked++;
            }
        }
    }
    // create html to display the results
    // notify user that feed is associated to processList
    
    // create a simple list of feed ids and names to display to the user
    let feedListShort = '';
    for(id in titles){
        feedListShort += '['+id+'] '+titles[id]+', ';
    }
    // remove the last comma
    feedListShort = feedListShort.slice(0, -2);

    // create a container to store the result that is displayed to the user
    total_summary = '<div id="deleteFeedModalSelectedItems">';
    total_selected = Object.keys(titles).length;
    if (total_selected == 1) {
    // if only one is selected display it's id & name
        feedProcessList = total_linked > 0 ? '<span class="badge badge-default" style="padding-left:4px"><i class="icon icon-white icon-exclamation-sign"></i> 1 Input process associated with this feed':'';
        total_summary += '<h5>'+feedListShort+'</h5>';
    } else {
    // show a summary total if more than one are selected
        feedProcessList = total_linked > 0 ? '<span class="badge badge-default" style="padding-left:4px"><i class="icon icon-white icon-exclamation-sign"></i> '+(' %s Input processes associated with these feeds'.replace('%s',total_linked))+'</span>' : '';
        total_summary += '<h5 title="'+feedListShort+'">%s Feeds selected <i class="icon icon-question-sign"></i></h5>'.replace('%s', total_selected);
    }
    total_summary += '</div>';
    $("#feeds-to-delete").html(total_summary); // show how many feeds have been selected
    $("#feedProcessList").html(feedProcessList); // show how many processes are associated with the selected feeds

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
            $btn.attr('title',$btn.attr('title')+' - [Out of range]');
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
        const PHPFIWA = 6;
        const VIRTUALFEED = 7;   // Virtual feed, on demand post processing
        const MYSQLMEMORY = 8;   // Mysql with MEMORY tables on RAM. All data is lost on shutdown 
        const REDISBUFFER = 9;   // (internal use only) Redis Read/Write buffer, for low write mode
        const CASSANDRA = 10;    // Cassandra
    */
    let allowed_engines = [0,5,8] // array of allowed storage engines
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
                if(confirm("This is a new feature. Consider backing up your data before you continue. OK to continue?") == true) {
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
    $('#trimContainer').attr('title','"Trim" not available for this storage engine').addClass('muted')//.hide()
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
        const PHPFIWA = 6;
        const VIRTUALFEED = 7;   // Virtual feed, on demand post processing
        const MYSQLMEMORY = 8;   // Mysql with MEMORY tables on RAM. All data is lost on shutdown 
        const REDISBUFFER = 9;   // (internal use only) Redis Read/Write buffer, for low write mode
        const CASSANDRA = 10;    // Cassandra
    */
    let allowed_engines = [0,5,8]; // array of allowed storage engines 
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
            if( confirm("Are you sure you want to delete all the feeds data?") == true ){
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

    $('#clearContainer').attr('title','"Clear" not available for this storage engine').addClass('muted')//.hide()
        .find('h4').removeClass('text-info').addClass('muted').end()
        .find('button').addClass('disabled');
}

$("#feedDelete-confirm").click(function(){
    if( confirm("Are you sure you want to delete?") == true) {
        for (let feedid in selected_feeds) {
            if (selected_feeds[feedid]) {
                let response = feed.remove(feedid);
                response = response ? response : {success:true, message: '<?php echo _("Feeds Deleted") ?>'};
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
