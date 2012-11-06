<?php
  global $path, $session;

  $img = "<img style='margin-top: -4px;' id='emoncms-logo' src='".$path."Theme/basic/emoncms-logo.png' />";

  $menu_left[] = array('name'=>$img, 'path'=>get_user_name($session['userid']) , 'session'=>"read", 'order' => 0 );

  $menu_left[] = array('name'=>"Dashboard", 'path'=>"dashboard/list" , 'session'=>"write", 'order' => 3 );

?>
