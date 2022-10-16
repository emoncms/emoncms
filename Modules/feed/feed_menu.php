<?php
global $session;
if ($session["read"] || $session['public_userid']) {

    $menu["setup"]["l2"]['feed'] = array(
        "name"=>_("Feeds"),
        "href"=>"feed/view", 
        "order"=>2, 
        "icon"=>"format_list_bulleted"
    );
    
    if ($session['public_userid']) {
        $menu["setup"]["l2"]['feed']["href"] = $session['public_username']."/feed/view";
    }
}
