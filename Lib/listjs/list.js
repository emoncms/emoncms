var list;

function listjs(_list)
{
  list = _list;

  $(document).ready(function(){
    draw_list(); 
  });

  updatetimer = setInterval(update_list,list['updaterate']);
}

function update_list()
{
    $.ajax({                                      
      url: list['path']+list['controller']+"/"+list['listaction']+".json",                
      dataType: 'json',
      success: function(items) 
      { 
        list['items'] = items;
        draw_list();
      }
    });
}

function draw_list()
{
        var feeds = {};
        var groups = {};
        // 1) Sort into associative array by rowid
        // 2) Create associative array of groups with array of id's that belong to group
        for (z in list['items'])
        {
          var rowid = list['items'][z]['id'];
          var group = list['items'][z][list['groupby']];

          if (!groups[group]) groups[group] = [];
          groups[group].push(list['items'][z]['id']);

          if (!list['group_properties'][group]) list['group_properties'][group] = {};
          if (list['group_properties'][group]['expanded']==undefined) {list['group_properties'][group]['expanded'] = false;} 

          feeds[rowid] = list['items'][z];
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
          out += "<i id='iconp"+z+"' node='"+z+"' class='icon-plus' "; if (list['group_properties'][z]['expanded']==true) out += "style='display:none;'"; out+="></i>";
          out += "<i id='iconn"+z+"' node='"+z+"' class='icon-minus' "; if (list['group_properties'][z]['expanded']==false) out += "style='display:none;'"; out+="></i>";
          if (z != 'null' && z != 0) out += " <b>"+list['group_prefix']+z+"</b>"; else  out += " <b>No Group</b>";
          if (list['group_properties'][z]['description']) out += ":&nbsp;"+list['group_properties'][z]['description'];
          out += "</div>";

          // Start of items table
          out += "<table ";
          if (list['group_properties'][z]['expanded']==false) out += "style='display:none;'";
          out += " class='catlist' id='node"+z+"'><tr>";

          // ---------------------------------------------------------------------------------------------------------
          // 2) Draw group items table headings
          // ---------------------------------------------------------------------------------------------------------
          for (n in list['fields']) out += "<th>"+n+"</th>";
          out += "<th></th></tr>";

          // ---------------------------------------------------------------------------------------------------------
          // 3) Draw each row in the list according to list defenition
          // ---------------------------------------------------------------------------------------------------------          
          for (i in groups[z])
          { 
            var rowid = groups[z][i];

            out += "<tr class='d"+(i & 1)+"' >";

            for (field in list['fields'])
            {
              out += "<td id='"+field+rowid+"' >";

              // if button type: add button surround
              if (list['fields'][field]['button'] != undefined) out += "<a style='text-decoration:none;' href='"+list['path']+list['fields'][field]['button']+rowid+"'><div class='button05' style='width:150px; margin: 0 auto;'>";

              if (list['fields'][field]['format'] == undefined) out += feeds[rowid][field];
              if (list['fields'][field]['format'] == 'updated') out += list_format_updated(feeds[rowid]['time']);
              if (list['fields'][field]['format'] == 'value') out += list_format_value(feeds[rowid][field]);
              if (list['fields'][field]['format'] == 'select') out += "<span style='font-size:12px'>"+list['fields'][field]['options'][feeds[rowid][field]]+"</span>";

              // Toggle icon is an icon that can be toggled between two icon types denoting two states, true or false
              // here we draw the icon depending on the feed status and we make the icon clickable
              if (list['fields'][field]['format'] == "toggleicon") 
              {
                if (feeds[rowid][field]==true) out += "<span class='toggleicon' rowid='"+rowid+"' field='"+field+"' state=0 ><i class='icon-"+list['fields'][field]['icon-true']+"'></i></span>";
                if (feeds[rowid][field]==false || feeds[rowid][field]==null) out += "<span class='toggleicon' rowid='"+rowid+"' field='"+field+"' state=1 ><i class='icon-"+list['fields'][field]['icon-false']+"'></i></span>";
              }
         
              if (list['fields'][field]['button'] != undefined) out += "</div></a>";
              out += "</td>";
            }
            out += "<td>";
            if (list['editable']) out += "<span class='edit-row' rowid='"+rowid+"' id='edit"+rowid+"' mode=0><i class='icon-pencil'></i></span>&nbsp;&nbsp;";
            if (list['deletable']) out += "<span class='delete-row' rowid='"+rowid+"' ><i class='icon-remove'></i></span>";
            if (list['restoreable']) out += "<span class='restore-row' rowid='"+rowid+"' ><i class='icon-share'></i></span>";

            out += "</td></tr>";
          }

          out += "</table></div>";
        }

        // Insert generated html in element
        $("#"+list['element']).html(out);


        $(".toggleicon").click(function()
        {
          var rowid = $(this).attr('rowid');
          var state = $(this).attr("state");
          var field = $(this).attr("field");

          // Send field data back to server to be saved.
          $.ajax({                                      
            url: list['path']+list['controller']+"/set.json?id="+rowid+"&field="+field+"&value="+state, async:false
          });

          update_list();
        });

        if (list['editable']) 
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

            for (field in list['fields'])
            {
              if (list['fields'][field]['input'] == "text") $("#"+field+rowid).html("<input type='edit' style='width:50px;' value='"+feeds[rowid][field]+"' / >");

              if (list['fields'][field]['input'] == "select") 
              {
                // Create option list
                var options = "";
                for (option in list['fields'][field]['options']) {
                  options += "<option ";
                  if (feeds[rowid]['datatype']==option) options += "selected";
                  options += " value='"+option+"' >"+list['fields'][field]['options'][option]+"</option>";
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

            for (field in list['fields'])
            {
              if (list['fields'][field]['input']=="text")
              {
                feeds[rowid][field] = $("#"+field+rowid+" input").val();
                $("#"+field+rowid).html(feeds[rowid][field]);
              }

              if (list['fields'][field]['input']=="select")
              {
                feeds[rowid][field] = $("#"+field+rowid+" select").val();
                $("#"+field+rowid).html("<span style='font-size:12px'>"+list['fields'][field]['options'][feeds[rowid][field]]+"</span>");
              }

              // Send field data back to server to be saved.
              $.ajax({                                      
                url: list['path']+list['controller']+"/set.json?id="+rowid+"&field="+field+"&value="+feeds[rowid][field], async:false
              });
            }

            update_list();
            updatetimer = setInterval(update_list,list['updaterate']);
          }
        });

        }

        // Delete feed
        $(".delete-row").click(function() {
          var rowid = $(this).attr('rowid');
            $.ajax({                                      
              url: list['path']+list['controller']+"/delete.json?id="+rowid, async: false
            });
            update_list();
        });

        // Delete feed
        $(".restore-row").click(function() {
          var rowid = $(this).attr('rowid');
            $.ajax({                                      
              url: list['path']+list['controller']+"/restore.json?id="+rowid, async: false
            });
            update_list();
        });

        // Expand group
        $(".icon-plus").click(function(){
          var nid = $(this).attr("node");

          $("#node"+nid).show();
          $(this).hide();
          $("#iconn"+nid).show();
          list['group_properties'][nid]['expanded']=true;
        });

        // Minimize group
        $(".icon-minus").click(function(){
          var nid = $(this).attr("node");
          
          $("#node"+nid).hide();
          $(this).hide();
          $("#iconp"+nid).show();
          list['group_properties'][nid]['expanded']=false;
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


