<?php
    $lang = "";
    if (isset($_SESSION['lang'])) {
        $lang = $_SESSION['lang']; 
    }
?>
<script>
    var _user = {};
    _user.lang = "<?php echo $lang; ?>";
</script>

<?php load_js("Lib/js/user_locale.js"); ?>
<script>
// @todo: standardise these translations functions, also used in admin_main_view.php and input_view.php
/**
 * return object of gettext translated strings
 *
 * @return object
 */
function getTranslations(){
    return {
        'Tag': "<?php echo tr('Tag') ?>",
        'Feed ID': "<?php echo tr('Feed ID') ?>",
        'Feed Interval': "<?php echo tr('Feed Interval') ?>",
        'Feed Start Time': "<?php echo tr('Feed Start Time') ?>",
        'Feed Engine': <?php echo json_encode(tr('Feed Engine')); ?>,
        'Feed Interval (approx)': <?php echo json_encode(tr('Feed Interval (approx)')); ?>,
        'Feed End Time': <?php echo json_encode(tr('Feed End Time')); ?>,
        'Nothing changed': <?php echo json_encode(tr('Nothing changed')); ?>,
        'Saved': <?php echo json_encode(tr('Saved')); ?>,
        'Save failed': <?php echo json_encode(tr('Save failed')); ?>,
        'feeds selected': <?php echo json_encode(tr('feeds selected')); ?>,
        'input processes': <?php echo json_encode(tr('input processes')); ?>,
        'and': <?php echo json_encode(tr('and')); ?>,
        'virtual feed processes': <?php echo json_encode(tr('virtual feed processes')); ?>,
        'associated with this feed': <?php echo json_encode(tr('associated with this feed')); ?>,
        'associated with these feeds': <?php echo json_encode(tr('associated with these feeds')); ?>,
        'Out of range': <?php echo json_encode(tr('Out of range')); ?>,
        'This is a new feature. Consider backing up your data before you continue. OK to continue?': <?php echo json_encode(tr('This is a new feature. Consider backing up your data before you continue. OK to continue?')); ?>,
        '"Trim" not available for this storage engine': <?php echo json_encode(tr('"Trim" not available for this storage engine')); ?>,
        'Are you sure you want to delete all the feeds data?': <?php echo json_encode(tr('Are you sure you want to delete all the feeds data?')); ?>,
        '"Clear" not available for this storage engine': <?php echo json_encode(tr('"Clear" not available for this storage engine')); ?>,
        'Are you sure you want to delete?': <?php echo json_encode(tr('Are you sure you want to delete?')); ?>,
        'Feeds Deleted': <?php echo json_encode(tr('Feeds Deleted')); ?>,
        'Realtime': "<?php echo tr('Realtime') ?>",
        'Daily': "<?php echo tr('Daily') ?>",
        'Total size of used space for feeds:': "<?php echo tr('Total size of used space for feeds:') ?>",
        'Please enter a valid start date.': "<?php echo tr('Please enter a valid start date.'); ?>",
        'Please enter a valid end date.': "<?php echo tr('Please enter a valid end date.'); ?>",
        'Start date must be further back in time than end date.': "<?php echo tr('Start date must be further back in time than end date.'); ?>",
        'Please select interval to download.': "<?php echo tr('Please select interval to download.'); ?>",
        'Estimated download file size is large.': "<?php echo tr('Estimated download file size is large.'); ?>",
        'Server could take a long time or abort depending on stored data size.': "<?php echo tr('Server could take a long time or abort depending on stored data size.'); ?>",
        'Limit is': "<?php echo tr('Limit is'); ?>",
        'Try exporting anyway?': "<?php echo tr('Try exporting anyway?'); ?>"
    }
}
/**
 * wrapper for gettext like string replace function
 */
function tr(str) {
    return translate(str);
}
/**
 * emulate the php gettext function for replacing php strings in js
 */
function translate(property) {
    _strings = typeof translations === 'undefined' ? getTranslations() : translations;
    if (_strings.hasOwnProperty(property)) {
        return _strings[property];
    } else {
        return property;
    }
}

</script>