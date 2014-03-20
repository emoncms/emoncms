<?php

    /*

    Database connection settings

    */

    $username = "";
    $password = "";
    $server   = "localhost";
    $database = "";

    $redis_enabled = true;
    
    $feed_settings = array(

        'creatable_engines'=>array('MYSQL','TIMESTORE','PHPTIMESERIES','GRAPHITE','PHPTIMESTORE'),

        'timestore'=>array(
            'adminkey'=>""
        ),

        'graphite'=>array(
            'port'=>0,
            'host'=>0
        ),
        
        // The default data directory is /var/lib/phpfiwa,phpfina,phptimeseries on windows or shared hosting you will likely need to specify a different data directory.
        // Make sure that emoncms has write permission's to the datadirectory folders
        
        'phpfiwa'=>array(
            //'datadir'=>'/home/username/emoncmsdata/phpfiwa/'
        ),
        'phpfina'=>array(
            //'datadir'=>'/home/username/emoncmsdata/phpfina/'
        ),
        'phptimeseries'=>array(
            //'datadir'=>'/home/username/emoncmsdata/phptimeseries/'
        )
    );
    
    // (OPTIONAL) Used by password reset feature
    $smtp_email_settings = array(
      'host'=>"",
      'username'=>"",
      'password'=>"",
      'from'=>array('an email address' => 'an email tag name')
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

    // Allow user register in emoncms
    $allowusersregister = TRUE;

    // Enable remember me feature - needs more testing
    $enable_rememberme = TRUE;

    // Skip database setup test - set to false once database has been setup.
    $dbtest = TRUE;

