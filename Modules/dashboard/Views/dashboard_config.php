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

?>

<div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">

    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel"><?php echo _('Dashboard Configuration'); ?></h3>
    </div>

    <div class="modal-body">

        <label><?php echo _('Dashboard name: '); ?></label>
        <input type="text" name="name" value="<?php echo $dashboard['name']; ?>" />
        <label><?php echo _('Menu name: (lowercase a-z only)'); ?></label>
        <input type="text" name="alias" value="<?php echo $dashboard['alias']; ?>" />
        <label><?php echo _('Description: '); ?></label>
        <textarea name="description"><?php echo $dashboard['description']; ?></textarea>

        <label class="checkbox">
            <input type="checkbox" name="main" id="chk_main" value="1" <?php if ($dashboard['main'] == true) echo 'checked'; ?> />
            <abbr title="<?php echo _('Make this dashboard the first shown'); ?>"><?php echo _('Main'); ?></abbr>
        </label>

        <label class="checkbox">
            <input type="checkbox" name="published" id="chk_published" value="1" <?php if ($dashboard['published'] == true) echo 'checked'; ?> />
            <abbr title="<?php echo _('Activate this dashboard'); ?>"><?php echo _('Published'); ?></abbr>
        </label>

        <label class="checkbox">
            <input type="checkbox" name="public" id="chk_public" value="1" <?php if ($dashboard['public'] == true) echo 'checked'; ?> />
            <abbr title="<?php echo _('Anyone with the URL can see this dashboard'); ?>"><?php echo _('Public'); ?></abbr>
        </label>

        <label class="checkbox">
            <input type="checkbox" name="showdescription" id="chk_showdescription" value="1" <?php if ($dashboard['showdescription'] == true) echo 'checked'; ?> />
            <abbr title="<?php echo _('Shows dashboard description on mouse over dashboard name in menu project'); ?>"><?php echo _('Show description'); ?></abbr>
        </label>

    </div>

    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Close'); ?></button>
        <button id="configure-save" class="btn btn-primary"><?php echo _('Save changes'); ?></button>
    </div>

</div>

<script type="application/javascript">

    var dashid = <?php echo $dashboard['id']; ?>;
    var path = "<?php echo $path; ?>";

    $("#configure-save").click(function ()
    {
        var fields = {};

        fields['name'] = $("input[name=name]").val();
        fields['alias']  = $("input[name=alias]").val();
        fields['description']  = $("input[name=description]").val();

        if ($("#chk_main").is(":checked")) fields['main'] = true; else fields['main'] = false;
            if ($("#chk_public").is(":checked")) fields['public'] = true; else fields['public'] = false;
        if ($("#chk_published").is(":checked")) fields['published'] = true; else fields['published'] = false;
            if ($("#chk_showdescription").is(":checked")) fields['showdescription'] = true; else fields['showdescription'] = false;

        $.ajax({
            url :  path+"dashboard/set.json",
            data : "&id="+dashid+"&fields="+JSON.stringify(fields),
            dataType : 'json',
            success : function(result) {console.log(result)}
        });

        $('#myModal').modal('hide');
    });
</script>

