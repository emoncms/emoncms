
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED DELETE MODAL                                                                                                                             -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedDeleteModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="feedDeleteModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="feedDeleteModalLabel"><?php echo tr('Delete feed'); ?> 
        <span id="feedDelete-message" class="label label-warning" data-default="<?php echo tr('Deleting a feed is permanent.'); ?>"><?php echo tr('Deleting a feed is permanent.'); ?></span>
        </h3>
    </div>
    <div class="modal-body">
        <div class="clearfix d-flex row">
            <div id="clearContainer" class="span6">
                <div style="min-height:12.1em; position:relative" class="well well-small">
                    <h4 class="text-info"><?php echo tr('Clear') ?>:</h4>
                    <p><?php echo tr('Empty feed of all data') ?></p>
                    <button id="feedClear-confirm" class="btn btn-inverse" style="position:absolute;bottom:.8em"><?php echo tr('Clear Data'); ?>&hellip;</button>
                </div>
            </div>

            <div id="trimContainer" class="span6">
                <div class="well well-small">
                    <h4 class="text-info"><?php echo tr('Trim') ?>:</h4>
                    <p><?php echo tr('Empty feed data up to') ?>:</p>
                    <div id="trim_start_time_container" class="control-group" style="margin-bottom:1.3em">
                        <div class="controls">
                            <div id="feed_trim_datetimepicker" class="input-append date" style="margin-bottom:0">
                                <input id="trim_start_time" class="input-medium" data-format="dd/MM/yyyy hh:mm:ss" type="text" placeholder="dd/mm/yyyy hh:mm:ss">
                                <span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-calendar"></i></span>
                            </div>
                            <div class="btn-group" style="margin-bottom:-4px">
                                <button class="btn btn-mini active" title="<?php echo tr('Set to the start date') ?>" data-relative_time="start"><?php echo tr('Start') ?></button>
                                <button class="btn btn-mini" title="<?php echo tr('One year ago') ?>" data-relative_time="-1y"><?php echo tr('- 1 year') ?></button>
                                <button class="btn btn-mini" title="<?php echo tr('Two years ago') ?>" data-relative_time="-2y"><?php echo tr('- 2 year') ?></button>
                                <button class="btn btn-mini" title="<?php echo tr('Set to the current date/time') ?>" data-relative_time="now"><?php echo tr('Now') ?></button>
                            </div>
                        </div>
                    </div>
                    <button id="feedTrim-confirm" class="btn btn-inverse"><?php echo tr('Trim Data'); ?>&hellip;</button>
                </div>
            </div>
        </div>
        
        <div class="well well-small" style="margin-bottom:0">
            <h4 class="text-info"><?php echo tr('Delete')?>: <span id="feedProcessList"></span></h4>
            <p id="deleteFeedText"><?php echo tr('If you have Input Processlist processors that use this feed, after deleting it, review that process lists or they will be in error, freezing other Inputs. Also make sure no Dashboards use the deleted feed.'); ?></p>
            <p id="deleteVirtualFeedText"><?php echo tr('This is a Virtual Feed, after deleting it, make sure no Dashboard continue to use the deleted feed.'); ?></p>
            <button id="feedDelete-confirm" class="btn btn-danger"><?php echo tr('Delete feed permanently'); ?></button>
        </div>
    </div>
    <div class="modal-footer">
        <div id="feeds-to-delete" class="pull-left"></div>
        <div id="feedDelete-loader" class="ajax-loader" style="display:none;"></div>
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo tr('Close'); ?></button>
    </div>
</div>