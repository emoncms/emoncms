<?php
  /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  */

  global $path;
?>

<script type="text/javascript" src="<?php print $path; ?>Lib/flot/jquery.min.js"></script>

<div style="float:right;"><a href="api"><?php echo _("Feed API Help");?></a></div>
<h2><?php if ($del) echo _('Deleted feeds'); else echo _('Feeds'); ?></h2>

<div id="feedlist"></div>
<?php if (!$del) { ?><br><a href="?del=1" class="btn btn-danger"><?php echo _('Deleted feeds'); ?></a><?php } ?>
<?php if ($del && $feeds) { ?><br><a href="emptybin"><?php echo _('Delete feeds permanently'); ?></a> (no confirmation)<?php } ?>

<script type="text/javascript">
  var path =  "<?php echo $path; ?>";
  var feeds = <?php echo json_encode($feeds); ?>;
  var del = <?php echo $del; ?>;

  var tagvis = {};

  update_list();
  var updatetimer = setInterval(update_list,2000);

  function update_list()
  {
    $.ajax({                                      
      url: path+"feed/list.json?del="+del,                
      dataType: 'json',
      success: function(data) 
      { 
       // feeds = data;
        var feeds = {};
        var tags = {};
        for (z in data)
        {
          var feedid = data[z]['id'];
          var tag = 0; if (data[z]['tag']!=0) tag = data[z]['tag'];
          if (!tags[tag]) tags[tag] = [];
          tags[tag].push(data[z]);
          if (tagvis[tag]==undefined) {tagvis[tag] = true;} 
          feeds[feedid] = data[z];
        }

        var out = "";

        for (z in tags)
        {
          out += "<div style='background-color:#dedede; padding: 2px 2px 2px 2px; margin-bottom:10px;'><div style='padding:10px'>";
          out += "<i id='iconp"+z+"' node='"+z+"' class='icon-plus' "; if (tagvis[z]==true) out += "style='display:none;'"; out+="></i>";
          out += "<i id='iconn"+z+"' node='"+z+"' class='icon-minus' "; if (tagvis[z]==false) out += "style='display:none;'"; out+="></i>";
          out += "<b>"+z+"</b></div>";

          out += "<table ";
          if (tagvis[z]==false) out += "style='display:none;'";
          out += " class='catlist' id='node"+z+"'><tr>";
          out += "<th>id</th><th><?php echo _('Name'); ?></th><th><?php echo _('Tag'); ?></th><th><?php echo _('Type'); ?></th><th><?php echo _('Size'); ?></th><th><?php echo _('Updated'); ?></th><th><?php echo _('Value'); ?></th><th></th></tr>";

          for (i in tags[z])
          {
            var feedid = tags[z][i]['id'];

            out += "<tr class='d"+(i & 1)+"' ><td>"+tags[z][i]['id']+"</td>";
            out += "<td id='name"+feedid+"'>"+feeds[feedid]['name']+"</td>";   

            var now = (new Date()).getTime();
            var update = (new Date(feeds[feedid]['time'])).getTime();
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

            var value = 0;
            if (feeds[feedid]['value']>10) value = (1*feeds[feedid]['value']).toFixed(1);
            if (feeds[feedid]['value']>100) value = (1*feeds[feedid]['value']).toFixed(0);
            if (feeds[feedid]['value']<10) value = (1*feeds[feedid]['value']).toFixed(2);

            out += "<td id='tag"+feedid+"'>"+feeds[feedid]['tag']+"</td>";
            out += "<td id='datatype"+feedid+"'>"+feeds[feedid]['datatype']+"</td>";
            out += "<td>"+(feeds[feedid]['size']/1000).toFixed(1)+" KiB</td>";
            out += "<td style='color:"+color+";'>"+updated+"</td>";
            out += "<td>"+value+"</td>";

            out += "<td><span class='editfeed' feed='"+feedid+"' id='edit"+feedid+"' edit=0><i class='icon-pencil'></i></span>&nbsp;&nbsp;<span feed='"+feedid+"' ";
            if (del==0) out += "class='deletefeed' ><i class='icon-remove'></i>";
            if (del==1) out += "class='restorefeed' ><i class='icon-share'></i>"; 
            out +="</span></td></tr>";
   
          }

          out += "</table></div>";
        }

        $("#feedlist").html(out);

        $(".editfeed").click(function() {
          clearInterval(updatetimer);
          var feedid = $(this).attr('feed');
          var mode = $(this).attr("edit");

          if (mode == 0 )
          {
            $(this).attr("edit",1);
            $("#edit"+feedid).html("<i class='icon-ok'></i>");
            $("#tag"+feedid).html("<input type='edit' style='width:50px;' value='"+feeds[feedid]['tag']+"' / >");
            $("#name"+feedid).html("<input type='edit' style='width:50px;' value='"+feeds[feedid]['name']+"' / >");
            $("#datatype"+feedid).html("<input type='edit' style='width:50px;' value='"+feeds[feedid]['datatype']+"' / >");
          }
          
          if (mode == 1 )
          {
            $(this).attr("edit",0);

            feeds[feedid]['name'] = $("#name"+feedid+" input").val();
            feeds[feedid]['tag'] = $("#tag"+feedid+" input").val();
            feeds[feedid]['datatype'] = $("#datatype"+feedid+" input").val();

            $("#edit"+feedid).html("<i class='icon-pencil'></i>");

            $("#tag"+feedid).html(feeds[feedid]['tag']);
            $("#name"+feedid).html(feeds[feedid]['name']);
            $("#datatype"+feedid).html(feeds[feedid]['datatype']);

            $.ajax({                                      
              url: path+"feed/set.json?id="+feedid+"&field=name&value="+feeds[feedid]['name']
            });

            $.ajax({                                      
              url: path+"feed/set.json?id="+feedid+"&field=tag&value="+feeds[feedid]['tag']
            });

            $.ajax({                                      
              url: path+"feed/set.json?id="+feedid+"&field=datatype&value="+feeds[feedid]['datatype']
            });

            update_list();
            updatetimer = setInterval(update_list,2000);
          }
        });

        $(".deletefeed").click(function() {
          var feedid = $(this).attr('feed');
            $.ajax({                                      
              url: path+"feed/delete.json?id="+feedid
            });
            update_list();
        });

        $(".restorefeed").click(function() {
          var feedid = $(this).attr('feed');
            $.ajax({                                      
              url: path+"feed/restore.json?id="+feedid
            });
            update_list();
        });
        
    }
  });
}

</script>
