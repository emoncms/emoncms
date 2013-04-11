<?php

class admin_module implements iModule {
  
  public function __construct()
  {
  }  
  
  public function getmenu(&$menu_left)
  {
    $menu_left[] = array('name'=>_("Admin"), 'path'=>"admin/view" , 'session'=>"admin",'order'=>5);
  }
  
  public function modulename()
  {
    return "Admin Module";
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
    return "Administration EmonCMS module";
  }
  
}

?>