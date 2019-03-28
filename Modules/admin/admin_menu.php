<?php
    defined('EMONCMS_EXEC') or die('Restricted access');

    $menu['sidebar']['setup'][] = array(
        'text' => _("Admin"),
        'path' => 'admin/view',
        'active' => 'admin',
        'icon' => 'tasks',
        'order' => 6
    );