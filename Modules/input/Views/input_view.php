<?php 
defined('EMONCMS_EXEC') or die('Restricted access');

load_js("Lib/js/vue.global.prod-3.5.22.min.js");
if ($device_module) {
    load_js("Modules/device/Views/device.js");
}
load_js("Modules/input/Views/input.js");
load_js("Lib/js/list_format_time_value.js");
load_js("Modules/feed/feed.js");
load_css("Modules/input/Views/input_view.css");

?>

<!-- PHP code to determine if the device module is installed AND translations -->
<script>
    var path = "<?php echo $path; ?>";
    const DEVICE_MODULE = <?php if ($device_module) echo 'true';
                            else echo 'false'; ?>;
</script>
<?php require "Modules/input/Views/translate.php"; ?>

<div id="input-header">
    <span id="api-help" style="float:right"><a href="<?php echo $path ?>input/api"><?php echo tr('API Help'); ?></a></span>
    <h3><?php echo tr('Inputs'); ?></h3>
</div>

<div class="input-controls-sentinel"></div>
<div id="input-app">
    <div class="controls input-controls" v-cloak v-if="total_devices > 0">
        <button @click="collapseAll" id="expand-collapse-all" class="btn" :title="collapse_title">
            <i class="icon" :class="allCollapsed ? 'icon-resize-full' : 'icon-resize-small'"></i>
        </button>
        <button @click="selectAll" class="btn" :title="'<?php echo addslashes(tr('Select all')); ?>' + ' (' + total_inputs + ')'">
            <i class="icon" :class="selected.length > 0 && selected.length >= total_inputs ? 'icon-ban-circle' : 'icon-check'"></i>
            <span>{{selected.length}}</span>
        </button>
        <button @click="open_delete" class="btn input-delete" v-if="selected.length > 0" title="<?php echo tr('Delete'); ?>"><i class="icon-trash"></i></button>
        <button @click="open_edit" class="btn input-edit" v-if="selected.length > 0" title="<?php echo tr('Edit'); ?>"><i class="icon-pencil"></i></button>
        <!-- input processing configure only show if one input selected -->
        <button
            v-if="selected.length === 1"
            @click="showInputConfigure(selected[0])"
            class="btn input-configure"
            :title="'<?php echo addslashes(tr('Configure Input processing')); ?>'">
            <i class="icon-wrench"></i>
        </button>
        <button v-if="show_clean" @click="clean_unused" class="btn pull-right ml-3" title="<?php echo tr('Clean unused devices'); ?>">
            <i class="icon-leaf"></i>
        </button>
        <input type="text" name="filter" id="input-filter" v-model="filterText" :class="{hide: selected.length > 0}" placeholder="<?php echo tr('Filter inputs') ?>">
    </div>

    <div id="noprocesses" class="clearfix"></div>

    <div v-cloak>

        <!-- alert danger if input creation is disabled for user, click enable button to enable -->
        <div v-if="input_creation_disabled" class="alert alert-danger" style="padding-right:8px">
                <button @click="enableInputCreation" class="btn pull-right">
                    <i class="icon icon-play"></i>
            <div style="margin: 5px 0;"><?php echo tr('<b>Input creation disabled:</b> Enable to add new inputs & devices'); ?></div>
        </div>

        <template v-if="loaded">
            <template v-if="total_devices > 0">
            <div class="group-list input-list-grid">
                <template v-for="(device,nodeid) in filteredDevices">
                    <div class="group-list-group" :class="{'select-mode': selectMode}">

                        <!-- Node Header -->
                        <div @click="toggleNode(nodeid)" class="group-list-header" :class="{'collapsed': !nodesDisplay[nodeid]}" :style="{'--status-color': device.time_color}">
                            <!-- Col 1: Arrow / select-all checkbox -->
                            <div data-col="select" class="group-list-cell text-center has-indicator" @click.stop="toggleNode(nodeid)">
                                <span v-if="!selectMode || getDeviceInputIds(device).length == 0" class="group-list-chevron"></span>
                                <input v-else @click.stop="selectAllDeviceInputs(device)" type="checkbox" class="feed-select" :checked="isFullySelected(device)" :title="'<?php echo addslashes(tr("Select all %s inputs")); ?>'.replace('%s',getDeviceInputIds(device).length)">
                            </div>
                            <!-- Col 2: Node name -->
                            <div data-col="name" class="group-list-cell group-list-name">{{ nodeid }}<small style="margin-left:8px" v-if="getDeviceSelectedInputids(device).length > 0">&nbsp;({{ getDeviceSelectedInputids(device).length }})</small></div>
                            <!-- Col 3: Description -->
                            <div data-col="description" class="group-list-cell text-nowrap">{{ device.description }}</div>
                            <!-- Col 4: Process -->
                            <div data-col="process" class="group-list-cell"></div>
                            <!-- Spacer -->
                            <div data-col="spacer" class="group-list-cell"></div>
                            <!-- Col 5: Last updated -->
                            <div data-col="updated" class="group-list-cell text-center" :style="{color: device.time_color}">{{ device.time_value }}</div>
                            <!-- Col 6: Device key button -->
                            <div data-col="value" class="group-list-cell text-center">
                                <a @click.prevent.stop="show_device_key(device)" href="#" :class="{'text-muted': !device_module}" title="<?php echo tr('Show device key'); ?>">
                                    <i class="icon-lock"></i>
                                </a>
                            </div>
                            <!-- Col 7: Device configure button -->
                            <div data-col="configure" class="group-list-cell text-center">
                                <a @click.prevent.stop="device_configure(device)" href="#" :class="{'text-muted': !device_module}" title="<?php echo tr('Configure device using device template'); ?>">
                                    <i class="icon-cog"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Node Inputs (collapsible) -->
                        <div class="group-list-rows" :class="{'is-expanded': nodesDisplay[nodeid]}">
                            <div class="group-list-rows-inner">
                            <div @click="toggleSelected($event, input.id)" class="group-list-row" :key="input.id" v-for="(input,index) in device.inputs" :style="{'--status-color': input.time_color}" :class="{'selected': selected.indexOf(input.id) > -1}">
                                <!-- Col 1: Checkbox -->
                                <div data-col="select" class="group-list-cell text-center" @click.stop>
                                    <input class="feed-select input-select" type="checkbox" :value="input.id" v-model="selected">
                                </div>
                                <!-- Col 2: Name -->
                                <div data-col="name" class="group-list-cell text-nowrap">{{ input.name }}</div>
                                <!-- Col 3: Description -->
                                <div data-col="description" class="group-list-cell text-nowrap">{{ input.description }}</div>
                                <!-- Col 4: Processlist -->
                                <div data-col="process" class="group-list-cell">
                                    <div class="label-container" v-html="input.processlistHtml"></div>
                                </div>
                                <!-- Spacer -->
                                <div data-col="spacer" class="group-list-cell"></div>
                                <!-- Col 5: Last updated -->
                                <div data-col="updated" @click.stop class="group-list-cell text-center" :style="{color: input.time_color}">{{ input.time_value }}</div>
                                <!-- Col 6: Value -->
                                <div data-col="value" @click.stop class="group-list-cell text-center">{{ input.value_str }}</div>
                                <!-- Col 7: Configure button -->
                                <div data-col="configure" class="group-list-cell text-center" @click.stop>
                                    <a @click.prevent="showInputConfigure(input.id)" class="cursor-pointer" title="<?php echo tr('Configure Input processing') ?>" href="#">
                                        <i class="icon-wrench"></i>
                                    </a>
                                </div>
                            </div>
                            </div>
                        </div>

                    </div>
                    <div style="height:10px; grid-column: 1 / -1"></div>
                </template>
            </div>
        </template>
            <div class="alert" v-else>
                <h3><?php echo tr('No inputs created'); ?></h3>
                <p><?php echo tr('Inputs are the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
                <button @click.prevent="create_device" class="btn">
                    <i class="icon-plus-sign"></i> <?php echo tr('New device'); ?>
                </button>
            </div>
        </template>
        <h4 v-else><?php echo tr('Loading') ?></h4>

        <!-- disable input creation button, only show if input creation is not already disabled and there are existing inputs -->
        <div v-if="!input_creation_disabled && total_inputs > 0">
            <button @click="disableInputCreation" class="btn" style="margin-top:10px;">
                <i class="icon icon-lock" style="margin-top:2px"></i>
                <?php echo tr('Disable further input creation'); ?></button>
        </div>
    </div>

    <div id="input-loader" class="ajax-loader"></div>
</div>

<script>
(function() {
    var sentinel = document.querySelector('.input-controls-sentinel');
    if (!sentinel || !('IntersectionObserver' in window)) return;

    // Sync --feed-top to navbar height (0 when hidden on mobile scroll)
    var nav = document.querySelector('.menu-top');
    function updateFeedTop() {
        document.documentElement.style.setProperty('--feed-top', (nav && !nav.classList.contains('menu-top-hide') ? nav.offsetHeight : 0) + 'px');
    }
    if (nav) new MutationObserver(updateFeedTop).observe(nav, { attributes: true, attributeFilter: ['class'] });
    updateFeedTop();

    // Toggle is-sticky class when controls scroll behind the navbar
    new IntersectionObserver(function(entries) {
        var controls = document.querySelector('.input-controls');
        if (controls) controls.classList.toggle('is-sticky', !entries[0].isIntersecting);
    }, { rootMargin: '-46px 0px 0px 0px', threshold: 0 }).observe(sentinel);
})();
</script>


<?php
// Main input list javascript
load_js("Modules/input/Views/input_view.js");

// Device modal: enables configuring devices using pre-set templates
if ($device_module) {
    require "Modules/device/Views/device_dialog.php";
}

// Input processing modal: configure input processing
require "Modules/process/Views/process_ui.php";

// Edit input modal
require "Modules/input/Views/modals/edit/edit_modal.php";
load_js("Modules/input/Views/modals/edit/edit_modal.js");

// Delete input modal
require "Modules/input/Views/modals/delete/delete_modal.php";
load_js("Modules/input/Views/modals/delete/delete_modal.js");
?>