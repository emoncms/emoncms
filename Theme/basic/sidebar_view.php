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
    foreach($sidebar['category'] as $item) {
        $matches = (array) $item['active'];
        if (in_array($route->controller, $matches)) {
            echo $item['title'];
        }
    }
?>
    </h4>
    <?php /*
    <ul id="top_nav" class="nav sidenav-menu btn-group d-flex">
    <?php
    // top level menu icons
    if(!empty($sidebar['category'][$route->controller])): ?>
        <?php echo makeListLink($sidebar['category'][$route->controller]); ?>
    <?php endif;?>
    </ul>
    */ ?>

    <?php 
    // 2nd level links
    if (in_array($route->controller, explode(',','graph,input,feed,device,config,admin'))): ?>
    <ul id="sub_nav" class="nav sidenav-menu">
    <?php if(!empty($sidebar['setup'])): foreach($sidebar['setup'] as $item): ?>
        <?php echo makeListLink($item) ?>
    <?php endforeach; endif;?>
    </ul>
    <?php endif; ?>

    <?php /*
    // graph only links
    if (in_array($route->controller, explode(',','graph'))): ?>
    <ul id="sub_nav" class="nav sidenav-men">
    <?php if(!empty($sidebar['setup'])): foreach($sidebar['setup'] as $item): ?>
        <?php if ($item['path']=='graph') echo makeListLink($item) ?>
    <?php endforeach; endif;?>
    </ul>
    <?php endif;  */ ?>

    <ul id="module_nav" class="nav sidenav-menu">
    <?php 
    // controller specific links
    if(!empty($sidebar[$route->controller])): foreach($sidebar[$route->controller] as $key=>$item):
        echo makeListLink($item);
    endforeach; endif;
    ?>
    </ul>

    <?php 
    // controller specific includes
    if(!empty($sidebar['includes'][$route->controller])): foreach($sidebar['includes'][$route->controller] as $item): ?>
        <?php echo $item; ?>
    <?php endforeach; endif; ?>

</div>
<div id="footer_nav" class="nav">
    <?php
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
    ?>
    <ul id="sidebar_user_dropdown" class="nav sidenav-menu collapse">
    <?php 
        $controller = 'user';
        // @todo: check for controller specific footer menus
        if(!empty($sidebar['footer'][$controller])): foreach($sidebar['footer'][$controller] as $item): 
            echo makeListLink($item);
        endforeach; endif;
    ?>
    </ul>
</div>

<script>
    var list = document.getElementById('sidebar_user_dropdown');
    document.getElementById('sidebar_user_toggle').addEventListener('click', function(){
        // list.classList.toggle('collapsed');
        if(list.parentNode) list.parentNode.classList.toggle('expanded');
    })
    document.querySelectorAll('a[data-toggle="collapse"]').forEach(function(item){
        item.addEventListener('click', function(event){
            event.preventDefault();
        });
        var sidebar_footer = document.getElementById('footer_nav')
        // sidebar_footer.style.position = 'absolute';
        // setTimeout(function(){
        //     sidebar_footer.style.position = 'fixed';
        // }, 1000);
    })

</script>