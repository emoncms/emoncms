<?php
    global $path;
?>
<?php
    load_language_files("Modules/schedule/locale", "schedule_messages");
    load_js("Modules/schedule/Views/schedule.js");
?>

<div id="schedule-app">

    <div v-show="schedules.length" class="page-header">
        <h2><?php echo ctx_tr('schedule_messages','Schedules'); ?></h2>
        <a href="api"><?php echo ctx_tr('schedule_messages','Schedule Help'); ?></a>
    </div>

    <div v-if="!schedules.length" class="empty-state">
        <h4><?php echo ctx_tr('schedule_messages','No schedules'); ?></h4>
        <p><?php echo ctx_tr('schedule_messages','There are no public schedules and you have not created your own yet. Please add a new schedule.<br><br>For help and examples on how to configure a schedule, read the <a href="api#expression">Expression documentation</a>.'); ?></p>
    </div>

    <div v-if="schedules.length" class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo ctx_tr('schedule_messages','Name'); ?></th>
                    <th><?php echo ctx_tr('schedule_messages','Expression'); ?></th>
                    <th><?php echo ctx_tr('schedule_messages','Public'); ?></th>
                    <th class="col-action"></th>
                    <th class="col-action"></th>
                    <th class="col-action"></th>
                    <th class="col-action"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="s in schedules" :key="s.id">
                    <td class="col-secondary">{{ s.id }}</td>
                    <td class="col-primary">
                        <input v-if="editingId === s.id" type="text" v-model="editFields.name" />
                        <span v-else>{{ s.name }}</span>
                    </td>
                    <td>
                        <input v-if="editingId === s.id" type="text" v-model="editFields.expression" />
                        <span v-else>{{ s.expression }}</span>
                    </td>
                    <td class="col-action">
                        <i :class="s.public ? 'icon-white icon-globe' : 'icon-white icon-lock'"
                           class="row-action"
                           @click="s.own ? togglePublic(s) : null"></i>
                    </td>
                    <td class="col-action">
                        <a v-if="s.own" class="row-action" @click="editingId === s.id ? saveEdit(s) : startEdit(s)">
                            <i :class="editingId === s.id ? 'icon-white icon-ok' : 'icon-white icon-pencil'"></i>
                        </a>
                    </td>
                    <td class="col-action">
                        <a v-if="s.own" class="row-action" @click="promptDelete(s.id)">
                            <i class="icon-white icon-trash"></i>
                        </a>
                    </td>
                    <td class="col-action">
                        <i class="icon-white icon-wrench row-action" @click="wrenchSchedule(s)"></i>
                    </td>
                    <td class="col-action">
                        <i class="icon-white icon-eye-open row-action" @click="testSchedule(s)"></i>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="app-loader" v-show="loading"></div>

    <div class="app-toolbar"><hr>
        <button class="app-btn" @click="addNew"><i class="icon-white icon-plus-sign"></i> <?php echo ctx_tr('schedule_messages','New schedule'); ?></button>
    </div>

    <div v-if="deleteTargetId !== null" class="modal show" tabindex="-1" role="dialog" style="display:block;" data-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" @click="cancelDelete" aria-hidden="true">×</button>
                    <h3><?php echo ctx_tr('schedule_messages','Delete schedule'); ?></h3>
                </div>
                <div class="modal-body">
                    <p><?php echo ctx_tr('schedule_messages','Deleting a schedule is permanent.'); ?>
                       <br><br>
                       <?php echo ctx_tr('schedule_messages','If you have an Input or Feed Processlist that use this schedule, after deleting it, review that process list or it will be in error freezing other process lists.'); ?>
                       <br><br>
                       <?php echo ctx_tr('schedule_messages','Are you sure you want to delete?'); ?>
                    </p>
                </div>
                <div class="modal-footer">
                    <button class="btn" @click="cancelDelete"><?php echo ctx_tr('schedule_messages','Cancel'); ?></button>
                    <button class="btn btn-primary" @click="confirmDelete"><?php echo ctx_tr('schedule_messages','Delete permanently'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var scheduleApp = Vue.createApp({
    data: function() {
        return {
            schedules: [],
            editingId: null,
            editFields: { name: '', expression: '' },
            deleteTargetId: null,
            loading: false,
            updater: null
        };
    },
    methods: {
        update: function() {
            var self = this;
            $.ajax({ url: path + "schedule/list.json", dataType: 'json', async: true, success: function(data) {
                // Don't clobber a row currently being edited
                if (self.editingId !== null) return;
                self.schedules = data || [];
                self.loading = false;
            }});
        },
        startUpdater: function(interval) {
            clearInterval(this.updater);
            this.updater = null;
            if (interval > 0) this.updater = setInterval(this.update.bind(this), interval);
        },
        startEdit: function(s) {
            this.editingId = s.id;
            this.editFields = { name: s.name, expression: s.expression };
            this.startUpdater(0);
        },
        saveEdit: function(s) {
            var self = this;
            var fieldsToUpdate = {};
            if (this.editFields.name !== s.name) fieldsToUpdate.name = this.editFields.name;
            if (this.editFields.expression !== s.expression) fieldsToUpdate.expression = this.editFields.expression;

            if (Object.keys(fieldsToUpdate).length) {
                self.loading = true;
                var result = schedule.set(s.id, fieldsToUpdate);
                self.loading = false;
                if (!result.success) {
                    alert(result.message);
                    return;
                }
                s.name = this.editFields.name;
                s.expression = this.editFields.expression;
            }
            this.editingId = null;
            this.startUpdater(10000);
        },
        togglePublic: function(s) {
            var self = this;
            var newVal = !s.public;
            self.loading = true;
            var result = schedule.set(s.id, { public: newVal });
            self.loading = false;
            if (result.success) {
                s.public = newVal;
            } else {
                alert(result.message);
            }
        },
        promptDelete: function(id) {
            this.deleteTargetId = id;
            this.startUpdater(0);
        },
        cancelDelete: function() {
            this.deleteTargetId = null;
            this.startUpdater(10000);
        },
        confirmDelete: function() {
            var id = this.deleteTargetId;
            schedule.remove(id);
            this.schedules = this.schedules.filter(function(s) { return s.id !== id; });
            this.deleteTargetId = null;
            this.startUpdater(10000);
        },
        addNew: function() {
            var self = this;
            $.ajax({ url: path + "schedule/create.json", success: function(data) { self.update(); } });
        },
        wrenchSchedule: function(s) {
            console.log(s);
            alert("TBD: Javascript expression builder " + s.id);
        },
        testSchedule: function(s) {
            console.log(s);
            var result = schedule.test(s.id);
            alert("Schedule expression returned '" + result.result + "'.\n\nDetails:\n" + result.debug);
        }
    },
    mounted: function() {
        this.update();
        this.startUpdater(10000);
    },
    beforeUnmount: function() {
        this.startUpdater(0);
    }
});

scheduleApp.mount('#schedule-app');
</script>
