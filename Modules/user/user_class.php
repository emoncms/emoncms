<?php

class user_module {
  
  public function __construct()
  {
  }  
  
  public function getmenu(&$menu_left)
  {   
    $menu_left[] = array('name'=>"Account", 'path'=>"user/view" , 'session'=>"write",'order' => 5);
    $menu_left[] = array('name'=>"Logout", 'path'=>"user/logout" , 'session'=>"write",'order' => 6);
  }
}

?>