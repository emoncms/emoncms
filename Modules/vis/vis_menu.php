<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/vis/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_dropdown[] = array('name'=> dgettext($domain, "Visualization"), 'path'=>"vis/list" , 'session'=>"write", 'order' => 3 );