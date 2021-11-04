<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- IMPORT DATA                                                                                                                                    -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="importDataModal" class="modal hide keyboard" tabindex="-1" role="dialog" aria-labelledby="importDataModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="importDataModalLabel"><?php echo _('Import Data'); ?></h3>
    </div>
    <div class="modal-body">
    
    <div id="import-alert" class="alert alert-danger hide" style="margin-bottom:15px"></div>
    
    <label>Paste CSV: <i>(Format: unix timestamp, value)</i></label>
    <textarea id="import-textarea" style="width:515px" rows=8 autocomplete="off"></textarea>
    
    <label>Select or create new feed:</label>
                            
    <div class="input-prepend input-append">
        <span class="add-on">Feed</span>
        <select id="import-feed-select" style="width:130px"></select>
        <span class="import-new-feed">
            <input id="import-feed-tag" type="text" style="width:150px" placeholder="Tag" />
            <input id="import-feed-name" type="text" style="width:150px" placeholder="Name" />
        </span>
    </div>
    
    <div class="input-prepend input-append import-new-feed">
        <span class="add-on">Engine</span>
        <select id="import-feed-engine" style="width:280px">
            <?php foreach (Engine::get_all_descriptive() as $engine) { ?>
            <option value="<?php echo $engine["id"]; ?>"><?php echo $engine["description"]; ?></option>
            <?php } ?>
        </select>
        <select id="import-feed-interval" style="width:60px">
            <?php foreach (Engine::available_intervals() as $i) { ?>
            <option value="<?php echo $i["interval"]; ?>"><?php echo $i["description"]; ?></option>
            <?php } ?>
        </select>
    </div>
    
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="importData" class="btn btn-primary"><?php echo _('Import'); ?></button>
    </div>
</div>
