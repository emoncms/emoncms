<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/

global $path, $session, $menu, $user;
if (!isset($session['profile'])) {
    $session['profile'] = 0;
}
?>
<?php
// if not logged in show login button top right
$nav_layout = $session['read'] ? 'justify-content-between': 'justify-content-end';
?>
<div class="navbar-inner bg-primary text-dark d-flex flex-nowrap <?php echo $nav_layout ?>">

<?php
if ($session['read']) {
?>

<ul id="left-nav" class="nav mr-0 d-flex">

<?php
// $menu['tabs'][] = array(
//     'title'=> _("Open/Close Sidebar"),
//     'id' => 'sidebar-toggle',
//     'href' => '#',
//     'icon' => 'icon-menu',
//     'order' => -1,
//     'li_style' => 'width:0; overflow:hidden; visibility:hidden',
//     'data'=> array(
//         'toggle' => 'slide-collapse',
//         'target' => '#sidebar'
//     )
// );

// top level menu icons (MAIN MENU)
if(!empty($menu['tabs'])) {
    foreach($menu['tabs'] as &$item) {
        // find matching sidebar
        $matching_menu = getChildMenuItems($item);
        // add active class to <li>  if item in matching sidebar is current page/route
        if(is_current_menu($matching_menu)) $item['li_class'][] = 'active';
        // render menu item
        echo makeListLink($item);
    }
}

// left aligned menu items
if(!empty($menu['left'])): foreach ($menu['left'] as $item):
    $item['class'] = 'menu-left';
    echo makeListLink($item);
endforeach; endif;
?>
</ul>

<?php } ?>

<ul id="right-nav" class='nav d-flex align-items-stretch mr-0 pull-right'>

<?php
$isBookmarked = currentPageIsBookmarked();
$addBookmark = array(
    'icon'=>'star_border',
    'href'=>'#',
    'id'=>'set-bookmark',
    'title'=>_('Add Bookmark')
);
$removeBookmark = array(
    'icon'=>'star',
    'href'=>'#',
    'id'=>'remove-bookmark',
    'title'=>_('Remove Bookmark')
);
if($isBookmarked){
    $addBookmark['li_class'] = 'd-none';
} else {
    $removeBookmark['li_class'] = 'd-none';
}
if ($session['write']) {
    echo makeListLink($removeBookmark);
    echo makeListLink($addBookmark);
}

if ($session['read']) {
    // add user_menu.php items
    if (!empty($menu['setup'])) {
        $sub_items = array();

        foreach($menu['setup'] as $sub_item) {
            $sub_items[] = $sub_item;
        }
        // build dropdown with above items
        echo makeDropdown(array(
            'title' => _("Setup"),
            'href' => '#',
            'icon' => 'cog',
            'sub_items' => $sub_items
        ));
    }
}
?>


<?php
// top navbar user menu
$menu_index = 'user';
if($session['read']){
    $item = array(
        'title' => $session['username'],
        'href' => '#',
        'icon' => 'user',
        'class'=> 'grav-container img-circle',
        'id'=>'user-dropdown',
    );
    $item['li_class'][] = 'menu-user';
    $item['li_class'][] = 'd-flex';
    $item['li_class'][] = 'align-items-center';

    // use the text as the title if not available
    if(empty($item['title'])) $item['title'] = $item['text'];

    // indicate if user is admin
    if ($session['admin'] == 1) {
        settype($item['class'],'array');
        $item['class'][] = 'is_admin';
        $item['title'] .= sprintf(' (%s)',_('Admin'));
    }
    // add gravitar
    $grav_email = $user->get($session['userid'])->gravatar;
    if(!empty($grav_email)) {
        $item['icon'] = '';
        $atts['class'] = 'grav img-circle';
        $item['text'] = get_gravatar( $grav_email, 52, 'mp', 'g', true, $atts );
    } else {
        $item['li_class'][] = 'no-gravitar';
    }
    // add user_menu.php items
    if(!empty($menu[$menu_index])): foreach($menu[$menu_index] as $sub_item): 
        $item['sub_items'][] = $sub_item;
    endforeach; endif;

    // build dropdown with above items
    echo makeDropdown($item);

} else {
    // show login link to non-logged in users
    if(!empty($menu[$menu_index])): foreach($menu[$menu_index] as $item): 
        echo makeListLink($item);
    endforeach; endif;

} ?>
</ul>
</div>



<?php
/**
 * Get either a Gravatar URL or complete image tag for a specified email address.
 *
 * @param string $email The email address
 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
 * @param string $d Default imageset to use [ 404 | mp | identicon | monsterid | wavatar ]
 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
 * @param boole $img True to return a complete IMG tag False for just the URL
 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
 * @return String containing either just a URL or a complete image tag
 * @source https://gravatar.com/site/implement/images/php/
 */
function get_gravatar( $email, $s = 80, $d = 'mp', $r = 'g', $img = false, $atts = array() ) {
    $url = 'https://www.gravatar.com/avatar/';
    $url .= md5( strtolower( trim( $email ) ) );
    $url .= "?s=$s&d=$d&r=$r";
    if ( $img ) {
        $url = '<img src="' . $url . '"';
        foreach ( $atts as $key => $val )
            $url .= ' ' . $key . '="' . $val . '"';
        $url .= ' />';
    }
    return $url;
}
?>
