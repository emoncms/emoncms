<?php
  
  class docs_module implements iModule, iMenuModule {
  
  public function __construct()
  {
  }  
  
  public function getmenu()
  {
    return array('name'=>"<b>Docs</b>", 'path'=>"site/docs", 'order' => 10 );
    // array('name'=>"<b>Troubleshooting</b>", 'path'=>"site/docs/status", 'order' => 10 ); 
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
  
    
}