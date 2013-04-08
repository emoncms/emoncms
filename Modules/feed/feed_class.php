<?php

class feed_module {
  
  public function __construct()
  {
  }  
  
  public function getmenu(&$menu_left)
  {
    $menu_left[] = array('name'=>"Feeds", 'path'=>"feed/list" , 'session'=>"write", 'order' => 2 );
  }  
}

?>