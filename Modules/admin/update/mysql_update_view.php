<?php
    defined('EMONCMS_EXEC') or die('Restricted access');
    global $path;

    $out = "";
    foreach ($updates as $update)
    {
        if ($update['operations'])
        {
            $done = false;
            $out.='<div class="panel-header panel-header-static"><span class="panel-accent"></span><span class="panel-name">'.$update['title'].'</span></div>';
            $out.='<div class="panel-body text-muted">'.$update['description'].'</div>';
            $out.='<table>';
            foreach ($update['operations'] as $operation)
            {
                $out.='<tr><td class="db-sql">'.htmlspecialchars($operation).';</td></tr>';
            }
            $out.="</table>";
        }
    }
?>

<?php load_css("Modules/admin/static/admin_styles.css"); ?>
<div class="panel-page admin-page">

<div class="page-header">
    <h3><?php echo tr("Update database"); ?></h3>
</div>
<?php
    if ($out && !$applychanges) {
        echo '<div class="alert alert-warning"><b>Todo:</b> These changes need to be applied</div>';
        echo '<div class="panel db-updates">'.$out.'</div>';
?>
<a href="<?php echo $path; ?>admin/db?apply=true" class="btn btn-primary"><?php echo tr('Apply changes'); ?></a>
<?php }
    elseif ($applychanges && !empty($error)) {
        echo '<div class="alert alert-danger"><b>Error:</b> The following error has occured:<br>'.$error.'</div>';
?>
<a href="<?php echo $path; ?>admin/db" class="btn btn-default"><?php echo tr('Back'); ?></a>

<?php }
    elseif ($out && $applychanges) {
        echo '<div class="alert alert-success"><b>Success:</b> The following changes have been applied</div>';
        echo '<div class="panel db-updates">'.$out.'</div>';
?>
<a href="<?php echo $path; ?>admin/db" class="btn btn-default"><?php echo tr('Check for further updates'); ?></a>
<?php
    } else {
?>
<div class="alert alert-success">
    <b><?php echo tr('Database is up to date '); ?></b> - <?php echo tr('Nothing to do'); ?>
</div>
<a href="<?php echo $path; ?>admin/update" class="btn btn-default"><?php echo tr('Return to Update Page'); ?></a>
<?php } ?>

</div>
