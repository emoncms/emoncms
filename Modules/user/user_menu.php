<?php

global $session;

$menu['right'][] = array(
    'text' => _("Add Bookmark"),
    'icon' => 'plus',
    'path' => 'user/bookmarks/add',
    'order' => 0,
    'text'=> _("Apps")
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
    'order' => 4,
    'id' => 'logout-link'
);

$menu['user'][] = array(
    'title' => _("Login"),
    // 'text' => _("Login"),
    'icon' => 'enter',
    'path' => '/',
    'public' => true,
    'public_only' => true
);