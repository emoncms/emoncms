<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/




// logic starts here
// -------------------------------------------------------
// creates all second and third level menus with their associated hierarch
// built up from each Module's `*_menu.php` file
// will mark the active menu and any parent menus
/* EXAMPLE MARKUP OF A SINGLE MENU ---------
http://localhost/emoncms/example/1

<div id="sidebar_apps" class="sidebar-inner active">
    <h4 class="sidebar-title">Apps</h4>
    <ul id="menu-apps" class="nav sidebar-menu">
        <li class="collapse in active"><a class="active" href="http://localhost/emoncms/example/1" title="Example 1">Example 1</a></li>
        <li class="collapse in"><a href="http://localhost/emoncms/example/2" title="Example 2">Example 2</a></li>
    </ul>
</div>



--------- EXAMPLE END */

if (!isset($session['profile'])) {
    $session['profile'] = 0;
}
$third_level_open_status = array();
$default_nav = 'emoncms';
// blank menus
$second_level_menus = array(); // sidebars & dropdowns
$third_level_menus = array(); // sub menu sidebars
$third_level_includes = array(); // module specific sidebar include
$bookmarks = array();

global $mysqli,$user;
require_once "Modules/dashboard/dashboard_model.php";
$dashboard = new Dashboard($mysqli);
$default_dashboard = array();
foreach($dashboard->get_list($session['userid'],false,false) as $item){
    if($item['main']===true){
        $default_dash = $item;
    }
    if($item['published']===true){
        $fav_dash[] = $item;
    }
}
// ADD DEFAULT DASHBOARD
if (!empty($default_dash)) {
    $bookmarks[] = array(
        'text' => _('Default Dashboard'),
        'title'=> sprintf('%s - %s',$default_dash['name'], $default_dash['description']),
        'icon' => 'star',
        'order'=> 999,
        'path' => 'dashboard/view?id='.$default_dash['id']
    );
}
/*
// ADD BOOKMARKED DASHBOARDS
if (!empty($fav_dash)) {
    $orderbase = 999;
    foreach($fav_dash as $fav) {
        $bookmarks[] = array(
            'text' => $fav['name'],
            'path' => 'dashboard/view?id='.$fav['id'],
            'order'=> $orderbase++,
            'icon' => 'dashboard',
            'title'=> $fav['description']
        );
    }
}
*/


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
            if(empty($second_level_item['path'])) {
                settype($second_level_item, 'array');
                $second_level_item['path'] = '';
            }
            $path_controller = getPathController($second_level_item['path']);
            settype($second_level_item['li_class'], 'array');
            $second_level_item['li_class'][] = 'collapse';
            if(!$third_level_open) {
                $second_level_item['li_class'][] = 'in';
            }
            $second_level_menus[$menu_key][$path_controller][] = $second_level_item;
        }
    }
}

// highlight default sidebar if none selected
$empty_sidebar = true;
foreach($second_level_menus as $menu_key => $second_level_menu) {
    if(is_current_group($second_level_menu)) {
        $empty_sidebar = false;
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
        // # CLEAN OUT ARRAY VALUES
        $markup[] = implode(tab(5), array_filter($items, function($var){
            return gettype($var)!=='array';
        }));
    }
    // activate active menu item or default menu
    $active_css = is_current_group($second_level_menu) ||  ($menu_key == $default_nav && $empty_sidebar) ? ' active': '';
    
    $_close = _('Close');



// logic ends here (should be in a controller or model??)
// -------------------------------------------------------
// view starts here





    echo <<<SIDEBARSTART
    <div id="sidebar_{$menu_key}" class="sidebar-inner{$active_css}">
        <a href="#" style="padding: .8em" class="btn btn-large btn-link pull-right btn-dark btn-inverse text-light d-md-none" data-toggle="slide-collapse" data-target="#sidebar" title="{$_close}">&times;</a>
        <h4 class="sidebar-title">{$menu_key}</h4>

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

<?php
// sidebar user shortcut footer menu
$user_bookmarks = $user->getUserBookmarks($session['userid']);
if(!empty($user_bookmarks)) {
    $bookmarks = array_merge($bookmarks, $user_bookmarks);
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// $expanded = !empty($_COOKIE['bookmarks_collapsed']) && $_COOKIE['bookmarks_collapsed']!=='true';
$expanded = true;

if($session['write']){ ?>
                <div id="footer_nav" class="nav <?php echo $expanded ? 'expanded':''?>"<?php if(empty($bookmarks)) echo ' style="display:none"' ?>>
                <?php
                    echo makeLink(array(
                        'text' => _('Bookmarks').':<span class="arrow arrow-up pull-right"></span>',
                        'class'=> array('d-none',!$expanded ? 'collapsed':''),
                        'href' => '#',
                        'id' => 'sidebar_user_toggle',
                        'data' => array(
                            'toggle' => 'collapse',
                            'target' => '#sidebar_user_dropdown'
                        )
                    ));
                ?>
                    <h4 class="sidebar-title d-flex justify-content-between align-items-center">Bookmarks 
                    <a id="edit_bookmarks" class="btn btn-inverse btn-sm btn-link pull-right" type="button" href="/emoncms/user/bookmarks" title="<?php echo _("Edit") ?>"><svg class="icon"><use xlink:href="#icon-cog"></use></svg></a>
                    </h4>
                    <ul id="sidebar_user_dropdown" class="nav sidebar-menu collapse<?php echo $expanded ? ' in':''?>">
                    <?php 
                        // bookmarks
                        // make menu item link to the original and not the bookmark 
                        foreach ($bookmarks as $item){
                            $item['href'] = !empty($item['path']) ? getAbsoluteUrl($item['path']) : ''; // add absolute path
                            $item['path'] = ''; // empty original relative path
                            echo makeListLink($item);
                        }
                    ?>
                    </ul>
                    <!-- used to add more bookmarks -->
                    <template id="bookmark_link"><li><a href=""></a></li></template>
                </div>
<?php } ?>

<?php


// view ends here
// -------------------------------------------------------
// assets start here  (should be in a .js or .css file??)


?>
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
                <style>
                </style>