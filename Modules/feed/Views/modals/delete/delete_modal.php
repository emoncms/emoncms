
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED DELETE MODAL                                                                                                                             -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedDeleteModal" class="modal" tabindex="-1" aria-labelledby="feedDeleteModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="feedDeleteModalLabel" class="modal-title"><?php echo tr('Delete feed'); ?> 
                <span id="feedDelete-message" class="badge bg-warning" data-default="<?php echo tr('Deleting a feed is permanent.'); ?>"><?php echo tr('Deleting a feed is permanent.'); ?></span>
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="clearfix d-flex row">
                    <div id="clearContainer" class="col-6">
                        <div style="min-height:12.1em; position:relative" class="bg-body-tertiary border rounded mb-3 p-2">
                            <h4 class="text-info"><?php echo tr('Clear') ?>:</h4>
                            <p><?php echo tr('Empty feed of all data') ?></p>
                            <button id="feedClear-confirm" class="btn btn-dark" style="position:absolute;bottom:.8em"><?php echo tr('Clear Data'); ?>&hellip;</button>
                        </div>
                    </div>
        
                    <div id="trimContainer" class="col-6">
                        <div class="bg-body-tertiary border rounded mb-3 p-2">
                            <h4 class="text-info"><?php echo tr('Trim') ?>:</h4>
                            <p><?php echo tr('Empty feed data up to') ?>:</p>
                            <div id="trim_start_time_container" style="margin-bottom:1.3em">
                                <div class="input-group" style="margin-bottom:0">
                                    <input id="trim_start_time" class="form-control input-165" type="text" placeholder="YYYY-MM-DD HH:MM:SS">
                                </div>
                                <div class="btn-group" style="margin-bottom:-4px">
                                    <button class="btn btn-default btn-xs active" title="<?php echo tr('Set to the start date') ?>" data-relative_time="start"><?php echo tr('Start') ?></button>
                                    <button class="btn btn-default btn-xs" title="<?php echo tr('One year ago') ?>" data-relative_time="-1y"><?php echo tr('- 1 year') ?></button>
                                    <button class="btn btn-default btn-xs" title="<?php echo tr('Two years ago') ?>" data-relative_time="-2y"><?php echo tr('- 2 year') ?></button>
                                    <button class="btn btn-default btn-xs" title="<?php echo tr('Three years ago') ?>" data-relative_time="-3y"><?php echo tr('- 3 year') ?></button>
                                    <button class="btn btn-default btn-xs" title="<?php echo tr('Set to the current date/time') ?>" data-relative_time="now"><?php echo tr('Now') ?></button>
                                </div>
                            </div>
                            <button id="feedTrim-confirm" class="btn btn-dark"><?php echo tr('Trim Data'); ?>&hellip;</button>
                        </div>
                    </div>
                </div>
                
                <div class="bg-body-tertiary border rounded mb-3 p-2" style="margin-bottom:0">
                    <h4 class="text-info"><?php echo tr('Delete')?>: <span id="feedProcessList"></span></h4>
                    <p id="deleteFeedText"><?php echo tr('If you have Input Processlist processors that use this feed, after deleting it, review that process lists or they will be in error, freezing other Inputs. Also make sure no Dashboards use the deleted feed.'); ?></p>
                    <p id="deleteVirtualFeedText"><?php echo tr('This is a Virtual Feed, after deleting it, make sure no Dashboard continue to use the deleted feed.'); ?></p>
                    <button id="feedDelete-confirm" class="btn btn-danger"><?php echo tr('Delete feed permanently'); ?></button>
                </div>
            </div>
            <div class="modal-footer">
                <div id="feeds-to-delete" class="float-start"></div>
                <div id="feedDelete-loader" class="ajax-loader" style="display:none;"></div>
                <button class="btn btn-default" data-bs-dismiss="modal" aria-hidden="true"><?php echo tr('Close'); ?></button>
            </div>
        </div>
    </div>
</div>