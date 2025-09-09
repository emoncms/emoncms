<?php
defined('EMONCMS_EXEC') or die('Restricted access');
?>
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- DOWN SAMPLE MODAL (list selected feeds and their intervals, enter new interval below                                                           -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="downsampleModal" class="modal hide keyboard" tabindex="-1" role="dialog" aria-labelledby="downsampleModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="downsampleModalLabel"><?php echo tr('Downsample feeds'); ?></h3>
    </div>
    <div class="modal-body">
        <p>Reduce disk space used by feeds by downsampling to a longer interval.</p>
        <div class="downsample-options alert alert-warning"><b>Warning: Original data is not preserved.</b> <br>Only the downsampled data is kept.</div>
        <hr>
        <p>Selected feeds:</p>
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo tr('Tag'); ?></th>
                    <th><?php echo tr('Name'); ?></th>
                    <th><?php echo tr('Current interval'); ?></th>
                    <th class="downsample-new-interval"><?php echo tr('New interval'); ?></th>
                </tr>
            </thead>
            <tbody id="downsample-feeds">
            </tbody>
        </table>
        <div id="downsample-alert" class="alert alert-info"></div>
        <div class="downsample-options">
            <label><?php echo tr('New interval: '); ?></label>
            <select id="downsample-interval" style="width:350px">
                <?php foreach (Engine::available_intervals() as $i) { ?>
                <option value="<?php echo $i["interval"]; ?>"><?php echo ctx_tr('process_messages',$i["description"]); ?></option>
                <?php } ?>
            </select>
            <p><b>Note:</b> This uses averaging based downsampling, suitable for feeds such as power and temperature feeds. It is not suitable for cumulative feeds such as cumulative kWh data.</p>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo tr('Cancel'); ?></button>
        <button id="downsample-finish" class="btn btn-success" data-dismiss="modal" aria-hidden="true"><?php echo tr('Finish'); ?></button>
        <button id="downsample-confirm" class="btn btn-primary"><?php echo tr('Down sample'); ?></button>
    </div>
</div>
