<?php
    defined('EMONCMS_EXEC') or die('Restricted access');

    global $mysqli, $session;

    $domain3 = "vis_messages";
    bindtextdomain($domain3, "Modules/vis/locale");
    bind_textdomain_codeset($domain3, 'UTF-8');

    require "Modules/vis/multigraph_model.php";
    $multigraph = new Multigraph($mysqli);
    $multigraph_list = $multigraph->getlist($session['userid']);
    
    $multigraphs = array();
    foreach($multigraph_list as $mg)
    {
           $multigraphs[] = array($mg['id'],$mg['id'].":".$mg['name']);
    }
?>
<script>var multigraphsDropBoxOptions = <?php echo json_encode($multigraphs); ?>;</script>
