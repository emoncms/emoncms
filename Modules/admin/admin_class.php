<?php

class admin_module {
  
  public function __construct()
  {
  }  
  
  public function getmenu(&$menu_left)
  {
    $menu_left[] = array('name'=>"Admin", 'path'=>"admin/view" , 'session'=>"admin",'order'=>5);
  }
}

?>