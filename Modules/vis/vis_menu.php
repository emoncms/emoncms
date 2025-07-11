<?php
global $session;
if ($session["write"]) {
    load_language_files("Modules/vis/locale", "vis_messages");
    $menu["setup"]["l2"]['vis'] = array(
        "name"=>ctx_tr("vis_messages","Visualization"),
        "href"=>"vis/list", 
        "order"=>3, 
        "icon"=> 'present_to_all'
    );
    
    $menu["setup"]["l2"]['divider'] = array(
        "divider"=>"15px",
        "href"=>'',
        "order"=>4
    );
}
