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
    <script type="text/javascript"><?php require "Modules/dashboard/dashboard_langjs.php"; ?></script>
    <link href="<?php echo $path; ?>Modules/dashboard/Views/js/widget.css" rel="stylesheet">

    <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/dashboard.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgetlist.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/render.js"></script>

    <script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

    <?php require_once "Modules/dashboard/Views/loadwidgets.php"; ?>

<div id="dashboardpage">
    <div id="widget_options" class="modal hide keyboard" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="myModalLabel"><?php echo _('Configure element'); ?></h3>
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
        <button id="options-button" class="btn" data-toggle="modal" data-target="#widget_options"><i class="icon-wrench"></i> <?php echo _('Configure'); ?></button>
        <button id="delete-button" class="btn btn-danger"><i class="icon-trash"></i> <?php echo _('Delete'); ?></button>
    </span>
    <span><button id="save-dashboard" class="btn btn-success" style="float:right"><?php echo _('Not modified'); ?></button></span>
</div>

<div id="page-container" style="height:<?php echo $dashboard['height']; ?>px; background-color:#<?php echo $dashboard['backgroundcolor']; ?>; position:relative;">
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
    var widget = <?php echo json_encode($widgets); ?>;
    var redraw = 0;
    var reloadiframe = -1; // force iframes url to recalculate for all vis widgets 

    $('#can').width($('#dashboardpage').width());

    render_widgets_init(widget); // populate widgets variable 

    designer.canvas = "#can";
    designer.grid_size = 20; // change default here
    designer.widgets = widgets;
    designer.init();

    render_widgets_start(); // start widgets refresh

    $("#save-dashboard").click(function (){
        //recalculate the height so the page_height is shrunk to the minimum but still wrapping all components
        //otherwise a user can drag a component far down then up again and a too high value will be stored to db.
        designer.page_height = 0;
        designer.scan();
        designer.draw();
        console.log("Dashboard HTML content: " + $("#page").html());
        var result=dashboard.setcontent(dashid,$("#page").html(),designer.page_height)
        if (result.success) {
            $("#save-dashboard").attr('class','btn btn-success').text('<?php echo _("Saved") ?>'); 
        } else {
            alert('ERROR: Could not save Dashboard. '+result.message);
        }
    });

    $(window).resize(function(){
        designer.draw();
    });
</script>
