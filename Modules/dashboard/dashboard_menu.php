<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/dashboard/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_left[] = array('name'=> dgettext($domain, "Dashboard"), 'path'=>"dashboard/view" , 'session'=>"write", 'order' => 4 );
