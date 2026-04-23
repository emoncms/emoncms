<?php $v=36; 
defined('EMONCMS_EXEC') or die('Restricted access');
?>
<!-- Load dependencies -->
<?php if ($device_module) { ?>
    <script src="<?php echo $path; ?>Modules/device/Views/device.js?v=28"></script>
<?php } ?>

<!-- This view uses vue.js -->
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>

<!-- used by input delete modal -->
<script src="<?php echo $path; ?>Modules/input/Views/input.js?v=27"></script>
<!-- used by input processing ui -->
<script src="<?php echo $path; ?>Modules/feed/feed.js?v=26"></script>
<!-- used for formatting last updated time both colour and text -->
<script src="<?php echo $path; ?>Lib/list_format_time_value.js?v=27"></script>
<!-- js translation support -->
<script src="<?php echo $path; ?>Lib/misc/gettext.js?v=<?php echo $v; ?>"></script>

<!-- input list and edit/delete modal css -->
<link rel="stylesheet" href="<?php echo $path; ?>Modules/input/Views/input_view.css?v=<?php echo $v; ?>">

<!-- PHP code to determine if the device module is installed AND translations -->
<script>
    var path = "<?php echo $path; ?>";
    const DEVICE_MODULE = <?php if ($device_module) echo 'true';
                            else echo 'false'; ?>;

    var _user = {};
    _user.lang = "<?php echo $_SESSION['lang']; ?>";

    // @todo: standardise these translations functions, also used in admin_main_view.php and feedlist_view.php
    /**
     * return object of gettext translated strings
     *
     * @return object
     */
    function getTranslations() {
        return {
            'ID': "<?php echo tr('ID'); ?>",
            'Value': "<?php echo tr('Value'); ?>",
            'Time': "<?php echo tr('Time'); ?>",
            'Updated': "<?php echo tr('Updated'); ?>",
            'Configure your device here': "<?php echo tr('Configure your device here'); ?>",
            'Show node key': "<?php echo tr('Show node key'); ?>",
            'Configure device using device template': "<?php echo tr('Configure device using device template'); ?>",
            'Configure Input processing': "<?php echo tr('Configure Input processing'); ?>",
            'Saving': "<?php echo tr('Saving'); ?>",
            'Collapse': "<?php echo tr('Collapse'); ?>",
            'Expand': "<?php echo tr('Expand'); ?>",
            'Select all %s inputs': "<?php echo tr('Select all %s inputs'); ?>",
            'Select all': "<?php echo tr('Select all'); ?>",
            'Please install the device module to enable this feature': "<?php echo tr('Please install the device module to enable this feature'); ?>"
        }
    }
</script>

<div class="position-relative">
    <div id="input-header" class="page-header">
        <h2><?php echo tr('Inputs'); ?></h2>
        <a id="api-help" href="<?php echo $path ?>input/api"><?php echo tr('API Help'); ?></a>
    </div>

    <div class="sticky-sentinel" style="height: 1px; position: absolute; top: 45px; width: 100%; pointer-events: none;"></div>
    <div v-cloak id="input-controls" class="sticky-controls" v-if="total_devices > 0">
        <button @click="collapseAll" id="expand-collapse-all" class="btn" :title="collapse_title">
            <i class="icon icon-check" :class="{'icon-resize-small': !allCollapsed, 'icon-resize-full': allCollapsed}"></i>
        </button>
        <button @click="selectAll" class="btn" :title="'<?php echo addslashes(tr('Select all')); ?>' + ' (' + total_inputs + ')'">
            <svg class="icon icon-check">
                <use :xlink:href="checkbox_icon"></use>
            </svg>
            <span>{{selected.length}}</span>
        </button>
        <button @click="open_delete" class="btn input-delete" :class="{'hide': !selectMode}" title="<?php echo tr('Delete'); ?>"><i class="icon-trash"></i></button>
        <button @click="open_edit" class="btn input-edit" :class="{'hide': !selectMode}" title="<?php echo tr('Edit'); ?>"><i class="icon-pencil"></i></button>
        <!-- input processing configure only show if one input selected -->
        <button
            v-if="selectMode && selected.length === 1"
            @click="showInputConfigure(selected[0])"
            class="btn input-configure"
            :title="'<?php echo addslashes(tr('Configure Input processing')); ?>'">
            <i class="icon-wrench"></i>
        </button>
        <button v-if="show_clean" @click="clean_unused" class="btn pull-right" title="<?php echo tr('Clean unused devices'); ?>">
            <i class="icon-leaf"></i>
        </button>
    </div>

    <div id="noprocesses clearfix"></div>

    <div id="app" v-cloak>

        <!-- alert danger if input creation is disabled for user, click enable button to enable -->
        <div v-if="input_creation_disabled" class="alert alert-danger" style="padding-right:8px">
            <button @click="enableInputCreation" class="btn" style="float:right;">
                <i class="icon icon-play" style="margin-top:2px"></i>
                <?php echo tr('Enable Input Creation'); ?></button>
            <div style="margin: 5px 0;"><?php echo tr('<b>Input creation disabled:</b> Enable to add new inputs & devices'); ?></div>
        </div>

        <template v-if="loaded">
            <template v-if="total_devices > 0">
            <div class="input-list-grid">
                <template v-for="(device,nodeid) in devices">
                    <div class="node-group" :class="{'select-mode': selectMode}">

                        <!-- Node Header -->
                        <div @click="toggleNode(nodeid)" class="grid-row group-header" :style="{'--status-color': device.time_color}">
                            <!-- Col 1: Arrow / select-all checkbox -->
                            <div class="grid-cell text-center has-indicator" @click.stop="toggleNode(nodeid)">
                                <span v-if="!selectMode || getDeviceInputIds(device).length == 0" class="arrow-icon" :class="[nodesDisplay[nodeid] ? 'icon-chevron-down' : 'icon-chevron-right']"></span>
                                <input v-else @click.stop="selectAllDeviceInputs(device)" type="checkbox" class="checkbox-lg feed-select" :checked="isFullySelected(device)" :title="'<?php echo addslashes(tr("Select all %s inputs")); ?>'.replace('%s',getDeviceInputIds(device).length)">
                            </div>
                            <!-- Col 2: Node name -->
                            <h5 class="grid-cell">{{ nodeid }}<small class="ml-2" v-if="getDeviceSelectedInputids(device).length > 0">&nbsp;({{ getDeviceSelectedInputids(device).length }})</small></h5>
                            <!-- Col 3: Description -->
                            <div class="grid-cell text-nowrap">{{ device.description }}</div>
                            <!-- Col 4: Processlist spacer -->
                            <div class="grid-cell"></div>
                            <!-- Col 5: Last updated -->
                            <div class="grid-cell text-center" :style="{color: device.time_color}">{{ device.time_value }}</div>
                            <!-- Col 6: Device key button -->
                            <div class="grid-cell text-center">
                                <a @click.prevent.stop="show_device_key(device)" href="#" :class="{'text-muted': !device_module}" title="<?php echo tr('Show device key'); ?>">
                                    <i class="icon-lock"></i>
                                </a>
                            </div>
                            <!-- Col 7: Device configure button -->
                            <div class="grid-cell text-center">
                                <a @click.prevent.stop="device_configure(device)" href="#" :class="{'text-muted': !device_module}" title="<?php echo tr('Configure device using device template'); ?>">
                                    <i class="icon-cog"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Node Inputs (collapsible) -->
                        <div class="vue-collapsible-content" :class="{'is-expanded': nodesDisplay[nodeid]}">
                            <div @click="toggleSelected($event, input.id)" class="grid-row node-feed" :key="input.id" v-for="(input,index) in device.inputs" :style="{'--status-color': input.time_color}" :class="{'selected': selected.indexOf(input.id) > -1}">
                                <!-- Col 1: Checkbox -->
                                <div class="grid-cell text-center" @click.stop>
                                    <input class="feed-select input-select" type="checkbox" :value="input.id" v-model="selected">
                                </div>
                                <!-- Col 2: Name -->
                                <div class="grid-cell text-nowrap">{{ input.name }}</div>
                                <!-- Col 3: Description -->
                                <div class="grid-cell text-nowrap">{{ input.description }}</div>
                                <!-- Col 4: Processlist -->
                                <div class="grid-cell">
                                    <div class="label-container" v-html="input.processlistHtml"></div>
                                </div>
                                <!-- Col 5: Last updated -->
                                <div @click.stop class="grid-cell text-center" :style="{color: input.time_color}">{{ input.time_value }}</div>
                                <!-- Col 6: Value -->
                                <div @click.stop class="grid-cell text-center">{{ input.value_str }}</div>
                                <!-- Col 7: Configure button -->
                                <div class="grid-cell text-center" @click.stop>
                                    <a @click.prevent="showInputConfigure(input.id)" class="cursor-pointer" title="<?php echo tr('Configure Input processing') ?>" href="#">
                                        <i class="icon-wrench"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div style="height:10px; grid-column: 1 / -1"></div>
                </template>
            </div>
        </template>
            <div class="alert" v-else>
                <h3 class="alert-heading mt-0"><?php echo tr('No inputs created'); ?></h3>
                <p><?php echo tr('Inputs are the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
                <button @click.prevent="create_device" class="btn">
                    <i class="icon-plus-sign"></i> <?php echo tr('New device'); ?>
                </button>
            </div>
        </template>
        <h4 v-else><?php echo tr('Loading') ?></h4>

        <!-- disable input creation button, only show if input creation is not already disabled and there are existing inputs -->
        <div v-if="!input_creation_disabled && total_inputs > 0">
            <button @click="disableInputCreation" class="btn float-end" style="margin-top:10px;">
                <i class="icon icon-lock" style="margin-top:2px"></i>
                <?php echo tr('Disable further input creation'); ?></button>
        </div>
    </div>

    <div id="input-none" class="alert alert-block hide">
        <h4 class="alert-heading"><?php echo tr('No inputs created'); ?></h4>
        <p><?php echo tr('Inputs are the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
    </div>

    <div id="input-footer" class="hide">
        <button id="device-new" class="btn btn-small">&nbsp;<i class="icon-plus-sign"></i>&nbsp;<?php echo tr('New device'); ?></button>
    </div>

    <div id="input-loader" class="ajax-loader"></div>
</div>

<!-- Main input list javascript -->
<script src="<?php echo $path; ?>Modules/input/Views/input_view.js?v=<?php echo $v; ?>"></script>

<!-- Device modal: enables configuring devices using pre-set templates -->
<?php if ($device_module) require "Modules/device/Views/device_dialog.php"; ?>

<!-- Input processing modal: configure input processing -->
<?php require "Modules/process/Views/process_ui.php"; ?>

<!-- Edit input modal -->
<?php require "Modules/input/Views/modals/edit/edit_modal.php"; ?>
<script src="<?php echo $path; ?>Modules/input/Views/modals/edit/edit_modal.js?v=<?php echo $v; ?>"></script>

<!-- Delete input modal -->
<?php require "Modules/input/Views/modals/delete/delete_modal.php"; ?>
<script src="<?php echo $path; ?>Modules/input/Views/modals/delete/delete_modal.js?v=<?php echo $v; ?>"></script>