<?php

  /*
 
  Database connection settings

  */

  $username = "";
  $password = "";
  $server   = "localhost";
  $database = "";

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
  $default_controller = "user";
  $default_action = "login";

  // Default controller and action if none are specified and user is logged in
  $default_controller_auth = "user";
  $default_action_auth = "view";

  // Public profile functionality
  $public_profile_enabled = TRUE;
  $public_profile_controller = "dashboard"; 
  $public_profile_action = "view";

  /*

  Other

  */

  // Theme location
  $theme = "basic";
  
  // Error processing
  $display_errors = TRUE;

  // Allow user register in emoncms
  $allowusersregister = TRUE;

  // Enable remember me feature - needs more testing
  $enable_rememberme = TRUE; 

  // Skip database setup test - set to false once database has been setup.
  $dbtest = TRUE;
  
  // Enable user recovery password
  $recoverypass = TRUE;

?>
