<?php
    global $path;
?>
<style>

.modal-body .form-group{
    position: relative;
}
.modal-body .form-group .add-on,
.modal-body .form-group input{
    margin-bottom: 0;
    margin-top: .8rem
}
.modal-body .form-group input + label {
    position: absolute;
    transition: all .2s ease-out;
    left: 0.45rem;
    opacity: 0;
    top: 1.1rem;
}
.modal-body .form-group input:focus + label,
.modal-body .form-group input:focus::placeholder{
    color: #5b9bce;
}
.modal-body .form-group label.away{
    font-size: x-small;
    top: -.7em;
    left: .5em;
    opacity: 1;
}
.modal-body form.align-items-center p,
.modal-body form.align-items-center button{
    margin-top: .75rem;
}
.modal-footer::before, .modal-footer::after{
    content: none
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.25s ease-out;
}
.fade-enter, .fade-leave-to {
    opacity: 0;
}
.dl-horizontal dt {
    float: left;
}
.dl-horizontal dt.text-right{
    text-align: right 
}
.dl-horizontal dt{
    float: left;
}
.clear-both {
    clear: both!important;
}

</style>

<div id="inputDeleteModal">
    <div class="modal" :class="{'hide': hidden}" tabindex="-1" role="dialog" aria-labelledby="inputDeleteModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-header">
            <button @click="closeModal" type="button" class="close" aria-hidden="true">×</button>
            <h3 id="inputDeleteModalLabel"><?php echo _('Delete Input'); ?></h3>
        </div>
        <div class="modal-body">
            <div class="alert alert-danger d-inline-block">
            <?php echo _('Deleting an Input will lose it name and configured Processlist.<br>A new blank input is automatic created by API data post if it does not already exists.'); ?>
            </div>
            <h4>
                <?php echo _('Are you sure you want to delete?'); ?>
                <em class="text-muted muted">({{selected.length}} <?php echo _('Inputs') ?>)</em>
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
            <button @click="closeModal" class="btn"><?php echo _('Cancel'); ?></button>
            <button @click="confirm" class="btn" :class="buttonClass">{{buttonLabel}}</button>
        </div>
    </div>
    <div @click="closeModal" class="modal-backdrop" :class="{'hide': hidden}"></div>
    <!-- <script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/input_dialog.js"></script> -->
</div>

<div id="inputEditModal">
    <div :class="{hide: hidden}" class="modal modal-wide" tabindex="-1" role="dialog" aria-labelledby="inputEditModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-header">
            <button @click="closeModal" type="button" class="close" aria-hidden="true">×</button>
            <h3 id="inputEditModalLabel"><?php echo _('Edit Input'); ?></h3>
        </div>
        <div class="modal-body">
            <p><?php echo _("Edit the input's name and description."); ?>
            <em class="text-muted muted">({{selected.length}} <?php echo _('Inputs') ?>)</em>
            </p>
            <form class="d-flex align-items-center" v-for="input in inputs" :key="input.id" v-if="selected.indexOf(input.id)>-1" @submit.prevent="save">
                <div class="input-prepend form-group mb-0">
                    <span class="add-on">{{input.nodeid}}:</span>
                    <input :id="'name_' + input.id" type="text" class="input-small" placeholder="<?php echo _('Name') ?>" v-model="input.name" name="name">
                    <label :for="'name_' + input.id" :class="{away: input.name.length > 0}" class="text-muted muted"><?php echo _('Name') ?></label>
                </div>
                <div class="form-group mx-2">
                    <input type="hidden" :value="input.id" name="id">
                    <input :id="'description_' + input.id" type="text" placeholder="<?php echo _('Description') ?>"  v-model="input.description" name="description">
                    <label :for="'description_' + input.id" :class="{away: input.description.length > 0}" class="text-muted muted"><?php echo _('Description') ?></label>
                </div>
                <button type="submit" class="btn"><?php echo _('Save') ?></button>
                <transition name="fade">
                    <p class="pl-3 mb-0 text-muted muted" v-if="errors[input.id]"><small>{{ errors[input.id] }}</small></p>
                </transition>
            </form>
            <div id="inputEdit-loader" class="ajax-loader" :class="{'hide': !loading}"></div>
            <div id="edit-input-form-container"></div>
        </div>
        <div class="modal-footer modal-footer d-flex justify-content-between align-items-center">
            <div class="position-relative">
                <h5 class="m-0">
                    <transition name="fade" appear>
                        <span v-if="message">{{message}}</span>
                    </transition>
                </h5>
            </div>
            <div>
                <button @click="closeModal" class="btn" aria-hidden="true" type="button"><?php echo _('Close'); ?></button>
                <button class="multiple btn btn-primary" type="button" @click="saveAll"><?php echo _('Save All'); ?></button>
            </div>
        </div>
    </div>
    <div @click="closeModal" class="modal-backdrop" :class="{'hide': hidden}"></div>
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