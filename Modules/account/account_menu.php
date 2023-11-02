<?php
global $session, $mysqli;
if ($session["write"]) {
    // Only show account menu if user is not a linked user
    $userid = $session["userid"];
    try {
        $result = $mysqli->query("SELECT * FROM accounts WHERE linkeduser='$userid'");
        if (!$result->fetch_object()) {
            $menu["setup"]["l2"]['account'] = array("name"=>_('Accounts'),"href"=>"account/list", "order"=>13, "icon"=>"user");
        }
    } catch (Exception $e) {
        // Do nothing
    }

}
