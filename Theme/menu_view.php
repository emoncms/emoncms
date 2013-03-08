<?php

  /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  */

  global $path, $session, $menu, $menu_right, $menu;
  if (!isset($session['profile'])) $session['profile'] = 0;

  $menu_left = $menu['left'];
  $menu_dropdown = $menu['dropdown'];
?>

<style>
  #mainnav li:first-child a { padding-left: 0px; }
</style>

<?php if ($session['profile']==0) { ?>

  <ul class="nav">
    <?php foreach ($menu_left as $item) { ?>
      <?php if (isset($session[$item['session']]) && $session[$item['session']]==1) { ?>
        <li><a href="<?php echo $path.$item['path']; ?>" ><?php echo $item['name']; ?></a></li>
      <?php } ?> 
    <?php } ?>

    <?php if (count($menu_dropdown) && $session['read']) { ?>
    <li class="dropdown">
      <a href="#" class="dropdown-toggle" data-toggle="dropdown">Extras <b class="caret"></b></a>
      <ul class="dropdown-menu">
        <?php foreach ($menu_dropdown as $item) { ?>
          <?php if (isset($session[$item['session']]) && $session[$item['session']]==1) { ?>
            <li><a href="<?php echo $path.$item['path']; ?>"><?php echo $item['name']; ?></a></li>
          <?php } ?>
        <?php } ?>
      </ul>
    </li>
    <?php } ?>
  </ul>

  <ul class="nav pull-right">
    <?php foreach ($menu_right as $item) { ?>
      <?php if (isset($session[$item['session']]) && $session[$item['session']]==1) { ?>
        <li><a href="<?php echo $path.$item['path']; ?>" ><?php echo $item['name']; ?></a></li>
      <?php } ?>
    <?php } ?>
  </ul>

<?php } ?>
