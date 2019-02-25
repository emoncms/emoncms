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

foreach($menu['sidebar'] as $menu_key => $sub_menu) : ?>
    <?php if($menu_key!=='includes') { ?>
        <div id="sidebar_<?php echo $menu_key ?>" class="sidebar-inner<?php if(is_current_menu($sub_menu)) echo ' active' ?>">
            <a href="#" class="close btn btn-large btn-link pull-right" data-toggle="slide-collapse" data-target="#sidebar">&times;</a>
            <h4 id="sidebar-title"><?php echo $menu_key ?></h4>
            <?php if(!empty($sub_menu)) { ?>
                <ul id="menu-<?php echo $menu_key ?>" class="nav sidebar-menu">
                    <?php
                        foreach($sub_menu as $item): 
                            echo makeListLink($item);
                        endforeach; 
                    ?>
                </ul>
            <?php }

            // controller specific includes
            if(!empty($menu['sidebar']['includes'][$menu_key])): foreach($menu['sidebar']['includes'][$menu_key] as $item):
                printf('<section id="%s-sidebar-include">%s</section>', $menu_key, $item);
            endforeach; endif;
            ?>

        </div>
    <?php } ?>

<?php endforeach;?>

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