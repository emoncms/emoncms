<?php

global $session;
if($session['userid']>0){

    $menu['right'][] = array(
        'text' => _("Add Bookmark"),
        'icon' => 'plus',
        'path' => 'user/bookmarks/add',
        'order' => 0
    );


    $menu['user'][] = array(
        'text' => _("Bookmarks"),
        'path' => 'user/bookmarks',
        'icon' => 'star',
        'order' => 2
    );
    $menu['user'][] = array(
        'text' => _("My Account"),
        'icon' => 'user',
        'path' => 'user/view',
        'order' => 1
    );
    $menu['user'][] = array(
        'li_class' => 'divider',
        'href' => '#',
        'order' => 3
    );
    $menu['user'][] = array(
        'text' => _("Logout"),
        'icon' => 'logout',
        'path' => 'user/logout',
        'order' => 4
    );
} else {
    $menu['user'][] = array(
        'text' => _("Login"),
        'icon' => 'user',
        'path' => '/',
    );
}