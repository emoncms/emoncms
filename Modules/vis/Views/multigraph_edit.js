/*
  multigraph_edit.js - Licence: GNU GPL Affero, Author: Trystan Lea

  draws multigraph feedlist table and handles feedlist table dynamic
  actions such as add a feed, left, right, fill.
*/

var multigraphID = 0;
var multigraphFeedlist = [];
var multigraphs=[];
var multigraphsName=[];
var movingtime = false;
var showtag = true;
var autorefresh = 0;
var showlegend = true;
var ymin = "auto";
var ymax = "auto";
var y2min = "auto";
var y2max = "auto";
var detail = "basic";

var baseElement = "#box-options";

function multigraphDropdown(){
  var z;

  multigraphsName = [];
  multigraphs = multigraph.getlist();
  var options = "";
  for (z in multigraphs) {
    multigraphsName[multigraphs[z]["id"]] = multigraphs[z]["name"];
    options +="<option value='"+multigraphs[z]["id"]+"'>"+multigraphs[z]["id"]+": "+multigraphs[z]["name"]+"</option>";
  }
  var out = "<div class='alert'>No multigraphs created yet, click new to create one:</div>";
  if (options){
    out = "<select id='multigraph-selector' class='form-control' style='width:160px'><option>Select multigraph:</option>"+options+"</select>";
  }
  return out+"<button id='multigraph-new-button' class='btn btn-info' style='float:right'>New multigraph</button><div id='feedtable' ></div>";
}

function getFeedPublic(id){
  var z;

  for (z in feedlist){
    if (feedlist[z]["id"] === id) { return feedlist[z]["public"]; }
  }
}

// Multigraph editor interface 
function drawMultigraphFeedlistEditor(){
  var barwidth=1;
  var graphtype;
  var z;
  var checked="";

  if (typeof multigraphFeedlist === "undefined"){
    $("#embedcode").val("");
    $("#feedtable").html("");
    return;
  }

  if (multigraphFeedlist === null) { multigraphFeedlist = []; }
  
  if (typeof multigraphFeedlist[0] !== "undefined") {
      if (typeof multigraphFeedlist[0]["detail"] !== "undefined") {
        detail=multigraphFeedlist[0]["detail"];
      } else {
        detail="basic";
      }

      if (multigraphFeedlist[0]["end"] !== 0) {
        movingtime=false;
      } else {
        movingtime=true;
      }

      if (typeof multigraphFeedlist[0]["showtag"] !== "undefined") {
        showtag = multigraphFeedlist[0]["showtag"];
      } else {
        showtag = true;
      }

      if (typeof multigraphFeedlist[0]["autorefresh"] !== "undefined") {
        autorefresh = multigraphFeedlist[0]["autorefresh"];
      } else {
        autorefresh = 0;
      }

      if (typeof multigraphFeedlist[0]["showlegend"] !== "undefined") {
        showlegend = multigraphFeedlist[0]["showlegend"];
      } else {
        showlegend = true;
      }

      if (typeof multigraphFeedlist[0]["ymin"] !== "undefined" && $.isNumeric(multigraphFeedlist[0]["ymin"])) {
        ymin = multigraphFeedlist[0]["ymin"];
      } else {
        ymin = "auto";
      }

      if (typeof multigraphFeedlist[0]["ymax"] !== "undefined" && $.isNumeric(multigraphFeedlist[0]["ymax"])) {
        ymax = multigraphFeedlist[0]["ymax"];
      } else {
        ymax = "auto";
      }
    
      if (typeof multigraphFeedlist[0]["y2min"] !== "undefined" && $.isNumeric(multigraphFeedlist[0]["y2min"])) {
        y2min = multigraphFeedlist[0]["y2min"];
      } else {
        y2min = "auto";
      }

      if (typeof multigraphFeedlist[0]["y2max"] !== "undefined" && $.isNumeric(multigraphFeedlist[0]["y2max"])) {
        y2max = multigraphFeedlist[0]["y2max"];
      } else {
        y2max = "auto";
      }

      detail= multigraphFeedlist[0]["detail"] === "advanced" ? "advanced" : "basic";
  }

  var out = "";
  out += "<table style='table-layout:fixed; width:300px; margin-bottom:0px;'><tbody><tr valign='middle'>";
  out += "<td style='text-align:right;width:144px;padding-bottom:7px;padding-right:5px'>Options :</td>";
  out += "<td style='width:58px'><label><input name='detail' id='basic' type='radio' "+ (detail!=="advanced" ? "checked" : "") +" style='margin-bottom:5px'> Basic</label></td>";
  out += "<td style='width:88px'><label><input name='detail' id='advanced' type='radio' "+ (detail==="advanced" ? "checked" : "") +" style='margin-bottom:5px'> Advanced</label></td>";
  out += "</tr></tbody></table>";

  out += "<div id='myModal' class='modal hide' tabindex='-1' role='dialog' aria-labelledby='myModalLabel' aria-hidden='true' data-backdrop='static'>";
  out += "<div class='modal-header'><button type='button' class='close' data-dismiss='modal' aria-hidden='true'>×</button><h3 id='myModalLabel'>Delete Multigraph</h3></div>";
  out += "<div class='modal-body'><p>Deleting a multigraph is permanent.<br>Make sure no Dashboard continue to use the deleted multigraph<br><br>Are you sure you want to delete?</p></div>";
  out += "<div class='modal-footer'><button class='btn' data-dismiss='modal' aria-hidden='true'>Cancel</button><button id='confirmdelete' class='btn btn-primary'>Delete permanently</button></div></div>";
  
  out += "<table class='table' style='table-layout:fixed; width:300px;border-color:transparent;' >";
  out += "<tr><th style='width:130px;' >Feed</th><th style='text-align: center;'>Left</th><th style='text-align: center;'>Right</th><th style='text-align: center;'>Fill</th><th style='padding:0px; width:30px;'></th></tr>";

  var publicfeed = 1;
  for (z in multigraphFeedlist) {
    out += "<tr style='border-top: 2px solid black;'>";
    out += "<td style='vertical-align:middle;word-wrap:break-word;'>"+multigraphFeedlist[z]["tag"]+": "+multigraphFeedlist[z]["name"]+"</td>";
    if (!multigraphFeedlist[z]["left"] && !multigraphFeedlist[z]["right"]) {
      multigraphFeedlist[z]["left"] = true; // Default is left
    }
    if (typeof multigraphFeedlist[z]["barwidth"] !== "undefined" && $.isNumeric(multigraphFeedlist[z]["barwidth"])) {
      barwidth=multigraphFeedlist[z]["barwidth"]*100;
      barwidth>100 ? barwidth=100 : barwidth <1 ? barwidth=1: null ;
    } else {
      barwidth=100;
    }

    if (typeof multigraphFeedlist[z]["graphtype"] === "undefined") {
      graphtype=multigraphFeedlist[z]["datatype"]==="1" ? "lines" : "bars";
    } else {
      graphtype=multigraphFeedlist[z]["graphtype"];
    }

    checked = ""; if (multigraphFeedlist[z]["left"]) { checked = "checked"; }
    out += "<td style='text-align: center;'><input listid='"+z+"' class='left' name='xpos"+z+"' type='radio' "+checked+" /></td>";
    checked = ""; if (multigraphFeedlist[z]["right"]) { checked = "checked"; }
    out += "<td style='text-align: center;'><input listid='"+z+"' class='right' name='xpos"+z+"' type='radio' "+checked+" /></td>";
    checked = ""; if (multigraphFeedlist[z]["fill"]) { checked = "checked"; }
    out += "<td style='text-align: center;'><input listid='"+z+"' class='fill' type='checkbox' "+checked+" /></td>";
    out += "<td><a class='close'><i listid='"+z+"' id='multigraph-feed-remove-button' class='icon-remove' style='vertical-align:middle;'></i></a></td>";
    out += "</tr>";
    var setColour = ""; if (multigraphFeedlist[z]["lineColour"]) { setColour = multigraphFeedlist[z]["lineColour"]; }
    out += "<tr>";
    out += "<td style='text-align: right;vertical-align:middle;border-color:transparent;'>Line Colour</td>";
    out += "<td colspan='4' style='vertical-align:middle;border-color:transparent;'><input id='lineColour' listid='"+z+"' style='width:110px;margin-bottom:0px;' type='color' value='#"+setColour+"'></td>";
    out += "</tr>";


    if (detail==="advanced") {
      checked = "checked"; if (!multigraphFeedlist[z]["skipmissing"]) { checked = ""; }
      out += "<tr>";
      out += "<td style='text-align: right;vertical-align:middle;border-color:transparent;'>Skip missing data</td>";
      out += "<td style='text-align: center;vertical-align:middle;border-color:transparent;'><input id='skipmissing'  listid='"+z+"' type='checkbox' "+checked+" /></td>";
      checked = ""; if (multigraphFeedlist[z]["stacked"]) { checked = "checked"; }
      out += "<td style='text-align: right;vertical-align:middle;border-color:transparent;'>Stack</td>";
      out += "<td style='text-align: center;vertical-align:middle;border-color:transparent;'><input id='stacked'  listid='"+z+"' type='checkbox' "+checked+" /></td>";
      out += "<td style='text-align: right;vertical-align:middle;border-color:transparent;'></td>";
      out += "</tr>";
      out += "<tr>";

      out += "<td style='text-align: right;vertical-align:middle;border-color:transparent;'>Graph Type</td>";
      out += "<td colspan='4' style='vertical-align:middle;'>";
      out += "<select id='graphtype-selector' listid='"+z+"' class='options' style='width:140px;margin-bottom:0px;'>";
      out += "<optgroup label= 'Select Display Type:'>";
      out += "<option value='lines'"+ (graphtype==="lines" && "selected") +">Lines</option>";
      out += "<option value='lineswithsteps'"+ (graphtype==="lineswithsteps" && "selected") +">Lines with Steps</option>";
      out += "<option value='bars'"+ (graphtype==="bars" && "selected") +">Bars</option>";
      out += "</optgroup> </select>";
      out += "</td>";
      out += "</tr>";

      if (graphtype==="bars") {
        out += "<tr><td style='text-align: right;vertical-align:middle;border-color:transparent;'>Bar Width (%)</td>";
        out += "<td colspan='4' style='vertical-align:middle;border-color:transparent;'><input listid='"+z+"' style='width:110px' id='barwidth' value='" + barwidth + "'/></td>";
        out += "</tr>";
      }
    }

    if (publicfeed === 1) { publicfeed = (getFeedPublic(multigraphFeedlist[z]["id"])); }
  }
  var visurl = path+"vis/"+"multigraph?mid="+multigraphID;
  if (publicfeed === 1) {
    $("#embedcode").val("<iframe style='width:580px; height:400px;' frameborder='0' scrolling='no' marginheight='0' marginwidth='0' src='"+visurl+"&embed=1'></iframe>");
  } else {
    $("#embedcode").val("Some of the feeds selected are not public, to embed a visualisation publicly first make the feeds that you want to use public.\n\nTo embed privately:\n\n<iframe style='width:580px; height:400px;' frameborder='0' scrolling='no' marginheight='0' marginwidth='0' src='"+visurl+"&embed=1&apikey="+apikey+"'></iframe>");
  }

  out += "<tr>";
  out += "<td>"+selectFeed("feedselect", feedlist, 0)+"</td>";
  out += "<td></td>";
  out += "<td></td>";
  out += "<td><input id='add' type='button' class='btn' value='Add'/ ></td>";
  out += "<td></td>";
  out += "</tr>";

  if (detail==="advanced") {
    out += "<tr><td style='width:130px;' >Y axes limits</td><td colspan='2' style='text-align: center;'>Min</td><td colspan='2' style='text-align: center;'>Max</td></tr>";
    out += "<tr><td style='text-align: right;vertical-align:middle;border-color:transparent;'>Left</td>";
    out += "<td colspan='2'><input style='width:50px' id='ymin' value='" + ymin + "'/></td>";
    out += "<td colspan='2'><input style='width:50px' id='ymax' value='" + ymax + "'/></td>";
    out += "</tr>";
    out += "<tr><td style='text-align: right;vertical-align:middle;border-color:transparent;'>Right</td>";
    out += "<td colspan='2'><input style='width:50px' id='y2min' value='" + y2min + "'/></td>";
    out += "<td colspan='2'><input style='width:50px' id='y2max' value='" + y2max + "'/></td>";
    out += "</tr>";

    out += "<tr><td>Floating time</td>";
    checked = ""; if (movingtime) { checked = "checked"; }
    out += "<td><input id='movingtime' type='checkbox' "+checked+" /></td>";
    out += "<td></td>";
    out += "<td></td>";
    out += "<td></td></tr>";
    out += "<tr><td>Auto refresh (secs)</td>";
    out += "<td><input style='width:110px' id='autorefresh' value='" + autorefresh + "'/></td>";
    out += "<td></td>";
    out += "<td></td>";
    out += "<td></td></tr>";
    out += "<tr><td>Show tag name</td>";
    checked = ""; if (showtag) { checked = "checked"; }
    out += "<td><input id='showtag' type='checkbox' "+checked+" /></td>";
    out += "<td></td>";
    out += "<td></td>";
    out += "<td></td></tr>";
    out += "<tr><td>Show Legend</td>";
    checked = ""; if (showlegend) { checked = "checked"; }
    out += "<td><input id='showlegend' type='checkbox' "+checked+" /></td>";
    out += "<td></td>";
    out += "<td></td>";
    out += "<td></td></tr>";
  }

  out += "</table>";
  var name = "<div class='input-prepend'><span class='add-on' style='width: 70px; text-align: right;'>Name</span><input class='options' id='multigraph-name' value='"+multigraphsName[multigraphID]+"' type='text'></div>";
  out += name+"<button id='delete-multigraph-button' class='btn btn-danger'><i class='icon-trash'></i>Delete</button>";
  out += "<button id='save-multigraph-button' class='btn btn-success' style='float:right'>Not modified</button>";
  $("#feedtable").html(out);
}

function getFeedName(id){
  var z;

  for (z in feedlist){
    if (feedlist[z]["id"] === id) { return feedlist[z]["name"]; }
  }
}

function getFeedTag(id){
  var z;

  for (z in feedlist){
    if (feedlist[z]["id"] === id) { return feedlist[z]["tag"]; }
  }
}

function getFeedDatatype(id){
  var z;

  for (z in feedlist){
    if (feedlist[z]["id"] === id) { return feedlist[z]["datatype"]; }
  }
}

function updateMultigraphFeedlistNames(){
  var m;

  for (m in multigraphFeedlist) {
    if (typeof multigraphFeedlist[m] !== "undefined"){
        var feedid = multigraphFeedlist[m]["id"];
        multigraphFeedlist[m]["tag"] = getFeedTag(feedid);
        multigraphFeedlist[m]["name"] = getFeedName(feedid);
    }
  }
}

function modified(){
  $(baseElement + " #save-multigraph-button").attr("class","btn btn-warning").text("Changed, press to save");
}

// Events
function loadEvents(){
  var barwidth=100;

  $(baseElement).unbind();

  $(baseElement).on("change","#multigraph-selector",function(event){
    multigraphID = $(this).val();
    if (multigraphID > 0) {
      multigraphFeedlist = multigraph.get(multigraphID)["feedlist"];
      updateMultigraphFeedlistNames();
    } else {
      multigraphID=0;
      multigraphFeedlist=undefined;
      //$("#feedtable").html("");
    }
    // Draw multigraph feedlist editor
    drawMultigraphFeedlistEditor();
    // Draw multigraph
    multigraphInit("#visiframe");
    visFeedData();
  });

  $(baseElement).on("click","#multigraph-new-button",function(event){
    multigraphID = multigraph.new();
    $(baseElement).html(multigraphDropdown());            // Reload all
    $(baseElement + " #multigraph-selector").val(multigraphID);  // Refresh
    $(baseElement + " #multigraph-selector").change();            // 
  });

  $(baseElement).on("click","#add",function(event){
    var feedid = $("#feedselect").val();
    multigraphFeedlist.push({"id":feedid,"tag":getFeedTag(feedid),"name":getFeedName(feedid),"datatype":getFeedDatatype(feedid),"left":false,"right":false,"fill":false,"end":0,"skipmissing":true});
    drawMultigraphFeedlistEditor();
    visFeedData();
    modified();
  });

  
  $(baseElement).on("change","#ymin",function(event){
    ymin = $(this)[0].value;
    if (!$.isNumeric(ymin)) { ymin = null; }
    multigraphFeedlist[0]["ymin"] = ymin;
    visFeedData();
    modified();
  });
  
  $(baseElement).on("change","#ymax",function(event){
    ymax = $(this)[0].value;
    if (!$.isNumeric(ymax)) { ymax = null; }
    multigraphFeedlist[0]["ymax"] = ymax;
    visFeedData();
    modified();
  });
  
  $(baseElement).on("change","#y2min",function(event){
    y2min = $(this)[0].value;
    if (!$.isNumeric(y2min)) { y2min = null; }
    multigraphFeedlist[0]["y2min"] = y2min;
    visFeedData();
    modified();
  });
  
  $(baseElement).on("change","#y2max",function(event){
    y2max = $(this)[0].value;
    if (!$.isNumeric(y2max)) { y2max = null; }
    multigraphFeedlist[0]["y2max"] = y2max;
    visFeedData();
    modified();
  });
  
  $(baseElement).on("click","#movingtime",function(event){
    movingtime = $(this)[0].checked;
    visFeedData();
    modified();
  });
  $(baseElement).on("click","#showtag",function(event){
    showtag = $(this)[0].checked;
    multigraphFeedlist[0]["showtag"] = showtag;
    visFeedData();
    modified();
  });
  $(baseElement).on("change","#autorefresh",function(event){
    autorefresh = $(this)[0].value;
    multigraphFeedlist[0]["autorefresh"] = autorefresh;
    // visFeedData(); doesn't affect data
    modified();
  });
  $(baseElement).on("click","#showlegend",function(event){
    showlegend = $(this)[0].checked;
    multigraphFeedlist[0]["showlegend"] = showlegend;
    visFeedData();
    modified();
  });
  // Event for every change event in the lineColour input for each line in the plot.
  $(baseElement).on("input","#lineColour",function(event){
    var z = $(this).attr("listid");
    multigraphFeedlist[z]["lineColour"] = $(this)[0].value.replace("#","");
    modified();
  });
  // This only fires when the user either deselects the lineColour text-box, or hits enter
  $(baseElement).on("change","#lineColour",function(event){
    visFeedData();
    modified();
  });

  $(baseElement).on("click","#skipmissing",function(event){
    var z = $(this).attr("listid");
    multigraphFeedlist[z]["skipmissing"] = $(this)[0].checked;
    visFeedData();
    modified();
  });

  $(baseElement).on("change","#barwidth",function(event){
    var z = $(this).attr("listid");
    barwidth = $(this).val();
    if (!$.isNumeric(barwidth) || barwidth > 100 ) {
      barwidth = 100;
    } else if (barwidth <1 ) {
      barwidth=1;
    }
    multigraphFeedlist[z]["barwidth"] = barwidth/100;
    $(this).val(barwidth);
    visFeedData();
    modified();
  });
 
  $(baseElement).on("click","#basic",function(event){
    $(this)[0].checked ? detail="basic" : detail="advanced";
    multigraphFeedlist[0]["detail"] = detail;
    drawMultigraphFeedlistEditor();
    modified();
  });

  $(baseElement).on("click","#advanced",function(event){
    $(this)[0].checked ? detail="advanced" : detail="basic";
    multigraphFeedlist[0]["detail"] = detail;
    drawMultigraphFeedlistEditor();
    modified();
  });


  $(baseElement).on("change","#graphtype-selector",function(event){
    var z = $(this).attr("listid");
    var graphtype = $(this).val();
    multigraphFeedlist[z]["graphtype"]=graphtype;
    drawMultigraphFeedlistEditor();
    visFeedData();
    modified();
  });

   $(baseElement).on("click","#stacked",function(event){
    var z = $(this).attr("listid");
    multigraphFeedlist[z]["stacked"] = $(this)[0].checked;
    visFeedData();
    modified();
  });
  
 $(baseElement).on("click",".left",function(event){
    var z = $(this).attr("listid");
    multigraphFeedlist[z]["left"] = $(this)[0].checked;
    if (multigraphFeedlist[z]["left"] === true && multigraphFeedlist[z]["right"] === true)
      multigraphFeedlist[z]["right"] = false;
    $(".right[listid="+z+"]").attr("checked",false);

    visFeedData();
    modified();
  });

  $(baseElement).on("click",".right",function(){
    var z = $(this).attr("listid");
    multigraphFeedlist[z]["right"] = $(this)[0].checked;
    if (multigraphFeedlist[z]["left"] === true && multigraphFeedlist[z]["right"] === true)
      multigraphFeedlist[z]["left"] = false;
    $(".left[listid="+z+"]").attr("checked",false);
    visFeedData();
    modified();
  });

  $(baseElement).on("click",".fill",function(){
    var z = $(this).attr("listid");
    multigraphFeedlist[z]["fill"] = $(this)[0].checked;
    visFeedData();
    modified();
  });

  $(baseElement).on("click","#multigraph-feed-remove-button",function(){
    var z = $(this).attr("listid");
    multigraphFeedlist.splice(z,1);
    drawMultigraphFeedlistEditor();
    visFeedData();
    modified();
  });

  $(baseElement).on("click","#delete-multigraph-button",function(){
    $("#myModal").modal("show");
  });

  $(baseElement).on("click","#confirmdelete",function() {
    multigraph.remove(multigraphID);
    $("#myModal").modal("hide");
    $(baseElement).html(multigraphDropdown());         // Reload all
    //$(baseElement + " #multigraph-selector").val(undefined);  // Refresh
    $(baseElement + " #multigraph-selector").change(); // 
  });
  
  $(baseElement).on("input propertychange paste","#multigraph-name",function(){
    modified();
  });
  
  $(baseElement).on("click","#save-multigraph-button",function(event){
    // Save multigraph view start and end time to feedlist array
    if (typeof multigraphFeedlist[0] !== "undefined") {
      multigraphFeedlist[0].timeWindow = view.end - view.start;
      if (movingtime) multigraphFeedlist[0].end = 0;
      else multigraphFeedlist[0].end = view.end;
    }
    var new_name=$("#multigraph-name").val();
    if(new_name==="") new_name="No name";

    var result = multigraph.set(multigraphID,multigraphFeedlist,new_name);
    if (result.success) {
        $(baseElement).html(multigraphDropdown());                    // Reload all
        $(baseElement + " #multigraph-selector").val(multigraphID);  // Refresh
        $(baseElement + " #multigraph-selector").change();            // 
        $(baseElement + " #save-multigraph-button").attr("class","btn btn-success").text("Saved");
    }
    else { alert("ERROR: Could not save Multigraph. "+result.message); }
  });
}

// Multigraph new and selector interface
function multigraphGUI(){
  $("#viewbtn").hide();
  $(baseElement).html(multigraphDropdown());
  loadEvents();
}
