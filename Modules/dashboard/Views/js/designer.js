function dashboard_designer(_canvas, _grid_size, _widgets)
{
    var canvas = _canvas;
    var widgets = _widgets;
    var grid_size = _grid_size;

    var canvas = document.getElementById("can");
    var ctx = canvas.getContext("2d");

    var boxlist = {};
    var resize = {};

    var selected_box = null;
    var selected_edge = null;
    var edit_mode = true;
    var create = null;

    var boxi = 0;

    var mousedown = false;
    var mousedown_x = 0;
    var mousedown_y = 0;

    var page_width = $(canvas).attr("width");
    var page_height = $(canvas).attr("height");
    $("#when-selected").hide();
    draw();
    scan();
    widget_buttons();

  function snap(pos) {return Math.round(pos/grid_size)*grid_size;}

  function onbox(x,y) 
  {
    var box = null;
    for (z in boxlist) {
      if (x>boxlist[z]['left'] && x<(boxlist[z]['left']+boxlist[z]['width'])) {
        if (y>boxlist[z]['top'] && y<(boxlist[z]['top']+boxlist[z]['height'])) {
          box = z;
        }
      }
    }
    return box;
  }

  function scan()
  {
    for (z in widgets)
    {
    $("."+z).each(function() 
    { 
      var id = 1*($(this).attr("id"));
      if (id>boxi) boxi = id;
      boxlist[id] = {
	'top':parseInt($(this).css("top")),
	'left':parseInt($(this).css("left")),
	'width':parseInt($(this).css("width")),
	'height':parseInt($(this).css("height"))
      };
    });
    }
  }

  function draw()
  {
    ctx.clearRect(0,0,page_width,page_height);

    //--------------------------------------------------------------------
    // Draw grid
    //--------------------------------------------------------------------
    ctx.fillStyle    = "rgba(0, 0, 0, 0.2)";
    ctx.strokeStyle    = "rgba(0, 0, 0, 0.2)";
    for (var x=1; x<(page_width/grid_size); x++)
    {
      for (var y=1; y<(page_height/grid_size); y++)
      {
        ctx.fillRect((x*grid_size)-1,(y*grid_size)-1,2,2);
      }
    }
    ctx.strokeRect(0,0,page_width,page_height);
 
    //--------------------------------------------------------------------
    // Draw selected box points
    //--------------------------------------------------------------------
    if (selected_box)
    {
    $("#state").html("Changed");
      var top = boxlist[selected_box]['top'];
      var left = boxlist[selected_box]['left'];
      var width = boxlist[selected_box]['width'];
      var height = boxlist[selected_box]['height'];

      ctx.fillRect(left-5,top+(height/2)-5,10,10);
      ctx.fillRect(left+width-5,top+(height/2)-5,10,10);

      ctx.fillRect(left+(width/2)-5,top-5,10,10);
      ctx.fillRect(left+(width/2)-5,top+height-5,10,10);

      ctx.fillRect(left+(width/2)-5,top+(height/2)-5,10,10);
    }

    //--------------------------------------------------------------------
    // Update position and dimentions of elements
    //--------------------------------------------------------------------
    for (z in boxlist) {
      if (z){
        var element = "#"+z
        $(element).css("top", boxlist[z]['top']+"px");
        $(element).css("left", boxlist[z]['left']+"px");
        $(element).css("width", boxlist[z]['width']+"px");
        $(element).css("height", boxlist[z]['height']+"px");
      }
    }
    redraw = 1;
  }

  function draw_options(box_options)
  {
    // Build options table html
    var options_html = "<table>";
    for (z in box_options)
    {
      var val = $("#"+selected_box).attr(box_options[z]);
      if (val == undefined) val="";
      options_html += "<tr><td>"+box_options[z]+":</td>";
      options_html += "<td><input class='options' id='"+box_options[z]+"' type='text' value='"+val+"'/ ></td></tr>"
    }
    options_html += "</table>";
    $("#box-options").html(options_html);
  }

  function widget_buttons()
  {
    var widget_html = "";
    var select = [];
    for (z in widgets)
    {
      var menu = widgets[z]['menu'];
      if (menu) 
      { 
        select[menu] += "<option>"+z+"</option>";
      } else {
        widget_html +="<input class='widget-button' name='"+z+"' type='button' value='"+z+"' / >";
      }
    }

    for (z in select)
    {
      widget_html += "<select id='"+z+"' class='widgetmenu' style='width:120px; margin:5px;'><option title=1 >"+z+":</option>"+select[z]+"</select>";
    }
    $("#widget-buttons").html(widget_html);

    $(".widget-button").click(function(event) { 
      create = $(this).attr("name");
      edit_mode = false;
    });

    $(".widgetmenu").click(function(event) { 
      create = ($(this).find("option:selected").text());
      var title = $(this).find("option:selected").attr("title");
      if (create && title!=1) edit_mode = false;
    });

  }

  function add_widget(mx,my,type)
  {
    boxi++;
    var html = widgets[type]['html'];
    if (html == undefined) html = "";
    $("#page").append('<div id="'+boxi+'" class="'+type+'" style="position:absolute; margin: 0; top:'+snap(my+widgets[type]['offsety'])+'px; left:'+snap(mx+widgets[type]['offsetx'])+'px; width:'+widgets[type]['width']+'px; height:'+widgets[type]['height']+'px;" >'+html+'</div>');

    scan();
    redraw = 1;
    edit_mode = true;
  }

  // Click to select
  $(canvas).click(function(event) { 
    var mx = event.layerX;
    var my = event.layerY;
    if (edit_mode) selected_box = onbox(mx,my);
    if (!selected_box)  {$("#testo").hide(); $("#when-selected").hide();}

    draw()
  });

  $(canvas).mousedown(function(event) { 
    mousedown = true;
    var mx = event.layerX;
    var my = event.layerY;
    if (edit_mode) 
    {
      // If its not yet selected check if a box is selected now
      if (!selected_box) selected_box = onbox(mx,my);

      if (selected_box) {
      $("#when-selected").show();
      resize = boxlist[selected_box];

      var rightedge = resize['left']+resize['width'];
      var bottedge = resize['top']+resize['height'];
      var midx = resize['left']+(resize['width']/2);
      var midy = resize['top']+(resize['height']/2);

      selected_edge = null;
      if (Math.abs(mx - rightedge)<20) selected_edge = "right";
      if (Math.abs(mx - resize['left'])<20) selected_edge = "left";
      if (Math.abs(my - bottedge)<20) selected_edge = "bottom";
      if (Math.abs(my - resize['top'])<20) selected_edge = "top";
      if (Math.abs(my - midy)<20 && Math.abs(mx - midx)<20) selected_edge = "center";
      }
    }
    else
    {
      if (create)
      {
        add_widget(mx,my,create);
        create = null;
        $('option:selected', 'select').removeAttr('selected');
        $('option[title=1]').attr('selected','selected');
        $("#when-selected").show();
      }
    }
  });

  $(canvas).mouseup(function(event) { 
    mousedown = false;
    selected_edge = null;
  });

  $(canvas).mousemove(function(event) { 
    if (mousedown && selected_box && selected_edge){
    var mx = event.layerX;
    var my = event.layerY;

      var rightedge = resize['left']+resize['width'];
      var bottedge = resize['top']+resize['height'];

      if (selected_edge == "right") boxlist[selected_box]['width'] = (snap(mx)-resize['left']);
      if (selected_edge == "left") 
      {
        boxlist[selected_box]['left'] = (snap(mx));
        boxlist[selected_box]['width'] = rightedge - snap(mx);
      }

      if (selected_edge == "bottom") boxlist[selected_box]['height'] = (snap(my)-resize['top']);
      if (selected_edge == "top") 
      { 
        boxlist[selected_box]['top'] = (snap(my));
        boxlist[selected_box]['height'] = bottedge - snap(my);
      }

      if (selected_edge == "center")
      { 
        boxlist[selected_box]['left'] = (snap(mx-boxlist[selected_box]['width']/2));
        boxlist[selected_box]['top'] = (snap(my-boxlist[selected_box]['height']/2));
      }

   if (bottedge>parseInt($("#page-container").css("height"))){
     $("#page-container").css("height",bottedge);
     $("#can").attr("height",bottedge);
     page_height = bottedge;
   }

      draw();
    }
  });

  // On save click
  $("#options-save").click(function() 
  {
    $(".options").each(function() {
      if ($(this).attr("id")!="html") $("#"+selected_box).attr($(this).attr("id"), $(this).val());
      if ($(this).attr("id")=="html") $("#"+selected_box).html($(this).val());    
    });
    $("#testo").hide();
    redraw = 1;
    reloadiframe = selected_box;
    $("#state").html("Changed");
  });

 $("#delete-button").click(function(event) { 
    if (selected_box)
    {
      delete boxlist[selected_box];
      $("#"+selected_box).remove();
      draw();
    }
  });

  $("#options-button").click(function(event) { 
    if (selected_box){
      draw_options(widgets[$("#"+selected_box).attr("class")]["options"]);
      $("#testo").show();
    }
  });
}
