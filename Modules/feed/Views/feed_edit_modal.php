
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED EDIT MODAL                                                                                                                               -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedEditModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="feedEditModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="feedEditModalLabel"><?php echo tr('Edit feed'); ?></h3>
    </div>
    <div class="modal-body">

        <div class="input-prepend input-append" id="edit-feed-name-div">
          <span class="add-on" style="width:100px"><?php echo tr('Name'); ?></span>
          <input id="feed-name" type="text" style="width:250px">
          <button class="btn btn-primary feed-edit-save" field="name">Save</button>
        </div>
    
        <div class="input-prepend input-append">
          <span class="add-on" style="width:100px"><?php echo tr('Node'); ?></span>
          <div class="autocomplete">
              <input id="feed-node" type="text" style="width:250px">
          </div>
          <button class="btn btn-primary feed-edit-save" field="node">Save</button>
        </div>

        <div class="input-prepend input-append">
          <span class="add-on" style="width:100px"><?php echo tr('Make public'); ?></span>
          <span class="add-on" style="width:255px"><input id="feed-public" type="checkbox"></span>
          <button class="btn btn-primary feed-edit-save" field="public">Save</button>
        </div>

        <div class="input-prepend input-append" id="edit-feed-name-div">
          <span class="add-on" style="width:100px"><?php echo tr('Unit'); ?></span>
          <select id="feed_unit_dropdown" style="width:auto">
              <option value=""></option>
              <?php
              // add available units from units.php
              include('Lib/units.php');
              if (defined('UNITS')) {
                  foreach(UNITS as $unit){
                      printf('<option value="%s">%s (%1$s)</option>',$unit['short'],$unit['long']);
                  }
              }
              ?>
              <option value="_other"><?php echo tr('Other'); ?></option>
          </select>
          <input type="text" id="feed_unit_dropdown_other" style="width:100px"/>       
          <button class="btn btn-primary feed-edit-save" field="unit">Save</button>
        </div>
    </div>
    <div class="modal-footer">
        <div id="feed-edit-save-message" style="position:absolute"></div>
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo tr('Close'); ?></button>
    </div>
</div>

<script>

// ---------------------------------------------------------------------------------------------
// EDIT FEED
// ---------------------------------------------------------------------------------------------
function openEditFeedModal(selected_feeds){
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
};

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
</script>