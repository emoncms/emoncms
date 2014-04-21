<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/vis/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_left[] = array('name'=> dgettext($domain, "Vis"), 'path'=>"vis/list" , 'session'=>"write", 'order' => 3 );