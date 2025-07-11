<?php
global $session;
if ($session["write"] && $session["admin"]) {
    $menu['setup']['l2']['admin'] = array(
        'name' => tr("Admin"),
        'href' => 'admin',
        'default' => 'admin/info',
        'icon' => 'tasks',
        'order' => 13,

        "l3"=>array(
            "info"=>array(
                "name"=>tr("System Info"),
                "href"=>"admin/info", 
                "order"=>1, 
                "icon"=>"input"
            ),
            "update"=>array(
                "name"=>tr("Update"),
                "href"=>"admin/update", 
                "order"=>1, 
                "icon"=>"input"
            ),
            "components"=>array(
                "name"=>tr("Components"),
                "href"=>"admin/components", 
                "order"=>1, 
                "icon"=>"input"
            ),
            "firmware"=>array(
                "name"=>tr("Serial Monitor"),
                "href"=>"admin/serial", 
                "order"=>1, 
                "icon"=>"input"
            ),
            "serialconfig"=>array(
                "name"=>tr("Serial Config"),
                "href"=>"admin/serconfig", 
                "order"=>1, 
                "icon"=>"input"
            ),
            "log"=>array(
                "name"=>tr("Emoncms Log"),
                "href"=>"admin/log", 
                "order"=>1, 
                "icon"=>"input"
            ),
            "users"=>array(
                "name"=>tr("Users"),
                "href"=>"admin/users", 
                "order"=>1, 
                "icon"=>"input"
            )
        )

    );
}
