<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/user/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    //$menu_right[] = array('name'=> dgettext($domain, "Account"), 'path'=>"user/view" , 'session'=>"write");
    //$menu_right[] = array('name'=> dgettext($domain, "Logout"), 'path'=>"user/logout" , 'session'=>"write");
    $menu_dropdown_config[] = array('name'=> "<i class='icon-user'></i> " . dgettext($domain, "My Account"), 'path'=>"user/view" , 'session'=>"write" , 'order' => 20, 'divider' => true);
    $menu_right[] = array('name'=> "<i class='icon-off icon-white' title='" . dgettext($domain, "Logout") . "'></i>", 'path'=>"user/logout" , 'session'=>"write");
