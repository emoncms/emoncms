<?php
  global $path, $session, $user;

  //$menu_left[] = array('name'=>"Dashboard", 'path'=>"dashboard/view" , 'session'=>"write", 'order' => 4 );
  $menu_left[] = array('name'=>"Account", 'path'=>"user/view" , 'session'=>"write",'order' => 5);
  $menu_left[] = array('name'=>"Logout", 'path'=>"user/logout" , 'session'=>"write",'order' => 6);

?>