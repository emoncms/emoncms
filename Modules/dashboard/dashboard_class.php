<?php

class dashboard_module implements iModule {
  
  public function __construct()
  {
  }  
  
  public function getmenu(&$menu_left)
  {
    $menu_left[] = array('name'=>"Dashboard", 'path'=>"dashboard/view" , 'session'=>"write", 'order' => 4 );
  }  
  
  public function modulename()
  {
    return "Dashboard Module";
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
    return "Design dashboard module";
  }
      
}

?>