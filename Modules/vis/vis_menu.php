<?php
    load_language_files("Modules/vis/locale", "vis_messages");
    $menu['sidebar']['emoncms'][] = array(
        'text' => dgettext("vis_messages","Visualization"),
        'path' => 'vis/list',
        'icon' => 'present_to_all',
        'order' => 3
    );
