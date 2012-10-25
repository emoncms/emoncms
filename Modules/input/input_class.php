<?php
  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
 
  */


/**
 * 
 */
class emoncms_input_module_class extends emoncms_custom_module {
	   
  public function description()
  {
    return _("Input emoncms module");
  }
  
  public function register_menu()
  {
    return array('name'=>_("Input"), 'path'=>"input/list" , 'session'=>"write", 'order' => 1 );
  }
    
	function __construct() {
		
	}
}

?>
