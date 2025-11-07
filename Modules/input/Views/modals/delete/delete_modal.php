<div id="inputDeleteModal" v-cloak>
    <div class="modal" :class="{'hide': hidden}" tabindex="-1" role="dialog" aria-labelledby="inputDeleteModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-header">
            <button @click="closeModal" type="button" class="close" aria-hidden="true">×</button>
            <h3 id="inputDeleteModalLabel"><?php echo tr('Delete Input'); ?></h3>
        </div>
        <div class="modal-body">
            <div class="alert alert-danger d-inline-block">
            <?php echo tr('Deleting an Input will lose it name and configured Processlist.<br>A new blank input is automatic created by API data post if it does not already exists.'); ?>
            </div>
            <h4>
                <?php echo tr('Are you sure you want to delete?'); ?>
                <em class="text-muted muted">({{selected.length}} <?php echo tr('Inputs') ?>)</em>
            </h4>
            <div class="card well well-small bg-light">
                <dl class="dl-horizontal row m-0">
                    <template v-for="inputid in selected">
                        <dt class="col-6 col-md-3 text-right pull-left clear-both" :title="getInputNode(inputid)">{{ getInputNode(inputid) }}: </dt>
                        <dd class="col-6 col-md-9 ml-2 pull-left">{{ getInputName(inputid) }}</dd>
                    </template>
                </dl>
            </div>
            
            <div id="inputs-to-delete"></div>
            <div id="inputDelete-loader" class="ajax-loader" style="display:none;"></div>
        </div>
        <div class="modal-footer">
            <button @click="closeModal" class="btn"><?php echo tr('Cancel'); ?></button>
            <button @click="confirm" class="btn" :class="buttonClass">{{buttonLabel}}</button>
        </div>
    </div>
    <div @click="closeModal" class="modal-backdrop" :class="{'hide': hidden}"></div>
</div>