<?php

class vis_module implements iModule {
  
  public function __construct()
  {
  }  
  
  public function getmenu(&$menu_left)
  {
    $menu_left[] = array('name'=>"Vis", 'path'=>"vis/list" , 'session'=>"write", 'order' => 3 );
  }  
  
  public function modulename()
  {
    return "Visualization Module";
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
    return "Visualization module";
  }
    
}

?>