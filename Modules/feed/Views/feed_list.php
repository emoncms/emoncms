<?php
    defined('EMONCMS_EXEC') or die('Restricted access');
    global $path, $settings, $session;
        
    $public_username_str = "";
    if ($session['public_userid']) {
        $public_username_str = $session['public_username']."/";
    }

    load_js("Lib/js/vue.global.prod-3.5.22.min.js");
    load_js("Modules/user/user.js");
?>

<script>

/**
 * formats a unix timestamp using native Date/Intl in UTC
 * @param int time unix epoc time
 * @param string format limited date format tokens used by feed list
 */
function format_time(time,format){
    if(!Number.isInteger(time)) return time;
    format = format || 'YYYY-MM-DD';

    var locale = (typeof _user !== 'undefined' && _user && _user.lang)
        ? String(_user.lang).replace('_', '-')
        : (navigator.language || 'en-GB');
    var date = new Date(time * 1000);

    if (format === 'YYYY-MM-DD') {
        var year = date.getUTCFullYear();
        var month = String(date.getUTCMonth() + 1).padStart(2, '0');
        var day = String(date.getUTCDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    if (format === 'LL LTS') {
        try {
            return new Intl.DateTimeFormat(locale, {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                timeZone: 'UTC'
            }).format(date);
        } catch (e) {
            return date.toISOString().replace('T', ' ').slice(0, 19);
        }
    }

    return date.toISOString().replace('T', ' ').slice(0, 19);
}
var feedviewpath = "<?php echo $settings['interface']['feedviewpath']; ?>";
var engines_hidden = <?php echo json_encode($settings["feed"]['engines_hidden']); ?>;
var available_intervals = <?php echo json_encode(Engine::available_intervals()); ?>;
var downloadlimit = <?php echo $settings['feed']['csv_downloadlimit_mb']; ?>;

</script>
<?php require "Modules/feed/Views/translate.php"; 

// feed.clear, trim, remove used by delete modal
load_js("Modules/feed/feed.js");
load_js("Lib/js/date_time.js");
load_js("Lib/js/DateTimePicker.js");
load_css("Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css");
load_js("Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js");
load_js("Lib/js/autocomplete.js");
load_css("Modules/feed/Views/feed_view.css");
?>
<!--------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED LIST VIEW                                                                                                                                   -->   
<!--------------------------------------------------------------------------------------------------------------------------------------------------- -->

<div id="feed-header">
    <span id="api-help" style="float:right"><a href="<?php echo $path.$public_username_str; ?>feed/api"><?php echo tr('Feed API Help'); ?></a></span>
    <h3 id="feeds-title"><?php echo tr('Feeds'); ?></h3>
    <h3 id="public-feeds-title" class="hide"><?php echo tr('Public Feeds'); ?></h3>
</div>

<div class="feed-controls-sentinel"></div>
<div id="feed-app">
    <div class="controls feed-controls">
        <button class="btn" :title="allExpanded ? '<?php echo tr('Collapse') ?>' : '<?php echo tr('Expand') ?>'" @click="expandAllNodes()">
            <i :class="allExpanded ? 'icon-resize-small' : 'icon-resize-full'"></i>
        </button>
        <button class="btn" :title="allSelected ? '<?php echo tr('Unselect all') ?>' : '<?php echo tr('Select all') ?>'" @click="selectAllFeeds()">
            <i :class="allSelected ? 'icon-ban-circle' : 'icon-check'"></i> <span>{{ selectedFeedCount }}</span>
        </button>
        <button class="btn" v-if="selectedFeedCount > 0" title="<?php echo tr('Edit') ?>" @click="editFeeds">
            <i class="icon-pencil"></i>
        </button>
        <button class="btn" v-if="selectedFeedCount > 0 && session_write" title="<?php echo tr('Delete') ?>" @click="deleteFeeds">
            <i class="icon-trash"></i>
        </button>
        <button class="btn" v-if="showDownsample" title="<?php echo tr('Downsample') ?>" @click="downsampleFeeds">
            <i class="icon-repeat"></i>
        </button>
        <button class="btn" v-if="selectedFeedCount > 0" title="<?php echo tr('Download') ?>" @click="exportFeeds">
            <i class="icon-download"></i>
        </button>
        <button class="btn" v-if="selectedFeedCount > 0" title="<?php echo tr('Graph view') ?>" @click="graphSelectedFeeds">
            <i class="icon-eye-open"></i>
        </button>
        <button class="btn" v-if="showProcess" title="<?php echo tr('Process config') ?>" @click="processSelectedFeed">
            <i class="icon-wrench"></i>
        </button>
        <input type="text" name="filter" id="filter" v-model="filterText" v-show="selectedFeedCount === 0" placeholder="<?php echo tr('Filter feeds') ?>">
    </div>

<!-- Vue.js Feed List Component -->
    <div v-if="nodes && Object.keys(nodes).length > 0" class="group-list feed-list-grid">
        <!-- Header Row -->
        <!--
        <div class="grid-row feed-header">
            <div class="grid-cell"></div>minmax(max-content, 1fr)
            <div class="grid-cell">Name</div>
            <div class="grid-cell text-center">Public</div>
            <div class="grid-cell">Engine</div>
            <div class="grid-cell text-center">Size</div>
            <div class="grid-cell text-left">Process List</div>
            <div class="grid-cell text-center">Value</div>
            <div class="grid-cell text-center">Updated</div>
        </div>
        -->
        <!-- Node Groups -->
        <template v-for="(nodeFeeds, node) in filteredNodes">

            <div class="group-list-group">
            <!-- Node Header -->
            <div :key="node" class="group-list-header" @click="toggleNode(node)" :class="{'collapsed': !nodesDisplay[node]}" :style="{'--status-color': node_time_and_colour[node].color}">
                <div data-col="select" class="group-list-cell text-center has-indicator" @click.stop="toggleNode(node)">
                    <span v-if="!selectedFeedCount || !nodes[node] || nodes[node].length == 0" class="group-list-chevron"></span>
                    <input v-else @click.stop="selectAllNodeFeeds(node)" type="checkbox" class="checkbox-lg feed-select" :checked="isNodeFullySelected(node)" :title="'<?php echo tr('Select all feeds in node'); ?>'">
                </div>
                <div data-col="name" class="group-list-cell group-list-name">{{ node }}<small class="ml-2" v-if="getNodeSelectedCount(node) > 0">&nbsp;({{ getNodeSelectedCount(node) }})</small></div>
                <div data-col="public" class="group-list-cell"></div>
                <div data-col="engine" class="group-list-cell"></div>
                <div data-col="size" class="group-list-cell text-center">{{ getNodeSize(nodeFeeds) }}</div>
                <div data-col="process" class="group-list-cell"></div>
                <div data-col="spacer" class="group-list-cell"></div>
                <div data-col="value" class="group-list-cell"></div>
                <div data-col="updated" class="group-list-cell text-center" :style="{color: node_time_and_colour[node].color}">
                    {{ node_time_and_colour[node].text }}
                </div>
            </div>

            <!-- Node Feeds -->
            <div class="group-list-rows" :class="{'is-expanded': nodesDisplay[node]}">
                <div class="group-list-rows-inner">
                    <div v-for="feed in nodeFeeds" :key="feed.id"
                     class="group-list-row feed-graph-link"
                     :class="{'selected': selectedFeeds[feed.id]}"
                     :style="{'--status-color': feed.color}"
                     :feedid="feed.id"
                     :title="getFeedTooltip(feed)"
                     @click="openFeedGraph(feed.id)">

                    <div data-col="select" class="group-list-cell text-center" @click.stop>
                        <input class="feed-select" type="checkbox" :feedid="feed.id" v-model="selectedFeeds[feed.id]" @change="onFeedSelectionChange">
                    </div>
                    <div data-col="name" class="group-list-cell">{{ feed.name }}</div>
                    <div data-col="public" class="group-list-cell text-center">
                        <i :class="feed.public == 1 ? 'icon-globe icon-public' : 'icon-lock icon-private'"></i>
                    </div>
                    <div data-col="engine" class="group-list-cell" v-html="formatEngine(feed.engine, feed.interval)"></div>
                    <div data-col="size" class="group-list-cell text-center text-muted">{{ formatSize(feed.size) }}</div>
                    <div data-col="process" class="group-list-cell text-left" v-html="feed.processListHTML"></div>
                    <div data-col="spacer" class="group-list-cell"></div>
                    <div data-col="sync" class="group-list-cell text-center" v-if="feed.sync_status" @click.stop>
                        <span :class="'sync-badge sync-' + feed.sync_status">{{ feed.sync_label }}</span>
                    </div>
                    <div data-col="value" class="group-list-cell text-right">{{ formatValueDynamic(feed.value) }} <span class="text-muted text-sm">{{ feed.unit }}</span></div>
                    <div data-col="updated" class="group-list-cell text-center" :style="{color: feed.color}">
                        {{ feed.formatted_time }}
                    </div>
                </div>
            </div>
            </div>

            <!-- Spacer for clarity -->
            <div style="height:10px; grid-column: 1 / -1"></div>
        </template>
    </div>

    <div id="feed-none" class="alert alert-block" v-show="showNoFeeds">
        <h4 class="alert-heading"><?php echo tr('No feeds created'); ?></h4>
        <p><?php echo tr('Feeds are where your monitoring data is stored. The route for creating storage feeds is to start by creating inputs (see the inputs tab). Once you have inputs you can either log them straight to feeds or if you want you can add various levels of input processing to your inputs to create things like daily average data or to calibrate inputs before storage. Alternatively you can create Virtual feeds, this is a special feed that allows you to do post processing on existing storage feeds data, the main advantage is that it will not use additional storage space and you may modify post processing list that gets applyed on old stored data. You may want the next link as a guide for generating your request: '); ?><a href="api"><?php echo tr('Feed API helper'); ?></a></p>
    </div>

    <div id="public-feeds-none" class="alert alert-block" v-show="showNoPublicFeeds">
        <h4 class="alert-heading"><?php echo tr('No public feeds available'); ?></h4>
    </div>

</div>

<div id="feed-footer">
    <button id="refreshfeedsize" class="btn btn-small" ><i class="icon-refresh" ></i>&nbsp;<?php echo tr('Refresh feed size'); ?></button>
    <button id="addnewfeed" class="btn btn-small" data-toggle="modal" data-target="#newFeedNameModal"><i class="icon-plus-sign" ></i>&nbsp;<?php echo tr('New feed'); ?></button>
    <button id="importdata" class="btn btn-small" data-toggle="modal" data-target="#importDataModal"><i class="icon-arrow-up" ></i>&nbsp;<?php echo tr('Import data'); ?></button>
</div>
<div id="feed-loader" class="ajax-loader"></div>

<script>
(function() {
    var sentinel = document.querySelector('.feed-controls-sentinel');
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
        var controls = document.querySelector('.feed-controls');
        if (controls) controls.classList.toggle('is-sticky', !entries[0].isIntersecting);
    }, { rootMargin: '-46px 0px 0px 0px', threshold: 0 }).observe(sentinel);
})();
</script>

<?php 

// Main feed list js
load_js("Modules/feed/Views/feed_list.js");

// Sync module integration (adds an upload status column to the feed list).
// Loaded only when the sync module is installed; all logic is self-contained
// in the sync module and registers via the generic feedListPlugins hook.
if (file_exists("Modules/sync/sync_model.php")) {
    load_js("Modules/sync/sync_integration.js");
    load_css("Modules/sync/sync_integration.css");
}

// --------------------------------------------------------------
// Modals
// --------------------------------------------------------------

// Delete Feed Modal
require "Modules/feed/Views/modals/delete/delete_modal.php";
load_js("Modules/feed/Views/modals/delete/delete_modal.js");

// Edit Feed Modal
require "Modules/feed/Views/modals/edit/edit_modal.php";
load_js("Modules/feed/Views/modals/edit/edit_modal.js");

// Download Feed Modal
require "Modules/feed/Views/modals/download/download_modal.php";
load_js("Modules/feed/Views/modals/download/download_modal.js");

// Import Feed Modal
require "Modules/feed/Views/modals/import/import_modal.php";
load_js("Modules/feed/Views/modals/import/import_modal.js");

// Downsample Feed Modal
require "Modules/feed/Views/modals/downsample/downsample_modal.php";
load_js("Modules/feed/Views/modals/downsample/downsample_modal.js");

// New Feed Modal
require "Modules/feed/Views/modals/new/new_modal.php";
load_js("Modules/feed/Views/modals/new/new_modal.js");

// Included process ui modal from process module
require "Modules/process/Views/process_ui.php";
?>

