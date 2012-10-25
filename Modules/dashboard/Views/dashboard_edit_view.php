<?php global $session,$path; 

if (!$dashboard['height']) $dashboard['height'] = 400;
?>

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

<div style="background-color:#ddd; padding:4px;">
  <span id="widget-buttons"></span>
  <span id="when-selected">
  <button id="options-button">Options</button>
  <button id="delete-button">Delete</button>
  </span>

  <button style="float:right; margin:6px;" id="save-dashboard">Save</button>
  <span id="state"  style="float:right; margin-top:9px; color:#888;"></span>
</div>

<div id="page-container" style="height:<?php echo $dashboard['height']; ?>px; position:relative;">
  <div id="page"><?php echo $dashboard['content']; ?></div>
  <canvas id="can" width="940px" height="<?php echo $dashboard['height']; ?>px" style="position:absolute; top:0px; left:0px; margin:0; padding:0;"></canvas>

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
  var page_height = "<?php echo $dashboard['height']; ?>";
  var path = "<?php echo $path; ?>";
  var apikey_read = "<?php echo $apikey_read; ?>";
  $("#testo").hide();

  var widget = <?php echo json_encode($widgets); ?>;

  console.log(widgets);

  for (z in widget)
  {
    var fname = widget[z]+"_widgetlist";
    var fn = window[fname];
    $.extend(widgets,fn());
  }
 
  var redraw = 0;
  var reloadiframe = 0;

  var grid_size = 20;

  dashboard_designer("#can",grid_size,widgets);

  show_dashboard();

  setInterval(function() { update("<?php echo $apikey_read; ?>"); }, 5000);
  setInterval(function() { fast_update("<?php echo $apikey_read; ?>"); }, 30);

  $("#save-dashboard").click(function (){
    console.log("Saving");
    $.ajax({
      type: "POST",
      url :  path+"dashboard/set.json",
      data : "&content=" + encodeURIComponent($("#page").html())+"&id="+dashid+"&height="+page_height,
      
      success : function(data) { console.log(data); if (data=="ok") $("#state").html("Saved"); } 
    });
  });
</script>
