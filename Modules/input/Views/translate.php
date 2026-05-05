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
<script>
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
