<?php
    load_language_files("Modules/schedule/locale", "schedule_messages");
    $menu['sidebar']['emoncms'][] = array(
        'text' => dgettext("schedule_messages","Schedule"),
        'path' => 'schedule/view',
        'icon' => 'schedule',
        'active' => 'schedule',
        'order' => 'b3'
    );

