<?php $v=9; ?>

<?php if ($device_module) { ?>
<script src="<?php echo $path; ?>Modules/device/Views/device.js?v=<?php echo $v; ?>"></script>
<?php } ?>

<script src="<?php echo $path; ?>Modules/input/Views/input.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $path; ?>Modules/feed/feed.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $path; ?>Lib/responsive-linked-tables.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>

<script>
    var path = "<?php echo $path; ?>";
    const DEVICE_MODULE = <?php if ($device_module) echo 'true'; else echo 'false'; ?>;

    var _user = {};
    _user.lang = "<?php echo $_SESSION['lang']; ?>";

    // @todo: standardise these translations functions, also used in admin_main_view.php and feedlist_view.php
    /**
     * return object of gettext translated strings
     *
     * @return object
     */
    function getTranslations(){
        return {
            'ID': "<?php echo _('ID'); ?>",
            'Value': "<?php echo _('Value'); ?>",
            'Time': "<?php echo _('Time'); ?>",
            'Updated': "<?php echo _('Updated'); ?>",
            'Configure your device here': "<?php echo _('Configure your device here'); ?>",
            'Show node key': "<?php echo _('Show node key'); ?>",
            'Configure device using device template': "<?php echo _('Configure device using device template'); ?>",
            'Configure Input processing': "<?php echo _('Configure Input processing'); ?>",
            'Saving': "<?php echo _('Saving'); ?>",
            'Collapse': "<?php echo _('Collapse'); ?>",
            'Expand': "<?php echo _('Expand'); ?>",
            'Select all %s inputs': "<?php echo _('Select all %s inputs'); ?>",
            'Select all': "<?php echo _('Select all'); ?>",
            'Please install the device module to enable this feature': "<?php echo _('Please install the device module to enable this feature'); ?>"
        }
    }
</script>
<style>

#footer {
    margin-left: 0px;
    margin-right: 0px;
}

.navbar-fixed-top {
    margin-left: 0px;
    margin-right: 0px;
}

input[type="checkbox"] { margin:0px; }
.controls { margin-bottom:10px; }
#inputs-to-delete { font-style:italic; }

#noprocesses .alert{margin:0;border-bottom-color:#fcf8e3;border-radius: 4px 4px 0 0;padding-right:14px}

@media (min-width: 768px) {
    .modal-wide{
        width:650px;
        margin-left:-325px
    }
}

@media (max-width: 768px) {
    body {padding:0};
}

.node .node-info{
    border-bottom: 1px solid white;
}
.node .node-info,
.node .node-input {
    position: relative;
}
.node .node-info::after,
.node .node-input::after {
    content: '';
    width: .4em;
    height: 100%;
    display: block;
    position: absolute;
    top: 0;
    right: 0;
    background: rgba(0,0,0,.1);
}
.buttons{
    padding-right: .4em;
}
.status-success.node-info::after,
.status-success.node-input::after{
    background: #28A745!important;
}
.status-danger.node-info::after,
.status-danger.node-input::after{
    background: #DC3545!important;
}
.status-warning.node-info::after,
.status-warning.node-input::after{
    background: #FFC107!important;
}

.status-success.node-info .last-update,
.status-success.node-input .last-update{
    color: #28A745!important;
}
.status-danger.node-info .last-update,
.status-danger.node-input .last-update{
    color: #DC3545!important;
}
.status-warning.node-info .last-update,
.status-warning.node-input .last-update{
    color: #C70!important;
}

[data-col] { overflow:hidden; }

[data-col="B"] { width:40px; }
[data-col="A"] { width:200px; }
[data-col="G"] { width:200px; }
[data-col="F"] { width:50px; }
[data-col="E"] { width:100px; }
[data-col="D"] { width:100px; }
[data-col="C"] { width:50px; }
a.text-muted i[class*="icon-"] {
    opacity: .5;
}
#app .accordion {
    margin-bottom: .3rem;
}
#input-controls {
    top: 3.5rem;
    z-index: 1;
}
#input-controls.fixed {
    position: sticky;
}
#input-controls:before {
    background: rgba(0, 0, 0, 0.5);
    content: "";
    position: fixed;
    top: 3rem;
    height: 0rem;
    width: 100%;
    left: 0;
    z-index: -1;
    margin-top: -.18em;
    transition: .2s height, .16s opacity ease-out;
    opacity: 0;
}
#input-controls.fixed:before {
    opacity: 1;
    height: 3.15rem;
}
.select-mode .node-inputs .node-input {
    cursor: pointer;
}
input.checkbox-lg {
    float: none;
}
input.checkbox-lg,
.select-mode .node-inputs .node-input input[type="checkbox"] {
    transform: scale(1.3);
}
.selected {
    background-color: rgba(68, 179, 226, 0.8) !important;
    background-color: #C2DBFF!important;
}
[v-cloak] {
  display: none;
}
.position-absolute {
    position: absolute;
}

/* overwrite bootstrap collapse animation */

.collapse .node-input {
    height: 0;
    transition-timing-function: cubic-bezier(.18,.89,.32,1.28);
    transition-duration: .6s;
    min-height: 0;
    border-width: 0;
}
.collapse.in .node-input {
    height: auto;
    transition: all .2s cubic-bezier(.23,1,.32,1);
    border-width: 1px;
}
.collapse {
    height: inherit!important;
}
.break-all {
    word-break: break-all;
}
.text-nowrap {
    white-space: nowrap !important;
}
[v-cloak] {
    visibility: hidden
}
</style>

<div class="position-relative">
    <div id="input-header" class="d-flex justify-content-between align-items-center">
        <h3><?php echo _('Inputs'); ?></h3>
        <span id="api-help"><a href="<?php echo $path ?>input/api"><?php echo _('Input API Help'); ?></a></span>
    </div>
    <div v-cloak id="input-controls" class="controls" v-if="total_devices > 0" :class="{'fixed': overlayControls}">
        <button @click="collapseAll" id="expand-collapse-all" class="btn" :title="collapse_title">
            <i class="icon" :class="{'icon-resize-small': collapsed.length < total_devices, 'icon-resize-full': collapsed.length >= total_devices}"></i>
        </button>
        <button @click="selectAll" class="btn" :title="'<?php echo addslashes(_('Select all')); ?>' + ' (' + total_inputs + ')'">
            <svg class="icon"><use :xlink:href="checkbox_icon"></use></svg>
            <span>{{selected.length}}</span>
        </button>
        <button @click="open_delete" class="btn input-delete" :class="{'hide': !selectMode}" title="<?php echo _('Delete'); ?>"><i class="icon-trash" ></i></button>
        <button @click="open_edit" class="btn input-edit" :class="{'hide': !selectMode}" title="<?php echo _('Edit'); ?>"><i class="icon-pencil" ></i></button>
    </div>

    <div id="noprocesses clearfix"></div>

    <div id="app" v-cloak>
      <template v-if="loaded">
        <template v-if="total_devices > 0">
        <div class="node accordion line-height-expanded" v-for="(device,nodeid) in devices" :class="{'select-mode': selectMode}">
            <div @click="toggleCollapse($event, nodeid)" class="node-info accordion-toggle thead" :class="[deviceStatus(device)]" :data-node="nodeid" :data-target="'#collapse_' + nodeid">

            <div class="select text-center has-indicator" data-col="B">
                <span v-if="!selectMode || getDeviceInputIds(device) == 0" class="icon-indicator" :class="{'icon-chevron-down': isCollapsed(nodeid),'icon-chevron-up': !isCollapsed(nodeid)}"></span>
                <input v-else @click.stop="selectAllDeviceInputs(device)" type="checkbox" class="checkbox-lg" :checked="isFullySelected(device)" :title="'<?php echo addslashes(_("Select all %s inputs")); ?>'.replace('%s',getDeviceInputIds(device).length)">
            </div>

            <h5 class="name text-nowrap" data-col="A" :style="{width:col.A+'px'}">
                <span>{{ nodeid }} 
                    <small class="position-absolute ml-1" v-if="getDeviceSelectedInputids(device).length > 0">({{ getDeviceSelectedInputids(device).length }})</small>
                </span>
            </h5>
            <span class="description text-nowrap" data-col="G" :style="{width:col.G+'px'}">{{device.description}}</span>
            <div class="processlist" data-col="H" :style="{width:col.H+'px'}"></div>
            <div class="buttons pull-right">
                <div class="device-schedule text-center hidden" data-col="F" :style="{width:col.F+'px'}"><i class="icon-time"></i></div>
                <div class="device-last-updated text-center" data-col="E" :style="{width:col.E+'px'}"></div>
                <a @click.prevent.stop="show_device_key(device)" href="#" class="device-key text-center" data-col="D" :style="{width:col.D+'px'}" :class="{'text-muted': !device_module}" data-col-width="50"  title="<?php echo _('Show device key'); ?>">
                    <i class="icon-lock"></i>
                </a>
                <a @click.prevent.stop="device_configure(device)" href="#" class="device-configure text-center" data-col="C" :style="{width:col.C+'px'}" :class="{'text-muted': !device_module}" title="<?php echo _('Configure device using device template'); ?>">
                    <i class="icon-cog"></i>
                </a>
            </div>
            </div>
            <div :id="'collapse_' + nodeid" class="node-inputs collapse tbody" :class="{in: collapsed.indexOf(nodeid) === -1}" :data-node="nodeid">
            <div @click="toggleSelected($event, input.id)" class="node-input" :id="input.id" v-for="(input,index) in device.inputs" :class="[inputStatus(input), {'selected': selected.indexOf(input.id) > -1}]">
                <div class="select text-center" data-col="B">
                    <input class="input-select" type="checkbox" :value="input.id" v-model="selected">
                </div>
                <div class="name text-nowrap" data-col="A" :style="{width:col.A+'px'}">{{ input.name }}</div>
                <div class="description text-nowrap" data-col="G" :style="{width:col.G+'px'}">{{ input.description }}</div>
                <div class="processlist" data-col="H" :style="{width:col.H+'px', height:col_h.H}">
                    <div class="label-container line-height-normal" v-html=input.processlistHtml></div>
                </div>
                <div class="buttons pull-right">
                    <div class="schedule text-center hidden" data-col="F" :style="{width:col.F+'px'}"></div>
                    <span @click.stop class="time text-center break-all" data-col="E" :style="{width:col.E+'px', height:col_h.E, color:input.time_color}">
                        {{ input.time_value }}
                    </span>
                    <span @click.stop class="value text-center" data-col="D" :style="{width:col.D+'px'}">
                        {{ input.value_str }}
                    </span>
                    <a @click.prevent.stop="showInputConfigure(input.id)" class="configure text-center cursor-pointer" data-col="C" :style="{width:col.C+'px'}" :id="input.id" title="<?php echo _('Configure Input processing') ?>" href="#">
                        <i class="icon-wrench"></i>
                    </a>
                </div>
            </div>
        </div>
        </template>
        <div class="alert" v-else>
            <h3 class="alert-heading mt-0"><?php echo _('No inputs created'); ?></h3>
            <p><?php echo _('Inputs are the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
            <button @click.prevent="create_device" class="btn" >
                <i class="icon-plus-sign" ></i> <?php echo _('New device'); ?>
            </button>
        </div>
      </template>
      <h4 v-else><?php echo _('Loading') ?></h4>
    </div>

    <!-- <div id="table" class="input-list"></div>

    <div id="output"></div> -->

    <div id="input-none" class="alert alert-block hide">
        <h4 class="alert-heading"><?php echo _('No inputs created'); ?></h4>
        <p><?php echo _('Inputs are the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
    </div>

    <div id="input-footer" class="hide">
        <button id="device-new" class="btn btn-small" >&nbsp;<i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New device'); ?></button>
    </div>
    <div id="input-loader" class="ajax-loader"></div>
</div>

<?php if ($device_module) require "Modules/device/Views/device_dialog.php"; ?>
<?php // delete and edit modals
    require "Modules/input/Views/input_dialog.php";
?>
<?php require "Modules/process/Views/process_ui.php"; ?>

<script src="<?php echo $path; ?>Lib/moment.min.js"></script>

<script src="<?php echo $path; ?>Lib/misc/gettext.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $path; ?>Lib/user_locale.js?v=<?php echo $v; ?>"></script>
<script>
    // example values:
    //  - "vis/auto?feedid="
    //  - "graph/"
    _SETTINGS['feedviewpath'] = "<?php if(isset($feedviewpath)) echo $feedviewpath; ?>";
</script>
<script src="<?php echo $path; ?>Modules/input/Views/input_view.js?v=<?php echo $v; ?>"></script>

