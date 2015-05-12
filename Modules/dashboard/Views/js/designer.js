/*
 designer.js -  Licence: GNU GPL Affero, Author: Trystan Lea

 The dashboard designer works around the concept of html elements with fixed positions
 and specified widths and heights. Its a box model where each box can hold a widget.
 A box specifies which widget it is by its class. The properties of a widget such as
 the feedid to use is also specified in the html element:

 <div class="dial" feedid="1" style="position:absolute; top:50px; left:50px; width:200px; height:100px;" ></div>

 render.js and associated widget render scripts then inserts the dial javascript into the element specified in the designer.

 The dashboard designer creates a canvas layer above the dashboard html elements layer and uses jquery to get the mouse positions and actions that specify the box position and dimentions.

 The functions: draw_options(box_options, options_type) and widget_buttons() draw the menu and widget options interface.

*/

var selected_edges = {none : 0, left : 1, right : 2, top : 3, bottom : 4, center : 5};

var designer = {

    'grid_size':20,
    'page_width':500,
    'page_height':500,

    'cnvs':null,
    'canvas':null,
    'designer.ctx':null,
    'widgets':null,

    'boxlist': {},
    'resize': {},

    'selected_box': null,
    'selected_edge': selected_edges.none,
    'edit_mode': true,
    'create': null,

    'boxi': 0,

    'mousedown': false,

    'init': function()
    {
        designer.cnvs = document.getElementById("can");
        designer.ctx = designer.cnvs.getContext("2d");

        $("#when-selected").hide();
        designer.scan();
        designer.draw();
        designer.widget_buttons();
        designer.add_events();
    },


    'snap': function(pos) {return Math.round(pos/designer.grid_size)*designer.grid_size;},

    'modified': function() {
        $("#save-dashboard").attr('class','btn btn-warning').text(_Tr("Changed, press to save"));
    },

    'onbox': function(x,y)
    {
        var box = null;
        for (z in designer.boxlist) {
        if (x>designer.boxlist[z]['left']-4 && x<(designer.boxlist[z]['left']+designer.boxlist[z]['width']+4)) {
            if (y>designer.boxlist[z]['top']-4 && y<(designer.boxlist[z]['top']+designer.boxlist[z]['height']+4)) {
            box = z;
            }
        }
        }
        return box;
    },

    'scan': function()
    {
        for (z in widgets)
        {
            $("."+z).each(function()
            {
                var id = 1*($(this).attr("id"));
                if (id>designer.boxi) designer.boxi = id;
                designer.boxlist[id] = {
                    'top':parseInt($(this).css("top")),
                    'left':parseInt($(this).css("left")),
                    'width':parseInt($(this).css("width")),
                    'height':parseInt($(this).css("height")),
                    'styleUnitWidth': (designer.getStyle($(this),'width').indexOf("%") > -1  ? 1 : 0 ),
                    'styleUnitHeight': (designer.getStyle($(this),'height').indexOf("%") > -1  ? 1 : 0 )
                };

                if (designer.boxlist[id]['width'] < designer.grid_size) designer.boxlist[id]['width'] = designer.grid_size;    // Zero cant be selected se we default to minimal grid size
                if (designer.boxlist[id]['height'] < designer.grid_size) designer.boxlist[id]['height'] = designer.grid_size;
                
                if ((designer.boxlist[id]['top'] + designer.boxlist[id]['height'])>designer.page_height) designer.page_height = (designer.boxlist[id]['top'] + designer.boxlist[id]['height']);
            });
        }
    },
    
    // given an element and a style name, returns the exact style value
    'getStyle':function(element,style)
    {
           var stylestemp = $(element).attr('style').split(';');
           var c = '';
           for (var x = 0, l = stylestemp.length; x < l; x++) {
             c = stylestemp[x].split(':');
             if ($.trim(c[0]) == style) return $.trim(c[1]);
           }
    },
    
    'draw': function()
    {
        $("#page-container").css("height",designer.page_height);
        $("#can").attr("height",designer.page_height);

        designer.page_width = parseInt($('#dashboardpage').width());
        $('#can').width($('#dashboardpage').width());
        designer.cnvs.setAttribute('width', designer.page_width);
        designer.ctx = designer.cnvs.getContext("2d");

        designer.ctx.clearRect(0,0,designer.page_width,designer.page_height);
        designer.ctx.strokeRect(0,0,designer.page_width,designer.page_height);

        designer.ctx.translate(0.5, 0.5); // Move the canvas by 0.5px to fix blurring

        //--------------------------------------------------------------------
        // Draw grid
        //--------------------------------------------------------------------
        designer.ctx.fillStyle    = "rgba(0, 0, 0, 0.2)";

        for (var x=1; x<parseInt(designer.page_width/designer.grid_size); x++)
        {
            for (var y=1; y<parseInt(designer.page_height/designer.grid_size); y++)
            {
                designer.ctx.fillRect((x*designer.grid_size)-1,(y*designer.grid_size)-1,1,1);
            }
        }

        //--------------------------------------------------------------------
        // Draw selected box points
        //--------------------------------------------------------------------
        if (designer.selected_box)
        {
            var strokeColor = "rgba(140, 179, 255, 0.9)";
            var selectedColor = "rgba(255, 0, 0, 0.9)";

            var top = designer.boxlist[designer.selected_box]['top'];
            var left = designer.boxlist[designer.selected_box]['left'];
            var width = designer.boxlist[designer.selected_box]['width'];
            var height = designer.boxlist[designer.selected_box]['height'];

            designer.ctx.strokeStyle = (selected_edge == selected_edges.left ? selectedColor : strokeColor );
            designer.ctx.strokeRect(left-4,top+(height/2)-4,8,8);

            designer.ctx.strokeStyle = (selected_edge == selected_edges.right ? selectedColor : strokeColor );
            designer.ctx.strokeRect(left+width-4,top+(height/2)-4,8,8);

            designer.ctx.strokeStyle = (selected_edge == selected_edges.top ? selectedColor : strokeColor );
            designer.ctx.strokeRect(left+(width/2)-4,top-4,8,8);

            designer.ctx.strokeStyle = (selected_edge == selected_edges.bottom ? selectedColor : strokeColor );
            designer.ctx.strokeRect(left+(width/2)-4,top+height-4,8,8);

            designer.ctx.strokeStyle = (selected_edge == selected_edges.center ? selectedColor : strokeColor );
            designer.ctx.strokeRect(left+(width/2)-4,top+(height/2)-4,8,8);

            designer.ctx.strokeStyle  = strokeColor;
            designer.ctx.setLineDash([3]);
            designer.ctx.strokeRect(left,top,width,height);
        }

        //--------------------------------------------------------------------
        // Update position and dimentions of elements
        //--------------------------------------------------------------------
        for (z in designer.boxlist) {
            if (z){
                var element = "#"+z
                $(element).css("top", designer.boxlist[z]['top']+"px");
                $(element).css("left", designer.boxlist[z]['left']+"px");
                if (designer.boxlist[z]['styleUnitWidth'] == 1) {
                    $(element).css("width", Math.round(designer.boxlist[z]['width'] / designer.page_width * 100) + "%");
                } else {
                    $(element).css("width", designer.boxlist[z]['width']+"px");
                }
                if (designer.boxlist[z]['styleUnitHeight'] == 1) {
                    $(element).css("height", Math.round(designer.boxlist[z]['height'] / designer.page_height * 100) + "%");
                } else {
                    $(element).css("height", designer.boxlist[z]['height']+"px");
                }
            }
        }
        redraw = 1;
    },

    'draw_options': function(widget)
    {
        var box_options = widgets[widget]["options"];
        var options_type = widgets[widget]["optionstype"];
        var options_name = widgets[widget]["optionsname"];
        var optionshint = widgets[widget]["optionshint"];
        var optionsdata = widgets[widget]["optionsdata"];

        // Build options table html
        var options_html = '<div id="box-options">';
        for (z in box_options)
        {
            // look into the designer DOM to extract the div parameters from the selected widget.
            var val = $("#"+designer.selected_box).attr(box_options[z]);

            if (val == undefined) val="";

            options_html += '<div class="control-group"><div class="controls">';
            options_html += '<div class="input-prepend" style="margin-bottom: 0px;">';
            options_html += '<span class="add-on" style="width:80px; text-align: right;">'+options_name[z]+'</span>';

            if (options_type && options_type[z] == "feed")
            {
                options_html += "<select id='"+box_options[z]+"' class='options' >";
                for (i in feedlist)
                {
                    var selected = "";
                    if (val == feedlist[i]['name'].replace(/\s/g, '-'))
                        selected = "selected";
                    options_html += "<option value='"+feedlist[i]['name'].replace(/\s/g, '-')+"' "+selected+" >"+feedlist[i]['id']+": "+feedlist[i]['tag']+":"+feedlist[i]['name']+"</option>";
                }
                options_html += "</select>";
            }

            else if (options_type && options_type[z] == "feedid")
            {
                options_html += "<select id='"+box_options[z]+"' class='options' >";
                for (i in feedlist)
                {
                    var selected = "";
                    if (val == feedlist[i]['id'])
                        selected = "selected";
                    options_html += "<option value='"+feedlist[i]['id']+"' "+selected+" >"+feedlist[i]['id']+": "+feedlist[i]['tag']+":"+feedlist[i]['name']+"</option>";
                }
                options_html += "</select>";
            }

            else if (options_type && options_type[z] == "html")
            {
                val = $("#"+designer.selected_box).html();
                options_html += "<textarea class='options' id='"+box_options[z]+"' >"+val+"</textarea>"
            }

            // Combobox for selecting options
            else if (options_type && options_type[z] == "dropbox" && optionsdata[z])  // Check we have optionsdata before deciding to draw a combobox
            {
                options_html += "<select id='"+box_options[z]+"' class='options' >";
                for (i in optionsdata[z])
                {
                    var selected = "";
                    if (val == optionsdata[z][i][0])
                        selected = "selected";
                    options_html += "<option "+selected+" value=\""+optionsdata[z][i][0]+"\">"+optionsdata[z][i][1]+"</option>";
                }
                options_html += "</select>";
            }

            else if (options_type && options_type[z] == "colour_picker")
            {
                 options_html += "<input  type='color' class='options' id='"+box_options[z]+"'  value='#"+val+"'/ >"
            }

/*
            // Radio-buttons for selecting options
            // It was a bit confusing to use, so it's disabled until I get a change to revisit and style it better (Fake-name)
            else if (options_type && options_type[z] == "toggle" && optionsdata[z])  // Check we have optionsdata before deciding to draw a combobox
            {
             options_html += "<td>";
             for (i in optionsdata[z])
             {
                 var selected = "";
                 if (val == optionsdata[z][i][0])
                     selected = "checked";
                 options_html += "<input type='radio' class='options' id='"+box_options[z]+"' value='0' style='vertical-align: baseline; padding: 5px; margin: 5px;' "+selected+">"+optionsdata[z][i][1]+"<br>";
             }
            }
*/

            else
            {
                options_html += "<input class='options' id='"+box_options[z]+"' type='text' value='"+val+"'/ >"
            }

            options_html += '</div>';
            options_html += '<span class="help-inline"><small class="muted">'+optionshint[z]+'</small></span>';
            options_html +='</div></div>';

        }

        // Generic sizing options for all widgets (an hack so we dont add new options to all widgets)
        var selPixel = (designer.boxlist[designer.selected_box]['styleUnitWidth'] == 0 ? "selected" : "");
        var selPercent = (designer.boxlist[designer.selected_box]['styleUnitWidth'] == 1 ? "selected" : "");
        options_html += '<div class="control-group"><div class="controls"><div style="margin-bottom: 0px;" class="input-prepend"><span style="width:80px; text-align: right;" class="add-on">'+_Tr("Width")+'</span>';
        options_html += '<select class="options" id="styleUnitWidth"><option value="0" '+selPixel+'>'+_Tr("Pixels")+'</option><option value="1" '+selPercent+'>'+_Tr("Percentage")+'</option></select>';
        options_html += '</div><span class="help-inline"><small class="muted">'+_Tr("Choose width unit")+'</small></span></div></div>';

        var selPixel = (designer.boxlist[designer.selected_box]['styleUnitHeight'] == 0 ? "selected" : "");
        var selPercent = (designer.boxlist[designer.selected_box]['styleUnitHeight'] == 1 ? "selected" : "");
        options_html += '<div class="control-group"><div class="controls"><div style="margin-bottom: 0px;" class="input-prepend"><span style="width:80px; text-align: right;" class="add-on">'+_Tr("Height")+'</span>';
        options_html += '<select class="options" id="styleUnitHeight"><option value="0" '+selPixel+'>'+_Tr("Pixels")+'</option><option value="1" '+selPercent+'>'+_Tr("Percentage")+'</option></select>';
        options_html += '</div><span class="help-inline"><small class="muted">'+_Tr("Choose height unit")+'</small></span></div></div>';

        
        options_html += "</div>";

        // Fill the modal configuration window with options
        $("#widget_options_body").html(options_html);

    },

    'widget_buttons': function()
    {
        var widget_html = "";
        var select = [];

        for (z in widgets)
        {
            var menu = widgets[z]['menu'];

            if (typeof select[menu] === "undefined")
                select[menu] = "<li><a id='"+z+"' class='widget-button'>"+z+"</a></li>";
            else
                select[menu] += "<li><a id='"+z+"' class='widget-button'>"+z+"</a></li>";
        }

        for (z in select)
        {
            widget_html += "<div class='btn-group'><button class='btn dropdown-toggle widgetmenu' data-toggle='dropdown'>"+z+"&nbsp<span class='caret'></span></button>";
            widget_html += "<ul class='dropdown-menu' name='d'>"+select[z]+"</ul>";
        }
        $("#widget-buttons").html(widget_html);

        $(".widget-button").click(function(event) {
            designer.create = $(this).attr("id");
            designer.edit_mode = false;
        });

    },

    'add_widget': function(mx,my,type)
    {
        designer.boxi++;
        var html = widgets[type]['html'];
        if (html == undefined) html = "";
        $("#page").append('<div id="'+designer.boxi+'" class="'+type+'" style="position:absolute; margin: 0; top:'+designer.snap(my+widgets[type]['offsety'])+'px; left:'+designer.snap(mx+widgets[type]['offsetx'])+'px; width:'+widgets[type]['width']+'px; height:'+widgets[type]['height']+'px;" >'+html+'</div>');

        designer.scan();
        designer.draw();
        designer.modified();
        designer.edit_mode = true;
    },

    'get_unified_event': function(e) {
        var coors;
        if (e.originalEvent.touches) {  // touch
            coors = e.originalEvent.touches[0];
        } else {                        // mouse
            coors = e;
        }
        return coors;
    },

    'add_events': function()
    {
        // Click to select
        $(this.canvas).click(function(event) {

            var mx = 0, my = 0;
            if(event.offsetX==undefined)
            {
                mx = (event.pageX - $(event.target).offset().left);
                my = (event.pageY - $(event.target).offset().top);
            } else {
                mx = event.offsetX;
                my = event.offsetY;
            }

            if (designer.edit_mode) designer.selected_box = designer.onbox(mx,my);
            if (!designer.selected_box) $("#when-selected").hide();

            designer.draw()
        });

        $(this.canvas).bind('touchstart mousedown', function(e) {
            designer.mousedown = true;

            var mx = 0, my = 0;
            var event = designer.get_unified_event(e);
            if(event.offsetX==undefined) // this works for Firefox
            {
                mx = (event.pageX - $(event.target).offset().left);
                my = (event.pageY - $(event.target).offset().top);
            } else {
                mx = event.offsetX;
                my = event.offsetY;
            }

            if (designer.edit_mode)
            {
                // If its not yet selected check if a box is selected now
                if (!designer.selected_box) designer.selected_box = designer.onbox(mx,my);

                if (designer.selected_box) {
                    $("#when-selected").show();
                    resize = designer.boxlist[designer.selected_box];

                    var rightedge = resize['left']+resize['width'];
                    var bottedge = resize['top']+resize['height'];
                    var midx = resize['left']+(resize['width']/2);
                    var midy = resize['top']+(resize['height']/2);

                    if (Math.abs(mx - rightedge)<4)
                        selected_edge = selected_edges.right;
                    else if (Math.abs(mx - resize['left'])<4)
                        selected_edge = selected_edges.left;
                    else if (Math.abs(my - bottedge)<4)
                        selected_edge = selected_edges.bottom;
                    else if (Math.abs(my - resize['top'])<4)
                        selected_edge = selected_edges.top;
                    else if (Math.abs(my - midy)<4 && Math.abs(mx - midx)<4)
                        selected_edge = selected_edges.center;
                    else
                        selected_edge = selected_edges.none;

                    designer.draw();
                }
            }
            else
            {
                if (designer.create)
                {
                    designer.add_widget(mx,my,designer.create);
                    designer.create = null;
                    $("#when-selected").show();
                }
            }
        });

        $(this.canvas).bind('touchend touchcancel mouseup', function(event) {
            designer.mousedown = false;
            selected_edge = selected_edges.none;
            designer.draw();
        });

        $(this.canvas).bind('touchmove mousemove', function(e) {
            if (designer.mousedown && designer.selected_box && selected_edge){

                var mx = 0, my = 0;
                var event = designer.get_unified_event(e);
                if(event.offsetX==undefined) // this works for Firefox
                {
                    mx = (event.pageX - $(event.target).offset().left);
                    my = (event.pageY - $(event.target).offset().top);
                } else {
                    mx = event.offsetX;
                    my = event.offsetY;
                }
                // Force limits to designer area
                if (mx < 0) mx = 0; else if (mx >  designer.page_width) mx = designer.page_width;
                if (my < 0) my = 0;

                var rightedge = resize['left']+resize['width'];
                var bottedge = resize['top']+resize['height'];

                switch(selected_edge)
                {
                    case selected_edges.right:
                        designer.boxlist[designer.selected_box]['width'] = (designer.snap(mx)-resize['left']);
                        break;
                    case selected_edges.left:
                        designer.boxlist[designer.selected_box]['left'] = (designer.snap(mx));
                        designer.boxlist[designer.selected_box]['width'] = rightedge - designer.snap(mx);
                        break;
                    case selected_edges.bottom:
                        designer.boxlist[designer.selected_box]['height'] = (designer.snap(my)-resize['top']);
                        break;
                    case selected_edges.top:
                        designer.boxlist[designer.selected_box]['top'] = (designer.snap(my));
                        designer.boxlist[designer.selected_box]['height'] = bottedge - designer.snap(my);
                        break;
                    case selected_edges.center:
                        designer.boxlist[designer.selected_box]['left'] = (designer.snap(mx-designer.boxlist[designer.selected_box]['width']/2));
                        designer.boxlist[designer.selected_box]['top'] = (designer.snap(my-designer.boxlist[designer.selected_box]['height']/2));
                        break;
                }
                if (designer.boxlist[designer.selected_box]['width'] < designer.grid_size) designer.boxlist[designer.selected_box]['width'] = designer.grid_size;    // Zero cant be selected se we default to minimal grid size
                if (designer.boxlist[designer.selected_box]['height'] < designer.grid_size) designer.boxlist[designer.selected_box]['height'] = designer.grid_size;
                
                if (bottedge>designer.page_height-designer.grid_size)
                {
                    designer.page_height = bottedge+designer.grid_size;
                }

                designer.draw();
                designer.modified();

                return false;
            }
        });

        // On save click
        $("#options-save").click(function()
        {
            $(".options").each(function() {
                if ($(this).attr("id")=="html")
                {
                    $("#"+designer.selected_box).html($(this).val());
                }
                else if ($(this).attr("id")=="colour")
                {
                    // Since colour values are generally prefixed with "#", and "#" isn't valid in URLs, we strip out the "#".
                    // It will be replaced by the value-checking in the actual plot function, so this won't cause issues.
                    var colour = $(this).val();
                    colour = colour.replace("#","");
                    $("#"+designer.selected_box).attr($(this).attr("id"), colour);
                }
                else if ($(this).attr("id").indexOf("styleUnit") == 0)
                {
                    //Get styleUnit* options and set it to boxlist array
                    designer.boxlist[designer.selected_box][$(this).attr("id")]=parseInt($(this).val());
                }
                else
                {
                    $("#"+designer.selected_box).attr($(this).attr("id"), $(this).val());
                }
            });
            $('#widget_options').modal('hide')
            designer.draw();
            designer.modified();
            reloadiframe = designer.selected_box;
        });

         $("#delete-button").click(function(event) {
            if (designer.selected_box)
            {
                delete designer.boxlist[designer.selected_box];
                $("#"+designer.selected_box).remove();
                designer.selected_box = 0;
                designer.draw();
                designer.modified();
                $("#when-selected").hide();
            }
        });

        $("#options-button").click(function(event) {
            if (designer.selected_box){
                designer.draw_options($("#"+designer.selected_box).attr("class"));
            }
        });
    }
}
