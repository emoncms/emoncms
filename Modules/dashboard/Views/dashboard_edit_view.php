<?php 

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

global $session,$path; 

if (!$dashboard['height']) $dashboard['height'] = 400;
?>

  <link href="<?php echo $path; ?>Modules/dashboard/Views/js/widget.css" rel="stylesheet">

  <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgetlist.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/render.js"></script>

  <script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

  <?php require_once "Modules/dashboard/Views/loadwidgets.php"; ?>

<div id="dashboardpage">

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

</div>

<script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/designer.js"></script>
<script type="application/javascript">

  var dashid = <?php echo $dashboard['id']; ?>;
  var page_height = "<?php echo $dashboard['height']; ?>";
  var path = "<?php echo $path; ?>";
  var apikey = "";
  var feedlist = feed.list();
  var userid = <?php echo $session['userid']; ?>;

  $("#testo").hide();

  var widget = <?php echo json_encode($widgets); ?>;

  for (z in widget)
  {
    var fname = widget[z]+"_widgetlist";
    var fn = window[fname];
    $.extend(widgets,fn());
  }
 
  var redraw = 0;
  var reloadiframe = 0;

  var grid_size = 20;
  $('#can').width($('#dashboardpage').width());

  designer.canvas = "#can";
  designer.grid_size = 20;
  designer.widgets = widgets;

  designer.init();

  show_dashboard();

  setInterval(function() { update(); }, 10000);
  setInterval(function() { fast_update(); }, 30);

  $("#save-dashboard").click(function (){
    $.ajax({
      type: "GET",
      url :  path+"dashboard/setcontent.json",
      data : "&id="+dashid+'&content='+encodeURIComponent($("#page").html())+'&height='+page_height,
      dataType: 'json',
      success : function(data) { console.log(data); if (data.success==true) $("#state").html("Saved"); } 
    });
  });

  $(window).resize(function(){
    designer.draw();
  });
</script>
