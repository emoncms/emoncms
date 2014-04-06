<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/feed/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_left[] = array('name'=> dgettext($domain, "Feeds"), 'path'=>"feed/list" , 'session'=>"write", 'order' => 2 );