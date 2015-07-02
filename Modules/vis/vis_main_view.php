<!--
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
-->

<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

global $path;
?>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.time.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/multigraph.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/vis.helper.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/multigraph_api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/multigraph_edit.js"></script>

<h2><?php echo _("Visualisations"); ?></h2>

<div id="vispage">
<div style="float:left">

<div style="width:320px; background-color:#efefef; margin-bottom:10px; border: 1px solid #ddd;">
    <div style="padding:10px;  border-top: 1px solid #fff">
        <div style="float:left; padding-top:2px; font-weight:bold;">1) <?php echo _("Select visualisation:")?> </div>
        <div style="float:right;">
            <span id="select"></span>
        </div>
        <div style="clear:both"></div>
    </div>
</div>

<div style="width:320px; background-color:#efefef; margin-bottom:10px; border: 1px solid #ddd;">
    <div style="padding:10px;  border-top: 1px solid #fff">
        <div style="padding-top:2px; font-weight:bold;">2) <?php echo _("Set options:")?> </div><br>
        <div id="box-options" ></div><br>
        <p style="font-size:12px; color:#444;"><b><?php echo _("Note:");?></b> <?php echo _("If a feed does not appear in the selection box, check that the type has been set on the feeds page."); ?></p>
    </div>
</div>

<div style="width:320px; background-color:#efefef; margin-bottom:10px; border: 1px solid #ddd;">
    <div style="padding:10px;  border-top: 1px solid #fff">
        <div style="float:left; padding-top:2px; font-weight:bold;">3) </div>
        <div style="float:right;">
        <input id="viewbtn" type="submit" value="<?php echo _("View"); ?>" class="btn btn-info" />
        <input id="fullscreen" type="submit" value="<?php echo _("Full screen"); ?>" class="btn btn-info" />
        </div>
        <div style="clear:both"></div>
    </div>
</div>

<div style="width:320px; background-color:#efefef; margin-bottom:10px; border: 1px solid #ddd;">
    <div style="padding:10px;  border-top: 1px solid #fff">
        <div style="padding-top:2px; font-weight:bold;"><?php echo _("Embed in your website:"); ?> </div><br>
        <textarea id="embedcode" style="width:290px; height:120px;" readonly="readonly"></textarea>
    </div>
</div>

</div>

<div id="vis_bound" style="width:600px; height:420px; float:right">
        <div id="visiframe"><div style="height:400px; border: 1px solid #ddd; " ></div></div>
</div>

<div id="visurl"></div>

</div>

<script type="application/javascript">
    var path = "<?php echo $path; ?>";
    var feedlist = <?php echo json_encode($feedlist); ?>;
    var widgets = <?php echo json_encode($visualisations); ?>;

    var embed = 0;

    //var apikey = "<?php echo $apikey; ?>";
    var apikey = "";

    vis_resize();
    
    var out = '<select id="visselect" style="width:180px; margin:0px;">';
    for (z in widgets)
    {
        // If widget action specified: use action otherwise override with widget key
        var action = z;
        var label = z;
        if (widgets[z]['action']!=undefined) action = widgets[z]['action'];
        if (widgets[z]['label']!=undefined) label = widgets[z]['label'];
        out += "<option value='"+action+"' >"+label+"</option>";
    }
    out += '</select>';
    $("#select").html(out);

    draw_options(widgets['realtime']['options']);

    
    // --- Actions

    $("#visselect").change(function() {
        // Custom multigraph visualisation items
        if ($(this).val()=="multigraph")
        {
            multigraphGUI();
        }
        else
        {
            $("#viewbtn").show();
            // Normal visualisation items
            draw_options(widgets[$(this).val()]['options'], widgets[$(this).val()]['optionstype']);
        }
    });

    $("#viewbtn").click(function(){
        var visurl = "";
        var vistype = $("#visselect").val();
        visurl += path+"vis/"+vistype;

        // Here we go through all the options that are set and get their values creating a url string that gets the
        // visualisation. We also check for each feed if the feed is a public feed or not.
        // If the feed is not public then we include the read apikey in the embed code box.

        var publicfeed = 1;
        var options = [];
        $(".options").each(function() {
            if ($(this).val()) {
                if ($(this).attr("id")=="colour")
                {
                    // Since colour values are generally prefixed with "#", and "#" isn't valid in URLs, we strip out the "#".
                    // It will be replaced by the value-checking in the actual plot function, so this won't cause issues.
                    var colour = $(this).val();
                    colour = colour.replace("#","");
                    options.push($(this).attr("id")+"="+colour);
                }
                else 
                {
                    options.push($(this).attr("id")+"="+$(this).val());
                }
                if ($(this).attr("otype")=='feed') publicfeed = $('option:selected', this).attr('public');
            }
        });
        
        visurl += "?"+options.join("&");
        var width = $("#vis_bound").width();
        var height = width * 0.58;
        if (vistype == "compare") height = height * 3;
        $("#visiframe").width(width);
        $("#visiframe").height(height);
        $("#visiframe").html('<iframe style="width:'+width+'px; height:'+height+'px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visurl+'&embed=1"></iframe>');
        vis_resize();

        if (publicfeed == 1) $("#embedcode").val('<iframe style="width:580px; height:400px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visurl+'&embed=1"></iframe>'); else $("#embedcode").val('<?php echo addslashes(_("Some of the feeds selected are not public, to embed a visualisation publicly first make the feeds that you want to use public."));?>\n\n<?php echo _("To embed privately:");?>\n\n<iframe style="width:580px; height:400px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visurl+'&embed=1&apikey='+apikey+'"></iframe>');
    });

    $("#fullscreen").click(function(){
        var visurl = "";
        var vistype = $("#visselect").val();

        // Added in custom action for multigraph
        // if the vis type is multigraph then we construct
        // the visurl with multigraph?id=1
        if (vistype=="multigraph")
        {
            visurl = "multigraph?mid="+multigraph_id;
        }
        else
        {
            visurl += path+"vis/"+vistype;
            var options = [];
            $(".options").each(function() {
                if ($(this).val())
                {
                    if ($(this).attr("id")=="colour")
                    {
                        // Since colour values are generally prefixed with "#", and "#" isn't valid in URLs, we strip out the "#".
                        // It will be replaced by the value-checking in the actual plot function, so this won't cause issues.
                        var colour = $(this).val();
                        colour = colour.replace("#","");
                        options.push($(this).attr("id")+"="+colour);
                    }
                    else 
                    {
                        options.push($(this).attr("id")+"="+$(this).val());
                    }
                }
            });
        }
        if (options) visurl += "?"+options.join("&");
        //$(window.location).attr('href',visurl);
        window.open(visurl,'_blank');
    });

    $(window).resize(function(){vis_resize();});


    // --- Functions
    function draw_options(box_options)
    {
        // Build options table html
        var options_html = "";
        for (z in box_options)
        {

            options_html += "<div class='input-prepend'><span class='add-on' style='width: 70px; text-align: right;'>"+box_options[z][1]+"</span>";

            var type = box_options[z][2];

            if (type == 0 || type == 1 || type == 2 || type == 3)
            {
                options_html += select_feed(box_options[z][0], feedlist, type);
            }
            else
            {
                if (box_options[z][0] == "colour") 
                {
                    options_html += "<input type='color' class='options' id='"+box_options[z][0]+"' value='#"+box_options[z][3]+"'>";
                } else {
                    options_html += "<input type='text' class='options' id='"+box_options[z][0]+"' value='"+box_options[z][3]+"'>";
                }
            }
            options_html += "</div>";
        }
        options_html += "";

        $("#box-options").html(options_html);
    }

    // Create a drop down select box with a list of feeds.
    function select_feed(id, feedlist, type)
    {
        var feedgroups = [];
        for (z in feedlist) {
            if (feedlist[z].datatype == type || (type == 0 && (feedlist[z].datatype == 1 || feedlist[z].datatype == 2))) {
                var group = (feedlist[z].tag === null ? "NoGroup" : feedlist[z].tag);
                if (group!="Deleted") {
                    if (!feedgroups[group]) feedgroups[group] = []
                    feedgroups[group].push(feedlist[z]);
                }
            }
        }
        var out = "<select id='"+id+"' class='options' otype='feed'>";
        for (z in feedgroups) {
            out += "<optgroup label='"+z+"'>";
            for (p in feedgroups[z]) {
                out += "<option value="+feedgroups[z][p]['id']+" public="+feedgroups[z][p]['public']+">"+feedgroups[z][p].id+": "+feedgroups[z][p].name+"</option>";
            }
            out += "</optgroup>";
        }
        out += "</select>";
        return out;
    }

    function vis_resize()
    {
        var viswidth = $("#vispage").width() - 340;
        var visheight = $("#visiframe").height();

        $("#vis_bound").width(viswidth);
        $("#vis_bound").height(visheight);
        $("#visiframe").width(viswidth);
        $("#visiframe").height(visheight);
    }

</script>
