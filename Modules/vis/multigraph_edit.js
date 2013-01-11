/*
 multigraph_edit.js - Licence: GNU GPL Affero, Author: Trystan Lea

 draws multigraph feedlist table and handles feedlist table dynamic
 actions such as add a feed, left, right, fill.
*/
var movingtime = 0;

function draw_multigraph_feedlist_editor()
{
  var out = "<table class='catlist' style='table-layout:fixed; width:300px;' >";
  out += "<tr><th style='width:120px;' >Feed</th><th>Left</th><th>Right</th><th>Fill</th><th style='width:46px;'></th></tr>";

  for (z in multigraph_feedlist)
  {
    out += "<tr>";

    out += "<td style='word-wrap:break-word;'>"+multigraph_feedlist[z]['name']+"</td>";

    var checked = ""; if (multigraph_feedlist[z]['left']) checked = "checked";
    out += "<td><input listid='"+z+"' class='left' type='checkbox' "+checked+" / ></td>";

    var checked = ""; if (multigraph_feedlist[z]['right']) checked = "checked";
    out += "<td><input listid='"+z+"' class='right' type='checkbox' "+checked+" / ></td>";

    var checked = ""; if (multigraph_feedlist[z]['fill']) checked = "checked";
    out += "<td><input listid='"+z+"' class='fill' type='checkbox' "+checked+" / ></td>";

    out += "<td><i listid='"+z+"' class='icon-remove'></i></td>";
    out += "</tr>";
  }

  out += "<tr><td><select id='feedselect' style='width:120px;'>";
  for (z in feedlist)
  {
    if (feedlist[z]['datatype']==1 || feedlist[z]['datatype']==2)
    {
      out += "<option value='"+feedlist[z]['id']+"' >"+feedlist[z]['name']+"</options>";
    }
  }
  out += "</select></td>";
  out += "<td></td>";
  out += "<td></td>";
  out += "<td></td>";
  out += "<td><input id='add' type='button' class='button05' value='Add'/ ></td>";
  out += "</tr></table>";
  out += '<br><p style="color:#444; ">Delete multigraph&nbsp;&nbsp;<i class="icon-trash" mid="'+multigraph+'"></p>';
  $("#feedtable").html(out);

  $("#add").click(function(){
    var feedid = $("#feedselect").val();
    var left = $("#addleft").attr("checked");
    var right = $("#addright").attr("checked");
    var fill = $("#addfill").attr("checked");
    multigraph_feedlist.push({'id':feedid,'name':get_feed_name(feedid),'datatype':get_feed_datatype(feedid),'left':left,'right':right,'fill':fill });
    draw_multigraph_feedlist_editor();
  });

  $(".left").click(function(){ 
    var z = $(this).attr('listid');
    multigraph_feedlist[z]['left'] = $(this).attr("checked");
    if (multigraph_feedlist[z]['left'] == true && multigraph_feedlist[z]['right'] == true) multigraph_feedlist[z]['right'] = false;
    $(".right[listid="+z+"]").attr("checked",false); 
    update_multigraph_feedlist();
    vis_feed_data();
  });

  $(".right").click(function(){
    var z = $(this).attr('listid');
    multigraph_feedlist[z]['right'] = $(this).attr("checked");
    if (multigraph_feedlist[z]['left'] == true && multigraph_feedlist[z]['right'] == true) multigraph_feedlist[z]['left'] = false;
    $(".left[listid="+z+"]").attr("checked",false); 
    update_multigraph_feedlist();
    vis_feed_data();
  });

  $(".fill").click(function(){
    var z = $(this).attr('listid');
    multigraph_feedlist[z]['fill'] = $(this).attr("checked");
    update_multigraph_feedlist();
    vis_feed_data();
  });

  $(".icon-remove").click(function(){
    var z = $(this).attr('listid');
    multigraph_feedlist.splice(z,1);
    draw_multigraph_feedlist_editor();
    update_multigraph_feedlist();
    vis_feed_data();
  });

  $(".icon-trash").click(function(){
    var mid = $(this).attr('mid');
    $.ajax({                                      
      type: "GET",
      url: path+"vis/multigraph/delete.json?id="+mid,
      dataType: 'json',
      async: false,
      success: function(data){}
    });
    window.location = "list";
  });
}

function get_feed_name(id)
{
  for (z in feedlist)
  {
    if (feedlist[z]['id'] == id) return feedlist[z]['name'];
  }
}

function get_feed_datatype(id)
{
  for (z in feedlist)
  {
    if (feedlist[z]['id'] == id) return feedlist[z]['datatype'];
  }
}

function update_multigraph_feedlist()
{
  // Save multigraph view start and end time to feedlist array
  multigraph_feedlist[0].timeWindow = end - start;
  //Always make multigraph update to latest end time
  //if (movingtime) multigraph_feedlist[0].end = 0; else multigraph_feedlist[0].end = end;
  multigraph_feedlist[0].end = 0;
  movingtime = 0;

  $.ajax({                                      
    type: "GET",
    url: path+"vis/multigraph/set.json?id="+multigraph+"&feedlist="+JSON.stringify(multigraph_feedlist),   
    dataType: 'json',
    async: false,
    success: function(data){}
  });
}
