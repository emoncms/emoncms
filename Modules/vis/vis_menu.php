<?php

    $domain3 = "vis_messages";
    bindtextdomain($domain3, "Modules/vis/locale");
    bind_textdomain_codeset($domain3, 'UTF-8');

    $menu_dropdown[] = array('name'=> dgettext($domain3, "Visualization"),'icon'=>'icon-tint', 'path'=>"vis/list" , 'session'=>"write", 'order' => 20);

    $menu['extras'][] = array(
        'text' => _("Visualization"),
        'path' => 'vis/list',
        'icon' => 'present_to_all'
    );
