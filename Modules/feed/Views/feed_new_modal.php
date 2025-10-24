<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- NEW VIRTUAL FEED                                                                                                                              -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="newFeedNameModal" class="modal hide keyboard" tabindex="-1" role="dialog" aria-labelledby="newFeedNameModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="newFeedNameModalLabel"><?php echo tr('New Feed'); ?></h3>
    </div>
    <div class="modal-body">
        <label><?php echo tr('Feed Name: '); ?></label>
        <input type="text" value="New Feed" id="newfeed-name">
        <label><?php echo tr('Feed Tag: '); ?></label>
        <input type="text" value="" id="newfeed-tag">
        <label><?php echo tr('Feed Engine: '); ?></label>
        <select id="newfeed-engine" style="width:350px">
            <option value="7" selected>VIRTUAL Feed</option>
            <?php foreach (Engine::get_all_descriptive() as $engine) { ?>
            <option value="<?php echo $engine["id"]; ?>"><?php echo $engine["description"]; ?></option>
            <?php } ?>
        </select>      
        <select id="newfeed-interval" class="input-mini hide">
            <?php foreach (Engine::available_intervals() as $i) { ?>
            <option value="<?php echo $i["interval"]; ?>"><?php echo $i["description"]; ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo tr('Cancel'); ?></button>
        <button id="newfeed-save" class="btn btn-primary"><?php echo tr('Save'); ?></button>
    </div>
</div>
<script>

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
</script>