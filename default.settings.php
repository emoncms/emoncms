<?php

    /*

    Database connection settings

    */

    $username = "_DB_USER_";
    $password = "_DB_PASSWORD_";
    $server   = "localhost";
    $database = "emoncms";

    $redis_enabled = true;

    // Enable this to try out the experimental MQTT Features:
    // - updated to feeds are published to topic: emoncms/feed/feedid    
    $mqtt_enabled = false;
    
    $feed_settings = array(

        // Configure supported engines. Uncommented engines will not be available for user to create a new feed using it. Existing feeds engines still work.
        'engines_hidden'=>array(
            ENGINE::MYSQL             // 0
            ,Engine::PHPTIMESERIES    // 2
            //,Engine::PHPFINA        // 5
            //,Engine::PHPFIWA        // 6
        ),
        
        // The default data directory is /var/lib/phpfiwa,phpfina,phptimeseries 
        // --on windows or shared hosting you will likely need to specify a different data directory--
        // Make sure that emoncms has write permission's to the datadirectory folders
        
        'phpfiwa'=>array(
            'datadir'=>'/var/lib/phpfiwa/'
        ),
        'phpfina'=>array(
            'datadir'=>'/var/lib/phpfina/'
        ),
        'phptimeseries'=>array(
            'datadir'=>'/var/lib/phptimeseries/'
        )
    );
    
    // (OPTIONAL) Used by password reset feature
    $smtp_email_settings = array(
      'host'=>"_SMTP_HOST_",
      'username'=>"_SMTP_USER_",
      'password'=>"_SMTP_PASSWORD_",
      'from'=>array('_SMTP_EMAIL_ADDR_' => '_SMTP_EMAIL_NAME_')
    );

    // To enable / disable password reset set to either true / false
    // default value of " _ENABLE_PASSWORD_RESET_ " required for .deb only
    // uncomment 1 of the 2 following lines & comment out the 3rd line.
    // $enable_password_reset = true;
    // $enable_password_reset = false;
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
    // Allows http://yourdomain.com/[username]/[dash alias] or ?id=[dash id]
    // Alternative to http://yourdomain.com/dashboard/view?id=[dash id]
    // Add optional '&embed=1' in the end to remove header and footer
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

    // Log4PHP configuration
    $log4php_configPath = 'logconfig.xml';

    // CSV export options for the number of decimal_places, decimal_place_separator and field_separator
    // The thousands separator is not used (specified as "nothing")
    // NOTE: don't make $csv_decimal_place_separator == $csv_field_separator
    // Adjust as appropriate for your location

    // number of decimal places
    $csv_decimal_places = 2;

    // decimal place separator
    $csv_decimal_place_separator = ".";

    // field separator
    $csv_field_separator = ",";
