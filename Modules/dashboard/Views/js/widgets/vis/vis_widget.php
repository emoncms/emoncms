<?php
  /*

  As well as loading the default visualisations 
  we load here the custom multigraph visualisations.
  the object multigraphs is recognised in designer.js
  and used to create a drop down menu of available
  user multigraphs

  */

  global $session,$path;

  require "Modules/vis/multigraph_model.php";
  $multigraphs = get_user_multigraph($session['userid']);
  
?>

<script>
  var multigraphs = <?php echo json_encode($multigraphs); ?>;
</script>

<?php
  // Widget renderer 
  echo "<script type='text/javascript' src='".$path."Modules/dashboard/Views/js/widgets/vis/vis_render.js'></script>";  
?>
