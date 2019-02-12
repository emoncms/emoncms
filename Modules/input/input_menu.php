<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/input/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    // $menu_dropdown_config[] = array('name'=> dgettext($domain, "Inputs"), 'icon'=>'icon-arrow-right', 'path'=>"input/view" , 'session'=>"write", 'order' => 10 );

    $sidebar['category'][] = array(
        'li_class'=>'btn-li',
        'icon'=>'wrench',
        'title'=> _("Setup"),
        'path'=> 'input/view',
        'active'=> explode(',','input,feed,graph,device,config'),
        'sort'=> 1
    );

    $sidebar['setup'][] = array(
        'text' => _("Inputs"),
        'path' => 'input/view',
        'icon' => 'input',
        'active' => 'input',
        'sort' => 0
    );