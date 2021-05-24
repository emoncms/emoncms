<?php
global $session;
if ($session["admin"]) {
    $menu['setup']['l2']['admin'] = array(
        'name' => _("Admin"),
        'href' => 'admin',
        'default' => 'admin/view',
        'icon' => 'tasks',
        'order' => 13,

        "l3"=>array(
            "info"=>array(
                "name"=>_("System Info"),
                "href"=>"admin/view", 
                "order"=>1, 
                "icon"=>"input"
            ),
            "components"=>array(
                "name"=>_("Components"),
                "href"=>"admin/components", 
                "order"=>1, 
                "icon"=>"input"
            ),
            "users"=>array(
                "name"=>_("Users"),
                "href"=>"admin/users", 
                "order"=>1, 
                "icon"=>"input"
            )
        )

    );
}
