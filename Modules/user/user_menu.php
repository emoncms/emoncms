<?php

global $session;

$menu['right'][] = array(
    'text' => __("Add Bookmark"),
    'icon' => 'plus',
    'path' => 'user/bookmarks/add',
    'order' => 0
);
$menu['user'][] = array(
    'text' => __("Bookmarks"),
    'path' => 'user/bookmarks',
    'icon' => 'star',
    'order' => 2
);
$menu['user'][] = array(
    'text' => __("My Account"),
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
    'text' => __("Logout"),
    'icon' => 'logout',
    'path' => 'user/logout',
    'order' => 4,
    'id' => 'logout-link'
);

$menu['user'][] = array(
    'title' => __("Login"),
    // 'text' => __("Login"),
    'icon' => 'enter',
    'path' => '/',
    'public' => true,
    'public_only' => true
);
