<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/feed/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    if (user_has_capability('feed_menuitem')) {
        $menu_dropdown_config[] = array('name'=> dgettext($domain, "Feeds"), 'icon'=>'icon-list-alt', 'path'=>"feed/list" , 'session'=>"write", 'order' => 20 );
    }
