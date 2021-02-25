<?php
global $session;
if ($session["admin"]) {
    $menu['setup']['l2']['admin'] = array(
        'name' => _("Admin"),
        'href' => 'admin/view',
        'icon' => 'tasks',
        'order' => 13
    );
}
