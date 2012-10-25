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

// Sort menu
usort($menu_left, "custom_sort");
// Define the custom sort function
function custom_sort($a,$b) {
  return $a['order']>$b['order'];
}

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
