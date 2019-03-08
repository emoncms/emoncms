<?php
    defined('EMONCMS_EXEC') or die('Restricted access');

    $menu['sidebar']['setup'][] = array(
        'text' => _("Admin"),
        'path' => 'admin/view',
        'icon' => 'tasks',
        'order' => 6
    );