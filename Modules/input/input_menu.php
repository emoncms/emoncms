<?php
global $session;
if ($session["write"]) $menu["setup"]["l2"]['input'] = array("name"=>"Inputs","href"=>"input/view", "order"=>1, "icon"=>"input");
