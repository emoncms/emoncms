<?php
    defined('EMONCMS_EXEC') or die('Restricted access');

    $menu['sidebar']['emoncms'][] = array(
        'text' => _("Setup"),
        'li_class' => 'sidebar-subtitle',
        'order' => 'b'
    );

    $menu['sidebar']['emoncms'][] = array(
        'text' => _("Admin"),
        'path' => 'admin/view',
        'active' => 'admin',
        'icon' => 'tasks',
        'order' => 'b7'
    );
