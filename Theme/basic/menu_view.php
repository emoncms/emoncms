<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/

global $path, $session, $menu;
if (!isset($session['profile'])) {
    $session['profile'] = 0;
}

?>

<ul class='nav'>
<?php
echo '<li class="btn-li"><a data-toggle="slide-collapse" data-target="#sidebar" href="#" class="btn"><svg class="icon"><use xlink:href="#icon-menu"></use></svg></a></li>';

if(!empty($menu['left'])): foreach ($menu['left'] as $item):
    $item['class'] = 'menu-left';
    echo makeListLink($item);
endforeach; endif;

// top level menu icons
if(!empty($menu['category'])): foreach($menu['category'] as $item):
    echo makeListLink($item);
endforeach; endif;
?>
</ul>


<ul class='nav pull-right'>
<?php

// if (!empty($menu['dropdown']) && count($menu['dropdown']) && $session['read']) {
//     $extra = array();
//     $extra['name'] = 'Extra';
//     $extra['icon'] = 'icon-plus icon-white';
//     $extra['class'] = 'menu-extra';
//     $extra['session'] = 'read';
//     $extra['dropdown'] = $menu['dropdown'];
//     echo makeListLink($extra);
// }

if(!empty($menu['right'])): foreach ($menu['right'] as $item):
    $item['class'] = 'menu-right';
    echo makeListLink($item);
endforeach; endif;
?>


<?php

if($session['userid']>0){
    $item = array(
        'li_class' => 'menu-user',
        'text' => $session['username'],
        'href' => '#',
        'icon' => 'user'
    );
    // add user_menu.php items
    $controller = 'user';
    if(!empty($menu[$controller])): foreach($menu[$controller] as $sub_item): 
        $item['sub_items'][] = makeListLink($sub_item);
    endforeach; endif;
    
    // indicate if user is admin
    if ($session['admin'] == 1) {
        $item['text'] .= ' <small>(' . _('Admin') . ')</small>';
    }

    // build dropdown with above items
    echo makeDropdown($item);

} else {
    // show login link to non-logged in users
    echo makeListLink(array(
        'path'=>'/',
        'text'=>_('Login'),
        'icon'=>'user'
    ));

} ?>
</ul>
