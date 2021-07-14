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
<h2><?php echo _("Update database"); ?></h2>
<?php
    if ($out && !$applychanges) {
        echo '<div class="alert alert-block"><p><b>Todo:</b> These changes need to be applied</p><br>'.$out.'</div>';
?>
<a href="<?php echo $path; ?>admin/db?apply=true" class="btn btn-info"><?php echo _('Apply changes'); ?></a>
<?php } 
    elseif ($applychanges && !empty($error)) {
        echo '<div class="alert alert-danger"><p><b>Error:</b> The following error has occured:</b></p><br>'.$error.'</div>';
?>
<a href="<?php echo $path; ?>admin/db" class="btn btn-info"><?php echo _('Back'); ?></a>

<?php } 
    elseif ($out && $applychanges) {
        echo '<div class="alert alert-success"><p><b>Success:</b> The following changes have been applied</b></p><br>'.$out.'</div>';
?>
<a href="<?php echo $path; ?>admin/db" class="btn btn-info"><?php echo _('Check for further updates'); ?></a>
<?php
    } else {
?>
<div class="alert alert-success">
    <b><?php echo _('Database is up to date '); ?></b> - <?php echo _('Nothing to do'); ?>
</div>
<a href="<?php echo $path; ?>admin/info" class="btn btn-info"><?php echo _('Return to Administration Page'); ?></a>
<?php } ?>
