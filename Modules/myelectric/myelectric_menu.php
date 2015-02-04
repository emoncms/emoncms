<?php

    global $session, $user;
    
    $domain = "messages";
    bindtextdomain($domain, "Modules/myelectric/locale");
    bind_textdomain_codeset($domain, 'UTF-8');
  
    if ($session['write']) $apikey = "?apikey=".$user->get_apikey_write($session['userid']); else $apikey = "";
  
    $menu_left[] = array('name'=> dgettext($domain, "My Electric"), 'path'=>"myelectric".$apikey , 'session'=>"write", 'order' => -2 );
