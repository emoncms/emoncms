<?php
  
  class menu_module implements iModule, iHTLMModule {
  
  public function __construct()
  {
  }  
    
  public function modulename()
  {
    return "Main menu Module";
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
    return "EmonCMS main menu module";
  }
  
  public function moduleHTMLRender()
  {
    global $session,$path;
    
  $modules = get_modules();
  $bmenu = "";
  
  foreach ($modules as $module)
  {
    $module_class = new $module();    
    
    if ($module_class instanceof iMenuModule) {      
      $menu_item = $module_class->getmenu();
            
      if (isset($menu_item['session'])) {
        if (isset($session[$menu_item['session']]) && $session[$menu_item['session']]==1) {
          $bmenu = $bmenu . "<li><a href=".$path.$menu_item['path']." >".$menu_item['name']."</a></li>";
        }       
      } 
      else
      {
          $bmenu = $bmenu . "<li><a href='' >".$menu_item['name']."</a></li>";
      }
    }  
 }  
  
 return $bmenu;  
  }
    
    
}