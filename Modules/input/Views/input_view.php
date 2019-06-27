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

#auth-check {
    padding:10px;
    background-color:#dc9696;
    margin-top:50px;
    margin-bottom:10px;
    font-weight:bold;
    border: 1px solid #de6464;
    color:#fff;
}

.auth-check-btn {
    float:right;
    margin-top:-2px;
}

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
        <h3> <?php echo _('Inputs'); ?></h3>
    </div>

    <div id="feedlist-controls" class="controls" data-spy="affix" data-offset-top="100">
        <button id="expand-collapse-all" class="btn" title="<?php echo _('Collapse') ?>" data-alt-title="<?php echo _('Expand') ?>"><i class="icon icon-resize-small"></i></button>
        <button id="select-all" class="btn" title="<?php echo _('Select all') ?>" data-alt-title="<?php echo _('Unselect all') ?>"><i class="icon icon-check"></i></button>
        <button class="btn input-delete hide" title="Delete"><i class="icon-trash" ></i></button>
        <a href="#inputEditModal" class="btn input-edit hide" title="Edit" data-toggle="modal"><i class="icon-pencil" ></i></a>
    </div>

    <div id="auth-check" class="hide">
        <i class="icon-exclamation-sign icon-white"></i> Device on ip address: <span id="auth-check-ip"></span> would like to connect
        <button class="btn btn-small auth-check-btn auth-check-allow">Allow</button>
    </div>

    <div id="input-none" class="alert alert-block hide">
        <h4 class="alert-heading"><?php echo _('No inputs created'); ?></h4>
        <p><?php echo _('Inputs are the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
    </div>

    <div id="noprocesses"></div>
    <div id="table" class="input-list"></div>

    <div id="output"></div>


    <div id="input-footer" class="hide">
        <button id="device-new" class="btn btn-small" >&nbsp;<i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New device'); ?></button>
    </div>
    <div id="input-loader" class="ajax-loader"></div>
</div>

<?php
require "Modules/process/Views/process_ui.php";
if ($deviceModule) : 
    require "Modules/device/Views/device_dialog.php";
    require "Modules/input/Views/input_dialog.php";
?>
    <script src="<?php echo $path; ?>Modules/device/Views/device.js"></script>
<?php endif; ?>

<script src="<?php echo $path; ?>Modules/input/Views/input.js"></script>
<script src="<?php echo $path; ?>Modules/feed/feed.js"></script>
<script src="<?php echo $path; ?>Lib/responsive-linked-tables.js"></script>
<script src="<?php echo $path; ?>Lib/moment.min.js"></script>
<script>
    var _user = {};
    _user.lang = "<?php echo $_SESSION['lang']; ?>";
</script>
<script src="<?php echo $path; ?>Lib/user_locale.js"></script>
<script src="<?php echo $path; ?>Lib/misc/gettext.js"></script>
<script>
/**
 * return object of gettext translated strings
 *
 * @return {object} key/value pairs for each translated string
 */
function getTranslations(){
    return {
        'ID': "<?php echo _('ID') ?>",
        'Value': "<?php echo _('Value') ?>",
        'Time': "<?php echo _('Time') ?>",
        'Updated': "<?php echo _('Updated') ?>",
        'Configure your device here': "<?php echo _("Configure your device here") ?>",
        'Show node key': "<?php echo _("Show node key") ?>",
        'Configure Input processing': "<?php echo _("Configure Input processing") ?>",
        'Configure device using device template': "<?php echo _("Configure device using device template") ?>",
        'Saving': "<?php echo _("Saving") ?>"
    }
}
</script>
<script>

    var path = "<?php echo $path; ?>";
    var requestTime = 0;
    var firstLoad = true;
    var local_cache_key = 'input_nodes_display';
    var nodes_display = docCookies.hasItem(local_cache_key) ? JSON.parse(docCookies.getItem(local_cache_key)) : {};
    var selected_inputs = {};
    var selected_device = false;
    var isCollapsed = true;

    var device_templates = {};
    var firstLoad = true;
    
    const DEVICE_MODULE_INSTALLED = <?php echo $deviceModule ? 'true': 'false';?>;
    
    if (!DEVICE_MODULE_INSTALLED) {
        var nodes = {}
    } else {
        var devices = {}

    }

</script>
<script src="<?php echo $path; ?>Modules/input/Views/input_view.js"></script>
