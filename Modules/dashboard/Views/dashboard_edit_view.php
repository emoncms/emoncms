<?php

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

global $session,$path;

if (!$dashboard['height']) $dashboard['height'] = 400;
?>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/dashboard_langjs.php"></script>

    <link href="<?php echo $path; ?>Modules/dashboard/Views/js/widget.css" rel="stylesheet">

    <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgetlist.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/render.js"></script>

    <script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

    <?php require_once "Modules/dashboard/Views/loadwidgets.php"; ?>

<div id="dashboardpage">

<div id="widget_options" class="modal hide keyboard" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3><?php echo _('Configure element'); ?></h3>
    </div>
    <div id="widget_options_body" class="modal-body"></div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="options-save" class="btn btn-primary"><?php echo _('Save changes'); ?></button>
    </div>
    </div>
</div>

<div style="background-color:#ddd; padding:4px;">
    <span id="widget-buttons"></span>
    <span id="when-selected">
        <button id="options-button" class="btn" data-toggle="modal" data-target="#widget_options"><i class="icon-wrench"></i><?php echo _('Configure'); ?></button>
        <button id="delete-button" class="btn btn-danger"><i class="icon-trash"></i><?php echo _('Delete'); ?></button>
    </span>
    <button id="save-dashboard" class="btn btn-success" style="float:right"><?php echo _('Not modified'); ?></button>
</div>

<div id="page-container" style="height:<?php echo $dashboard['height']; ?>px; position:relative;">
    <div id="page"><?php echo $dashboard['content']; ?></div>
    <canvas id="can" width="940px" height="<?php echo $dashboard['height']; ?>px" style="position:absolute; top:0px; left:0px; margin:0; padding:0;"></canvas>
</div>

<script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/designer.js"></script>
<script type="application/javascript">

    var dashid = <?php echo $dashboard['id']; ?>;
    var path = "<?php echo $path; ?>";
    var apikey = "";
    var feedlist = feed.list();
    var userid = <?php echo $session['userid']; ?>;

    $("#testo").hide();

    var widget = <?php echo json_encode($widgets); ?>;

    for (z in widget)
    {
        var fname = widget[z]+"_widgetlist";
        var fn = window[fname];
        $.extend(widgets,fn());
    }

    var redraw = 0;
    var reloadiframe = 0;

    var grid_size = 20;
    $('#can').width($('#dashboardpage').width());

    designer.canvas = "#can";
    designer.grid_size = 20;
    designer.widgets = widgets;

    designer.init();

    show_dashboard();

    setInterval(function() { update(); }, 10000);
    setInterval(function() { fast_update(); }, 30);


    $("#save-dashboard").click(function (){
        //recalculate the height so the page_height is shrunk to the minimum but still wrapping all components
        //otherwise a user can drag a component far down then up again and a too high value will be stored to db.
        designer.page_height = 0;
        designer.scan();
        $.ajax({
            type: "POST",
            url :  path+"dashboard/setcontent.json",
            data : "&id="+dashid+'&content='+encodeURIComponent($("#page").html())+'&height='+designer.page_height,
            dataType: 'json',
            success : function(data) { console.log(data); if (data.success==true) $("#save-dashboard").attr('class','btn btn-success').text('<?php echo _("Saved") ?>');
            }
        });
    });


    $(window).resize(function(){
        designer.draw();
    });
</script>
