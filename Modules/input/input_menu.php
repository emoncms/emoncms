<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/input/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    // $menu_dropdown_config[] = array('name'=> dgettext($domain, "Inputs"), 'icon'=>'icon-arrow-right', 'path'=>"input/view" , 'session'=>"write", 'order' => 10 );

    $menu['setup'][] = array(
        'text' => _("Inputs"),
        'path' => 'input/view',
        'icon' => 'input',
        'sort' => 1
    );