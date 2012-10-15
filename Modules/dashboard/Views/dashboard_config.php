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

<script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
<script src="<?php print $GLOBALS['path']; ?>Lib/bootstrap/js/jquery.js"></script>
<script src="<?php print $GLOBALS['path']; ?>Lib/bootstrap/js/bootstrap-modal.js"></script>
<script src="<?php print $GLOBALS['path']; ?>Lib/bootstrap/js/bootstrap-transition.js"></script>

<div class="modal hide fade" id="myModal">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3><?php echo _('Dashboard Configuration'); ?></h3>
  </div>
  <div class="modal-body">
    <form id="confform" action="">
      <label><?php echo _('Dashboard name: '); ?></label>
      <input type="text" name="name" value="<?php echo $dashboard['name']; ?>" />
      <label><?php echo _('Menu name: (lowercase a-z only)'); ?></label>
      <input type="text" name="alias" value="<?php echo $dashboard['alias']; ?>" />
      <label><?php echo _('Description: '); ?></label>           
      <textarea name="description"><?php echo $dashboard['description']; ?></textarea>      
 		</form>
 		<label class="checkbox">    	
    	<input type="checkbox" name="main" id="chk_main" value="1" <?php
        if ($dashboard['main'] == true)
        	echo 'checked';
        ?> /><abbr title="<?php echo _('Make this dashboard the first shown'); ?>"><?php echo _('Main'); ?></abbr>
  	</label>
		<label class="checkbox">
			<input type="checkbox" name="published" id="chk_published" value="1" <?php
        if ($dashboard['published'] == true)
        	echo 'checked';
        ?> /><abbr title="<?php echo _('Activate this dashboard'); ?>"><?php echo _('Published'); ?></abbr>
		</label>			  	
  	<label class="checkbox">
			<input type="checkbox" name="public" id="chk_public" value="1" <?php
      	if ($dashboard['public'] == true)
        	echo 'checked';
        ?> /><abbr title="<?php echo _('Anyone with the URL can see this dashboard'); ?>"><?php echo _('Public'); ?></abbr>
		</label>			  	
  	<label class="checkbox">
			<input type="checkbox" name="showdescription" id="chk_showdescription" value="1" <?php
        if ($dashboard['showdescription'] == true)
        	echo 'checked';
        ?> /><abbr title="<?php echo _('Shows dashboard description on mouse over dashboard name in menu project'); ?>"><?php echo _('Show description'); ?></abbr>
		</label>			  	
  </div>
  <div class="modal-footer">
    <a href="#" class="btn" data-dismiss="modal"><?php echo _('Close'); ?></a>
    <a href="#" id="configure-save" class="btn btn-primary"><?php echo _('Save changes'); ?></a>
  </div>
</div>

<script type="application/javascript">
  var dashid = <?php echo $dashboard['id']; ?>;
  var path = "<?php echo $path; ?>";

  $("#configure-save").click(function (){
  	// serialize doesnt return unchecked checkboxes so manual url must be built
  	$main = '0';  
  	$public = '0';
  	$published = '0';
  	$showdescription = '0';
  	
  	if ($("#chk_main").is(":checked")) $main = '1';
		if ($("#chk_public").is(":checked")) $public = '1';
  	if ($("#chk_published").is(":checked")) $published = '1';
		if ($("#chk_showdescription").is(":checked")) $showdescription = '1';  	
  	
  	
    $.ajax({
      type : "POST",
      url :  path+"dashboard/setconf",
      //data : $('#confform').serialize()+"&id="+dashid,   // serialize doesnt return unchecked checkboxes
      data : $('#confform').serialize()+"&id="+dashid+"&main="+$main+"&public="+$public+"&published="+$published+"&showdescription="+$showdescription,      
      dataType : 'json',
      success : function() {}
      //success : location.reload()    //// if reload, the editor content not saved is lost!! what to do?
    });
    
    $('#myModal').modal('hide');
  });
</script>
