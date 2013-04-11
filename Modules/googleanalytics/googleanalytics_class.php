<?php

class googleanalytics_module implements iModule, iHTLMModule {
  
  public function __construct()
  {
  }  
  
  public function modulename()
  {
    return "Google Analytics Module";
  }
  
  public function moduleversion()
  {
    return "1.0";
  }
  
  public function moduletype()
  {
    return "test";
  }

  public function moduledescription()
  {
    return "Google Analytics track module";
  }
  
  public function moduleHTMLRender($position)
  {
    return "";
  }
    
}

?>