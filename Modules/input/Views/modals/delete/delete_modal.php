<div id="inputDeleteModal" class="modal" tabindex="-1" aria-labelledby="inputDeleteModalLabel" aria-hidden="true" data-bs-backdrop="static" v-cloak>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="inputDeleteModalLabel" class="modal-title"><?php echo tr('Delete Input'); ?></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                <?php echo tr('Deleting an Input will lose it name and configured Processlist.<br>A new blank input is automatic created by API data post if it does not already exists.'); ?>
                </div>
                <h4>
                    <?php echo tr('Are you sure you want to delete?'); ?>
                    <em class="text-muted">({{selected.length}} <?php echo tr('Inputs') ?>)</em>
                </h4>
                <div class="bg-body-tertiary border rounded mb-3 p-2">
                    <dl class="row g-0">
                        <template v-for="inputid in selected">
                            <dt class="col-sm-4 text-sm-end text-truncate pe-3" :title="getInputNode(inputid)">{{ getInputNode(inputid) }}: </dt>
                            <dd class="col-sm-8">{{ getInputName(inputid) }}</dd>
                        </template>
                    </dl>
                </div>
                
                <div id="inputs-to-delete"></div>
                <div id="inputDelete-loader" class="ajax-loader" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button @click="closeModal" class="btn btn-default btn-sm"><?php echo tr('Cancel'); ?></button>
                <button @click="confirm" class="btn btn-default btn-sm" :class="buttonClass">{{buttonLabel}}</button>
            </div>
        </div>
    </div>
</div>