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
<script type='text/javascript' src="<?php echo $path; ?>Modules/vis/vis/vis_render.js"></script>

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
  var feedlist = <?php echo json_encode($feedlist); ?>;
  var widgets = vis_widgetlist();

  var apikey = "<?php echo $apikey; ?>";

  var out = '<select id="visselect" style="width:120px; margin:0px;">';
  for (z in widgets)
  {
    out += "<option value='"+z+"' >"+z+"</option>";
  }
  out += '</select>';
  $("#select").html(out);

  draw_options(widgets['realtime']['options'], widgets['realtime']['optionstype']);

  $("#visselect").click(function(){
    draw_options(widgets[$(this).val()]['options'], widgets[$(this).val()]['optionstype']);
  });

  $("#viewbtn").click(function(){
    var visurl = "";
    var vistype = $("#visselect").val();
    visurl += path+"vis/"+vistype;
    $(".options").each(function() {
      if ($(this).val()) visurl += "&"+$(this).attr("id")+"="+$(this).val();
    });

    $("#visiframe").html('<iframe style="width:580px; height:400px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visurl+'&embed=1"></iframe>');

    $("#embedcode").val('<iframe style="width:580px; height:400px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visurl+'&embed=1&apikey='+apikey+'"></iframe>');
  });

  $("#fullscreen").click(function(){
    var visurl = "";
    var vistype = $("#visselect").val();
    visurl += path+"vis/"+vistype;
    $(".options").each(function() {
      if ($(this).val()) visurl += "&"+$(this).attr("id")+"="+$(this).val();
    });
    $(window.location).attr('href',visurl);
  });

  function draw_options(box_options, options_type)
  {
    // Build options table html
    var options_html = "<table>";
    for (z in box_options)
    {
      options_html += "<tr><td style='width:100px'><b>"+box_options[z]+":</b></td>";

      if (options_type && options_type[z] == "feed-inst") 
      {
        options_html += "<td>"+select_feed(box_options[z], feedlist, 1)+"</td>";
      }

      else if (options_type && options_type[z] == "feed-daily") 
      {
        options_html += "<td>"+select_feed(box_options[z], feedlist, 2)+"</td>";
      }

      else if (options_type && options_type[z] == "feed-hist") 
      {
        options_html += "<td>"+select_feed(box_options[z], feedlist, 3)+"</td>";
      }

      else
      {
        options_html += "<td><input style='width:120px' class='options' id='"+box_options[z]+"' type='text' / ></td>";
      }
      options_html += "</tr>";
    }
    options_html += "</table>";
    $("#box-options").html(options_html);
  }

  // Create a drop down select box with a list of feeds.
  function select_feed(id, feedlist, type)
  {
    var out = "<select style='width:120px' id='"+id+"' class='options' >";
    for (i in feedlist)
    {
      if (feedlist[i]['datatype']==type) out += "<option value='"+feedlist[i]['id']+"' >"+feedlist[i]['id']+": "+feedlist[i]['name']+"</option>";
    }
    out += "</select>";
    return out;
  }

</script>
