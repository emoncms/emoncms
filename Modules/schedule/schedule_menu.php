<?php
global $session;
load_language_files(dirname(__DIR__).'/locale', "schedule_messages");
if ($session["write"]) $menu["setup"]["l2"]['schedule'] = array("name"=>_("Schedule"),"href"=>"schedule/view", "order"=>8, "icon"=>"schedule");
