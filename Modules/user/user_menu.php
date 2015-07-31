<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/user/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_dropdown_config[] = array('name'=> dgettext($domain, "My Account"), 'icon'=>'icon-user', 'path'=>"user/view" , 'session'=>"write" , 'order' => 30, 'divider' => true);
    $menu_right[] = array('name'=> dgettext($domain, "Logout"), 'icon'=>'icon-off icon-white', 'path'=>"user/logout" , 'session'=>"write");
