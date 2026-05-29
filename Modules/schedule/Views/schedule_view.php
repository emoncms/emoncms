<?php
    global $path;
?>
<?php
    load_language_files("Modules/schedule/locale", "schedule_messages");
    load_js("Lib/js/vue.global.prod-3.5.22.min.js");
    load_js("Modules/schedule/Views/schedule.js");
?>

<div id="schedule-app">

    <a href="api" style="float:right"><?php echo ctx_tr('schedule_messages','Schedule API'); ?></a>

    <h3><?php echo ctx_tr('schedule_messages','Schedules'); ?></h3>

    <details class="schedule-expr-details well" :open="loaded && !schedules.length">
        <summary><b v-if="!schedules.length"><?php echo ctx_tr('schedule_messages','No schedules yet'); ?></b><b v-else><?php echo ctx_tr('schedule_messages','Expression reference'); ?></b></summary>
        <div class="schedule-expr-body mt-3">
            <div v-if="!schedules.length">
                <p><?php echo ctx_tr('schedule_messages','A schedule defines an active time range using an expression — for example <b>Mon-Fri | 09:00-17:00</b> for weekday office hours, or <b>Summer | 00:00-23:59</b> for an entire season.'); ?></p>
                <p><?php echo ctx_tr('schedule_messages','Once created, a schedule can be assigned to an Input or Feed Processlist to control when that process runs. Click <b>New schedule</b> below to get started.'); ?></p>
                <hr style="border-color:inherit;opacity:0.4">
            </div>
            <p><?php echo ctx_tr('schedule_messages','Granularity is day light saving time, month, day, week day, hour and minute.'); ?></p>
            <p><?php echo ctx_tr('schedule_messages','Expression is built mixing basic blocks with operation characters. An hour is always required. All other basic blocks are optional and can be mixed on the same expression to build complex schedule rules. Ranges must be ordered older-newer. White spaces are ignored and can be ommited.'); ?></p>
            <p><?php echo ctx_tr('schedule_messages','Timezone of expression is the same as the user account that created or edited it.'); ?></p>
            <p><b><?php echo ctx_tr('schedule_messages','Basic blocks:'); ?></b></p>
            <pre>
            <b>Summer</b> or <b>Winter</b> =>  Day light saving time period
            <b>mm/dd</b> =>  Month and day in numeric format with leading zero
            <b>Mon Tue Wed Thu Fri Sat Sun</b> =>  Week day 3 letters english
            <b>hh:mm</b> =>  Hour in 24hrs format and minute with leading zero
            </pre>
            <p><b><?php echo ctx_tr('schedule_messages','Operation characters:'); ?></b></p>
            <pre>
            <b>-</b> => Range
            <b>,</b> => Addition
            <b>|</b> => Granularity separator
            </pre>
            <p><b><?php echo ctx_tr('schedule_messages','Expression examples:'); ?></b></p>
            <pre>
            '12:00-23:59'
            'Mon-Fri | 00:00-23:59'
            'Summer | Mon-Fri | 00:00-23:59'
            'Winter | Mon-Fri | 00:00-23:59'
            'Winter | Mon-Fri | 09:00-09:59, Summer | Mon-Fri | 08:00-08:59'
            'Mon,Wed | 00:00-06:00, 12:00-00:00, Fri-Sun | 00:00-06:00, 12:00-00:00'
            '12/25 | 00:00-23:59'
            '12/01 - 12/31 | Sat,Sun | 09:00-11:59, 13:00-19:59'
            '01/15, 02/29, 01/01-02/18, 08/01-12/25, 09/19 | Mon-Fri | 12:00-14:14, 18:00-22:29, Thu | 18:00-22:44'

            'Mon-Fri|00:00-06:59, Sat|00:00-09:29,13:00-18:29,22:00-23:59, Sun|00:00-23:59'    <- Weekly Winter Empty
            'Mon-Fri|07:00-09:29,12:00-18:29,21:00-23:59, Sat|09:30-12:59,18:30-21:59'         <- Weekly Winter Full
            'Mon-Fri|09:30-11:59,18:30-20:59'                                                  <- Weekly Winter Top

            'Mon-Fri|00:00-06:59, Sat|00:00-08:59,14:00-19:59,22:00-23:59, Sun|00:00-23:59'    <- Weekly Summer Empty
            'Mon-Fri|07:00-09:14,12:15-23:59, Sat|09:00-13:59,20:00-21:59'                     <- Weekly Summer Full
            'Mon-Fri|09:15-12:14'                                                              <- Weekly Summer Top
            </pre>
        </div>
    </details>


    <div v-if="schedules.length" class="">
        <table class="table table-striped">
            <tr>
                <th>ID</th>
                <th><?php echo ctx_tr('schedule_messages','Name'); ?></th>
                <th><?php echo ctx_tr('schedule_messages','Expression'); ?></th>
                <th style="width:300px">Actions</th>
            </tr>
            <tr v-for="s in schedules" :key="s.id">
                <td>{{ s.id }}</td>
                <td>
                    <input v-if="editingId === s.id" type="text" v-model="editFields.name" />
                    <span v-else>{{ s.name }}</span>
                </td>
                <td>
                    <input v-if="editingId === s.id" type="text" v-model="editFields.expression" />
                    <span v-else>{{ s.expression }}</span>
                </td>
                <td>
                    <button class="btn btn-info" v-if="editingId !== s.id" @click="startEdit(s)">Edit</button>
                    <button class="btn btn-warning" v-if="editingId === s.id" @click="saveEdit(s)">Save</button>
                    &nbsp;<button class="btn btn-danger" @click="promptDelete(s.id)">Delete</button>&nbsp;
                    <button class="btn btn-success" @click="testSchedule(s)">Test</button>
                </td>
            </tr>
        </table>
    </div>

    <div class="app-loader" v-show="loading"></div>

    <div class="app-toolbar">
        <button class="btn" @click="addNew"><i class="icon icon-plus-sign"></i> <?php echo ctx_tr('schedule_messages','New schedule'); ?></button>
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
            loaded: false,
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
                self.loaded = true;
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
