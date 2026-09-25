
<div id="inputEditModal" class="modal" tabindex="-1" aria-labelledby="inputEditModalLabel" aria-hidden="true" data-bs-backdrop="static" v-cloak>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="inputEditModalLabel" class="modal-title"><?php echo tr('Edit Input'); ?></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?php echo tr("Edit the input's description."); ?>
                <em class="text-muted">({{selected.length}} <?php echo tr('Inputs') ?>)</em>
                </p>
                <table class="table table-sm">
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
                            <td><input type="text" class="form-control" placeholder="<?php echo tr('Description') ?>" v-model="input.description"></td>
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
            <div class="modal-footer">
                <div>
                    <h5>
                        <transition name="fade" appear>
                            <span v-if="message">{{message}}</span>
                        </transition>
                    </h5>
                </div>
                <div>
                    <button @click="closeModal" class="btn btn-default btn-sm" type="button"><?php echo tr('Close'); ?></button>
                    <button class="btn btn-sm btn-primary" type="button" @click="saveAll"><?php echo tr('Save'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
