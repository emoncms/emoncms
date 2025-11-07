<?php
defined('EMONCMS_EXEC') or die('Restricted access');
?>
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED EXPORT                                                                                                                                   -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedExportModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="feedExportModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="feedExportModalLabel"><b><span id="SelectedExport"></span></b> <?php echo tr('CSV export'); ?></h3>
    </div>
    <div class="modal-body">
    <p><?php echo tr('Select the time range and interval that you wish to export: '); ?></p>
        <table class="table">
        <tr>
            <td>
                <p><b><?php echo tr('Start date & time'); ?></b></p>
                <div id="datetimepicker1" class="input-append date">
                    <input id="export-start" data-format="dd/MM/yyyy hh:mm:ss" type="text" />
                    <span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
                </div>
            </td>
            <td>
                <p><b><?php echo tr('End date & time ');?></b></p>
                <div id="datetimepicker2" class="input-append date">
                    <input id="export-end" data-format="dd/MM/yyyy hh:mm:ss" type="text" />
                    <span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <p><b><?php echo tr('Interval');?></b></p>
                <select id="export-interval" >
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
                <select id="export-timeformat">
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
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo tr('Close'); ?></button>
        <button class="btn" id="export"><?php echo tr('Export'); ?></button>
    </div>
</div>
