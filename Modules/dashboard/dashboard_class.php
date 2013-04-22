<?php

class dashboard_module implements iModule, iMenuModule {
  
  public function __construct()
  {
  }  
  
  public function getmenu()
  {
    return array('name'=>"Dashboard", 'path'=>"dashboard/view" , 'session'=>"write", 'order' => 4 );
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