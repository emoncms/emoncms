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

<style>
#mainnav li:first-child a
{
  padding-left: 0px;
}
</style>

<ul id="mainnav" class="nav" style="padding-left: 0px;">

<?php 

// Sort menu
usort($menu_left, "custom_sort");
// Define the custom sort function
function custom_sort($a,$b) {
  return $a['order']>$b['order'];
}

if (!isset($session['profile'])) $session['profile'] = 0;
if ($session['profile']==0)
{
foreach ($menu_left as $item) 
{
  if (isset($session[$item['session']]) && $session[$item['session']]==1) 
    echo "<li><a href=".$path.$item['path']." >"._($item['name'])."</a></li>";
} 
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
