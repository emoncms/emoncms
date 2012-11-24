<?php
/*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/
  
global $path, $session;
?>

<script type="text/javascript" src="<?php print $path; ?>Lib/flot/jquery.min.js"></script>
<script type="text/javascript" src="<?php print $path; ?>Lib/listjs/list.js"></script>

<div style="float:right;"><a href="api">Input API Help</a></div>

<h2><?php echo _("Inputs"); ?></h2>

<?php if ($inputs) { ?>
    
<div id="inputlist"></div>

<script type="text/javascript">

  // The list is created using list.js - a javascript dynamic user interface list creator created as part of this project
  // list.js is still in early development.

  var path =  "<?php echo $path; ?>";

  var items = <?php echo json_encode($inputs); ?>;
  console.log(items);

  var fields = 
  {
    'id':{}, 
    'name':
    {
      'button':"input/process/list.html?inputid="
    }, 
    'updated':
    { 
      'format':"updated"
    },
    'value':
    {
      'format':"value", 
    }
  };

  var group_properties = {};

  var group_prefix = "Node ";
  var controller = "input";
  var listaction = "list";
  var deletable = true;
  var restoreable = false;

  listjs("inputlist", 'nodeid', items, fields, group_properties, 5000);

</script>

<?php } else { ?>

<div class="alert alert-block">
<h4 class="alert-heading">No inputs created</h4>
<p>Inputs is the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.</p>
</div>

<p><b>To connect up a NanodeRF:</b></p>
<p>1) Download and open the <a href="https://github.com/openenergymonitor/NanodeRF/NanodeRF_multinode" >NanodeRF_multinode</a> firmware.</p>
<p>2) Set line 83 to: <b>char apikey[] = "<?php echo get_apikey_write($session['userid']); ?>";</b></p>
<p>3) Upload the firmware to your NanodeRF.</p>


<?php } ?> 
