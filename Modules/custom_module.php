<?php
  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
 
  */


/*
 * Emoncms custom module class
 */
abstract class emoncms_custom_module  {
	  
  /*
   * Returns module description
   */    
  abstract public function description();
  
  /*
   * Returns module menu
   */
  abstract public function register_menu();
  
  // TODO: maybe return module version, modules dependencies,....
  //
  
}

/*
 * Emoncms singleton modules pattern
 */
class emoncms_modules {
  
  private static $instance;
  private static $modules_instances;
  
  /*
   * Load and register modules in the emoncms framework 
   */ 
  private function register_modules()
  {    
    $dir = scandir("Modules");
    for ($i=2; $i<count($dir); $i++)
    {
      if (filetype("Modules/".$dir[$i])=='dir') 
      {
        if (is_file("Modules/".$dir[$i]."/".$dir[$i]."_class.php"))
        {
          require_once "Modules/".$dir[$i]."/".$dir[$i]."_class.php";     
          $moduleclass = "emoncms_".$dir[$i]."_module_class";
          
          $this->register_module($moduleclass);
        }
      }
    }   
  }
 
  /*
   * Constructor of the class
   */ 
  private function __construct()
  {
    $this->modules_instances = array();            
  }
 
  /*
   * Check for only one instance of modules container
   */ 
  public static function getInstance()
  {
    if (  !self::$instance instanceof self)
    {
      self::$instance = new self;
    }
   
    // Check for installed modules 
    self::$instance->register_modules();
          
    // Return singleton instance
    return self::$instance;
  }
  
  /*
   * Returns all registered modules
   */
  public function get_registered_modules()
  {
    return $this->modules_instances;    
  }
  
  /*
   * Register a module in the emoncms framework
   */
  public function register_module($emoncms_module)
  {
    if (!isset($this->modules_instances[$emoncms_module]))
    {
      $this->modules_instances[$emoncms_module] = new $emoncms_module;
    }
  }  
  
  public function build_menu_from_modules()
  {
    $amenu = array();
    
    foreach ($this->get_registered_modules() as $emoncms_module_instance) {
      $amenu[] = $emoncms_module_instance->register_menu();       
    }
    
    return $amenu;  
  }
   
}

?>