<?php global $session,$path; ?>

  <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.min.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgetlist.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/render.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgets/dial.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgets/led.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgets/cylinder.js"></script>
    
<div style="background-color:#ddd; padding:4px;">
  <span id="widget-buttons"></span>
  <span id="when-selected">
  <button id="options-button">Options</button>
  <button id="delete-button">Delete</button>
  </span>

  <button style="float:right; margin:6px;" id="save-dashboard">Save</button>
  <span id="state"  style="float:right; margin-top:9px; color:#888;"></span>
</div>

<div id="page-container" style="height:400px; position:relative;">
  <div id="page"><?php echo $dashboard['content']; ?></div>
  <canvas id="can" width="940px" height="400px" style="position:absolute; top:0px; left:0px; margin:0; padding:0;"></canvas>

  <div id="testo" style="position:absolute; top:0px; left:0px; width:938px; background-color:rgba(255,255,255,0.9); border: 1px solid #ddd;">
    <div style="padding:20px;">
      <div id="box-options"></div>
      <input id='options-save' type='button' value='save'/ >
    </div>
  </div> 
</div>

<script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/designer.js"></script>
<script type="application/javascript">

  var dashid = <?php echo $dashboard['id']; ?>;
  var path = "<?php echo $path; ?>";
  var apikey_read = "<?php echo $apikey_read; ?>";
  $("#testo").hide();
  
  var redraw = 0;
  var reloadiframe = 0;

  var grid_size = 20;

  dashboard_designer("#can",grid_size,widgets);

  show_dashboard();

  setInterval(function() { update("<?php echo $apikey_read; ?>"); }, 5000);
  setInterval(function() { fast_update("<?php echo $apikey_read; ?>"); }, 30);
  setInterval(function() { slow_update("<?php echo $apikey_read; ?>"); }, 60000);

  $("#save-dashboard").click(function (){
    console.log("Saving");
    $.ajax({
      type: "POST",
      url :  path+"dashboard/set.json",
      data : "&content=" + encodeURIComponent($("#page").html())+"&id="+dashid,
      
      success : function(data) { console.log(data); if (data=="ok") $("#state").html("Saved"); } 
    });
  });
</script>
