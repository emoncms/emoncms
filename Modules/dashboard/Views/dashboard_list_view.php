<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

global $session, $path, $useckeditor;

//require_once "Includes/messages.php";
?>

<script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.min.js"></script>

<script type="application/javascript">
  // Global page vars definition
  var path =   "<?php echo $path; ?>";
</script>

<!-- tool menu TODO:is the same at dashboard_thumb_view so it could be include from one place to share code -->
<div style="float:right">
  <a href="#" title="<?php echo _("New dashboard"); ?>" onclick="$.ajax({type : 'POST',url :  path + 'dashboard/new.json  ',data : '',dataType : 'json',success : location.reload()});"><i class="icon-plus-sign"></i></a>
  <a href="<?php echo $path; ?>dashboard/thumb" title="<?php echo _("Thumb view"); ?>"><i class="icon-th-large"></i></a>
  <!--<a href="<?php echo $path; ?>dashboard/list"><i class="icon-th-list"></i></a>-->
</div>

<h2>Dashboards</h2>
<?php 
  if (isset($dashboards) && count($dashboards)) { ?>
  <table class='catlist'>
    <tr>
        <th>
          <?php echo _('Id'); ?>
        </th>    
        <th>
          <?php echo _('Dashboard'); ?>
        </th>
        <th>
          <?php echo _('Main'); ?> 
        </th>
        <th>
          <?php echo _('Published'); ?> 
        </th>
        <th>
          <?php echo _('Public'); ?> 
        </th>
        <th>
          <?php echo _('Actions'); ?> 
        </th>
        <th>
          <?php echo _('Share'); ?> 
        </th> 
    </tr>
  
    <?php foreach ($dashboards as $dashboard) { ?>
    <tr class="d0">
      <td>
        <?php echo $dashboard['id']; ?>
      </td>
      <td>
        <div align="left">
          <?php echo $dashboard['name']; ?>       
          <h5><?php echo $dashboard['description']; ?></h5>
        </div>
       </td>
      <td>
        <?php
          if ($dashboard['main']) { ?>             
              <i title="<?php echo _('This is the main dashboard'); ?>" class='icon-star'></i>
          <?php } else { ?>          
          <a href="#" title="<?php echo _('Set main dashboard'); ?>" onclick="$.ajax({type : 'POST',url :  path + 'dashboard/setconf  ',data : 'main=1&id=<?php echo $dashboard['id'] ?>',dataType : 'json',success : location.reload()});"><i class='icon-star-empty'></i></a>
        <?php } ?> 
      </td>
      <td>
        <?php
          if ($dashboard['published']) { ?>           
            <a href="#" title="<?php echo _('Unpublish dashboard'); ?>" onclick="$.ajax({type : 'POST',url :  path + 'dashboard/setconf  ',data : 'published=0&id=<?php echo $dashboard['id'] ?>',dataType : 'json',success : location.reload()});"><i class='icon-ok'></i></a>
          <?php } else { ?>
            <a href="#" title="<?php echo _('Publish dashboard'); ?>" onclick="$.ajax({type : 'POST',url :  path + 'dashboard/setconf  ',data : 'published=1&id=<?php echo $dashboard['id'] ?>',dataType : 'json',success : location.reload()});"><i class='icon-remove'></i></a>
          <?php } ?>
      </td>
      <td>
        <?php 
          if ($dashboard['public']) { ?>           
            <a href="#" title="<?php echo _('Make dashboard private'); ?>" onclick="$.ajax({type : 'POST',url :  path + 'dashboard/setconf  ',data : 'public=0&id=<?php echo $dashboard['id'] ?>',dataType : 'json',success : location.reload()});"><i class='icon-globe'></i></a>
          <?php } else { ?>
            <a href="#" title="<?php echo _('Make dashboard public'); ?>" onclick="$.ajax({type : 'POST',url :  path + 'dashboard/setconf  ',data : 'public=1&id=<?php echo $dashboard['id'] ?>',dataType : 'json',success : location.reload()});"><i class='icon-lock'></i></a>
          <?php } ?>
      </td>  
      <td>
        <div>       
          <a href="#" title="<?php echo _('Draw'); ?>" onclick="$(window.location).attr('href',path+'dashboard/edit&id=<?php echo $dashboard['id']; ?>')"><i class='icon-edit'></i></a>
	  			<?php if ($useckeditor) { ?><a href="#" onclick="$(window.location).attr('href',path+'dashboard/ckeditor&id=<?php echo $dashboard['id']; ?>')"><img src="<?php echo $path; ?>/Includes/editors/images/ckicon.png" style="margin-top:-5px;" /></a><?php } ?>
          <a href="#" title="<?php echo _('View'); ?>" onclick="$(window.location).attr('href',path+'dashboard/view&id=<?php echo $dashboard['id']; ?>')"><i class='icon-eye-open'></i></a>
          <a href="#" title="<?php echo _('Delete'); ?>" onclick="$.ajax({type : 'POST',url :  path + 'dashboard/delete',data : '&id=<?php echo $dashboard['id']; ?>',dataType : 'json',success : location.reload()});"><i class='icon-trash'></i></a>
          <a href="#" title="<?php echo _('Clone'); ?>" onclick="$.ajax({type : 'POST',url :  path + 'dashboard/clone',data : '&id=<?php echo $dashboard['id']; ?>',dataType : 'json',success : location.reload()});"><i class='icon-random'></i></a>          
        </div>            
      </td> 
      <td>
        <?php
          if ($dashboard['published']) {
            if ($dashboard['public']) {  ?>
              <a href="<?php echo $GLOBALS['path'].$user['username']."&id=".$dashboard['id'] ?>" title="<?php echo _('Public share URL'); ?>"<i class='icon-share'></i></a>
						<?php } else { ?>
    	        <a href="<?php echo $GLOBALS['path'].$user['username']."&id=".$dashboard['id']."&apikey=".$user['apikey_read'] ?>" title="<?php echo _('Private share URL'); ?>"<i class='icon-share'></i></a>
      	    <?php         
        			} 
          } ?>
      </td>   
    </tr>
    <?php } // end foreach ?>

</table>
<?php  } else {  ?>
<div class="alert alert-block">
  <h4 class="alert-heading">No dashboards created</h4>
  <p>Maybe you would like to add your first dashboard using the 
  <a href="#" onclick="$.ajax({type : 'POST',url:'<?php echo $path; ?>dashboard/new.json',data : '',dataType : 'json',success : location.reload()});"><i class="icon-plus-sign"></i></a> button.
</div>
<?php  } ?>

