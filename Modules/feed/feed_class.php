<?php

class feed_module implements iModule, iMenuModule {
  
  public function __construct()
  {
  }  
  
  public function getmenu()
  {
    return array('name'=>"Feeds", 'path'=>"feed/list" , 'session'=>"write", 'order' => 2 );
  }
  
  public function modulename()
  {
    return "Feed Module";
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
    return "Manage user feeds";
  }
    
}

?>