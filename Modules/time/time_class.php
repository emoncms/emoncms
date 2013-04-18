<?php
  
  class time_module implements iModule {
  
  public function __construct()
  {
  }  
   
  public function modulename()
  {
    return "Time Module";
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
    return "EmonCMS Time Module";
  }
      
    
}