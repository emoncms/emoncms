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
class emoncms_dashboard_module_class extends emoncms_custom_module {
	   
  public function description()
  {
    return _("Dashboard emoncms module");
  }
  
  public function register_menu()
  {
    return array('name'=>"Dashboard", 'path'=>"dashboard/list" , 'session'=>"write", 'order' => 3 );
  }
  
}

?>
