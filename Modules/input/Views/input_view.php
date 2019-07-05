<?php
    global $path;
    $v = 1;
    
    $device_module = file_exists("Modules/device");
?>

<?php if ($device_module) { ?>
<script src="<?php echo $path; ?>Modules/device/Views/device.js?v=<?php echo $v; ?>"></script>
<?php } ?>
<script src="<?php echo $path; ?>Modules/input/Views/input.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $path; ?>Modules/feed/feed.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $path; ?>Lib/responsive-linked-tables.js?v=<?php echo $v; ?>"></script>

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
.controls { margin-bottom:10px; }

input[type="checkbox"] { margin:0px; }
#inputs-to-delete { font-style:italic; }

#noprocesses .alert{
    margin: 0;
    border-bottom-color: #fcf8e3;
    border-radius: 4px 4px 0 0;
    padding-right: 14px
}

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

.node-info.status-info .last-update,
.node-input.status-info .last-update {
    color: #3c87aa;
}
.node-info.status-success .last-update,
.node-input.status-success .last-update {
    color: #28a745;
}
.node-info.status-warning .last-update,
.node-input.status-warning .last-update {
    color: #cc7700;
}
.node-info.status-danger .last-update,
.node-input.status-danger .last-update {
    color: #dc3545;
}

.node-info.status-info::after,
.node-input.status-info::after {
    background: #3a87ad;
}
.node-info.status-success::after,
.node-input.status-success::after {
    background: #28a745;
}
.node-info.status-warning::after,
.node-input.status-warning::after {
    background: #ffc107;
}
.node-info.status-danger::after,
.node-input.status-danger::after {
    background: #dc3545;
}

[data-col="B"] { width:40px; }
[data-col="A"] { width:200px; }
[data-col="G"] { width:200px; }
[data-col="F"] { width:50px; }
[data-col="E"] { width:100px; }
[data-col="D"] { width:100px; }
[data-col="C"] { width:50px; }

</style>

<div>
    <div id="input-header">
        <span id="api-help" style="float:right"><a href="api"><?php echo _('Input API Help'); ?></a></span>
        <h3><?php echo _('Inputs'); ?></h3>
    </div>
    <div id="input-controls" class="controls hide" data-spy="affix" data-offset-top="100">
        <button id="expand-collapse-all" class="btn" title="<?php echo _('Collapse') ?>" data-alt-title="<?php echo _('Expand') ?>"><i class="icon icon-resize-small"></i></button>
        <button id="select-all" class="btn" title="<?php echo _('Select all') ?>" data-alt-title="<?php echo _('Unselect all') ?>"><i class="icon icon-check"></i></button>
        <button class="btn input-delete hide" title="Delete"><i class="icon-trash" ></i></button>
        <a href="#inputEditModal" class="btn input-edit hide" title="Edit" data-toggle="modal"><i class="icon-pencil" ></i></a>
    </div>
    
    <div id="noprocesses"></div>
    <div id="table" class="input-list"></div>
    
    <div id="output"></div>

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
function getTranslations() {
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
<script src="<?php echo $path; ?>Lib/user_locale.js"></script>
<script src="<?php echo $path; ?>Modules/input/Views/input_view.js?v=<?php echo $v; ?>"></script>
