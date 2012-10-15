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

?>

<!------------------------------------------------------------------------------------------
Dashboard related javascripts
------------------------------------------------------------------------------------------->
<script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.min.js"></script>
<!------------------------------------------------------------------------------------------
Dashboard HTML
------------------------------------------------------------------------------------------->

<script type="application/javascript">
  // Global page vars definition
  var path =   "<?php echo $path; ?>";
</script>

<!-- tool menu TODO:is the same at dashboard_list_view so it could be include from one place to share code -->
<div align="right">
  <a href="#" title="<?php echo _("New dashboard"); ?>" onclick="$.ajax({type : 'POST',url :  path + 'dashboard/new.json  ',data : '',dataType : 'json',success : location.reload()});"><i class="icon-plus-sign"></i></a>
 <!-- <a href="<?php echo $path; ?>dashboard/thumb"><i class="icon-th-large"></i></a> -->
  <a href="<?php echo $path; ?>dashboard/list" title="<?php echo _("List view"); ?>"><i class="icon-th-list"></i></a>
</div>

<?php 
  if (isset($dashboards) && count($dashboards)) { ?>
    <ul class="thumbnails">
    <?php foreach ($dashboards as $dashboard) { ?>
      <li class="span3">
        <div class="thumbnail">
          <img src="http://placehold.it/260x180" alt="">
            <div class="caption">
              <h5><?php echo $dashboard['name']; ?></h5>
              <p><?php echo $dashboard['description']; ?></p>
              <p>
                <a href="#" class="btn btn-danger" onclick="$.ajax({type : 'POST',url :  path + 'dashboard/delete',data : '&id=<?php echo $dashboard['id']; ?>',dataType : 'json',success : location.reload()});"><?php echo _("Delete"); ?></a>            
                <a href="#" class="btn" onclick="$(window.location).attr('href',path+'dashboard/view&id=<?php echo $dashboard['id']; ?>')">View</a>
                <?php if ($useckeditor) { ?>
                  <a href="#" class="btn" onclick="$(window.location).attr('href',path+'dashboard/ckeditor&id=<?php echo $dashboard['id']; ?>')">ckEditor</a>
                <?php } ?>
                <a href="#" class="btn" onclick="$(window.location).attr('href',path+'dashboard/edit&id=<?php echo $dashboard['id']; ?>')">Draw</a>            
              </p>
            </div>
        </div>
      </li>
      <?php } ?>
    </ul>
  <?php } // endif  
  else echo "no dash";
?>
