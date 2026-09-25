<?php
defined('EMONCMS_EXEC') or die('Restricted access');
?>
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- IMPORT DATA                                                                                                                                    -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="importDataModal" class="modal" tabindex="-1" aria-labelledby="importDataModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="importDataModalLabel" class="modal-title"><?php echo tr('Import Data'); ?></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            
            <div id="import-alert" class="alert alert-danger hide" style="margin-bottom:15px"></div>
            
            <label class="form-label">Paste CSV: <i>(Format: unix timestamp, value)</i></label>
            <textarea id="import-textarea" class="form-control mb-2" rows=8 autocomplete="off"></textarea>
            
            <label class="form-label">Select or create new feed:</label>
                                    
            <div class="input-group">
                <span class="input-group-text">Feed</span>
                <select id="import-feed-select" class="form-select" style="width:130px"></select>
                <span class="import-new-feed">
                    <input id="import-feed-tag" class="form-control input-165" type="text" placeholder="Tag" />
                    <input id="import-feed-name" class="form-control input-165" type="text" placeholder="Name" />
                </span>
            </div>
            
            <div class="input-group import-new-feed">
                <span class="input-group-text">Engine</span>
                <select id="import-feed-engine" class="form-select" style="width:280px">
                    <?php foreach (Engine::get_all_descriptive() as $engine) { ?>
                    <option value="<?php echo $engine["id"]; ?>"><?php echo $engine["description"]; ?></option>
                    <?php } ?>
                </select>
                <select id="import-feed-interval" class="form-select" style="width:60px">
                    <?php foreach (Engine::available_intervals() as $i) { ?>
                    <option value="<?php echo $i["interval"]; ?>"><?php echo $i["description"]; ?></option>
                    <?php } ?>
                </select>
            </div>
            
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-bs-dismiss="modal" aria-hidden="true"><?php echo tr('Cancel'); ?></button>
                <button id="importData" class="btn btn-primary"><?php echo tr('Import'); ?></button>
            </div>
        </div>
    </div>
</div>
