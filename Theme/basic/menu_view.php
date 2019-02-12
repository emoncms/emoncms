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
        $item['sub_items'][] = makeListLink($sub_item);
    endforeach; endif;

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
