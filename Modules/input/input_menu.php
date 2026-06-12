<?php
global $session;
if ($session["write"]) {
    $menu["setup"]["l2"]['input'] = array(
        "name"=>tr("Inputs"),
        "href"=>"input/view", 
        "order"=>1, 
        "icon"=>"input"
    );
    
    $menu["setup"]["l2"]['divider'] = array(
        "divider"=>"15px",
        "href"=>'',
        "order"=>6
    );
 
 }
