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
class emoncms_vis_module_class extends emoncms_custom_module {
	   
  public function description()
  {
    return _("Vis emoncms module");
  }
  
  public function register_menu()
  {
    return array('name'=>_("Vis"), 'path'=>"vis/list" , 'session'=>"write", 'order' => 4 );
  }
    
}

?>
