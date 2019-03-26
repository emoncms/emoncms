<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/
if (!isset($session['profile'])) {
    $session['profile'] = 0;
}
$third_level_open_status = array();

// build the individual menu parts
foreach($menu['sidebar'] as $menu_key => $sub_menu) {
    if(!empty($sub_menu)) { 
        if($menu_key === 'includes') break;
        
        // create array of 3rd level navigation markup
        if (!empty($menu['sidebar']['includes'][$menu_key])) {
            foreach($menu['sidebar']['includes'][$menu_key] as $controller_name=>$third_level_items) {
                // third level can be list of links or plain <html>
                $third_level_items = (array) $third_level_items; // typecast to array
                // loop through items array|string
                foreach($third_level_items as $third_level_index => $third_level_item) {
                    if(is_menu_item($third_level_item)) {
                        $third_level_item['li_class'] = 'collapse in';
                        $third_level_menus[$menu_key][$controller_name][] = makeListLink($third_level_item);
                    } else {
                        $third_level_includes[$menu_key][$controller_name][] = $third_level_item;
                    }
                    // check if 2nd level indiciator should show that 3rd level menu is active
                    $third_level_open_status[$controller_name] = thirdLevelActive($third_level_item);
                }
            }
        }
        if(empty($third_level_open_status[$route->controller])) {
            $third_level_open = false;
        } else {
            $third_level_open = true;
        }
        
        // create array of 2nd level navigation markup
        foreach($sub_menu as $second_level_item) {
            $path_controller = getPathController($second_level_item['path']);
            $second_level_item['li_class'][] = 'collapse';
            if(!$third_level_open) {
                $second_level_item['li_class'][] = 'in';
            }
            $second_level_menus[$menu_key][$path_controller][] = $second_level_item;
        }
    }
}

// output the <html> as a series of complete menus
foreach($second_level_menus as $menu_key => $second_level_menu) {
    $markup = array();
    foreach($second_level_menu as $controller_name => $second_level_items) {
        $items = array();
        $active = "";

        foreach($second_level_items as $item_key => $item) {
            if (is_menu_item($item)) {
                if ( $third_level_open && is_current_group($second_level_menu) ) {
                    // active 2nd level menu item (parent)
                    if ($route->controller === getPathController(getKeyValue('path', $item))) {
                        $item['li_class'][] = 'in';
                        if( empty($item['title'])) $item['title'] = stripslashes($item['text']);
                        $item['text'].='<span class="pull-right third-level-indicator">';
                        $item['text'].='  <svg class="icon"><use xlink:href="#icon-arrow_back"></use></svg>';
                        $item['text'].='</span>';
                    }
                } else {
                    // all 2nd level items
                    $item['li_class'][] = 'in';
                }
                $items[] = is_menu_item($item) ? makeListLink($item): $item;
            } else {
                $items[] = $item;
            }
 
        }
        // build the complete list of 2nd level items for each group
        $markup[] = implode(tab(5), $items);
    }
    $active_css = is_current_group($second_level_menu) ? ' active': '';
    
    echo <<<SIDEBARSTART

    <div id="sidebar_{$menu_key}" class="sidebar-inner{$active_css}">
        <a href="#" class="close btn btn-large btn-link pull-right" data-toggle="slide-collapse" data-target="#sidebar">&times;</a>
        <h4 id="sidebar-title">{$menu_key}</h4>

SIDEBARSTART;

    if(!empty($markup)) {
        printf(tab(4).'<ul id="menu-%s" class="nav sidebar-menu">%s'.tab(4).'</ul>', $menu_key, tab(5).implode(tab(5),$markup));

        // module specific menu - set in menu file in each module directory
        if(!empty($third_level_menus[$menu_key])) {
            foreach($third_level_menus[$menu_key] as $item_key => $_menu) {
                $active2 = $item_key === $route->controller ? 'in': '';
                $markup2 = sprintf(tab(5).'<ul class="nav sidebar-menu sub-nav">%s'.tab(5).'</ul>', tab(6).implode(tab(6), $_menu));
                printf(tab(4).'<section class="collapse %s" id="%s-%s-sidebar-include">%s'.tab(4).'</section>'."\n", $active2, $menu_key, $item_key, $markup2);
            }
        }
        
        // module specific includes - set in menu file in each module directory
        if(!empty($third_level_includes[$menu_key])) {
            foreach($third_level_includes[$menu_key] as $include_key => $include) {
                $active3 = $include_key === $route->controller ? 'in': '';
                $markup3 = "\n\t".implode("\n\t", $include);
                printf(tab(4).'<section class="collapse %s include-container" id="%s-%s-sidebar-include">%s'.tab(4).'</section>'."\n", $active3, $menu_key, $include_key, $markup3);
            }
        }
    }
    echo tab(3)."</div>";
}
?>

                <div id="footer_nav" class="nav">
                    <?php
                    // sidebar user footer menu
                        if($session['read']){
                            $link = array(
                                'text' => $session['username'],
                                'class'=> 'collapsed',
                                'href' => '#',
                                'id' => 'sidebar_user_toggle',
                                'icon' => 'user',
                                'data' => array(
                                    'toggle' => 'collapse',
                                    'target' => '#sidebar_user_dropdown'
                                )
                            );
                            if ($session['admin'] == 1) {
                                $link['text'] .= ' <small class="muted">Admin</small>';
                            }
                            $link['text'] .= '<span class="arrow arrow-up pull-right"></span>';
                            echo makeLink($link);
                        }
                    ?>
                    <ul id="sidebar_user_dropdown" class="nav sidebar-menu collapse">
                    <?php 
                        $controller = 'user';
                        // @todo: check for controller specific footer menus
                        if(!empty($menu[$controller])): foreach($menu[$controller] as $item): 
                            echo makeListLink($item);
                        endforeach; endif;
                    ?>
                    </ul>
                </div>


                <script>
                    // manage the open/close of the user menu in the sidebar
                    var list = document.getElementById('sidebar_user_dropdown');
                    var user_toggle = document.getElementById('sidebar_user_toggle');
                    if(user_toggle) {
                        user_toggle.addEventListener('click', function(event){
                            if(list.parentNode) list.parentNode.classList.toggle('expanded');
                            event.preventDefault();
                        })
                    }
                    document.querySelectorAll('a[data-toggle="collapse"]').forEach(function(item){
                        item.addEventListener('click', function(event){
                            event.preventDefault();
                        });
                        var sidebar_footer = document.getElementById('footer_nav');
                    })

                </script>