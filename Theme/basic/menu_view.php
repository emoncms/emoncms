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

    // Example how to add a fixed menu item:
    //$menu['dropdownconfig'][] = array('name'=>'Documentation', 'icon'=>'icon-book', 'path'=>"docs", 'session'=>"write", 'order' => 60,'divider' => true);

    usort($menu['left'], "menu_sort");
    usort($menu['dropdown'], "menu_sort");
    usort($menu['dropdownconfig'], "menu_sort");
    usort($menu['right'], "menu_sort");

    function drawItem($item)
    {
        global $path,$session;
        $out="";
        if (isset($item['session'])) {
            if ((isset($session[$item['session']]) && ($session[$item['session']]==1)) || $item['session'] == 'all') {
                $i = 0;
                $subactive = false;
                if (isset($item['dropdown']) && count($item['dropdown']) > 0) {
                    usort($item['dropdown'], "menu_sort");
                    $outdrop="";
                    foreach ($item['dropdown'] as $dropdownitem) {
                        if (!isset($dropdownitem['session']) || (isset($dropdownitem['session']) && $session[$dropdownitem['session']]==1)) {
                            $i++;
                            if (is_active($dropdownitem)) { $subactive = true; }
                            if (isset($dropdownitem['divider']) && $dropdownitem['divider']) { $outdrop .= '<li class="divider"></li>'; }
                            // TODO: Remove dependency of index position on APPs module
                            $outdrop .= '<li class="'. (is_active($dropdownitem) ? ' active' : '') . '"><a href="' . $path . (isset($dropdownitem['path']) ? $dropdownitem['path']:$dropdownitem['1']) . '">' . (isset($dropdownitem['name']) ? drawNameIcon($dropdownitem,true) : $dropdownitem['0']) . '</a></li>';
                        }
                    }
                }
                if ($i > 0) {
                    $out .= '<li class="dropdown' . ($subactive ? " active" : "") . (isset($item['class']) ? " ".$item['class'] : "") . '">';
                    $out .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown">' . drawNameIcon($item,false) . '<b class="caret"></b></a>';
                    $out .= '<ul class="dropdown-menu scrollable-menu">';
                    $out .= $outdrop;
                    $out .= '</ul></li>';
                }
                else if (isset($item['path']) && isset($item['name'])) {
                    $out .= "<li class='" . (is_active($item) ? "active" : "") . (isset($item['class']) ? " ".$item['class'] : "") . "'><a href=\"".$path.$item['path']."\">" . drawNameIcon($item,false) . "</a></li>";
                }
            }
        } else {
            $out .=  "<li class='" . (is_active($item) ? "active" : "") . (isset($item['class']) ? " ".$item['class'] : "") . "'><a href=\"".$path.$item['path']."\">" . drawNameIcon($item,false) . "</a></li>";
        }
        return $out;
    }

    function drawNameIcon($item, $alwaysshowname=false) {
        $out = "";
        $name = false;
        $desc = false;
        $icon = false;
        $published = false;
        $divid = "";
        if (isset($item['name'])) $name = $item['name'];
        if (isset($item['desc'])) $desc = $item['desc'];
        if (isset($item['icon'])) $icon = $item['icon'];
        if (isset($item['published'])) $published = $item['published'];
        if (isset($item['id'])) $divid = "id='".$item['id']."'";
        
        $title = ($desc ? $desc : $name);
        if($name && $published) $name = "<b>".$name."</b>";

        $out = "<div $divid style='display: inline'>";
        if ($icon) $out .= "<i class='".$icon."'" . ($title ? " title='".$title."'" : "") . "></i>";
        if ($name) {
            if ($alwaysshowname || !$icon) {
                $out .= " " . $name;
            } else {
                $out .= " <span class='menu-text'>" . $name . "</span>";
            }
        } else {
            $out .= 'unknown';
        }
        if ($desc) $out .= "<span class='menu-description'><small>".$desc."</small></span>";
        $out .= "</div>";
        return $out;
    }

    function is_active($item) {
        global $route;
        if (isset($item['path']) && ($item['path'] == $route->controller."/".$route->action || $item['path'] == $route->controller."/".$route->action."/".$route->subaction || $item['path'] == $route->controller."/".$route->action."&id=".get('id')))
            return true;
        return false;
    }

    // Menu sort by order
    function menu_sort($a,$b) {
        return $a['order']>$b['order'];
    }
?>

<ul class="nav">
<?php
    foreach ($menu['dashboard'] as $item) {
        $item['class'] = 'menu-dashboard';
        echo drawItem($item);
    }
    foreach ($menu['left'] as $item) {
        $item['class'] = 'menu-left';
        echo drawItem($item);
    }
?>
</ul>
<ul class="nav pull-right">
<?php
    if (count($menu['dropdown']) && $session['read']) {
        $extra = array();
        $extra['name'] = 'Extra';
        $extra['icon'] = 'icon-plus icon-white';
        $extra['class'] = 'menu-extra';
        $extra['session'] = 'read';
        $extra['dropdown'] = $menu['dropdown'];
        echo drawItem($extra);
    }

    if (count($menu['dropdownconfig'])) {
        $setup = array();
        $setup['name'] = 'Setup';
        $setup['icon'] = 'icon-wrench icon-white';
        $setup['class'] = 'menu-setup';
        $setup['session'] = 'read';
        $setup['dropdown'] = $menu['dropdownconfig'];
        echo drawItem($setup);
    }

    foreach ($menu['right'] as $item) {
        $item['class'] = 'menu-right';
        echo drawItem($item);
    }
?>
</ul>
