<?php

    /*

    Database connection settings

    */

    $username = "root";
    $password = "raspberry";
    $server   = "localhost";
    $database = "emoncms";

    $redis_enabled = true;

    // Enable this to try out the experimental MQTT Features:
    // - updated to feeds are published to topic: emoncms/feed/feedid    
    $mqtt_enabled = false;
    
    $feed_settings = array(
        'max_npoints_returned'=>800,
        
        'phpfina'=>array(
            'datadir'=>'/home/pi/data/phpfina/'
        ),
        
        'phptimeseries'=>array(
            'datadir'=>'/home/pi/data/phptimeseries/'
        )
    );
    
    // (OPTIONAL) Used by password reset feature
    $smtp_email_settings = array(
      'host'=>"_SMTP_HOST_",
      'username'=>"_SMTP_USER_",
      'password'=>"_SMTP_PASSWORD_",
      'from'=>array('_SMTP_EMAIL_ADDR_' => '_SMTP_EMAIL_NAME_')
    );

    $enable_password_reset = false;
    
    // Checks for limiting garbage data?
    $max_node_id_limit = 32;

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

    // Enable multi user emoncms.
    // If set to false, emoncms will automatically remove the register form and 
    // ability to create further users after the first user has been created
    $enable_multi_user = false;

    // Enable remember me feature - needs more testing
    $enable_rememberme = TRUE;

    // Skip database setup test - set to false once database has been setup.
    $dbtest = TRUE;

    // Log4PHP configuration
    $log4php_configPath = 'logconfig.xml';
