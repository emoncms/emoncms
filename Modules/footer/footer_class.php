<?php
  class footer_module implements iModule, iHTLMModule {
  
  public function __construct()
  {
  }  
  
  public function modulename()
  {
    return "Footer Module";
  }
  
  public function moduleversion()
  {
    return "1.0";
  }
  
  public function moduletype()
  {
    return "HTML";
  }

  public function moduledescription()
  {
    return "Footer module to test HTML module render";
  }
  
  public function moduleHTMLRender()
  {
    return _('Powered by ')."<a href='http://openenergymonitor.org'>openenergymonitor.org</a>"; 
  }
    
}