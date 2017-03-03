<?php
    global $path;
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/input_dialog.js"></script>

<div id="inputDeleteModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="inputDeleteModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="inputDeleteModalLabel"><?php echo _('Delete Input'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Deleting an Input will lose it name and configured Processlist.<br>A new blank input is automatic created by API data post if it does not already exists.'); ?>
        </p>
        <p>
           <?php echo _('Are you sure you want to delete?'); ?>
        </p>
		<div id="inputDelete-loader" class="ajax-loader" style="display:none;"></div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="inputDelete-confirm" class="btn btn-primary"><?php echo _('Delete'); ?></button>
    </div>
</div>