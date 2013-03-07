<?php
  global $path, $session, $user;

  $img = "<img style='margin-top: -4px;' id='emoncms-logo' src='".$path."Theme/emoncms-logo.png' />";

  $menu_left[] = array('name'=>$img, 'path'=>$user->get_username($session['userid']) , 'session'=>"read", 'order' => 0 );

  $menu_left[] = array('name'=>"Dashboard", 'path'=>"dashboard/list" , 'session'=>"write", 'order' => 4 );

?>
