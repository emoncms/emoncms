<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/user/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_right[] = array('name'=> dgettext($domain, "Account"), 'path'=>"user/view" , 'session'=>"write");
    $menu_right[] = array('name'=> dgettext($domain, "Logout"), 'path'=>"user/logout" , 'session'=>"write");