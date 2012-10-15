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
  
global $path;
?>

<script type="text/javascript" src="<?php print $path; ?>Lib/flot/jquery.min.js"></script>

<div style="float:right;"><a href="api">Input API Help</a></div>

<?php if ($inputs) { ?>
    
<h2><?php echo _("Node view"); ?></h2>

<div align="right">
  <!--<a href="#" title="<?php echo _("New dashboard"); ?>" onclick="$.ajax({type : 'POST',url :  path + 'dashboard/new.json  ',data : '',dataType : 'json',success : location.reload()});"><i class="icon-plus-sign"></i></a>-->
  <a href="<?php echo $path; ?>input/list" title="<?php echo _("Node view"); ?>"><i class="icon-th"></i></a>
  <!--<a href="<?php echo $path; ?>dashboard/list"><i class="icon-th-list"></i></a>-->
</div>

<div id="inputlist"></div>

<script type="text/javascript">

  var path =  "<?php echo $path; ?>";
  var inputs = <?php echo json_encode($inputs); ?>;

  var nodevis = {};

  update_list();
  setInterval(update_list,5000);

  function update_list()
  {
    $.ajax({                                      
      url: path+"input/node.json",                
      dataType: 'json',
      async: false,
      success: function(data) 
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
          out += "<div style='background-color:#dedede; padding: 2px 2px 2px 2px; margin-bottom:10px;'><div style='padding:10px'>";
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
    });
  }
</script>

<?php } ?>
