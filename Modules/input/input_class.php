<?php

class input_module {

  public function __construct()
  {
  }  
  
  public function getmenu(&$menu_left)
  {
    $menu_left[] = array('name'=>"Input", 'path'=>"input/node" , 'session'=>"write", 'order' => 1 );
  }  
}

?>