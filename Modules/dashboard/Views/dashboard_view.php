<?php global $session, $path; ?>

  <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.min.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgetlist.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/render.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgets/dial.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgets/led.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgets/cylinder.js"></script>

  <div id="page-container" style="height:400px; position:relative;">
    <div id="page"><?php echo $dashboard['content']; ?></div>
  </div>

<script type="application/javascript">
  var dashid = <?php echo $dashboard['id']; ?>;
  var path = "<?php echo $path; ?>";
  var apikey_read = "<?php echo $apikey_read; ?>";

  var redraw = 1;
  var reloadiframe = 0;
  show_dashboard();
  setInterval(function() { update("<?php echo $apikey_read; ?>"); }, 5000);
  setInterval(function() { fast_update("<?php echo $apikey_read; ?>"); }, 30);
  setInterval(function() { slow_update("<?php echo $apikey_read; ?>"); }, 60000);

</script>
