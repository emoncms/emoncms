<?php
    /*

    As well as loading the default visualisations
    we load here the custom multigraph visualisations.
    the object multigraphs is recognised in designer.js
    and used to create a drop down menu of available
    user multigraphs

    */

    global $mysqli, $session,$path;

    require "Modules/vis/multigraph_model.php";
    $multigraph = new Multigraph($mysqli);
    $multigraphs = $multigraph->getlist($session['userid']);
?>

<script>
    var multigraphs = <?php echo json_encode($multigraphs); ?>;
</script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgets/vis/vis_render.js"></script>
