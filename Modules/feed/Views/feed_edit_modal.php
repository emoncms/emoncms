<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED EDIT MODAL                                                                                                                               -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedEditModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="feedEditModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="feedEditModalLabel"><?php echo _('Edit feed'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Feed node:'); ?><br>
        <div class="autocomplete">
            <input id="feed-node" type="text" style="margin-bottom:0">
        </div>
        </p>

        <p><?php echo _('Feed name:'); ?><br>
        <input id="feed-name" type="text"></p>

        <p><?php echo _('Make feed public:'); ?>
        <input id="feed-public" type="checkbox"></p>

        <p><?php echo _('Feed Unit'); ?></p>
        <div class="input-prepend">
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
            <option value="_other"><?php echo _('Other'); ?></option>
        </select>
        <input type="text" id="feed_unit_dropdown_other" style="width:100px"/>
        </div>
    </div>
    <div class="modal-footer">
        <div id="feed-edit-save-message" style="position:absolute"></div>
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Close'); ?></button>
        <button id="feed-edit-save" class="btn btn-primary"><?php echo _('Save'); ?></button>
    </div>
</div>
