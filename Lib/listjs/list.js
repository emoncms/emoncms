var element = "";
var groupby;
var updatetimer;
var updaterate;
var editable = false;

function listjs(_element, _groupby, items, fields, group_properties, _updaterate)
{
  element = _element;
  updaterate = _updaterate;
  groupby = _groupby;

  for (n in fields) { if (fields[n]['input']!=undefined) editable = true; }

  $(document).ready(function(){
    draw_list(groupby, items, fields, group_properties); 
  });

  updatetimer = setInterval(update_list,updaterate);
}

function update_list()
{
    $.ajax({                                      
      url: path+controller+"/"+listaction+".json",                
      dataType: 'json',
      success: function(items) 
      { 
        draw_list(groupby, items, fields, group_properties);
      }
    });
}

function draw_list(groupby, items, fields, group_properties)
{
        var feeds = {};
        var groups = {};
        // 1) Sort into associative array by rowid
        // 2) Create associative array of groups with array of id's that belong to group
        for (z in items)
        {
          var rowid = items[z]['id'];
          var group = items[z][groupby];

          if (!groups[group]) groups[group] = [];
          groups[group].push(items[z]['id']);

          if (!group_properties[group]) group_properties[group] = {};
          if (group_properties[group]['expanded']==undefined) {group_properties[group]['expanded'] = false;} 

          feeds[rowid] = items[z];
        }

        // ---------------------------------------------------------------------------------------------------------
        // Draw list
        // ---------------------------------------------------------------------------------------------------------
        var out = "";
        for (z in groups)
        {
          // ---------------------------------------------------------------------------------------------------------
          // 1) Draw group box
          // ---------------------------------------------------------------------------------------------------------
          out += "<div style='background-color:#eee; margin-bottom:10px; border: 1px solid #ddd'><div style='padding:10px;  border-top: 1px solid #fff'>";
          out += "<i id='iconp"+z+"' node='"+z+"' class='icon-plus' "; if (group_properties[z]['expanded']==true) out += "style='display:none;'"; out+="></i>";
          out += "<i id='iconn"+z+"' node='"+z+"' class='icon-minus' "; if (group_properties[z]['expanded']==false) out += "style='display:none;'"; out+="></i>";
          if (z != 'null' && z != 0) out += " <b>"+group_prefix+z+"</b>"; else  out += " <b>No Group</b>";
          if (group_properties[z]['description']) out += ":&nbsp;"+group_properties[z]['description'];
          out += "</div>";

          // Start of items table
          out += "<table ";
          if (group_properties[z]['expanded']==false) out += "style='display:none;'";
          out += " class='catlist' id='node"+z+"'><tr>";

          // ---------------------------------------------------------------------------------------------------------
          // 2) Draw group items table headings
          // ---------------------------------------------------------------------------------------------------------
          for (n in fields) out += "<th>"+n+"</th>";
          out += "<th></th></tr>";

          // ---------------------------------------------------------------------------------------------------------
          // 3) Draw each row in the list according to list defenition
          // ---------------------------------------------------------------------------------------------------------          
          for (i in groups[z])
          { 
            var rowid = groups[z][i];

            out += "<tr class='d"+(i & 1)+"' >";

            for (field in fields)
            {
              out += "<td id='"+field+rowid+"' >";

              // if button type: add button surround
              if (fields[field]['button'] != undefined) out += "<a style='text-decoration:none;' href='"+path+fields[field]['button']+rowid+"'><div class='button05' style='width:150px; margin: 0 auto;'>";

              if (fields[field]['format'] == undefined) out += feeds[rowid][field];
              if (fields[field]['format'] == 'updated') out += list_format_updated(feeds[rowid]['time']);
              if (fields[field]['format'] == 'value') out += list_format_value(feeds[rowid][field]);
              if (fields[field]['format'] == 'select') out += "<span style='font-size:12px'>"+fields[field]['options'][feeds[rowid][field]]+"</span>";
         
              if (fields[field]['button'] != undefined) out += "</div></a>";
              out += "</td>";
            }
            out += "<td>";
            if (editable) out += "<span class='edit-row' rowid='"+rowid+"' id='edit"+rowid+"' mode=0><i class='icon-pencil'></i></span>&nbsp;&nbsp;";
            if (deletable) out += "<span class='delete-row' rowid='"+rowid+"' ><i class='icon-remove'></i></span>";
            if (restoreable) out += "<span class='restore-row' rowid='"+rowid+"' ><i class='icon-share'></i></span>";

            out += "</td></tr>";
          }

          out += "</table></div>";
        }

        // Insert generated html in element
        $("#"+element).html(out);


        if (editable) 
        {
 
        $(".edit-row").click(function() {
          clearInterval(updatetimer);
          var rowid = $(this).attr('rowid');
          var mode = $(this).attr("mode");

          // ---------------------------------------------------------------------------------------------------------
          // Mode: 0 EDIT MODE
          //
          // Generates input edit boxed and input select dropdown menu's from field defenition
          // ---------------------------------------------------------------------------------------------------------
          if (mode == 0 )
          {
            $(this).attr("mode",1);
            $("#edit"+rowid).html("<i class='icon-ok'></i>");

            for (field in fields)
            {
              if (fields[field]['input'] == "text") $("#"+field+rowid).html("<input type='edit' style='width:50px;' value='"+feeds[rowid][field]+"' / >");

              if (fields[field]['input'] == "select") 
              {
                // Create option list
                var options = "";
                for (option in fields[field]['options']) {
                  options += "<option ";
                  if (feeds[rowid]['datatype']==option) options += "selected";
                  options += " value='"+option+"' >"+fields[field]['options'][option]+"</option>";
                }
                $("#"+field+rowid).html("<select style='width:110px;'>"+options+"</select>");
              }
            }
          }

          // ---------------------------------------------------------------------------------------------------------
          // Mode: 1 SAVE VALUES
          // ---------------------------------------------------------------------------------------------------------
          if (mode == 1 )
          {
            $(this).attr("mode",0);
            $("#edit"+rowid).html("<i class='icon-pencil'></i>");

            for (field in fields)
            {
              if (fields[field]['input']=="text")
              {
                feeds[rowid][field] = $("#"+field+rowid+" input").val();
                $("#"+field+rowid).html(feeds[rowid][field]);
              }

              if (fields[field]['input']=="select")
              {
                feeds[rowid][field] = $("#"+field+rowid+" select").val();
                $("#"+field+rowid).html("<span style='font-size:12px'>"+fields[field]['options'][feeds[rowid][field]]+"</span>");
              }

              // Send field data back to server to be saved.
              $.ajax({                                      
                url: path+controller+"/set.json?id="+rowid+"&field="+field+"&value="+feeds[rowid][field], async:false
              });
            }

            update_list();
            updatetimer = setInterval(update_list,updaterate);
          }
        });

        }

        // Delete feed
        $(".delete-row").click(function() {
          var rowid = $(this).attr('rowid');
            $.ajax({                                      
              url: path+controller+"/delete.json?id="+rowid, async: false
            });
            update_list();
        });

        // Delete feed
        $(".restore-row").click(function() {
          var rowid = $(this).attr('rowid');
            $.ajax({                                      
              url: path+controller+"/restore.json?id="+rowid, async: false
            });
            update_list();
        });

        // Expand group
        $(".icon-plus").click(function(){
          var nid = $(this).attr("node");

          $("#node"+nid).show();
          $(this).hide();
          $("#iconn"+nid).show();
          group_properties[nid]['expanded']=true;
        });

        // Minimize group
        $(".icon-minus").click(function(){
          var nid = $(this).attr("node");
          
          $("#node"+nid).hide();
          $(this).hide();
          $("#iconp"+nid).show();
          group_properties[nid]['expanded']=false;
        });
}


// Calculate and color updated time
function list_format_updated(time)
{
  var now = (new Date()).getTime();
  var update = (new Date(time)).getTime();
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

  return "<span style='color:"+color+";'>"+updated+"</span>";
}

// Format value dynamically 
function list_format_value(value)
{
  if (value>10) value = (1*value).toFixed(1);
  if (value>100) value = (1*value).toFixed(0);
  if (value<10) value = (1*value).toFixed(2);
  return value;
}


