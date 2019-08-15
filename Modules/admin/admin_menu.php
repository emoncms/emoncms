<?php
    defined('EMONCMS_EXEC') or die('Restricted access');

    $menu['sidebar']['emoncms'][] = array(
        'text' => _("Setup"),
        'path' => null,
        'li_class' => 'sidebar-subtitle',
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
