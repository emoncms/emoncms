<?php
    global $session;

    $domain = "messages";
    bindtextdomain($domain, "Modules/user/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    if (!$session['write']) $menu_right[] = array('name'=>dgettext($domain, "Log In"), 'icon'=>'icon-home icon-white', 'path'=>"user/login", 'order' => 1000);

    // not sure if this is usefull yet?? need to add an ability to create "favorite" pages
    // $menu['setup'][] = array(
    //     'text' => _("Admin"),
    //     'path' => 'admin/view',
    //     'icon' => 'tasks',
    //     'active' => 'admin',
    //     'sort' => 6
    // );


    // links specific to 'user' controller
    $menu['user'][] = array(
        'text' => _("Add Shortcut"),
        'icon' => 'plus',
        'path' => 'user/links/add',
        'sort' => 0,
        'active' => 'none'
    );
    $menu['user'][] = array(
        'text' => _("All Shortcuts"),
        'path' => 'user/links',
        'icon' => 'favorite',
        'sort' => 1,
        'active' => 'none'
    );
    $menu['user'][] = array(
        'text' => _("My Account"),
        'icon' => 'user',
        'path' => 'user/view',
        'sort' => 2,
        'active' => 'none'
    );
    $menu['user'][] = array(
        'li_class' => 'divider',
        'sort' => 3,
        'active' => 'none'
    );
    $menu['user'][] = array(
        'text' => _("Logout"),
        'icon' => 'logout',
        'path' => 'user/logout',
        'sort' => 4,
        'active' => 'none'
    );


    $menu['includes']['user'][] = view('Modules/user/Views/sidebar.php');
