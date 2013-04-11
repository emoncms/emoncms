<?php

class user_module implements iModule {
  
  public function __construct()
  {
  }  
  
  public function getmenu(&$menu_left)
  {   
    $menu_left[] = array('name'=>"Account", 'path'=>"user/view" , 'session'=>"write",'order' => 5);
    $menu_left[] = array('name'=>"Logout", 'path'=>"user/logout" , 'session'=>"write",'order' => 6);
  }
  
  public function modulename()
  {
    return "User Module";
  }
  
  public function moduleversion()
  {
    return "1.0";
  }  
  
  public function moduletype()
  {
    return "core";
  }  
  
  public function moduledescription()
  {
    return "Manage user profile";
  }
    
}

?>