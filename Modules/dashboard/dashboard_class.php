<?php

class dashboard_module {
  
  public function __construct()
  {
  }  
  
  public function getmenu(&$menu_left)
  {
    $menu_left[] = array('name'=>"Dashboard", 'path'=>"dashboard/view" , 'session'=>"write", 'order' => 4 );
  }  
}

?>