<?php

  global $session, $path;

  // energyaudit module needs to be installed:
  echo "<script type='text/javascript' src='".$path."Modules/energy/stack_lib/stacks.js'></script>"; 
  echo "<script type='text/javascript' src='".$path."Modules/energy/stack_lib/stack_prepare.js'></script>";
  // Widget renderer 
  echo "<script type='text/javascript' src='".$path."Modules/dashboard/Views/js/widgets/stack/stack_render.js'></script>";  

  include "Modules/energy/energy_model.php";
  include "Modules/energy/energytypes.php";

  $energyitems = energy_get_year($session['userid'], 2012);
?>

<script>
  var energyitems = <?php echo json_encode($energyitems); ?>;
  var energytypes = <?php echo json_encode($energytypes); ?>;

  order_energyitems();

  for (z in energyitems)
  {
    energyitems[z]['data'] = JSON.parse(energyitems[z]['data'] || "null");
  }

  var stacks = prepare_stack();

</script>

<?php

?>
