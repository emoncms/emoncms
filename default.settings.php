<?php

/*
 * Database connection settings
 */
  $username = "";
  $password = "";
  $server   = "localhost";
  $database = "";

  $theme = "basic";
  
/*
 * Error processing
 */
  $display_errors = TRUE;
  
  /*

  Menu settings
 
  */

  $menu_left = array();
  $menu_left[] = array('name'=>"Input", 'path'=>"input/list" , 'session'=>"write");
  $menu_left[] = array('name'=>"Feeds", 'path'=>"feed/list" , 'session'=>"write");
  $menu_left[] = array('name'=>"Dashboard", 'path'=>"dashboard/list" , 'session'=>"write");
  $menu_left[] = array('name'=>"Vis", 'path'=>"vis/list" , 'session'=>"write");

  $menu_right = array();
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
  $public_profile_action = "run";

/*
 * Allow user register in emoncms
 */
  $allowusersregister = TRUE;

?>
