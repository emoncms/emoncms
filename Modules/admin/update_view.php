<?php

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
            $out.="<tr><td>$operation</td></tr>";
            }

            $out.="</table>";
        }
    }

?>

<br>
<h2><?php echo _("Update database"); ?></h2>

<?php

    if ($out && !$applychanges) {
        echo '<div class="alert alert-block"><p><b>Todo:</b> - these changes need to be applied</p><br>'.$out.'</div>';
?>

<a href="<?php echo $path; ?>admin/db?apply=true" class="btn btn-info"><?php echo _('Apply changes'); ?></a>

<?php } elseif ($out && $applychanges) {
    echo '<div class="alert alert-success"><p><b>Success:</b> - the following changes have been applied</b></p><br>'.$out.'</div>';

?>

<a href="<?php echo $path; ?>admin/db" class="btn btn-info"><?php echo _('Check for further updates'); ?></a>

<?
    } else {
?>

<div class="alert alert-success">
    <b><?php echo _('Database is up to date '); ?></b> - <?php echo _('Nothing to do'); ?>
</div>

<?php }