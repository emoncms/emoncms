<?php
  global $path;

  // energyaudit module needs to be installed:
  echo "<script type='text/javascript' src='".$path."Modules/energydata/stack_lib/stacks.js'></script>"; 

  // Widget renderer 
  echo "<script type='text/javascript' src='".$path."Modules/dashboard/Views/js/widgets/stack/stack_render.js'></script>";  
?>
