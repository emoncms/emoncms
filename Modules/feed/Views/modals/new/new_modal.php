<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- NEW VIRTUAL FEED                                                                                                                              -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="newFeedNameModal" class="modal keyboard" tabindex="-1" aria-labelledby="newFeedNameModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="newFeedNameModalLabel" class="modal-title"><?php echo tr('New Feed'); ?></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                <button class="btn btn-default" data-bs-dismiss="modal" aria-hidden="true"><?php echo tr('Cancel'); ?></button>
                <button id="newfeed-save" class="btn btn-primary"><?php echo tr('Save'); ?></button>
            </div>
        </div>
    </div>
</div>