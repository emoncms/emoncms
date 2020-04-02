<?php
global $session;
if ($session["write"]) $menu["setup"]["l2"]['schedule'] = array("name"=>"Schedule","href"=>"schedule/view", "order"=>8, "icon"=>"schedule");

