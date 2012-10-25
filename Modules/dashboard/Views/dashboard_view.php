<?php global $session, $path; ?>

  <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.min.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgetlist.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/render.js"></script>

  <?php

    $widgets = array();
    $dir = scandir("Modules/dashboard/Views/js/widgets");
    for ($i=2; $i<count($dir); $i++)
    {
      if (filetype("Modules/dashboard/Views/js/widgets/".$dir[$i])=='dir') 
      {
        if (is_file("Modules/dashboard/Views/js/widgets/".$dir[$i]."/".$dir[$i]."_widget.php"))
        {
          require_once "Modules/dashboard/Views/js/widgets/".$dir[$i]."/".$dir[$i]."_widget.php";
          $widgets[] = $dir[$i];
        }
        else if (is_file("Modules/dashboard/Views/js/widgets/".$dir[$i]."/".$dir[$i]."_render.js"))
        {
          echo "<script type='text/javascript' src='".$path."Modules/dashboard/Views/js/widgets/".$dir[$i]."/".$dir[$i]."_render.js'></script>";
          $widgets[] = $dir[$i];
        }
      }
    }

  ?>

  <div id="page-container" style="height:<?php echo $dashboard['height']; ?>px; position:relative;">
    <div id="page"><?php echo $dashboard['content']; ?></div>
  </div>

<script type="application/javascript">
  var dashid = <?php echo $dashboard['id']; ?>;
  var path = "<?php echo $path; ?>";
  var apikey_read = "<?php echo $apikey_read; ?>";
  var widget = <?php echo json_encode($widgets); ?>;

  for (z in widget)
  {
    var fname = widget[z]+"_widgetlist";
    var fn = window[fname];
    $.extend(widgets,fn());
  }

  var redraw = 1;
  var reloadiframe = 0;
  show_dashboard();
  setInterval(function() { update("<?php echo $apikey_read; ?>"); }, 5000);
  setInterval(function() { fast_update("<?php echo $apikey_read; ?>"); }, 30);

</script>
