<?php
    global $session;

    $domain = "messages";
    bindtextdomain($domain, "Modules/user/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_dropdown_config[] = array('name'=> dgettext($domain, "My Account"), 'icon'=>'icon-user', 'path'=>"user/view", 'session'=>"write", 'order' => 40, 'divider' => true);
    // $menu_right[] = array('name'=> dgettext($domain, "Logout"), 'icon'=>'icon-off icon-white', 'path'=>"user/logout", 'session'=>"write", 'order' => 1000);
    if (!$session['write']) $menu_right[] = array('name'=>dgettext($domain, "Log In"), 'icon'=>'icon-home icon-white', 'path'=>"user/login", 'order' => 1000);

 
    // not sure if this is usefull yet?? need to add an ability to create "favorite" pages
    $menu['setup'][] = array(
        'text' => _("Admin"),
        'path' => 'admin/view',
        'icon' => 'tasks',
        'active' => 'admin',
        'sort' => 6
    );


    // links specific to 'user' controller
    $menu['user'][] = array(
        'text' => _("Add Shortcut"),
        'icon' => 'plus',
        'path' => 'user/links/add',
        'sort' => 0
    );
    $menu['user'][] = array(
        'text' => _("All Shortcuts"),
        'path' => 'user/links',
        'icon' => 'favorite'
    );
    // shown at bottom of sidebar
    $menu['user'][] = array(
        'text' => _("My Account"),
        'icon' => 'user',
        'path' => 'user/view',
        'sort' => 1
    );
    $menu['user'][] = array(
        'li_class' => 'divider',
        'sort' => 2
    );
    $menu['user'][] = array(
        'text' => _("Logout"),
        'icon' => 'logout',
        'path' => 'user/logout',
        'sort' => 3
    );


    $menu['includes']['user'][] = view('Modules/user/Views/sidebar.php');
