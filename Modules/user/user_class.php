<?php

class user_module implements iModule, iMenuModule {
  
  public function __construct()
  {
  }  
  
  public function getmenu()
  {   
    return array('name'=>"Account", 'path'=>"user/view" , 'session'=>"write",'order' => 5);
  }
  
  public function modulename()
  {
    return "User Module";
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
    return "Manage user profile";
  }
    
}

?>