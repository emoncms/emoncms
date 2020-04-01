<?php
global $session;
if ($session["write"]) $menu["setup"]["l2"]['feed'] = array("name"=>"Feeds","href"=>"feed/view", "order"=>2, "icon"=>"format_list_bulleted");
