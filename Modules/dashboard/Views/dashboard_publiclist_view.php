<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

  global $path;
?>
<br>
<h2><?php echo _('Explore Public Dashboards'); ?></h2>
<br>
<?php 
  if (isset($dashboards) && count($dashboards)) { ?>
  <table class='catlist'>
    <tr>
      <th><?php echo _('User'); ?></th>
      <th><?php echo _('Name'); ?></th>
      <th><?php echo _('Alias'); ?></th>
      <th><?php echo _('Description'); ?></th>
      <th><?php echo _('View'); ?></th> 
    </tr>
  
    <?php foreach ($dashboards as $dashboard) { ?>
    <tr class="d0">
      <td><?php echo $dashboard['username']; ?></td>
      <td><?php echo $dashboard['name']; ?></td>
      <td><?php echo $dashboard['alias']; ?></td>
      <td><?php echo $dashboard['description']; ?></td>
      <td><a href="<?php echo $path.$dashboard['username'].'/'.$dashboard['alias']; ?>" title="<?php echo _('View'); ?>" ><i class='icon-arrow-right'></i></a></td>   
    </tr>
    <?php } // end foreach 
  } // endif ?> 
</table>


