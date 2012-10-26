<?php
  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
 
  */

  // Load emoncms framework core
  require("core.php");
    
  // Process user settings and framework configuration
  require "process_settings.php"; 

  require("locale.php");
  
  $path = get_application_path();

  // Database connect
  require("db.php");
  switch(db_connect()) {
    case 4: db_schema_setup(load_db_schema()); break;
  }
  
  // Module registration    
  require("Modules/custom_module.php");       
  
  // Session control
  require("Modules/user/user_model.php");
  if (get('apikey'))
    $session = user_apikey_session($_GET['apikey']);
  else
    $session = emon_session_start();

  set_emoncms_lang($session['userid']);

  // 1) Get route
  $route = decode_route(get('q'));

  if (get('embed')==1) $embed = 1; else $embed = 0;

  // If no route specified use defaults
  if (!$route['controller'] && !$route['action'])
  {
    // Non authenticated defaults
    if (!$session['read'])
    {
      $route['controller'] = $default_controller;
      $route['action'] = $default_action;
    }
    else // Authenticated defaults
    {
      $route['controller'] = $default_controller_auth;
      $route['action'] = $default_action_auth;
    }
  }

  // 2) Load the main page controller
  $output = controller($route['controller']);

  // If no controller of this name - then try username
  if (!$output['content'] && $public_profile_enabled)
  { 
    $userid = get_user_id($route['controller']);
    if ($userid) {
      $route['subaction'] = $route['action'];
      $session['userid'] = $userid;
      $session['username'] = $route['controller'];
      $session['read'] = 1; 
      $route['action'] = $public_profile_action;
      $output = controller($public_profile_controller); 
    }
  }

  // Output theming
  if ($route['format']=='json')
  {
    echo $output['message'].$output['content'];
  }

  if ($route['format']=='html')
  {
    $menu_left = emoncms_modules::getInstance()->build_menu_from_modules();
    $output['mainmenu'] = theme("menu_view.php", array());
    if ($embed == 0) print theme("theme.php", $output);
    if ($embed == 1) print theme("embed.php", $output);
  }

?>
