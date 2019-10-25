<?php
    defined('EMONCMS_EXEC') or die('Restricted access');
    global $path, $settings;

?>
<h2><?php echo _("Current Emoncms Settings"); ?></h2>
<pre>
<?php

print_r($settings);

?>
</pre>

<a href="<?php echo $path; ?>admin/view" class="btn btn-info"><?php echo _('Return to Administration Page'); ?></a>
<?php ?>
