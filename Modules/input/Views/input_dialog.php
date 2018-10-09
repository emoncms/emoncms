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
		<div id="inputs-to-delete"></div>
		<div id="inputDelete-loader" class="ajax-loader" style="display:none;"></div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="inputDelete-confirm" class="btn btn-primary"><?php echo _('Delete'); ?></button>
    </div>
</div>


<div id="inputEditModal" class="modal hide modal-wide" tabindex="-1" role="dialog" aria-labelledby="inputEditModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="inputEditModalLabel"><?php echo _('Edit Input'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Edit the input\'s name and description.'); ?></p>
        <div id="inputs-to-edit"></div>
        <div id="inputEdit-loader" class="ajax-loader" style="display:none;"></div>
        <div id="edit-input-form-container"></div>
    </div>
    <div class="modal-footer">
        <div id="input-edit-status" class="pull-left" style="max-width:75%"></div>
        <button class="btn" data-dismiss="modal" aria-hidden="true" type="button"><?php echo _('Close'); ?></button>
        <button class="single btn btn-primary" type="button" onclick="submitAllInputForms(event)"><?php echo _('Save'); ?></button>
        <button class="multiple btn btn-primary hide" type="button" onclick="submitAllInputForms(event)"><?php echo _('Save All'); ?></button>
    </div>
</div>
<!-- this template will repeat for every selected input -->
<template id="edit-input-form">
    <form class="form-inline" style="margin-bottom:.5em">
        <input name="inputid" type="hidden">
        <input name="name" required pattern="[A-Za-z0-9_\-@\.' ]*" title="<?php echo _('Basic text only. Symbols allowed _-.@')?>" class="form-control" placeholder="<?php echo _('name') ?>" data-lpignore="true">
        <input name="description" pattern="[A-Za-z0-9_\-@\.' ]*" title="<?php echo _('Basic text only. Symbols allowed _-.@')?>" class="form-control" placeholder="<?php echo _('description') ?>" data-lpignore="true">
        <button class="button-small"><?php echo _('Save') ?> <span class="input_id"></span></button>
    </form>
</template>