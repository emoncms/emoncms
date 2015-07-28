<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/input/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    //$menu_left[] = array('name'=> dgettext($domain, "Input"), 'path'=>"input/view" , 'session'=>"write", 'order' => 1 );
    $menu_dropdown_config[] = array('name'=> "<i class='icon-signal'></i> " . dgettext($domain, "Inputs"), 'path'=>"input/view" , 'session'=>"write", 'order' => 1 );
