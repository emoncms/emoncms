<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/device/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_dropdown_config[] = array('name'=> dgettext($domain, "Device Setup"), 'icon'=>'icon-facetime-video', 'path'=>"device/view" , 'session'=>"write", 'order' => 45 );
