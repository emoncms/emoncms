<?php
    defined('EMONCMS_EXEC') or die('Restricted access');
    global $path;

    $out = "";
    foreach ($updates as $update)
    {
        if ($update['operations'])
        {
            $done = false;
            $out.="<h4>".$update['title']."</h4>";
            $out.="<p>".$update['description']."</p>";
            $out.='<table class="table table-striped ">';
            foreach ($update['operations'] as $operation)
            {
                $out.="<tr><td>$operation;</td></tr>";
            }
            $out.="</table>";
        }
    }
?>

<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css">
<div class="admin-container">

<h2><?php echo tr("Update database"); ?></h2>
<?php
    if ($out && !$applychanges) {
        echo '<div class="alert alert-block"><p><b>Todo:</b> These changes need to be applied</p><br>'.$out.'</div>';
?>
<a href="<?php echo $path; ?>admin/db?apply=true" class="btn btn-info"><?php echo tr('Apply changes'); ?></a>
<?php } 
    elseif ($applychanges && !empty($error)) {
        echo '<div class="alert alert-danger"><p><b>Error:</b> The following error has occured:</b></p><br>'.$error.'</div>';
?>
<a href="<?php echo $path; ?>admin/db" class="btn btn-info"><?php echo tr('Back'); ?></a>

<?php } 
    elseif ($out && $applychanges) {
        echo '<div class="alert alert-success"><p><b>Success:</b> The following changes have been applied</b></p><br>'.$out.'</div>';
?>
<a href="<?php echo $path; ?>admin/db" class="btn btn-info"><?php echo tr('Check for further updates'); ?></a>
<?php
    } else {
?>
<div class="alert alert-success">
    <b><?php echo tr('Database is up to date '); ?></b> - <?php echo tr('Nothing to do'); ?>
</div>
<a href="<?php echo $path; ?>admin/update" class="btn btn-info"><?php echo tr('Return to Update Page'); ?></a>
<?php } ?>

</div>