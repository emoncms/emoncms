
<div id="inputEditModal" v-cloak>
    <div :class="{hide: hidden}" class="modal modal-wide" tabindex="-1" role="dialog" aria-labelledby="inputEditModalLabel" :aria-hidden="String(hidden)">
        <div class="modal-header">
            <button @click="closeModal" type="button" class="close"><span aria-hidden="true">×</span></button>
            <h3 id="inputEditModalLabel"><?php echo tr('Edit Input'); ?></h3>
        </div>
        <div class="modal-body">
            <p><?php echo tr("Edit the input's description."); ?>
            <em class="text-muted muted">({{selected.length}} <?php echo tr('Inputs') ?>)</em>
            </p>
            <table class="table table-condensed">
                <thead>
                    <tr>
                        <th><?php echo tr('Node') ?></th>
                        <th><?php echo tr('Name') ?></th>
                        <th><?php echo tr('Description') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="input in selectedInputs" :key="input.id">
                        <td class="text-muted">{{input.nodeid}}</td>
                        <td>{{input.name}}</td>
                        <td><input type="text" class="input-block-level" placeholder="<?php echo tr('Description') ?>" v-model="input.description"></td>
                        <td>
                            <transition name="fade">
                                <small class="text-muted" v-if="errors[input.id]">{{ errors[input.id] }}</small>
                            </transition>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div id="inputEdit-loader" class="ajax-loader" :class="{'hide': !loading}"></div>
        </div>
        <div class="modal-footer d-flex justify-content-between align-items-center">
            <div class="position-relative">
                <h5 class="m-0">
                    <transition name="fade" appear>
                        <span v-if="message">{{message}}</span>
                    </transition>
                </h5>
            </div>
            <div>
                <button @click="closeModal" class="btn" type="button"><?php echo tr('Close'); ?></button>
                <button class="btn btn-primary" type="button" @click="saveAll"><?php echo tr('Save'); ?></button>
            </div>
        </div>
    </div>
    <div @click="closeModal" class="modal-backdrop" :class="{'hide': hidden}"></div>
</div>
