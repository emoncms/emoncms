<?php

    global $session, $user;
  
    if ($session['write']) $apikey = "?apikey=".$user->get_apikey_write($session['userid']); else $apikey = "";
  
    $menu_left[] = array('name'=>"Meine Elektrik", 'path'=>"myelectric".$apikey , 'session'=>"write", 'order' => -2 );
