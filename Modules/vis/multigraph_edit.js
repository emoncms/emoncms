/*

  multigraph_edit.js - Licence: GNU GPL Affero, Author: Trystan Lea

  draws multigraph feedlist table and handles feedlist table dynamic
  actions such as add a feed, left, right, fill.

*/

var multigraph_id = 0;
var multigraph_feedlist = [];
var multigraphs=[];
var multigraphs_name=[];

// This is used with multigraph.js to tell it to call a save request in multigraph_edit.js
// when the multigraph time window is changed.
var multigraph_editmode = true;

var movingtime = false;

var baseElement = "#box-options";

// Multigraph new and selector interface
function multigraphGUI()
{
  $("#viewbtn").hide();
  $(baseElement).html(multigraphDropdown());
  load_events();
}

function multigraphDropdown()
{
  multigraphs_name = [];
  multigraphs = multigraph.getlist();
  var options = "";
  for (z in multigraphs) {
    multigraphs_name[multigraphs[z]['id']] = multigraphs[z]['name'];
    options +="<option value='"+multigraphs[z]['id']+"'>"+multigraphs[z]['id']+": "+multigraphs[z]['name']+"</option>";
  }
  var out = "<div class='alert'>No multigraphs created yet, click new to create one:</div>";
  if (options){
    out = "<select id='multigraph-selector' class='form-control' style='width:160px'><option>Select multigraph:</option>"+options+"</select>";
  }
  return out+"<button id='multigraph-new-button' class='btn btn-info' style='float:right'>New multigraph</button><div id='feedtable' ></div>";
}

// Multigraph editor interface 
function draw_multigraph_feedlist_editor()
{
  if (!multigraph_feedlist) multigraph_feedlist = [];
  if (typeof multigraph_feedlist[0] !== 'undefined' && multigraph_feedlist[0]['end'] != 0)
    movingtime=false;
  else
    movingtime=true;

  console.log("Moving time start: "+movingtime);
  var out = "";
  out += '<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">';
  out += '<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h3 id="myModalLabel">Delete Multigraph</h3></div>';
  out += '<div class="modal-body"><p>Deleting a multigraph is permanent.<br>Make sure no Dashboard continue to use the deleted multigraph<br><br>Are you sure you want to delete?</p></div>';
  out += '<div class="modal-footer"><button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button><button id="confirmdelete" class="btn btn-primary">Delete permanently</button></div></div>';
  
  out += "<table class='table' style='table-layout:fixed; width:300px;' >";
  out += "<tr><th style='width:130px;' >Feed</th><th style='text-align: center;'>Left</th><th style='text-align: center;'>Right</th><th style='text-align: center;'>Fill</th><th style='padding:0px; width:30px;'></th></tr>";

  var publicfeed = 1;
  for (z in multigraph_feedlist)
  {
    out += "<tr>";

    out += "<td style='vertical-align:middle;word-wrap:break-word;'>"+multigraph_feedlist[z]['name']+"</td>";

    if (!multigraph_feedlist[z]['left'] && !multigraph_feedlist[z]['right'])  multigraph_feedlist[z]['left'] = true; // Default is left
    
    var checked = ""; if (multigraph_feedlist[z]['left']) checked = "checked";
    out += "<td style='text-align: center;'><input listid='"+z+"' class='left' type='checkbox' "+checked+" /></td>";

    var checked = ""; if (multigraph_feedlist[z]['right']) checked = "checked";
    out += "<td style='text-align: center;'><input listid='"+z+"' class='right' type='checkbox' "+checked+" /></td>";

    var checked = ""; if (multigraph_feedlist[z]['fill']) checked = "checked";
    out += "<td style='text-align: center;'><input listid='"+z+"' class='fill' type='checkbox' "+checked+" /></td>";

    out += "<td><a class='close'><i listid='"+z+"' id='multigraph-feed-remove-button' class='icon-remove'></i></a></td>";
    
    out += "</tr>";
    
    var setColour = ""; if (multigraph_feedlist[z]['lineColour']) setColour = multigraph_feedlist[z]['lineColour'];
    out += "<tr>";
    out += "<td style='text-align: right;vertical-align:middle;border-color:transparent;'>Line Colour</td>";
    out += "<td colspan='4' style='vertical-align:middle;'><input id='lineColour' listid='"+z+"' style='width:110px' type='color' value='#"+setColour+"'></td>";
    out += "</tr>";
    
    var checked = "checked"; if (!multigraph_feedlist[z]['skipmissing']) checked = "";
    out += "<tr>";
    out += "<td style='text-align: right;vertical-align:middle;border-color:transparent;'>Skip missing data</td>";
    out += "<td style='text-align: center;vertical-align:middle;'><input id='skipmissing'  listid='"+z+"' type='checkbox' "+checked+" /></td>";
    out += "<td colspan='3'></td>";
    out += "</tr>";

    if (publicfeed == 1) publicfeed = (get_feed_public(multigraph_feedlist[z]['id']));
  }
  var visurl = path+"vis/"+"multigraph?mid="+multigraph_id;
  if (publicfeed == 1) $("#embedcode").val('<iframe style="width:580px; height:400px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visurl+'&embed=1"></iframe>'); else $("#embedcode").val('Some of the feeds selected are not public, to embed a visualisation publicly first make the feeds that you want to use public.\n\nTo embed privately:\n\n<iframe style="width:580px; height:400px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visurl+'&embed=1&apikey='+apikey+'"></iframe>');

  out += "<tr>";
  out += "<td>"+select_feed('feedselect', feedlist, 0)+"</td>";
  out += "<td></td>";
  out += "<td></td>";
  out += "<td><input id='add' type='button' class='btn' value='Add'/ ></td>";
  out += "<td></td>";
  out += "</tr>";

  out += "<tr><td>Floating time</strong></td>";
  var checked = ""; if (movingtime) checked = "checked";
  out += "<td><input id='movingtime' type='checkbox' "+checked+" /></td>";
  out += "<td></td>";
  out += "<td></td>";
  out += "<td></td></tr>";

  out += "</table>";

  var name = "<div class='input-prepend'><span class='add-on' style='width: 70px; text-align: right;'>Name</span><input class='options' id='multigraph-name' value='"+multigraphs_name[multigraph_id]+"' type='text'></div>";

  out += name+"<button id='delete-multigraph-button' class='btn btn-danger'><i class='icon-trash'></i>Delete</button>";
  out += "<button id='save-multigraph-button' class='btn btn-success' style='float:right'>Not modified</button>";
  
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

function get_feed_public(id)
{
  for (z in feedlist)
  {
    if (feedlist[z]['id'] == id) return feedlist[z]['public'];
  }
}

function modified() {
  $("#save-multigraph-button").attr('class','btn btn-warning').text("Changed, press to save");
}

// Events
function load_events()
{
  $(baseElement).unbind();

  $(baseElement).on("change","#multigraph-selector",function(event){
    multigraph_id = $(this).val();
    multigraph_feedlist = multigraph.get(multigraph_id)['feedlist'];
    // Draw multigraph feedlist editor
    draw_multigraph_feedlist_editor();
    // Draw multigraph
    multigraph_init("#visiframe");
    vis_feed_data();
  });

  $(baseElement).on("click","#multigraph-new-button",function(event){
    multigraph_id = multigraph.new();
    $(baseElement).html(multigraphDropdown());            // Reload all
    $(baseElement + ' #multigraph-selector').val(multigraph_id);  // Refresh
    $(baseElement + ' #multigraph-selector').change();            // 
  });

  $(baseElement).on("click","#add",function(event){
    var feedid = $("#feedselect").val();
    multigraph_feedlist.push({'id':feedid,'name':get_feed_name(feedid),'datatype':get_feed_datatype(feedid),'left':false,'right':false,'fill':false,'end':0,'skipmissing':true});
    draw_multigraph_feedlist_editor();
    vis_feed_data();
    modified();
  });

  $(baseElement).on("click","#movingtime",function(event){
    movingtime = $(this)[0].checked;
    vis_feed_data();
    modified();
  });
  // Event for every change event in the lineColour input for each line in the plot.
  $(baseElement).on("input","#lineColour",function(event){
    var z = $(this).attr('listid');
    multigraph_feedlist[z]["lineColour"] = $(this)[0].value;
    modified();
  });
  // This only fires when the user either deselects the lineColour text-box, or hits enter
  $(baseElement).on("change","#lineColour",function(event){
    vis_feed_data();
    modified();
  });

  $(baseElement).on("click","#skipmissing",function(event){
    var z = $(this).attr('listid');
    multigraph_feedlist[z]['skipmissing'] = $(this)[0].checked;
    vis_feed_data();
    modified();
  });
  
  $(baseElement).on("click",".left",function(event){
    var z = $(this).attr('listid');
    multigraph_feedlist[z]['left'] = $(this)[0].checked;
    if (multigraph_feedlist[z]['left'] == true && multigraph_feedlist[z]['right'] == true)
      multigraph_feedlist[z]['right'] = false;
    $(".right[listid="+z+"]").attr("checked",false);

    vis_feed_data();
    modified();
  });

  $(baseElement).on("click",".right",function(){
    var z = $(this).attr('listid');
    multigraph_feedlist[z]['right'] = $(this)[0].checked;
    if (multigraph_feedlist[z]['left'] == true && multigraph_feedlist[z]['right'] == true)
      multigraph_feedlist[z]['left'] = false;
    $(".left[listid="+z+"]").attr("checked",false);
    vis_feed_data();
    modified();
  });

  $(baseElement).on("click",".fill",function(){
    var z = $(this).attr('listid');
    multigraph_feedlist[z]['fill'] = $(this)[0].checked;
    vis_feed_data();
    modified();
  });

  $(baseElement).on("click","#multigraph-feed-remove-button",function(){
    var z = $(this).attr('listid');
    multigraph_feedlist.splice(z,1);
    draw_multigraph_feedlist_editor();
    vis_feed_data();
    modified();
  });

  $(baseElement).on("click","#delete-multigraph-button",function(){
    $('#myModal').modal('show');
  });

  $(baseElement).on("click","#confirmdelete",function() {
    multigraph.remove(multigraph_id);
    $('#myModal').modal('hide');
    $(baseElement).html(multigraphDropdown());            // Reload all
  });
  
  $(baseElement).on("input propertychange paste","#multigraph-name",function(){
    modified();
  });
  
  $(baseElement).on("click","#save-multigraph-button",function(event){
    // Save multigraph view start and end time to feedlist array
    if (typeof multigraph_feedlist[0] !== 'undefined') { 
        multigraph_feedlist[0].timeWindow = view.end - view.start;
        if (movingtime) multigraph_feedlist[0].end = 0;
        else multigraph_feedlist[0].end = view.end;
    }
    var new_name=$("#multigraph-name").val();
    if(new_name=="") new_name="No name";

    multigraph.set(multigraph_id,multigraph_feedlist,new_name);
    $("#save-multigraph-button").attr('class','btn btn-success').text('Saved');
    $(baseElement).html(multigraphDropdown());                    // Reload all
    $(baseElement + ' #multigraph-selector').val(multigraph_id);  // Refresh
    $(baseElement + ' #multigraph-selector').change();            // 
  });
}
