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

if ($session['read']) {
?>

<ul id="left-nav" class='nav'>
    <li class="btn-li">
        <a id="sidebar-toggle" title="<?php echo _('Open/Close Sidebar') ?>" data-toggle="slide-collapse" data-target="#sidebar" href="#" class="btn">
            <svg id="icon-menu" class="icon" viewBox="0 0 32 32">
                <path class="icon-menu-top" d="m 27.93924,5.3202643 v 2.65165 H 4.2497483 v -2.65165 z"></path>
                <path class="icon-menu-middle" d="m 27.93924,14.202737 v 2.65165 H 4.2497483 v -2.65165 z"></path>
                <path class="icon-menu-bottom" d="m 27.93924,23.085145 v 2.65165 H 4.2497483 v -2.65165 z"></path>
            </svg>
        </a>
    </li>
<?php

// top level menu icons
if(!empty($menu['category'])): foreach($menu['category'] as $item):
    echo makeListLink($item);
endforeach; endif;

// left aligned menu items
if(!empty($menu['left'])): foreach ($menu['left'] as $item):
    $item['class'] = 'menu-left';
    echo makeListLink($item);
endforeach; endif;
?>
</ul>

<?php } ?>
<ul class='nav pull-right'>
<?php

if ($session['read']) {
    // add user_menu.php items
    $menu_key = 'extras';
    $item = array(
        'text' => 'Extras',
        'href' => '#',
        'icon' => 'folder-plus'
    );
    if(!empty($menu[$menu_key])): foreach($menu[$menu_key] as $sub_item): 
    // use the text as the title if not available
    if(empty($item['title'])) $item['title'] = !empty($item['text']) ? $item['text']: '';
        $item['sub_items'][] = $sub_item;
    endforeach; endif;

    // build dropdown with above items
    echo makeDropdown($item);
    }
?>


<?php
// sidebar footer user menu
if($session['read']){
    $item = array(
        'li_class' => 'menu-user',
        'text' => $session['username'],
        'href' => '#',
        'class' => 'grav-container'
    );
    // use the text as the title if not available
    if(empty($item['title'])) $item['title'] = $item['text'];

    // indicate if user is admin
    if ($session['admin'] == 1) {
        $item['class'] = addCssClass('is_admin', $item['class']);
        $item['title'] .= sprintf(' (%s)',_('Admin'));
    }
    // add gravitar
    $grav_email = $user->get($session['userid'])->gravatar;
    if(!empty($grav_email)) {
        $atts['class'] = 'grav img-circle img-fluid';
        $item['text'] = get_gravatar( $grav_email, 52, 'mp', 'g', true, $atts );
    }
    // add user_menu.php items
    $controller = 'user';
    if(!empty($menu[$controller])): foreach($menu[$controller] as $sub_item): 
        $item['sub_items'][] = $sub_item;
    endforeach; endif;

    // build dropdown with above items
    echo makeDropdown($item);

} else {
    // show login link to non-logged in users
    $controller = 'user';
    if(!empty($menu[$controller])): foreach($menu[$controller] as $item): 
        echo makeListLink($item);
    endforeach; endif;

} ?>
</ul>


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
