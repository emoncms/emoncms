<?php

class vis_module {
  
  public function __construct()
  {
  }  
  
  public function getmenu(&$menu_left)
  {
    $menu_left[] = array('name'=>"Vis", 'path'=>"vis/list" , 'session'=>"write", 'order' => 3 );
  }  
}

?>