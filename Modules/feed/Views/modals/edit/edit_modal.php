
<?php
include_once('Lib/units.php');
?>
<style>
@media (min-width: 768px) {
    #feedEditModal .modal { width: 680px; margin-left: -340px; }
}
</style>
<script>var feed_units = <?php echo json_encode(defined('UNITS') ? UNITS : array(), JSON_UNESCAPED_UNICODE); ?>;</script>

<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED EDIT MODAL                                                                                                                               -->
<!------------------------------------------------------------------------------------------------------------------------------------------------- -->
<div id="feedEditModal" v-cloak>
    <div :class="{hide: hidden}" class="modal" tabindex="-1" role="dialog" aria-labelledby="feedEditModalLabel" :aria-hidden="String(hidden)">
        <div class="modal-header">
            <button @click="closeModal" type="button" class="close"><span aria-hidden="true">×</span></button>
            <h3 id="feedEditModalLabel"><?php echo tr('Edit Feed'); ?></h3>
        </div>
        <div class="modal-body">
            <p><?php echo tr("Edit the selected feed fields."); ?>
            <em class="text-muted">({{selectedFeedIds.length}} <?php echo tr('Feeds') ?>)</em>
            </p>
            <table class="table table-condensed">
                <thead>
                    <tr>
                        <th><?php echo tr('Name') ?></th>
                        <th><?php echo tr('Node') ?></th>
                        <th><?php echo tr('Unit') ?></th>
                        <th><?php echo tr('Public') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="feed in selectedFeeds" :key="feed.id">
                        <td>
                            <input v-if="selectedFeedIds.length === 1" type="text" class="input-block-level" v-model="feed.name">
                            <span v-else class="text-muted">{{feed.name}}</span>
                        </td>
                        <td>
                            <input type="text" class="input-block-level" v-model="feed.tag">
                        </td>
                        <td>
                            <select :value="unitOther[feed.id] ? '_other' : feed.unit" @change="onUnitChange(feed, $event)">
                                <option value=""><?php echo tr('-- select --') ?></option>
                                <option v-for="u in units" :key="u.short" :value="u.short">{{u.long}} ({{u.short}})</option>
                                <option value="_other"><?php echo tr('Other') ?></option>
                            </select>
                            <input v-if="unitOther[feed.id]" type="text" class="input-small" :value="feed.unit" @input="feed.unit = $event.target.value" placeholder="<?php echo tr('unit') ?>">
                        </td>
                        <td class="text-center">
                            <input type="checkbox" :checked="!!feed.public" @change="feed.public = $event.target.checked ? 1 : 0">
                        </td>
                        <td>
                            <transition name="fade">
                                <small class="text-muted" v-if="errors[feed.id]">{{ errors[feed.id] }}</small>
                            </transition>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="ajax-loader" :class="{'hide': !loading}"></div>
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