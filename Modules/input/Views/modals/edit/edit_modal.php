
<div id="inputEditModal" v-cloak>
    <div :class="{hide: hidden}" class="modal modal-wide" tabindex="-1" role="dialog" aria-labelledby="inputEditModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-header">
            <button @click="closeModal" type="button" class="close" aria-hidden="true">×</button>
            <h3 id="inputEditModalLabel"><?php echo tr('Edit Input'); ?></h3>
        </div>
        <div class="modal-body">
            <p><?php echo tr("Edit the input's description."); ?>
            <em class="text-muted muted">({{selected.length}} <?php echo tr('Inputs') ?>)</em>
            </p>
            <form class="d-flex align-items-center" v-for="input in inputs" :key="input.id" v-if="selected.indexOf(input.id)>-1" @submit.prevent="save">
                <div class="input-prepend form-group mb-0">
                    <span class="add-on">{{input.nodeid}}:</span>
                    <input :id="'name_' + input.id" type="text" class="input-small" placeholder="<?php echo tr('Name') ?>" v-model="input.name" name="name" disabled>
                    <label :for="'name_' + input.id" :class="{away: input.name.length > 0}" class="text-muted muted"><?php echo tr('Name') ?></label>
                </div>
                <div class="form-group mx-2">
                    <input type="hidden" :value="input.id" name="id">
                    <input :id="'description_' + input.id" type="text" placeholder="<?php echo tr('Description') ?>"  v-model="input.description" name="description">
                    <label :for="'description_' + input.id" :class="{away: input.description.length > 0}" class="text-muted muted"><?php echo tr('Description') ?></label>
                </div>
                <button type="submit" class="btn"><?php echo tr('Save') ?></button>
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
                <button @click="closeModal" class="btn" aria-hidden="true" type="button"><?php echo tr('Close'); ?></button>
                <button class="multiple btn btn-primary" type="button" @click="saveAll"><?php echo tr('Save All'); ?></button>
            </div>
        </div>
    </div>
    <div @click="closeModal" class="modal-backdrop" :class="{'hide': hidden}"></div>
</div>

<!-- this template will repeat for every selected input -->
<template id="edit-input-form" v-cloak>
    <form class="form-inline" style="margin-bottom:.5em">
        <input name="inputid" type="hidden">
        <input name="name" required pattern="[A-Za-z0-9_\-@\.' ]*" title="<?php echo tr('Basic text only. Symbols allowed _-.@')?>" class="form-control" placeholder="<?php echo tr('name') ?>" data-lpignore="true">
        <input name="description" pattern="[A-Za-z0-9_\-@\.' ]*" title="<?php echo tr('Basic text only. Symbols allowed _-.@')?>" class="form-control" placeholder="<?php echo tr('description') ?>" data-lpignore="true">
        <button class="button-small"><?php echo tr('Save') ?> <span class="input_id"></span></button>
    </form>
</template>
