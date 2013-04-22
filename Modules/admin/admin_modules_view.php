<?php global $path; ?>
<h2><?php echo _("EmonCMS Modules"); ?></h2>

<table class="table table-striped ">
<?php
  $modules = get_modules();
  
  echo "<thead><tr>"; 
  echo "<th>Module</th>";
  echo "<th>Version</th>"; 
  echo "<th>Type</th>";
  echo "<th>Description</th>";  
  echo "</tr></thead>";
  
  foreach ($modules as $module)
  {
  echo "<tr>";
  $module_class = new $module();
  echo "<td>".$module_class->modulename()."</td>";
  echo "<td>".$module_class->moduleversion()."</td>"; 
  echo "<td>".$module_class->moduletype()."</td>";
  echo "<td>".$module_class->moduledescription()."</td>";  
  echo "</tr>";  
  }
?>
 
</table>