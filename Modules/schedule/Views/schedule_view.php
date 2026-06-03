<?php
    global $path;
?>
<?php
    load_language_files("Modules/schedule/locale", "schedule_messages");
    load_js("Lib/js/vue.global.prod-3.5.22.min.js");
    load_js("Modules/schedule/Views/schedule.js");
?>

<style>
.sb-wrap { font-size:13px; }
.sb-rule { border:1px solid #ddd; padding:10px 12px; margin-bottom:8px; border-radius:4px; background:#fafafa; }
.sb-rule-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
.sb-rule-header strong { font-size:12px; color:#555; text-transform:uppercase; letter-spacing:.04em; }
.sb-row { display:flex; align-items:flex-start; margin-bottom:8px; gap:8px; }
.sb-label { min-width:56px; color:#666; font-size:12px; padding-top:4px; flex-shrink:0; }
.sb-day-btn { padding:2px 5px !important; font-size:12px !important; min-width:30px; margin:1px; line-height:1.4; }
.sb-presets { margin-left:6px; }
.sb-presets .btn { padding:2px 6px !important; font-size:11px !important; margin:1px; }
.sb-hint { color:#aaa; font-size:11px; margin-left:6px; }
.sb-preview { margin:8px 0 4px; padding:5px 8px; background:#f0f0f0; border-radius:3px; font-family:monospace; font-size:12px; word-break:break-all; color:#333; border:1px solid #e0e0e0; }
.sb-add-rule { margin-bottom:4px; font-size:12px; padding:3px 8px !important; }
.sb-mode-toggle { margin-top:6px; }
.sb-time-row { display:flex; align-items:center; gap:6px; margin-bottom:4px; }
.sb-time-row input[type="time"] { width:115px; padding:3px 5px; font-size:13px; }
.sb-time-remove { padding:1px 5px !important; font-size:12px !important; line-height:1.4; }
</style>

<div id="schedule-app">

    <a href="api" style="float:right"><?php echo ctx_tr('schedule_messages','Schedule API'); ?></a>

    <h3><?php echo ctx_tr('schedule_messages','Schedules'); ?></h3>
    <p style="color:#666;"><?php echo ctx_tr('schedule_messages','Schedules define active time windows that can be assigned to Input or Feed process lists to control when those processes run.'); ?></p>

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
                    <schedule-expr-builder v-if="editingId === s.id" v-model="editFields.expression"></schedule-expr-builder>
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

    <div v-if="testResult !== null" class="modal show" tabindex="-1" role="dialog" style="display:block;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" @click="closeTest" aria-hidden="true">×</button>
                    <h3>Test: {{ testResult.name }}</h3>
                </div>
                <div class="modal-body">
                    <div style="text-align:center;padding:12px 0 20px;">
                        <div :style="testResult.active ? 'color:#3a3' : 'color:#aaa'"
                             style="font-size:26px;font-weight:bold;line-height:1.2;">
                            {{ testResult.active ? '● Active' : '○ Inactive' }}
                        </div>
                        <div style="color:#999;font-size:12px;margin-top:4px;">at time of test</div>
                    </div>
                    <div style="margin-bottom:12px;">
                        <div style="font-size:12px;color:#666;margin-bottom:4px;">Expression</div>
                        <code style="display:block;padding:6px 8px;background:#f5f5f5;border:1px solid #e0e0e0;border-radius:3px;word-break:break-all;">{{ testResult.expression }}</code>
                    </div>
                    <div v-if="testResult.evaluatedAt" style="margin-bottom:12px;">
                        <div style="font-size:12px;color:#666;margin-bottom:4px;">Evaluated at</div>
                        <div style="font-size:13px;">{{ testResult.evaluatedAt }}</div>
                    </div>
                    <details style="margin-top:4px;">
                        <summary style="cursor:pointer;font-size:12px;color:#888;user-select:none;">Debug output</summary>
                        <pre style="font-size:11px;margin-top:8px;max-height:220px;overflow-y:auto;background:#f8f8f8;padding:8px;border:1px solid #e0e0e0;border-radius:3px;white-space:pre-wrap;word-break:break-word;">{{ testResult.debug }}</pre>
                    </details>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" @click="closeTest"><?php echo ctx_tr('schedule_messages','Close'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var SCHED_DAYS = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

var ScheduleExprBuilder = {
    name: 'ScheduleExprBuilder',
    props: { modelValue: String },
    emits: ['update:modelValue'],

    data: function() {
        return {
            DAYS: SCHED_DAYS,
            rules: [this.emptyRule()],
            customMode: false,
            customExpr: '',
            parseError: false,
            showHelp: false
        };
    },

    computed: {
        expression: function() {
            if (this.customMode) return this.customExpr;
            return this.buildExpression();
        }
    },

    watch: {
        modelValue: {
            immediate: true,
            handler: function(val) {
                if ((val || '') !== this.expression) this.load(val || '');
            }
        },
        expression: function(val) {
            this.$emit('update:modelValue', val);
        }
    },

    methods: {
        emptyRule: function() {
            return { dst: '', weekdays: [], times: [{ from: '', to: '' }] };
        },

        buildExpression: function() {
            var self = this;
            var parts = this.rules.map(function(rule) {
                var segs = [];
                if (rule.dst) segs.push(rule.dst);
                var de = self.daysToExpr(rule.weekdays);
                if (de) segs.push(de);
                var te = rule.times
                    .filter(function(t) { return t.from && t.to; })
                    .map(function(t) { return t.from + '-' + t.to; })
                    .join(',');
                if (!te) return null;
                return (segs.length ? segs.join('|') + '|' : '') + te;
            }).filter(Boolean);
            return parts.join(', ');
        },

        daysToExpr: function(days) {
            if (!days.length || days.length === 7) return '';
            var self = this;
            var idx = days.map(function(d) { return self.DAYS.indexOf(d); })
                         .filter(function(i) { return i !== -1; })
                         .sort(function(a, b) { return a - b; });
            var cont = idx.every(function(v, i, a) { return i === 0 || v === a[i-1] + 1; });
            if (cont && idx.length >= 2) return this.DAYS[idx[0]] + '-' + this.DAYS[idx[idx.length-1]];
            return idx.map(function(i) { return self.DAYS[i]; }).join(',');
        },

        load: function(expr) {
            if (!expr.trim()) {
                this.customMode = false;
                this.rules = [this.emptyRule()];
                return;
            }
            var rules = this.parseExpression(expr);
            if (!rules) {
                this.customMode = true;
                this.customExpr = expr;
                return;
            }
            this.customMode = false;
            this.rules = rules.length ? rules : [this.emptyRule()];
        },

        parseExpression: function(expr) {
            var self = this;
            var pieces = expr.split(',').map(function(p) { return p.trim(); }).filter(Boolean);
            var rules = [];
            var cur = [];

            function startsNewRule(p) {
                return p.indexOf('|') !== -1 || /^(Summer|Winter|Mon|Tue|Wed|Thu|Fri|Sat|Sun)/i.test(p);
            }

            for (var i = 0; i < pieces.length; i++) {
                var p = pieces[i];
                if (startsNewRule(p) && cur.length) {
                    var r = self.parseSegment(cur.join(','));
                    if (!r) return null;
                    rules.push(r);
                    cur = [p];
                } else {
                    cur.push(p);
                }
            }
            if (cur.length) {
                var r = self.parseSegment(cur.join(','));
                if (!r) return null;
                rules.push(r);
            }
            return rules;
        },

        parseSegment: function(seg) {
            var self = this;
            var parts = seg.split('|').map(function(p) { return p.trim(); });
            var i = 0, dst = '', weekdays = [];

            if (/^(Summer|Winter)$/i.test(parts[i] || '')) { dst = parts[i++]; }
            if (/\d{1,2}\/\d{1,2}/.test(parts[i] || '')) return null; // date filter not supported in builder
            if (/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun)/i.test(parts[i] || '')) {
                weekdays = self.expandWeekdays(parts[i++]);
            }

            var times = (parts[i] || '').split(',').map(function(t) {
                var m = t.trim().match(/^(\d{2}:\d{2})-(\d{2}:\d{2})$/);
                return m ? { from: m[1], to: m[2] } : null;
            }).filter(Boolean);

            if (!times.length) return null;
            return { dst: dst, weekdays: weekdays, times: times };
        },

        expandWeekdays: function(expr) {
            var self = this;
            var days = [];
            expr.split(',').forEach(function(part) {
                var p = part.trim();
                if (p.indexOf('-') !== -1) {
                    var sides = p.split('-');
                    var si = self.DAYS.indexOf(sides[0].trim());
                    var ei = self.DAYS.indexOf(sides[1].trim());
                    if (si !== -1 && ei !== -1) {
                        for (var j = si; j <= ei; j++) days.push(self.DAYS[j]);
                    }
                } else if (self.DAYS.indexOf(p) !== -1) {
                    days.push(p);
                }
            });
            return days;
        },

        toggleDay: function(rule, day) {
            var i = rule.weekdays.indexOf(day);
            if (i === -1) rule.weekdays.push(day);
            else rule.weekdays.splice(i, 1);
        },

        setDays: function(rule, preset) {
            if (preset === 'all')      rule.weekdays = this.DAYS.slice();
            else if (preset === 'weekdays') rule.weekdays = ['Mon','Tue','Wed','Thu','Fri'];
            else if (preset === 'weekend')  rule.weekdays = ['Sat','Sun'];
            else                       rule.weekdays = [];
        },

        addTime: function(rule)       { rule.times.push({ from: '', to: '' }); },
        removeTime: function(rule, i) { rule.times.splice(i, 1); },
        addRule: function()           { this.rules.push(this.emptyRule()); },
        removeRule: function(i)       { this.rules.splice(i, 1); },

        switchToCustom: function() {
            this.customExpr = this.expression;
            this.customMode = true;
            this.parseError = false;
        },

        switchToBuilder: function() {
            var rules = this.parseExpression(this.customExpr);
            if (!rules) { this.parseError = true; return; }
            this.parseError = false;
            this.customMode = false;
            this.rules = rules.length ? rules : [this.emptyRule()];
        }
    },

    template: `
        <div class="sb-wrap">
            <template v-if="!customMode">
                <div v-for="(rule, ri) in rules" :key="ri" class="sb-rule">
                    <div class="sb-rule-header">
                        <strong>Rule {{ ri + 1 }}</strong>
                        <button v-if="rules.length > 1" @click="removeRule(ri)"
                                class="btn btn-danger" style="padding:1px 7px;font-size:12px;">Remove</button>
                    </div>

                    <div class="sb-row">
                        <span class="sb-label">Season</span>
                        <select v-model="rule.dst" class="input-small" style="margin-bottom:0;">
                            <option value="">Any season</option>
                            <option value="Summer">Summer (DST on)</option>
                            <option value="Winter">Winter (DST off)</option>
                        </select>
                    </div>

                    <div class="sb-row">
                        <span class="sb-label">Days</span>
                        <div>
                            <button v-for="day in DAYS" :key="day"
                                    @click="toggleDay(rule, day)"
                                    :class="['btn', 'sb-day-btn', rule.weekdays.includes(day) ? 'btn-primary' : '']">
                                {{ day.slice(0,2) }}
                            </button>
                            <span class="sb-presets">
                                <button @click="setDays(rule,'all')"      class="btn">All</button>
                                <button @click="setDays(rule,'weekdays')" class="btn">M–F</button>
                                <button @click="setDays(rule,'weekend')"  class="btn">S–S</button>
                                <button @click="setDays(rule,'none')"     class="btn">Clear</button>
                            </span>
                            <span class="sb-hint" v-if="!rule.weekdays.length">none = any day</span>
                        </div>
                    </div>

                    <div class="sb-row">
                        <span class="sb-label" style="padding-top:5px;">Times</span>
                        <div>
                            <div v-for="(t, ti) in rule.times" :key="ti" class="sb-time-row">
                                <input type="time" v-model="t.from">
                                <span style="color:#888;">–</span>
                                <input type="time" v-model="t.to">
                                <button v-if="rule.times.length > 1" @click="removeTime(rule, ti)"
                                        class="btn btn-danger sb-time-remove">×</button>
                            </div>
                            <button @click="addTime(rule)" class="btn" style="padding:2px 8px;font-size:12px;margin-top:2px;">
                                + Add time range
                            </button>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:6px;">
                    <button @click="addRule" class="btn sb-add-rule">+ Add rule</button>
                </div>

                <div class="sb-preview">{{ expression || '(set times above to build expression)' }}</div>
            </template>

            <template v-else>
                <div style="display:flex;margin-bottom:4px;">
                    <input type="text" v-model="customExpr"
                           style="flex:1;min-width:0;margin-bottom:0;border-radius:3px 0 0 3px;"
                           placeholder="e.g. Mon-Fri | 09:00-17:00">
                    <button @click="showHelp=true" class="btn"
                            style="border-left:0;border-radius:0 3px 3px 0;"
                            title="Expression reference"><i class="icon icon-info-sign"></i></button>
                </div>
                <div v-if="parseError" style="color:#c00;font-size:11px;margin-bottom:2px;">
                    Expression too complex to convert to builder view.
                </div>
            </template>

            <div class="sb-mode-toggle">
                <a href="#" @click.prevent="customMode ? switchToBuilder() : switchToCustom()"
                   style="font-size:11px;color:#888;">
                    {{ customMode ? '← Use builder' : 'Custom expression →' }}
                </a>
            </div>

            <div v-if="showHelp" class="modal show" tabindex="-1" role="dialog" style="display:block;">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" @click="showHelp=false" aria-hidden="true">×</button>
                            <h3>Expression reference</h3>
                        </div>
                        <div class="modal-body">
                            <p>Granularity is day light saving time, month, day, week day, hour and minute.</p>
                            <p>An expression is built by mixing basic blocks with operation characters. An hour range is always required. All other blocks are optional and can be mixed to build complex rules. Ranges must be ordered older-to-newer. White spaces are ignored.</p>
                            <p>Timezone is that of the user account that created or last edited the schedule.</p>
                            <p><b>Basic blocks:</b></p>
                            <pre style="font-size:12px;"><b>Summer</b> or <b>Winter</b>          Day light saving time period
<b>mm/dd</b>                    Month and day (numeric, leading zero)
<b>Mon Tue Wed Thu Fri Sat Sun</b>  Week day (3-letter English)
<b>hh:mm</b>                    Hour in 24-hour format and minute (leading zero)</pre>
                            <p><b>Operation characters:</b></p>
                            <pre style="font-size:12px;"><b>-</b>   Range
<b>,</b>   Addition
<b>|</b>   Granularity separator</pre>
                            <p><b>Examples:</b></p>
                            <pre style="font-size:12px;">'12:00-23:59'
'Mon-Fri | 00:00-23:59'
'Summer | Mon-Fri | 00:00-23:59'
'Winter | Mon-Fri | 09:00-09:59, Summer | Mon-Fri | 08:00-08:59'
'Mon,Wed | 00:00-06:00, 12:00-00:00, Fri-Sun | 00:00-06:00, 12:00-00:00'
'12/25 | 00:00-23:59'
'12/01 - 12/31 | Sat,Sun | 09:00-11:59, 13:00-19:59'
'01/15, 02/29, 01/01-02/18, 08/01-12/25, 09/19 | Mon-Fri | 12:00-14:14, 18:00-22:29, Thu | 18:00-22:44'</pre>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-primary" @click="showHelp=false">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
};

var scheduleApp = Vue.createApp({
    data: function() {
        return {
            schedules: [],
            loaded: false,
            editingId: null,
            editFields: { name: '', expression: '' },
            deleteTargetId: null,
            testResult: null,
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
            var result = schedule.test(s.id);
            var lines = {};
            var re = /^(\w+) =(.+)$/mg, m;
            while ((m = re.exec(result.debug || '')) !== null) lines[m[1]] = m[2].trim();
            this.testResult = {
                name: s.name,
                active: result.result,
                expression: lines.Expression || s.expression,
                evaluatedAt: lines.HrMin || '',
                debug: result.debug || ''
            };
            this.startUpdater(0);
        },
        closeTest: function() {
            this.testResult = null;
            this.startUpdater(10000);
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

scheduleApp.component('schedule-expr-builder', ScheduleExprBuilder);
scheduleApp.mount('#schedule-app');
</script>
