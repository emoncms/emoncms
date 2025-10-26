
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