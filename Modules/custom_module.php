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
	  
  abstract public function description();
  abstract public function register_menu();
  
	function __construct() {
		
	}
  
}

/*
 * Emoncms singleton modules pattern
 */
class emoncms_modules {
  
  private static $instance;
  private static $modules_instances;
   
  private function __construct()
  {
    $this->modules_instances = array();            
  }
 
  public static function getInstance()
  {
    if (  !self::$instance instanceof self)
    {
      self::$instance = new self;
    }      
    return self::$instance;
  }
  
  public function get_registered_modules()
  {
    return $this->modules_instances;    
  }
  
  public function register_module($emoncms_module)
  {
    if (!isset($this->modules_instances[$emoncms_module]))
    {
      $this->modules_instances[$emoncms_module] = new $emoncms_module;
    }
  }  
  
  public function get_menu($menu)
  {
    $amenu = array();
    
    foreach ($this->get_registered_modules() as $emoncms_module_instance) {
      $amenu[] = $emoncms_module_instance->register_menu();       
    }
    
    return $amenu;  
  }
   
}

?>