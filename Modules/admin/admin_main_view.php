<?php global $path, $emoncms_version, $allow_emonpi_update, $log_enabled, $log_filename; ?>
<style>
table tr td.buttons { text-align: right;}
</style>

<h2>Admin</h2>

<table class="table table-striped ">
    <tr>
        <td colspan="2">Emoncms <?php echo _('version'); ?>: <?php echo $emoncms_version; ?></td>
    </tr>
    <tr>
        <td>
            <h3><?php echo _('Users'); ?></h3>
            <p><?php echo _('Administer user accounts'); ?></p>
        </td>
        <td class="buttons"><br>
            <a href="<?php echo $path; ?>admin/users" class="btn btn-info"><?php echo _('Users'); ?></a>
        </td>
    </tr>
    <tr>
        <td>
            <h3><?php echo _('Update database'); ?></h3>
            <p><?php echo _('Run this after updating emoncms, after installing a new module or to check emoncms database status.'); ?></p>
        </td>
        <td class="buttons"><br>
            <a href="<?php echo $path; ?>admin/db" class="btn btn-info"><?php echo _('Update & check'); ?></a>
        </td>
    </tr>
<?php
if ($log_enabled) {
?>
    <tr>
        <td>
            <h3><?php echo _('Logger'); ?></h3>
            <p>View last entries on the logfile: <?php echo $log_filename; ?></p>
            <div id="logreply" style="display:none"></div>
        </td>
        <td class="buttons"><br>
            <button id="getlog" class="btn btn-info"><?php echo _('Show Log'); ?></button>
        </td>
    </tr>
<?php
}
if ($allow_emonpi_update) {
?>
    <tr>
        <td>
            <h3><?php echo _('Update emonPi'); ?></h3>
            <p>Downloads latest Emoncms changes from Github and updates emonPi firmware. See important notes in <a href="https://github.com/openenergymonitor/emonpi/blob/master/Atmega328/emonPi_RFM69CW_RF12Demo_DiscreteSampling/compiled/CHANGE%20LOG.md">emonPi firmware change log.</a></p>
            <p>Note: If using emonBase (Raspberry Pi + RFM69Pi) the updater can still be used to update Emoncms, RFM69Pi firmware will not be changed.</p> 
            <div id="emonpireply" style="display:none"></div>
        </td>
        <td class="buttons"><br>
            <button id="emonpiupdate" class="btn btn-info"><?php echo _('Update Now'); ?></button><br><br>
            <button id="emonpiupdatelog" class="btn btn-info"><?php echo _('View Log'); ?></button>
        </td>
    </tr>
<?php 
}   
?>
</table>

<script>
var path = "<?php echo $path; ?>";
var logrunning = false;

var updater;
function updaterStart(func, interval){
    clearInterval(updater);
    updater = null;
    if (interval > 0) updater = setInterval(func, interval);
}

function getLog() {
    $.ajax({ url: path+"admin/getlog", async: true, dataType: "text", success: function(result)
        {
            $("#logreply").html('<pre class="alert alert-info"><small>'+result+'<small></pre>');
            $("#logreply").show();
        } 
    });
}

$("#getlog").click(function() {
    logrunning = !logrunning;
    if (logrunning) { updaterStart(getLog, 500); }
    else { updaterStart(getLog, 0); $("#logreply").hide(); }
});


$("#emonpiupdate").click(function() {
    $.ajax({ url: path+"admin/emonpi/update", async: true, dataType: "text", success: function(result)
        {
            $("#emonpireply").html('<pre class="alert alert-info"><small>'+result+'<small></pre>');
            $("#emonpireply").show();
        } 
    });
});

$("#emonpiupdatelog").click(function() {
    $.ajax({ url: path+"admin/emonpi/getupdatelog", async: true, dataType: "text", success: function(result)
        {
            $("#emonpireply").html('<pre class="alert alert-info"><small>'+result+'<small></pre>');
            $("#emonpireply").show();
        } 
    });
});
</script>
