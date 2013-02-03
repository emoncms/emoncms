<!--
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
-->

<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

global $path;
?>
  <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.min.js"></script>
  <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/multigraph_edit.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/multigraph.js"></script>


<h2>Visualisations</h2>

<div style="float:left">

<div style="width:320px; background-color:#efefef; margin-bottom:10px; border: 1px solid #ddd;">
  <div style="padding:10px;  border-top: 1px solid #fff">
    <div style="float:left; padding-top:2px; font-weight:bold;">1) Select visualisation: </div>
    <div style="float:right;">
      <span id="select"></span>
    </div>
    <div style="clear:both"></div>
  </div>
</div>

<div style="width:320px; background-color:#efefef; margin-bottom:10px; border: 1px solid #ddd;">
  <div style="padding:10px;  border-top: 1px solid #fff">
    <div style="padding-top:2px; font-weight:bold;">2) Set options: </div><br>
    <div id="box-options" ></div><br>
    <p style="font-size:12px; color:#444;"><b>Note:</b> If a feed does not appear in the selection box, check that the type has been set on the feeds page.</p>
  </div>
</div>

<div style="width:320px; background-color:#efefef; margin-bottom:10px; border: 1px solid #ddd;">
  <div style="padding:10px;  border-top: 1px solid #fff">
    <div style="float:left; padding-top:2px; font-weight:bold;">3) </div>
    <div style="float:right;">
    <input id="viewbtn" type="submit" value="View" class="btn btn-info" />
    <input id="fullscreen" type="submit" value="Full screen" class="btn btn-info" />
    </div>
    <div style="clear:both"></div>
  </div>
</div>

<div style="width:320px; background-color:#efefef; margin-bottom:10px; border: 1px solid #ddd;">
  <div style="padding:10px;  border-top: 1px solid #fff">
    <div style="padding-top:2px; font-weight:bold;">Embed in your website: </div><br>
    <textarea id="embedcode" style="width:290px; height:120px;" readonly="readonly"></textarea>
  </div>
</div>

</div>

<div style="width:600px; height:420px; float:right">
    <div id="visiframe"><div style="height:400px; border: 1px solid #ddd; " ></div></div>
</div>

<div id="visurl"></div>

<script type="application/javascript">
  var path = "<?php echo $path; ?>";
  var multigraphs = <?php echo json_encode($multigraphs); ?>;
  var feedlist = <?php echo json_encode($feedlist); ?>;
  var widgets = <?php echo json_encode($visualisations); ?>;

  var multigraph = 0;
  var multigraph_feedlist = [];

  var embed = 0;

  // This could be moved to the top of multigraph.js
  var timeWindow = (3600000*24.0*7);				//Initial time window
  var start = ((new Date()).getTime())-timeWindow;		//Get start time
  var end = (new Date()).getTime();				//Get end time

  // This is used with multigraph.js to tell it to call a save request in multigraph_edit.js
  // when the multigraph time window is changed.
  var multigraph_editmode = true;

  //var apikey = "<?php echo $apikey; ?>";
  var apikey = "";

  var out = '<select id="visselect" style="width:120px; margin:0px;">';
  for (z in widgets)
  {
    // If widget action specified: use action otherwise override with widget key
    var action = z;
    if (widgets[z]['action']!=undefined) action = widgets[z]['action'];
    out += "<option value='"+action+"' >"+z+"</option>"; 
  }
  out += '</select>';
  $("#select").html(out);

  draw_options(widgets['realtime']['options']);

  // 1) ON CLICK OF VISUALISATION OPTION:

  $("#visselect").change(function(){

    // Custom multigraph visualisation items
    if ($(this).val()=="multigraph")
    { 
      $("#viewbtn").hide();

      var out = "<p><b>Select multigraph:</b> <select id='midselector' style='width:50px; font-size:12px'>";
      for (z in multigraphs)
      {
        out +="<option value='"+multigraphs[z]['id']+"'>"+multigraphs[z]['id']+"</option>";
      }
      out += "</select>";

      // 1) Start by drawing a dropdown multigraph id selector
      $("#box-options").html(out+" &nbsp;&nbsp;&nbsp;<b>New:</b> <i class='icon-plus'></i></p><div id='feedtable' ></div>");

      $("#midselector").change(function(){
        multigraph = $(this).val();

        $.ajax({                                      
          type: "GET",
          url: path+"vis/multigraph/get.json?id="+multigraph,
          dataType: 'json',
          async: false,
          success: function(data){if (data!=null) multigraph_feedlist = data;}
        });

        // Draw multigraph feedlist editor
        draw_multigraph_feedlist_editor();
        // Draw multigraph
        multigraph_init("#visiframe");
        vis_feed_data();

      });

      $(".icon-plus").click(function(){
        $.ajax({                                      
          type: "GET",
          url: path+"vis/multigraph/new.json",
          dataType: 'json',
          async: false,
          success: function(data){}
        });
        window.location = "list";
      });
    }
    else
    {
      $("#viewbtn").show();
      // Normal visualisation items
      draw_options(widgets[$(this).val()]['options'], widgets[$(this).val()]['optionstype']);
    }
  });

  $("#viewbtn").click(function(){
    var visurl = "";
    var vistype = $("#visselect").val();
    visurl += path+"vis/"+vistype;

    // Here we go through all the options that are set and get their values creating a url string that gets the
    // visualisation. We also check for each feed if the feed is a public feed or not.
    // If the feed is not public then we include the read apikey in the embed code box.

    var publicfeed = 1;
    $(".options").each(function() {
      if ($(this).val()) 
      {
        visurl += "&"+$(this).attr("id")+"="+$(this).val();
        if ($(this).attr("otype")=='feed') publicfeed = $('option:selected', this).attr('public');
      }
    });

    $("#visiframe").html('<iframe style="width:580px; height:400px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visurl+'&embed=1"></iframe>');

    if (publicfeed == 1) $("#embedcode").val('<iframe style="width:580px; height:400px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visurl+'&embed=1"></iframe>'); else $("#embedcode").val('Some of the feeds selected are not public, to embed a visualisation publicly first make the feeds that you want to use public.\n\nTo embed privately:\n\n<iframe style="width:580px; height:400px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visurl+'&embed=1&apikey='+apikey+'"></iframe>');

  });

  $("#fullscreen").click(function(){
    var visurl = "";
    var vistype = $("#visselect").val();

    // Added in custom action for multigraph
    // if the vis type is multigraph then we construct
    // the visurl with multigraph?id=1
    if (vistype=="multigraph")
    { 
      visurl = "multigraph?mid="+multigraph;
    }
    else
    {
      visurl += path+"vis/"+vistype;
      $(".options").each(function() {
        if ($(this).val()) visurl += "&"+$(this).attr("id")+"="+$(this).val();
      });
    }
    $(window.location).attr('href',visurl);
  });

  function draw_options(box_options)
  {
    // Build options table html
    var options_html = "<table>";
    for (z in box_options)
    {
      options_html += "<tr><td style='width:100px'><b>"+box_options[z][0]+":</b></td>";
 
      var type = box_options[z][1];

      if (type == 1 || type == 2 || type == 3) 
      {
        options_html += "<td>"+select_feed(box_options[z][0], feedlist, type)+"</td>";
      }
      else
      {
        options_html += "<td><input style='width:120px' class='options' id='"+box_options[z][0]+"' type='text' value='"+box_options[z][2]+"' / ></td>";
      }
      options_html += "</tr>";
    }
    options_html += "</table>";
    $("#box-options").html(options_html);
  }

  // Create a drop down select box with a list of feeds.
  function select_feed(id, feedlist, type)
  {
    var out = "<select style='width:120px' id='"+id+"' class='options' otype='feed'>";
    for (i in feedlist)
    {
      if (feedlist[i]['datatype']==type) out += "<option value='"+feedlist[i]['id']+"' public='"+feedlist[i]['public']+"'>"+feedlist[i]['id']+": "+feedlist[i]['name']+"</option>";
    }
    out += "</select>";
    return out;
  }

</script>
