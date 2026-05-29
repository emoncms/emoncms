
<div id="inputEditModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="inputEditModalLabel" aria-hidden="true" data-backdrop="static" v-cloak>
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3 id="inputEditModalLabel"><?php echo tr('Edit Input'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo tr("Edit the input's description."); ?>
        <em class="muted">({{selected.length}} <?php echo tr('Inputs') ?>)</em>
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
                    <td class="muted">{{input.nodeid}}</td>
                    <td>{{input.name}}</td>
                    <td><input type="text" class="input-block-level" placeholder="<?php echo tr('Description') ?>" v-model="input.description"></td>
                    <td>
                        <transition name="fade">
                            <small class="muted" v-if="errors[input.id]">{{ errors[input.id] }}</small>
                        </transition>
                    </td>
                </tr>
            </tbody>
        </table>
        <div id="inputEdit-loader" class="ajax-loader" :class="{'hide': !loading}"></div>
    </div>
    <div class="modal-footer">
        <div>
            <h5>
                <transition name="fade" appear>
                    <span v-if="message">{{message}}</span>
                </transition>
            </h5>
        </div>
        <div>
            <button @click="closeModal" class="btn btn-small" type="button"><?php echo tr('Close'); ?></button>
            <button class="btn btn-small btn-primary" type="button" @click="saveAll"><?php echo tr('Save'); ?></button>
        </div>
    </div>
</div>
