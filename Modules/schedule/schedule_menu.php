<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/schedule/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_right[] = array('name'=> dgettext($domain, "Schedule"), 'path'=>"schedule/view" , 'session'=>"write" );