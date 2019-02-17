<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/
global $path, $session, $route;
if (!isset($session['profile'])) {
    $session['profile'] = 0;
}
?>
<div class="sidenav-inner">
    <h4 id="sidebar-title">
<?php
    //
    // index.php adds category menus items based on item 'path'
    //
    if(isset($menu['category'])):foreach($menu['category'] as $item):
        if(empty($item['active'])){
            $path_parts = explode('/', $item['path']);
            $item['active'] = array($path_parts[0]);
        }
        if(!empty($item['active'])) {
            $matches = (array) $item['active'];
            if (in_array($route->controller, $matches)) {
                echo $item['title'];
            }
        }
    endforeach;endif;
?>
    </h4>

    <?php 
    // 2nd level links
    if (in_array($route->controller, explode(',','graph,input,feed,device,config,admin'))): ?>
    <ul id="sub_nav" class="nav sidenav-menu">
    <?php if(!empty($menu['setup'])): foreach($menu['setup'] as $item): ?>
        <?php echo makeListLink($item) ?>
    <?php endforeach; endif;?>
    </ul>
    <?php endif; ?>

    <ul id="module_nav" class="nav sidenav-menu">
    <?php 
    // controller specific links
    if(!empty($menu[$route->controller])): foreach($menu[$route->controller] as $key=>$item):
        echo makeListLink($item);
    endforeach; endif;
    ?>
    </ul>

    <?php 
    // controller specific includes
    if(!empty($menu['includes'][$route->controller])): foreach($menu['includes'][$route->controller] as $item): ?>
        <?php echo $item; ?>
    <?php endforeach; endif; ?>

</div>
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
    <ul id="sidebar_user_dropdown" class="nav sidenav-menu collapse">
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