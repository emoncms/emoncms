<?php
  
  class logout_module implements iModule, iHTLMModule {
  
  public function __construct()
  {
  }  
   
  public function modulename()
  {
    return "Logout Module";
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
    return "EmonCMS logout module";
  }
  
  public function moduleHTMLRender()
  {
    global $session,$path;
        
    if ($session['write'])
      return "<li><a href='".$path."/user/logout'>Logout</a></li>";
        
  }
      
  
    
}