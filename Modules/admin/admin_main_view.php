<?php global $path, $emoncms_version; ?>
<h2>Admin</h2>

<table class="table table-striped ">
    <tr>
        <td colspan="2">Emoncms version: <?php echo $emoncms_version; ?></td>
    </tr>
    <tr>
        <td>
            <h3><?php echo _('Users'); ?></h3>
            <p><?php echo _('Administer user accounts'); ?></p>
        </td>
        <td>
            <a href="<?php echo $path; ?>admin/users" class="btn btn-info"><?php echo _('Users'); ?></a>
        </td>
    </tr>
    <tr>
        <td>
            <h3><?php echo _('Update database'); ?></h3>
            <p><?php echo _('Run this after updating emoncms, after installing a new module or to check emoncms database status.'); ?></p>
        </td>
        <td>
            <a href="<?php echo $path; ?>admin/db" class="btn btn-info"><?php echo _('Update & check'); ?></a>
        </td>
    </tr>
    <tr>
        <td>LOG4PHP INSTALLED: <?php if(LOG4PHP_INSTALLED) echo " yes"; else echo " no"; ?></td>
        <td></td>
        </tr>
    <tr>
</table>
