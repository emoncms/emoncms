<?php
    defined('EMONCMS_EXEC') or die('Restricted access');
    global $path, $settings, $session;
    $v=8;
        
    $public_username_str = "";
    if ($session['public_userid']) {
        $public_username_str = $session['public_username']."/";
    }
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
<script src="<?php echo $path; ?>Lib/moment.min.js?v=1"></script>

<script>

/**
 * uses moment.js to format to local time 
 * @param int time unix epoc time
 * @param string format moment.js date formatting options
 * @see date format options - https://momentjs.com/docs/#/displaying/
 */
function format_time(time,format){
    if(!Number.isInteger(time)) return time;
    format = format || 'YYYY-MM-DD';
    formatted_date = moment.unix(time).utc().format(format);
    return formatted_date;
}
var feedviewpath = "<?php echo $settings['interface']['feedviewpath']; ?>";
var engines_hidden = <?php echo json_encode($settings["feed"]['engines_hidden']); ?>;
var available_intervals = <?php echo json_encode(Engine::available_intervals()); ?>;
var downloadlimit = <?php echo $settings['feed']['csv_downloadlimit_mb']; ?>;

</script>
<?php require "Modules/feed/Views/translate.php"; ?>

<!-- feed.clear, trim, remove used by delete modal -->
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js?v=<?php echo $v; ?>"></script>

<link href="<?php echo $path; ?>Theme/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script src="<?php echo $path; ?>Theme/js/bootstrap-datetimepicker.min.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/misc/autocomplete.js?v=<?php echo $v; ?>"></script>
<link rel="stylesheet" href="<?php echo $path; ?>Lib/misc/autocomplete.css?v=<?php echo $v; ?>">

<!--------------------------------------------------------------------------------------------------------------------------------------------------- -->
<!-- FEED LIST VIEW                                                                                                                                   -->   
<!--------------------------------------------------------------------------------------------------------------------------------------------------- -->

<link rel="stylesheet" href="<?php echo $path; ?>Modules/feed/Views/feed_list.css?v=<?php echo $v; ?>">

<div id="feed-header">
    <span id="api-help" style="float:right"><a href="<?php echo $path.$public_username_str; ?>feed/api"><?php echo tr('Feed API Help'); ?></a></span>
    <h3 id="feeds-title"><?php echo tr('Feeds'); ?></h3>
    <h3 id="public-feeds-title" class="hide"><?php echo tr('Public Feeds'); ?></h3>
</div>

<input type="text" name="filter" id="filter" placeholder="Filter feeds" style="float:right">

<div id="feed-app">
    <div class="sticky-sentinel" style="height: 1px; position: absolute; top: 45px; width: 100%; pointer-events: none;"></div>
    <div class="sticky-controls">
        <button class="btn" :title="allExpanded ? '<?php echo tr('Collapse') ?>' : '<?php echo tr('Expand') ?>'" @click="expandAllNodes()">
            <i class="icon" :class="allExpanded ? 'icon-resize-small' : 'icon-resize-full'"></i>
        </button>
        <button class="btn" :title="allSelected ? '<?php echo tr('Unselect all') ?>' : '<?php echo tr('Select all') ?>'" @click="selectAllFeeds()">
            <i class="icon" :class="allSelected ? 'icon-ban-circle' : 'icon-check'"></i> <span>{{ selectedFeedCount }}</span>
        </button>
        <button class="btn" v-if="selectedFeedCount>0" title="<?php echo tr('Edit') ?>" @click="editFeeds">
            <i class="icon-pencil"></i>
        </button>
        <button class="btn" :class="{hide: !selectedFeedCount || !session_write}" title="<?php echo tr('Delete') ?>" @click="deleteFeeds">
            <i class="icon-trash"></i>
        </button>
        <button class="btn" :class="{hide: !showDownsample}" title="<?php echo tr('Downsample') ?>" @click="downsampleFeeds">
            <i class="icon-repeat"></i>
        </button>
        <button class="btn" v-if="selectedFeedCount>0" title="<?php echo tr('Download') ?>" @click="exportFeeds">
            <i class="icon-download"></i>
        </button>
        <button class="btn" v-if="selectedFeedCount>0" title="<?php echo tr('Graph view') ?>" @click="graphSelectedFeeds">
            <i class="icon-eye-open"></i>
        </button>
        <button class="btn" :class="{hide: !showProcess}" title="<?php echo tr('Process config') ?>" @click="processSelectedFeed">
            <i class="icon-wrench"></i>
        </button>
    </div>

<!-- Vue.js Feed List Component -->
    <div v-if="nodes && Object.keys(nodes).length > 0" class="feed-list-grid">
        <!-- Header Row -->
        <!--
        <div class="grid-row feed-header">
            <div class="grid-cell"></div>
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
        <template v-for="(nodeFeeds, node) in nodes">
            <!-- Node Header -->
            <div :key="node" class="grid-row node-header" @click="nodesDisplay[node] = !nodesDisplay[node]" :class="{'collapsed': !nodesDisplay[node]}" :style="{'--status-color': node_time_and_colour[node].color}">
                <div class="grid-cell text-center has-indicator">
                    <i class="arrow-icon" :class="[nodesDisplay[node] ? 'icon-chevron-down' : 'icon-chevron-right']" style="transition: transform 0.3s ease;"></i>
                </div>
                <h5>{{ node }}:</h5>
                <div class="grid-cell"></div>
                <div class="grid-cell"></div>
                <div class="grid-cell text-center">{{ getNodeSize(nodeFeeds) }}</div>
                <div class="grid-cell"></div>
                <div class="grid-cell"></div>
                <div class="grid-cell text-center" :style="{color: node_time_and_colour[node].color}">
                    {{ node_time_and_colour[node].text }}
                </div>
            </div>

            <!-- Node Feeds -->
            <div class="vue-collapsible-content" :class="{'is-expanded': nodesDisplay[node]}">
                <div v-for="feed in nodeFeeds" :key="feed.id"
                     class="grid-row node-feed feed-graph-link"
                     :class="{'selected': selectedFeeds[feed.id]}"
                     :style="{'--status-color': feed.color}"
                     :feedid="feed.id"
                     :title="getFeedTooltip(feed)"
                     @click="openFeedGraph(feed.id)">

                    <div class="grid-cell text-center" @click.stop>
                        <input class="feed-select" type="checkbox" :feedid="feed.id" v-model="selectedFeeds[feed.id]" @change="onFeedSelectionChange">
                    </div>
                    <div class="grid-cell">{{ feed.name }}</div>
                    <div class="grid-cell text-center">
                        <i :class="feed.public == 1 ? 'icon-globe' : 'icon-lock'"></i>
                    </div>
                    <div class="grid-cell">{{ formatEngine(feed.engine, feed.interval) }}</div>
                    <div class="grid-cell text-center">{{ formatSize(feed.size) }}</div>
                    <div class="grid-cell text-left" v-html="feed.processListHTML"></div>
                    <div class="grid-cell text-center" v-html="formatValue(feed.value, feed.unit)"></div>
                    <div class="grid-cell text-center" :style="{color: feed.color}">
                        {{ feed.formatted_time }}
                    </div>
                </div>
            </div>

            <!-- Spacer for clarity -->
            <div style="height:10px; grid-column: 1 / -1; background: #fff;"></div>
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

<!-- Main feed list javascript -->
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/feed_list.js?v=<?php echo $v; ?>"></script>

<!----------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- Feed list ui modals -->
<!----------------------------------------------------------------------------------------------------------------------------------------------------->

<!-- Delete Feed Modal -->
<?php require "Modules/feed/Views/modals/delete/delete_modal.php"; ?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/modals/delete/delete_modal.js?v=<?php echo $v; ?>"></script>

<!-- Edit Feed Modal -->
<?php require "Modules/feed/Views/modals/edit/edit_modal.php"; ?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/modals/edit/edit_modal.js?v=<?php echo $v; ?>"></script>

<!-- Download Feed Modal -->
<?php require "Modules/feed/Views/modals/download/download_modal.php"; ?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/modals/download/download_modal.js?v=<?php echo $v; ?>"></script>

<!-- Import Feed Modal -->
<?php require "Modules/feed/Views/modals/import/import_modal.php"; ?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/modals/import/import_modal.js?v=<?php echo $v; ?>"></script>

<!-- Downsample Feed Modal -->
<?php require "Modules/feed/Views/modals/downsample/downsample_modal.php"; ?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/modals/downsample/downsample_modal.js?v=<?php echo $v; ?>"></script>

<!-- New Feed Modal -->
<?php require "Modules/feed/Views/modals/new/new_modal.php"; ?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/Views/modals/new/new_modal.js?v=<?php echo $v; ?>"></script>

<!-- Included process ui modal from process module -->
<?php require "Modules/process/Views/process_ui.php"; ?>

