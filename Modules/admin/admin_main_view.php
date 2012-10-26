<?php global $path; ?>
<h2>Admin</h2>

<table class="table table-striped ">
<tr>
  <td>
    <h3>Update database</h3>
    <p>Run this after updating emoncms, after installing a new module or to check emoncms database status.</p>
  </td>
  <td>
    <br>
    <a href="<?php echo $path; ?>admin/db" class="btn btn-info"><?php echo _('Update & check'); ?></a>
  </td>
  </tr>
<tr>
  <td>
  <h3><?php echo _('Installed modules'); ?></h3>
  <?php
    foreach (emoncms_modules::getInstance()->get_registered_modules() as $emoncms_module_instance) {
      echo get_class($emoncms_module_instance).' '.$emoncms_module_instance->description().'<br>';       
    };
   ?>  
  </td>    
</tr>
</table>


