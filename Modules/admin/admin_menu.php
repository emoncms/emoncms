<?php
global $session;
if ($session["write"] && $session["admin"]) {
    $menu['setup']['l2']['admin'] = array(
        'name' => tr("Admin"),
        'href' => 'admin',
        'default' => 'admin/users',
        'icon' => 'tasks',
        'order' => 13,

        "l3"=>array(
            "users"=>array(
                "name"=>tr("Users"),
                "href"=>"admin/users", 
                "order"=>1, 
                "icon"=>"input"
            )
        )

    );
}
