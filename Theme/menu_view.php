<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/

    global $path, $session, $menu;
    if (!isset($session['profile'])) $session['profile'] = 0;

    if ($session['write']) $menu['dropdownconfig'][] = array('name'=>'Documentation', 'icon'=>'icon-book', 'path'=>"site/docs", 'order' => 50,'divider' => true);
    if (!$session['write']) $menu['right'][] = array('name'=>"Log In", 'icon'=>'icon-home icon-white', 'path'=>"user/login");
    
    usort($menu['left'], "menu_sort");
    usort($menu['dropdownconfig'], "menu_sort");

    function drawItem($item)
    {
        global $path,$session;
        $out="";
        if (isset($item['session'])) {
            if ((isset($session[$item['session']]) && ($session[$item['session']]==1)) || $item['session'] == 'all') {
                $i = 0;
                if (isset($item['dropdown']) && count($item['dropdown']) > 0) {
                    $outdrop="";
                    foreach ($item['dropdown'] as $dropdownitem) {
                        if (!isset($dropdownitem['session']) || (isset($dropdownitem['session']) && $session[$dropdownitem['session']]==1)) {
                            $i++;
                            if (isset($dropdownitem['divider']) && $dropdownitem['divider']) { $outdrop .= '<li class="divider"></li>'; }
                            // TODO: Remove dependency of index position on APPs module
                            $outdrop .= '<li><a href="' . $path . (isset($dropdownitem['path']) ? $dropdownitem['path']:$dropdownitem['1']) . '">' . (isset($dropdownitem['name']) ? drawNameIcon($dropdownitem,true) : $dropdownitem['0']) . '</a></li>';
                        }
                    }
                }
                if ($i > 0) {
                    $out .= '<li class="dropdown">';
                    $out .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown">' . drawNameIcon($item,false) . '<b class="caret"></b></a>';
                    $out .= '<ul class="dropdown-menu">';
                    $out .= $outdrop;
                    $out .= '</ul></li>';
                }   
                else if (isset($item['path']) && isset($item['name'])) {
                    $out .= "<li><a href=\"".$path.$item['path']."\">" . drawNameIcon($item,false) . "</a></li>";
                }
            }
        } else {
            $out .=  "<li><a href=\"".$path.$item['path']."\">" . drawNameIcon($item,false) . "</a></li>";
        }
        return $out;
    }
    
    function drawNameIcon($item, $showname=false){
        if (isset($item['icon']) && isset($item['name'])) {
            if ($showname) {
                return "<i class='".$item['icon']."' title='".$item['name']."'></i> " . $item['name'];
            } else {
                return "<div style='display: inline'><i class='".$item['icon']."' title='".$item['name']."'></i> <span class='visible-desktop visible-phone hidden-tablet'>" . $item['name'] . "</span></div>";
            }
        } else if (isset($item['icon'])) {
            return "<i class='".$item['icon']."'></i>";
        } else if (isset($item['name'])) {
            return $item['name'];
        } else {
            return 'unknown';
        }
    }
    
    // Menu sort by order
    function menu_sort($a,$b) {
        return $a['order']>$b['order'];
    }
?>

<ul class="nav">
<?php
    foreach ($menu['left'] as $item) {
        echo drawItem($item);
    }
?>
</ul>
<ul class="nav pull-right">
<?php
    if (count($menu['dropdown']) && $session['read']) { 
        $extra = array();
        $extra['name'] = 'Extras';
        $extra['icon'] = 'icon-plus icon-white';
        $extra['session'] = 'read';
        $extra['dropdown'] = $menu['dropdown'];
        echo drawItem($extra);
    }

    if (count($menu['dropdownconfig'])) { 
        $setup = array();
        $setup['name'] = 'Setup';
        $setup['icon'] = 'icon-wrench icon-white';
        $setup['session'] = 'read';
        $setup['dropdown'] = $menu['dropdownconfig'];
        echo drawItem($setup);
    }

    foreach ($menu['right'] as $item) {
        echo drawItem($item);
    }
?>
</ul>
