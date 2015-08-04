<?php
    global $session;

    $domain = "messages";
    bindtextdomain($domain, "Modules/user/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_dropdown_config[] = array('name'=> dgettext($domain, "My Account"), 'icon'=>'icon-user', 'path'=>"user/view", 'session'=>"write", 'order' => 40, 'divider' => true);
    $menu_right[] = array('name'=> dgettext($domain, "Logout"), 'icon'=>'icon-off icon-white', 'path'=>"user/logout", 'session'=>"write", 'order' => 1000);
    if (!$session['write']) $menu_right[] = array('name'=>"Log In", 'icon'=>'icon-home icon-white', 'path'=>"user/login", 'order' => 1000);