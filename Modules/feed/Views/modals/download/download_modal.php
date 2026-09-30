<?php
defined('EMONCMS_EXEC') or die('Restricted access');
?>
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED EXPORT                                                                                                                                   -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedExportModal" class="modal" tabindex="-1" aria-labelledby="feedExportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="feedExportModalLabel" class="modal-title"><b><span id="SelectedExport"></span></b> <?php echo tr('CSV export'); ?></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <p><?php echo tr('Select the time range and interval that you wish to export: '); ?></p>
                <table class="table">
                <tr>
                    <td>
                        <p><b><?php echo tr('Start date & time'); ?></b></p>
                        <div class="input-group">
                            <input id="export-start" class="form-control input-165" type="text" placeholder="YYYY-MM-DD HH:MM:SS" />
                        </div>
                    </td>
                    <td>
                        <p><b><?php echo tr('End date & time ');?></b></p>
                        <div class="input-group">
                            <input id="export-end" class="form-control input-165" type="text" placeholder="YYYY-MM-DD HH:MM:SS" />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p><b><?php echo tr('Interval');?></b></p>
                        <select id="export-interval" class="form-select input-220">
                            <option value=original><?php echo tr('Original feed interval');?></option>
                            <option value=5><?php echo tr('5s');?></option>
                            <option value=10><?php echo tr('10s');?></option>
                            <option value=30><?php echo tr('30s');?></option>
                            <option value=60><?php echo tr('1 min');?></option>
                            <option value=300><?php echo tr('5 mins');?></option>
                            <option value=600><?php echo tr('10 mins');?></option>
                            <option value=900><?php echo tr('15 mins');?></option>
                            <option value=1800><?php echo tr('30 mins');?></option>
                            <option value=3600><?php echo tr('1 hour');?></option>
                            <option value=21600><?php echo tr('6 hour');?></option>
                            <option value=43200><?php echo tr('12 hour');?></option>
                            <option value=daily><?php echo tr('Daily');?></option>
                            <option value=weekly><?php echo tr('Weekly');?></option>
                            <option value=monthly><?php echo tr('Monthly');?></option>
                            <option value=annual><?php echo tr('Annual');?></option>
                        </select>
                        
                        <p class="hide"><input id="export-average" type="checkbox" style="margin-top:-4px"> Return Averages</p>
                    </td>
                    <td>
                        <p><b><?php echo tr('Date time format');?></b></p>
                        <select id="export-timeformat" class="form-select input-220">
                            <option value="unix">Unix timestamp</option>
                            <option value="excel">Excel (d/m/Y H:i:s), Timezone set in user account</option>
                            <option value="iso8601">ISO 8601 (e.g: 2020-01-01T10:00:00+01:00)</option>
                        </select>
                    </td>
                </tr>
                </table>
            </div>
            <div class="modal-footer">
                <div id="downloadsizeplaceholder" style="float: left"><?php echo tr('Estimated download size: ');?><span id="downloadsize">0</span></div>
                <button class="btn btn-default" data-bs-dismiss="modal" aria-hidden="true"><?php echo tr('Close'); ?></button>
                <button class="btn btn-default" id="export"><?php echo tr('Export'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
var str_enter_valid_start_date = <?php echo json_encode(tr('Please enter a valid start date.')); ?>;
var str_enter_valid_end_date = <?php echo json_encode(tr('Please enter a valid end date.')); ?>;
var str_start_before_end = <?php echo json_encode(tr('The start date must be before the end date.')); ?>;
var str_interval_for_download = <?php echo json_encode(tr('Please select an interval.')); ?>;
var str_large_download = <?php echo json_encode(tr('This download is larger than the recommended limit. Continue?')); ?>;
</script>
