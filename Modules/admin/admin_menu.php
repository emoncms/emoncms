<?php
    defined('EMONCMS_EXEC') or die('Restricted access');

    $menu['sidebar']['emoncms'][] = array(
        'text' => '',
        'href' => '#', // items with no path or href are not shown,
        'li_class' => 'divider',
        'icon' => '',
        'order' => 'b'
    );

    $menu['sidebar']['emoncms'][] = array(
        'text' => _("Admin"),
        'path' => 'admin/view',
        'active' => 'admin',
        'icon' => 'tasks',
        'order' => 'b7'
    );
