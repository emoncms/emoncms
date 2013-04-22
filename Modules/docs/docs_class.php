<?php
  
  class docs_module implements iModule, iHTLMModule {
  
  public function __construct()
  {
  }  
   
  public function modulename()
  {
    return "Docs Module";
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
    return "EmonCMS documentation and troubleshooting";
  }
  
  public function moduleHTMLRender()
  {
    global $session,$path;
    
    $bmenu = "<li><a href='site/docs/status' ><b>Troubleshooting</b></a></li>";
    $bmenu = $bmenu."<li><a href='site/docs' ><b>Docs</b></a></li>";
    
    if (!$session['write']) $bmenu = "<li><a href='user/login'>Log In</a></li>";    
      
    return $bmenu;  
  }
      
  
    
}