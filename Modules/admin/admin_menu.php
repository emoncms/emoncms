<?php
    defined('EMONCMS_EXEC') or die('Restricted access');
/* Do we realy need this space?
    $menu['sidebar']['emoncms'][] = array(
        'text' => '',
        'href' => '#', // items with no path or href are not shown,
        'li_class' => 'divider',
        'icon' => '',
        'order' => '7'
    );
*/
    $menu['sidebar']['emoncms'][] = array(
        'text' => _("Admin"),
        'path' => 'admin/view',
        'active' => 'admin',
        'icon' => 'tasks',
        'order' => '8'
    );
