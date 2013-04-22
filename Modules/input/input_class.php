<?php

class input_module implements iModule, iMenuModule {

  public function __construct()
  {
  }  
  
  public function getmenu()
  {
    return array('name'=>"Input", 'path'=>"input/node" , 'session'=>"write", 'order' => 1 );
  }  
  
  public function modulename()
  {
    return "Input Module";
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
    return "Manage user inputs";
  }
    
}

?>