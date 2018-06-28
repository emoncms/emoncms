<?php
    global $session;

    $menu_dropdown_config[] = array('name'=> __("My Account"), 'icon'=>'icon-user', 'path'=>"user/view", 'session'=>"write", 'order' => 40, 'divider' => true);
    $menu_right[] = array('name'=> __("Logout"), 'icon'=>'icon-off icon-white', 'path'=>"user/logout", 'session'=>"write", 'order' => 1000);
    if (!$session['write']) $menu_right[] = array('name'=>__("Log In"), 'icon'=>'icon-home icon-white', 'path'=>"user/login", 'order' => 1000);