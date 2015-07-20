<?php

    /*
        All Emoncms code is released under the GNU Affero General Public License.
        See COPYRIGHT.txt and LICENSE.txt.

        ---------------------------------------------------------------------
        Emoncms - open source energy visualisation
        Part of the OpenEnergyMonitor project:
        http://openenergymonitor.org
    */

    global $path, $session, $menu;
    if (!isset($session['profile'])) $session['profile'] = 0;

    if ($session['write']) $menu['right'][] = array('name'=>"<b>Docs</b>", 'path'=>"site/docs", 'order' => 0 );
    if (!$session['write']) $menu['right'][] = array('name'=>"Log In", 'path'=>"user/login", 'order' => -1 );
    
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
                            // TODO: Remove dependency of index position on APPs module
                            $outdrop .= '<li><a href="' . $path . (isset($dropdownitem['path']) ? $dropdownitem['path']:$dropdownitem['1']) . '">' . (isset($dropdownitem['name']) ? $dropdownitem['name']:$dropdownitem['0']) . '</a></li>';
                        }
                    }
                }
                if ($i > 0) {
                    $out .= '<li class="dropdown">';
                    $out .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown">'. $item['name'] . '<b class="caret"></b></a>';
                    $out .= '<ul class="dropdown-menu">';
                    $out .= $outdrop;
                    $out .= '</ul></li>';
                }   
                else if (isset($item['path']) && isset($item['name'])) {
                    $out .= "<li><a href=\"".$path.$item['path']."\">".$item['name']."</a></li>";
                }
            }
        } else {
            $out .=  "<li><a href=\"".$path.$item['path']."\">".$item['name']."</a></li>";
        }
        return $out;
    }
?>

<ul class="nav">
<?php
    foreach ($menu['left'] as $item) {
        echo drawItem($item);
    }

    if (count($menu['dropdown']) && $session['read']) { 
        $extra = array();
        $extra['name'] = 'Extras';
        $extra['session'] = 'read';
        $extra['dropdown'] = $menu['dropdown'];
        echo drawItem($extra);
    }
?>
</ul>
<ul class="nav pull-right">
<?php
    foreach ($menu['right'] as $item) {
        echo drawItem($item);
    }
?>
</ul>
