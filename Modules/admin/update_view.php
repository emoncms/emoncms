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
<h2>Update database</h2>

<?php if ($out) { echo $out; ?>

<a href="<?php echo $path; ?>admin/db?apply=true" class="btn btn-info"><?php echo _('Apply changes'); ?></a>

<?php } else { ?>

<div id="nofeeds" class="alert alert-success">
  <b><?php echo _('Database is up to date '); ?></b>- Nothing to do
</div>

<?php } ?>

