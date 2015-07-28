<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/admin/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    //$menu_right[] = array('name'=> dgettext($domain, "Admin"), 'path'=>"admin/view" , 'session'=>"admin");
    $menu_dropdown_config[] = array('name'=> "<i class='icon-tasks'></i> " . dgettext($domain, "Administration"), 'path'=>"admin/view" , 'session'=>"admin", 'order' => 21 );

