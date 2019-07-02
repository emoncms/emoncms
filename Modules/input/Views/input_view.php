<?php
    global $path;
    $v = 2;

    $device_module = false;
    if (file_exists("Modules/device")) $device_module = true;
?>

<?php if ($device_module) { ?>
<script src="<?php echo $path; ?>Modules/device/Views/device.js?v=<?php echo $v; ?>"></script>
<?php } ?>

<script src="<?php echo $path; ?>Modules/input/Views/input.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $path; ?>Modules/feed/feed.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $path; ?>Lib/responsive-linked-tables.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>

<style>

.container-fluid { padding: 0px 10px 0px 10px; }

#table {
    margin-top:3rem
}
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
    .container-fluid { padding: 0px 20px 0px 20px; }
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

#app {
    margin-top: 3rem;
}
#app .accordion {
    margin-bottom: .3rem;
}
</style>

<div>
    <div id="input-header">
        <span id="api-help" style="float:right"><a href="api"><?php echo _('Input API Help'); ?></a></span>
        <h3> <?php echo _('Inputs'); ?></h3>
    </div>

    <div id="feedlist-controls" class="controls" data-spy="affix" data-offset-top="100">
        <button @click="collapseAll" id="expand-collapse-all" class="btn" title="<?php echo _('Collapse') ?>" data-alt-title="<?php echo _('Expand') ?>">
            <i class="icon" :class="{'icon-resize-small':true}"></i>
        </button>
        <button @click="selectAll" class="btn" title="<?php echo _('Select all') ?>" data-alt-title="<?php echo _('Unselect all') ?>">
            <i class="icon" :class="{'icon-check': selected.length < total_inputs, 'icon-ban-circle': selected.length >= total_inputs}"></i>
        </button>
        <button class="btn input-delete hide" title="Delete"><i class="icon-trash" ></i></button>
        <a href="#inputEditModal" class="btn input-edit hide" title="Edit" data-toggle="modal"><i class="icon-pencil" ></i></a>
    </div>

    <div id="noprocesses"></div>

    <div id="app">
      <div class="node accordion line-height-expanded" v-for="(device,nodeid) in devices">
        <div class="node-info accordion-toggle thead status-danger" @click="toggleCollapse($event, nodeid)" data-node="0" data-toggle="vue-collapse" :data-target="'#collapse_' + nodeid">
          <div class="select text-center has-indicator" data-col="B">
            <span class="icon-indicator" :class="{'icon-chevron-down': isCollapsed(nodeid),'icon-chevron-up': !isCollapsed(nodeid)}"><span>
          </div>
          <h5 class="name" data-col="A" :style="{width:col.A+'px'}">{{ nodeid }}</h5>
          <span class="description" data-col="G" :style="{width:col.G+'px'}"></span>
          <div class="processlist" data-col="H" :style="{width:col.H+'px'}"></div>
          <div class="buttons pull-right">
            <div class="device-schedule text-center hidden" data-col="F" :style="{width:col.F+'px'}"><i class="icon-time"></i></div>
            <div class="device-last-updated text-center" data-col="E" :style="{width:col.E+'px'}"></div>
            <a href="#" class="device-key text-center" data-col="D" :style="{width:col.D+'px'}" data-toggle="tooltip" data-tooltip-title="Show node key" data-device-key="No device key created" data-col-width="50"><i class="icon-lock"></i></a>
            <div class="device-configure text-center" data-col="C" :style="{width:col.C+'px'}"><i class="icon-cog" title="Configure device using device template"></i></div>
          </div>
        </div>
        <div :id="'collapse_' + nodeid" class="node-inputs collapse tbody" :class="{in: collapsed.indexOf(nodeid) === -1}" :data-node="nodeid">
          <div class="node-input status-danger" :id="input.id" v-for="(input,index) in device.inputs">
            <div class="select text-center" data-col="B">
                <input class="input-select" type="checkbox" :value="input.id" v-model="selected">
            </div>
            <div class="name" data-col="A" :style="{width:col.A+'px'}" >{{ input.name }}</div>
            <div class="description" data-col="G" :style="{width:col.G+'px'}">{{ input.description }}</div>
            <div class="processlist" data-col="H" :style="{width:col.H+'px'}">
                <div class="label-container line-height-normal" v-html=input.processlistHtml></div>
            </div>
            <div class="buttons pull-right">
                <div class="schedule text-center hidden" data-col="F" :style="{width:col.F+'px'}"></div>
                <div class="time text-center" data-col="E" :style="{width:col.E+'px', color:input.time_color}">{{ input.time_value }}</div>
                <div class="value text-center" data-col="D" :style="{width:col.D+'px'}">{{ input.value_str }}</div>
                <div class="configure text-center cursor-pointer" data-col="C" :style="{width:col.C+'px'}" :id="input.id"><i class="icon-wrench" title="Configure Input processing"></i></div>
            </div>
          </div>
        </div>
      </div>
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
<?php require "Modules/input/Views/input_dialog.php"; ?>
<?php require "Modules/process/Views/process_ui.php"; ?>

<script src="<?php echo $path; ?>Lib/moment.min.js"></script>
<script>
    var path = "<?php echo $path; ?>";
    var device_module = <?php if ($device_module) echo 'true'; else echo 'false'; ?>;
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
            'Configure Input processing': "<?php echo _('Configure Input processing') ?>",
            'Saving': "<?php echo _('Saving') ?>"
        }
    }
</script>
<script src="<?php echo $path; ?>Lib/misc/gettext.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $path; ?>Lib/user_locale.js"></script>
<script src="<?php echo $path; ?>Modules/input/Views/input_view.js?v=<?php echo $v; ?>"></script>
