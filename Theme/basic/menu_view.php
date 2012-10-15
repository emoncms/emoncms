<?php
  /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  */

  global $path, $session, $menu_left, $menu_right;

?>

<ul class="nav">
<?php 
foreach ($menu_left as $item) 
{
  if (isset($session[$item['session']]) && $session[$item['session']]==1) 
    echo "<li><a href=".$path.$item['path']." >"._($item['name'])."</a></li>";
} 
?>
</ul>

<ul class="nav pull-right">
<?php 
foreach ($menu_right as $item) 
{
  if (isset($session[$item['session']]) && $session[$item['session']]==1) 
    echo "<li><a href=".$path.$item['path']." >"._($item['name'])."</a></li>";
} 
?>
</ul>
