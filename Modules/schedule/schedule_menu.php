<?php

    $domain4 = "schedule_messages";
    bindtextdomain($domain, "Modules/schedule/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_dropdown[] = array('name'=> dgettext($domain4, "Schedule"),'icon'=>'icon-time', 'path'=>"schedule/view" , 'session'=>"write", 'order' => 1);
