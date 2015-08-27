<?php

//1 #### Mysql database settings
    $server   = "localhost";
    $database = "emoncms";
    $username = "_DB_USER_";
    $password = "_DB_PASSWORD_";
    // Skip database setup test - set to false once database has been setup.
    $dbtest = true;


//2 #### Redis
    $redis_enabled = false;
    $redis_server = array( 'host'   => 'localhost',
                           'port'   => 6379,
                           'auth'   => '',
                           'prefix' => 'emoncms');


//3 #### MQTT
    // Enable this to try out the experimental MQTT Features:
    // - updated to feeds are published to topic: emoncms/feed/feedid    
    $mqtt_enabled = false;
    $mqtt_server = "127.0.0.1";


//4 #### Engine settings
    $feed_settings = array(
        // Supported engines. Uncommented engines will not be available for user to create a new feed using it. Existing feeds with a hidden engine still work.
        // Place a ',' as the first character on all uncommented engines lines but first.
        'engines_hidden'=>array(
            //Engine::MYSQL         // 0  Mysql traditional
            //Engine::MYSQLMEMORY   // 8  Mysql with MEMORY tables on RAM. All data is lost on shutdown 
            //Engine::PHPTIMESERIES // 2
            //,Engine::PHPFINA      // 5
            //,Engine::PHPFIWA      // 6
        ),

        // Redis Low-write mode 
        'redisbuffer'=>array(
            'enabled' => false      // If enabled is true, requires redis enabled and feedwriter service running
            ,'sleep' => 60          // Number of seconds to wait before write buffer to disk
        ),

        'csvdownloadlimit_mb' => 10,     // Max csv download size in MB

        // Engines working folder. Default is /var/lib/phpfiwa,phpfina,phptimeseries 
        // On windows or shared hosting you will likely need to specify a different data directory--
        // Make sure that emoncms has write permission's to the datadirectory folders
        'phpfiwa'=>array(
            'datadir' => '/var/lib/phpfiwa/'
        ),
        'phpfina'=>array(
            'datadir' => '/var/lib/phpfina/'
        ),
        'phptimeseries'=>array(
            'datadir' => '/var/lib/phptimeseries/'
        )
    );
    
    // Max number of allowed inputs per user. For limiting garbage rf data
    $max_node_id_limit = 32;


//5 #### User Interface settings
    // Theme location
    $theme = "basic";

    // Use full screen width
    $fullwidth = true;
    
    // Main menu collapses on lower screen widths
    $menucollapses = false;

    // Enable multi user emoncms.
    // If set to false, emoncms will automatically remove the register form and 
    // ability to create further users after the first user has been created
    $enable_multi_user = false;

    // Enable remember me feature - needs more testing
    $enable_rememberme = true;

    // Allow user to reset his password
    $enable_password_reset = false;

    // (OPTIONAL) Email SMTP, used for password reset
    $smtp_email_settings = array(
      'host'=>"_SMTP_HOST_",
      'username'=>"_SMTP_USER_",
      'password'=>"_SMTP_PASSWORD_",
      'from'=>array('_SMTP_EMAIL_ADDR_' => '_SMTP_EMAIL_NAME_')
    );

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
    $public_profile_enabled = true;
    $public_profile_controller = "dashboard";
    $public_profile_action = "view";

    
//6 #### Other settings
    // Log file configuration
    $log_enabled = true;
    $log_filename = dirname(__FILE__).'/' . 'emoncms.log';
    
    // If installed on Emonpi, allow update from admin menu
    $allow_emonpi_update = true;

    //experimental feature for virtual feeds average, default is true, set to false to activate average agregation with all data points, will be slower
    $data_sampling = false;
    
    // Show all fatal PHP errors
    $display_errors = true;

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

    // Dont change - developper updates this when the config format changes
    $config_file_version = "5";