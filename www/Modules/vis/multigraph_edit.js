/*

  multigraph_edit.js - Licence: GNU GPL Affero, Author: Trystan Lea

  draws multigraph feedlist table and handles feedlist table dynamic
  actions such as add a feed, left, right, fill.

*/

var multigraph_id = 0;
var multigraph_feedlist = [];

var timeWindow = (3600000*24.0*7);				            // Initial time window
var start = ((new Date()).getTime())-timeWindow;		  // Get start time
var end = (new Date()).getTime();				              // Get end time

// This is used with multigraph.js to tell it to call a save request in multigraph_edit.js
// when the multigraph time window is changed.
var multigraph_editmode = true;

var movingtime = 0;

/* 

  Multigraph new and selector interface

*/

function multigraphGUI()
{
  load_events();

  $("#viewbtn").hide();
  var multigraphs = multigraph.getlist();
  $("#box-options").html(multigraphDropdown(multigraphs));
}

function multigraphDropdown(multigraphs)
{
  var options = "";
  for (z in multigraphs) options +="<option value='"+multigraphs[z]['id']+"'>Multigraph: "+multigraphs[z]['id']+"</option>";

  var out = "<div class='alert'>No multigraphs created yet, click new to create one:</div>";
  if (options) out = "<select id='midselector' style='width:150px'><option>Select multigraph:</option>"+options+"</select>";
  return out+"<button id='new-multigraph-button' class='btn btn-info' style='float:right'>New multigraph</button><div id='feedtable' ></div>";
}

/* 

  Multigraph table interface

*/

function draw_multigraph_feedlist_editor()
{
  if (!multigraph_feedlist) multigraph_feedlist = [];
  if (typeof multigraph_feedlist[0] !== 'undefined' && multigraph_feedlist[0]['end'] == 0
) movingtime=0;
  else movingtime=1;
 
  console.log("Moving time start: "+movingtime);

  var out = "<table class='table' style='table-layout:fixed; width:300px;' >";
  out += "<tr><th style='width:100px;' >Feed</th><th>Left</th><th>Right</th><th>Fill</th><th></th></tr>";

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
  out += "<td><input id='add' type='button' class='button05' value='Add'/ ></td></tr>";

  out += "<tr><td>Floating time</strong></td>";
  var checked = ""; if (typeof multigraph_feedlist[0] !== 'undefined' && multigraph_feedlist[0]['end'] == 0) checked = "checked";
  out += "<td><input id='movingtime' type='checkbox' "+checked+" / ></td>";
  out += "<td></td>";
  out += "<td></td>";
  out += "<td></td></tr>";

  out += "</table>";
  out += "<button id='delete-multigraph-button' class='btn btn-danger'><i class='icon-trash'></i> Delete multigraph</button>";
  out += "<button id='save-multigraph-button' class='btn btn-primary' style='float:right'>Save</button>";
  out += "<div id='saved' style='float:right; margin-top:5px; margin-right:10px;'>Saved</div>";

  $("#feedtable").html(out);
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

/*

  Events

*/

function load_events()
{
  var baseElement = "#box-options";

  $(baseElement).on("change","#midselector",function(event){
    multigraph_id = $(this).val();
    multigraph_feedlist = multigraph.get(multigraph_id);
    // Draw multigraph feedlist editor
    draw_multigraph_feedlist_editor();
    // Draw multigraph
    multigraph_init("#visiframe");
    vis_feed_data();
  });

  $(baseElement).on("click","#new-multigraph-button",function(event){
    multigraph.new();
    var multigraphs = multigraph.getlist();
    $("#box-options").html(multigraphDropdown(multigraphs));
  });

  $(baseElement).on("click","#add",function(event){
    var feedid = $("#feedselect").val();
    multigraph_feedlist.push({'id':feedid,'name':get_feed_name(feedid),'datatype':get_feed_datatype(feedid),'left':false,'right':false,'fill':false });
    draw_multigraph_feedlist_editor();
  });

  $(baseElement).on("click","#movingtime",function(event){
    if($(this)[0].checked) movingtime = 0;
    else movingtime=1;
    vis_feed_data();
    $("#saved").hide();
  });

  $(baseElement).on("click",".left",function(event){
    console.log("Click left:");
    console.log($(this)[0].checked);

    var z = $(this).attr('listid');
    multigraph_feedlist[z]['left'] = $(this)[0].checked;
    if (multigraph_feedlist[z]['left'] == true && multigraph_feedlist[z]['right'] == true) multigraph_feedlist[z]['right'] = false;
    $(".right[listid="+z+"]").attr("checked",false); 

    vis_feed_data();
    console.log(multigraph_feedlist);
    $("#saved").hide();
  });

  $(baseElement).on("click",".right",function(){
    console.log("Click right:");
    console.log($(this).attr("checked"));

    var z = $(this).attr('listid');
    multigraph_feedlist[z]['right'] = $(this)[0].checked;
    if (multigraph_feedlist[z]['left'] == true && multigraph_feedlist[z]['right'] == true) multigraph_feedlist[z]['left'] = false;
    $(".left[listid="+z+"]").attr("checked",false); 
    vis_feed_data();
    $("#saved").hide();
  });

  $(baseElement).on("click",".fill",function(){
    var z = $(this).attr('listid');
    multigraph_feedlist[z]['fill'] = $(this)[0].checked;
    vis_feed_data();
    $("#saved").hide();
  });

  $(baseElement).on("click",".icon-remove",function(){
    var z = $(this).attr('listid');
    multigraph_feedlist.splice(z,1);
    draw_multigraph_feedlist_editor();
    vis_feed_data();
    $("#saved").hide();
  });

  $(baseElement).on("click","#delete-multigraph-button",function(){
    multigraph.remove(multigraph_id);
    var multigraphs = multigraph.getlist();
    $("#box-options").html(multigraphDropdown(multigraphs));
  });

  $(baseElement).on("click","#save-multigraph-button",function(event){
    // Save multigraph view start and end time to feedlist array
    multigraph_feedlist[0].timeWindow = end - start;

    if (movingtime == 0) 
      multigraph_feedlist[0].end = 0; 
    else 
      multigraph_feedlist[0].end = end;

    console.log(movingtime);
    console.log(multigraph_feedlist[0].end);

    multigraph.set(multigraph_id,multigraph_feedlist);
    $("#saved").show();
  });
}
