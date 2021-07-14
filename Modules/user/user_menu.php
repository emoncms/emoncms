<?php
global $session;
if ($session["write"]) $menu["setup"]["l2"]['user'] = array("name"=>_('My Account'),"href"=>"user/view", "order"=>12, "icon"=>"user");
/*
global $session;

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
);*/
