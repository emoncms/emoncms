<?php
    defined('EMONCMS_EXEC') or die('Restricted access');

    $menu['sidebar']['emoncms'][] = array(
        'li_class' => 'divider',
        'href' => '#',
        'order' => 'b'
    );
    
    $menu['sidebar']['emoncms'][] = array(
        'text' => _("Admin"),
        'path' => 'admin/view',
        'active' => 'admin',
        'icon' => 'tasks',
        'order' => 'b7'
    );
