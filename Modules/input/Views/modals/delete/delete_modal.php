<div id="inputDeleteModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="inputDeleteModalLabel" aria-hidden="true" data-backdrop="static" v-cloak>
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3 id="inputDeleteModalLabel"><?php echo tr('Delete Input'); ?></h3>
    </div>
    <div class="modal-body">
        <div class="alert alert-danger">
        <?php echo tr('Deleting an Input will lose it name and configured Processlist.<br>A new blank input is automatic created by API data post if it does not already exists.'); ?>
        </div>
        <h4>
            <?php echo tr('Are you sure you want to delete?'); ?>
            <em class="muted">({{selected.length}} <?php echo tr('Inputs') ?>)</em>
        </h4>
        <div class="well well-small">
            <dl class="dl-horizontal">
                <template v-for="inputid in selected">
                    <dt :title="getInputNode(inputid)">{{ getInputNode(inputid) }}: </dt>
                    <dd>{{ getInputName(inputid) }}</dd>
                </template>
            </dl>
        </div>
        
        <div id="inputs-to-delete"></div>
        <div id="inputDelete-loader" class="ajax-loader" style="display:none;"></div>
    </div>
    <div class="modal-footer">
        <button @click="closeModal" class="btn btn-small"><?php echo tr('Cancel'); ?></button>
        <button @click="confirm" class="btn btn-small" :class="buttonClass">{{buttonLabel}}</button>
    </div>
</div>