<?php
/*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/
  
//require_once "Includes/messages.php";
  
global $path, $session;
?>

<script type="text/javascript" src="<?php print $path; ?>Lib/flot/jquery.min.js"></script>

<div style="float:right;"><a href="api">Input API Help</a></div>

<h2><?php echo _("Inputs"); ?></h2>

<?php if ($inputs) { ?>
    


<div align="right">
  <a href="<?php echo $path; ?>input/list" title="<?php echo _("Node view"); ?>"><i class="icon-th"></i></a>
</div>

<div id="inputlist"></div>

<script type="text/javascript">

  var path =  "<?php echo $path; ?>";
  var inputs = <?php echo json_encode($inputs); ?>;

  var nodevis = {};

  draw_inputs(inputs);

  update_list();
  setInterval(update_list,10000);

  function update_list()
  {
    $.ajax({                                      
      url: path+"input/node.json",                
      dataType: 'json',
      async: false,
      success: function(data) 
      { 
        draw_inputs(data);
      }
    });
  }

  function draw_inputs(data)
  {
        inputs = data; 

        var nodes = [];
        for (z in inputs)
        {
          var nodeid = 0; if (inputs[z]['nodeid']!=0) nodeid = inputs[z]['nodeid'];
          if (!nodes[nodeid]) nodes[nodeid] = [];
          nodes[nodeid].push(inputs[z]);
          if (nodevis[nodeid]==undefined) {nodevis[nodeid] = false;} 
        }

        console.log(nodevis);
 
        var out = "";

        for (z in nodes)
        {
          out += "<div style='background-color:#eee; margin-bottom:10px; border: 1px solid #ddd'><div style='padding:10px;  border-top: 1px solid #fff'>";
          out += "<i id='iconp"+z+"' node='"+z+"' class='icon-plus' "; if (nodevis[z]==true) out += "style='display:none;'"; out+="></i>";
          out += "<i id='iconn"+z+"' node='"+z+"' class='icon-minus' "; if (nodevis[z]==false) out += "style='display:none;'"; out+="></i>";
          out += "<b>Node "+z+"</b></div>";

          out += "<table ";
          if (nodevis[z]==false) out += "style='display:none;'";
          out += " class='catlist' id='node"+z+"'><tr>";
          out += "<th><span ><?php echo _('Name'); ?></span></th><th><?php echo _('Updated'); ?></th><th><?php echo _('Value'); ?></th></tr>";

          for (i in nodes[z])
          {
            out += "<tr class='d"+(i & 1)+"' >";
            out += "<td ><form action='../input/process/list.html' method='get' style='margin:0px;'><input type='hidden' name='inputid' value='"+nodes[z][i][0]+"'><input type='submit' value='"+nodes[z][i][1]+"' class='button05' style='width:150px'/ ></form></td>";   

            var now = (new Date()).getTime();
            var update = (new Date(nodes[z][i][2])).getTime();
            var lastupdate = (now-update)/1000;

            var secs = (now-update)/1000;
            var mins = secs/60;
            var hour = secs/3600

            var updated = secs.toFixed(0)+"s ago";
            if (secs>180) updated = mins.toFixed(0)+" mins ago";
            if (secs>(3600*2)) updated = hour.toFixed(0)+" hours ago";
            if (hour>24) updated = "inactive";

            var color = "rgb(255,125,20)";
            if (secs<60) color = "rgb(240,180,20)";
            if (secs<25) color = "rgb(50,200,50)";
            out += "<td style='color:"+color+";'>"+updated+"</td><td>"+nodes[z][i][3]+"</td></tr>";
   
          }

          out += "</table></div>";
        }

        out += "<br><a href='../input/autoconfigure'><?php echo _('Autoconfigure inputs'); ?></a>";
        $("#inputlist").html(out);

        $(".icon-plus").click(function(){
          var nid = $(this).attr("node");

          $("#node"+nid).show();
          $(this).hide();
          $("#iconn"+nid).show();
          nodevis[nid]=true;
        });

        $(".icon-minus").click(function(){
          var nid = $(this).attr("node");
          
          $("#node"+nid).hide();
          $(this).hide();
          $("#iconp"+nid).show();
          nodevis[nid]=false;
        });
  }
</script>

<?php } else { ?>

<div class="alert alert-block">
<h4 class="alert-heading">No inputs created</h4>
<p>Inputs is the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.</p>
</div>

<p><b>To connect up a NanodeRF:</b></p>
<p>1) Download and open the <a href="https://github.com/openenergymonitor/NanodeRF/NanodeRF_multinode" >NanodeRF_multinode</a> firmware.</p>
<p>2) Set line 83 to: <b>char apikey[] = "<?php echo get_apikey_write($session['userid']); ?>";</b></p>
<p>3) Upload the firmware to your NanodeRF.</p>


<?php } ?> 
