<?php
    global $path;
    load_language_files("Modules/schedule/locale", "schedule_messages");
    load_js("Lib/js/vue.global.prod-3.5.22.min.js");
    load_js("Modules/schedule/Views/schedule.js");
    load_css("Modules/schedule/Views/schedule_view.css");
?>

<div id="schedule-app" class="panel-page schedule-page" v-cloak>

    <div class="page-header">
        <h3><?php echo ctx_tr('schedule_messages','Schedules'); ?></h3>
        <a href="api"><?php echo ctx_tr('schedule_messages','Schedule API'); ?></a>
    </div>
    <p class="page-lead"><?php echo ctx_tr('schedule_messages','Schedules define active time windows that can be assigned to Input or Feed process lists to control when those processes run.'); ?></p>

    <div class="panel">
        <div class="panel-header panel-header-static">
            <span class="panel-accent"></span>
            <span class="panel-name"><?php echo ctx_tr('schedule_messages','Schedules'); ?></span>
            <span class="panel-badge">{{ schedules.length }}</span>
            <button class="btn btn-default btn-sm" @click="addNew"><span class="svg-icon-plus"></span> <?php echo ctx_tr('schedule_messages','New schedule'); ?></button>
        </div>
        <div class="panel-table" v-if="schedules.length">
        <table>
            <colgroup><col class="sch-col-name"><col><col class="sch-col-tz"><col class="sch-col-actions"></colgroup>
            <thead>
                <tr>
                    <th><?php echo ctx_tr('schedule_messages','Name'); ?></th>
                    <th><?php echo ctx_tr('schedule_messages','Expression'); ?></th>
                    <th>Timezone</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="s in schedules" :key="s.id" :class="{'is-editing': editingId === s.id}">
                    <td class="col-primary" :title="'ID: ' + s.id">{{ s.name }}</td>
                    <td>
                        <span v-if="s.expression" class="sch-expr">{{ s.expression }}</span>
                        <span v-else class="text-muted">No times set</span>
                    </td>
                    <td class="col-secondary">{{ s.timezone }}</td>
                    <td class="panel-actions">
                        <button class="btn btn-default btn-sm" @click="testSchedule(s)"><span class="svg-icon-play"></span> Test</button>
                        <button class="btn btn-default btn-sm" @click="startEdit(s)"><span class="svg-icon-pencil"></span> Edit</button>
                        <button class="btn btn-danger btn-sm" title="Delete" @click="promptDelete(s.id)"><span class="svg-icon-trash"></span></button>
                    </td>
                </tr>
            </tbody>
        </table>
        </div>
        <div class="panel-body panel-empty" v-else-if="loaded">No schedules yet.</div>
    </div>

    <div class="panel" v-if="editingId !== null" ref="editor">
        <div class="panel-header panel-header-static">
            <span class="panel-accent"></span>
            <span class="panel-name">Edit schedule</span>
            <span class="panel-badge">ID {{ editingId }}</span>
        </div>
        <div class="panel-body panel-form">
            <div class="panel-field">
                <label class="form-label"><?php echo ctx_tr('schedule_messages','Name'); ?></label>
                <input type="text" class="form-control input-285" v-model="editFields.name" />
            </div>
            <div class="panel-field">
                <label class="form-label"><?php echo ctx_tr('schedule_messages','Expression'); ?></label>
                <schedule-expr-builder v-model="editFields.expression"></schedule-expr-builder>
            </div>
            <div class="panel-buttons">
                <button class="btn btn-primary" @click="saveEdit">Save</button>
                <button class="btn btn-default" @click="cancelEdit"><?php echo ctx_tr('schedule_messages','Cancel'); ?></button>
            </div>
        </div>
    </div>

    <template v-if="deleteTargetId !== null">
        <div class="modal show" tabindex="-1" role="dialog" style="display:block;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title"><?php echo ctx_tr('schedule_messages','Delete schedule'); ?></h3>
                        <button type="button" class="btn-close" @click="cancelDelete" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><?php echo ctx_tr('schedule_messages','Deleting a schedule is permanent.'); ?></p>
                        <p><?php echo ctx_tr('schedule_messages','If you have an Input or Feed Processlist that use this schedule, after deleting it, review that process list or it will be in error freezing other process lists.'); ?></p>
                        <p><?php echo ctx_tr('schedule_messages','Are you sure you want to delete?'); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-default" @click="cancelDelete"><?php echo ctx_tr('schedule_messages','Cancel'); ?></button>
                        <button class="btn btn-danger" @click="confirmDelete"><?php echo ctx_tr('schedule_messages','Delete permanently'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop show"></div>
    </template>

    <template v-if="testResult !== null">
        <div class="modal show" tabindex="-1" role="dialog" style="display:block;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">Test: {{ testResult.name }}</h3>
                        <button type="button" class="btn-close" @click="closeTest" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="sch-test-state">
                            <div class="sch-test-value" :class="{'is-active': testResult.active}">
                                {{ testResult.active ? '● Active' : '○ Inactive' }}
                            </div>
                            <div class="sch-test-note">at time of test</div>
                        </div>
                        <div class="sch-test-field">
                            <div class="sch-test-label">Expression</div>
                            <code>{{ testResult.expression }}</code>
                        </div>
                        <div class="sch-test-field" v-if="testResult.evaluatedAt">
                            <div class="sch-test-label">Evaluated at</div>
                            <div>{{ testResult.evaluatedAt }}</div>
                        </div>
                        <details class="sch-test-debug">
                            <summary>Debug output</summary>
                            <pre>{{ testResult.debug }}</pre>
                        </details>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" @click="closeTest"><?php echo ctx_tr('schedule_messages','Close'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop show"></div>
    </template>
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
            var pieces = expr.split(/,\s+/).map(function(p) { return p.trim(); }).filter(Boolean);
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
                        <span class="sb-rule-title">Rule {{ ri + 1 }}</span>
                        <button v-if="rules.length > 1" @click="removeRule(ri)" class="btn btn-default btn-sm">Remove</button>
                    </div>

                    <div class="sb-row">
                        <span class="sb-label">Season</span>
                        <select v-model="rule.dst" class="form-select input-165">
                            <option value="">Any season</option>
                            <option value="Summer">Summer (DST on)</option>
                            <option value="Winter">Winter (DST off)</option>
                        </select>
                    </div>

                    <div class="sb-row">
                        <span class="sb-label">Days</span>
                        <div class="sb-days">
                            <div class="btn-group btn-group-sm">
                                <button v-for="day in DAYS" :key="day" type="button"
                                        @click="toggleDay(rule, day)"
                                        :class="['btn', 'sb-day', rule.weekdays.includes(day) ? 'btn-primary' : 'btn-default']">
                                    {{ day.slice(0,2) }}
                                </button>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button @click="setDays(rule,'all')"      type="button" class="btn btn-default">All</button>
                                <button @click="setDays(rule,'weekdays')" type="button" class="btn btn-default">M–F</button>
                                <button @click="setDays(rule,'weekend')"  type="button" class="btn btn-default">S–S</button>
                                <button @click="setDays(rule,'none')"     type="button" class="btn btn-default">Clear</button>
                            </div>
                            <span class="sb-hint" v-if="!rule.weekdays.length">none = any day</span>
                        </div>
                    </div>

                    <div class="sb-row">
                        <span class="sb-label">Times</span>
                        <div>
                            <div v-for="(t, ti) in rule.times" :key="ti" class="sb-time-row">
                                <input type="time" class="form-control" v-model="t.from">
                                <span class="sb-time-sep">–</span>
                                <input type="time" class="form-control" v-model="t.to">
                                <button v-if="rule.times.length > 1" @click="removeTime(rule, ti)"
                                        class="btn btn-default btn-sm" title="Remove time range"><span class="svg-icon-close"></span></button>
                            </div>
                            <button @click="addTime(rule)" class="btn btn-default btn-sm"><span class="svg-icon-plus"></span> Add time range</button>
                        </div>
                    </div>
                </div>

                <button @click="addRule" class="btn btn-default btn-sm sb-add-rule"><span class="svg-icon-plus"></span> Add rule</button>

                <div class="sb-preview" :class="{'is-empty': !expression}">{{ expression || '(set times above to build expression)' }}</div>
            </template>

            <template v-else>
                <div class="input-group sb-custom">
                    <input type="text" class="form-control" v-model="customExpr" placeholder="e.g. Mon-Fri | 09:00-17:00">
                    <button @click="showHelp=true" class="btn btn-default" title="Expression reference"><span class="svg-icon-info"></span></button>
                </div>
                <div v-if="parseError" class="sb-error">Expression too complex to convert to builder view.</div>
            </template>

            <div class="sb-mode-toggle">
                <a href="#" @click.prevent="customMode ? switchToBuilder() : switchToCustom()">
                    {{ customMode ? '← Use builder' : 'Custom expression →' }}
                </a>
            </div>

            <template v-if="showHelp">
                <div class="modal show sb-help" tabindex="-1" role="dialog" style="display:block;">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title">Expression reference</h3>
                                <button type="button" class="btn-close" @click="showHelp=false" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Granularity is day light saving time, month, day, week day, hour and minute.</p>
                                <p>An expression is built by mixing basic blocks with operation characters. An hour range is always required. All other blocks are optional and can be mixed to build complex rules. Ranges must be ordered older-to-newer. White spaces are ignored.</p>
                                <p>Timezone is that of the user account that created or last edited the schedule.</p>
                                <p><b>Basic blocks:</b></p>
                                <pre><b>Summer</b> or <b>Winter</b>          Day light saving time period
<b>mm/dd</b>                    Month and day (numeric, leading zero)
<b>Mon Tue Wed Thu Fri Sat Sun</b>  Week day (3-letter English)
<b>hh:mm</b>                    Hour in 24-hour format and minute (leading zero)</pre>
                                <p><b>Operation characters:</b></p>
                                <pre><b>-</b>   Range
<b>,</b>   Addition
<b>|</b>   Granularity separator</pre>
                                <p><b>Examples:</b></p>
                                <pre>'12:00-23:59'
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
                <div class="modal-backdrop show"></div>
            </template>
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
            updater: null
        };
    },
    methods: {
        update: function(callback) {
            var self = this;
            $.ajax({ url: path + "schedule/list.json", dataType: 'json', async: true, success: function(data) {
                // Don't clobber a row currently being edited
                if (self.editingId !== null && !callback) return;
                self.schedules = data || [];
                self.loaded = true;
                if (callback) callback();
            }});
        },
        startUpdater: function(interval) {
            clearInterval(this.updater);
            this.updater = null;
            if (interval > 0) this.updater = setInterval(this.update.bind(this), interval);
        },
        startEdit: function(s) {
            var self = this;
            this.editingId = s.id;
            this.editFields = { name: s.name, expression: s.expression };
            this.startUpdater(0);
            this.$nextTick(function() {
                if (self.$refs.editor) self.$refs.editor.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            });
        },
        cancelEdit: function() {
            this.editingId = null;
            this.startUpdater(10000);
        },
        saveEdit: function() {
            var id = this.editingId;
            var s = this.schedules.find(function(x) { return x.id === id; });
            if (!s) { this.cancelEdit(); return; }

            var fieldsToUpdate = {};
            if (this.editFields.name !== s.name) fieldsToUpdate.name = this.editFields.name;
            if (this.editFields.expression !== s.expression) fieldsToUpdate.expression = this.editFields.expression;

            if (Object.keys(fieldsToUpdate).length) {
                var result = schedule.set(s.id, fieldsToUpdate);
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
            if (this.editingId === id) this.editingId = null;
            this.deleteTargetId = null;
            this.startUpdater(10000);
        },
        addNew: function() {
            var self = this;
            $.ajax({ url: path + "schedule/create.json", dataType: 'json', success: function(id) {
                self.update(function() {
                    var s = self.schedules.find(function(x) { return x.id === id; });
                    if (s) self.startEdit(s);
                });
            }});
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
