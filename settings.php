<?php

  /*

  Read configuration file

  */

  $db_settings = parse_ini_file("settings.ini", true);

  /*

  Database connection settings

  */

  $username = $db_settings['database']['username'];
  $password = $db_settings['database']['password'];
  $server = $db_settings['database']['server'];
  $database = $db_settings['database']['database'];

  /*

  Core menu settings

  */

  $menu_right = array();
  $menu_right[] = array('name'=>"Admin", 'path'=>"admin/view" , 'session'=>"admin");
  $menu_right[] = array('name'=>"Account", 'path'=>"user/view" , 'session'=>"write");
  $menu_right[] = array('name'=>"Logout", 'path'=>"user/logout" , 'session'=>"write");

  /*

  Default router settings - in absence of stated path

  */

  // Default controller and action if none are specified and user is anonymous
  $default_controller = $db_settings['router']['default_controller'];
  $default_action = $db_settings['router']['default_action'];

  // Default controller and action if none are specified and user is logged in
  $default_controller_auth = $db_settings['router']['default_controller_auth'];
  $default_action_auth = $db_settings['router']['default_action_auth'];

  // Public profile functionality
  $public_profile_enabled = $db_settings['router']['public_profile_enabled'];
  $public_profile_controller = $db_settings['router']['public_profile_controller'];
  $public_profile_action = $db_settings['router']['public_profile_action'];

  /*

  Other

  */

  // Theme location
  $theme = $db_settings['misc']['theme'];
  
  // Error processing
  $display_errors = $db_settings['misc']['display_errors'];

  // Allow user register in emoncms
  $allowusersregister = $db_settings['misc']['allowusersregister'];

  // Skip database setup test - set to false once database has been setup.
  $dbtest = $db_settings['misc']['dbtest'];

?>
