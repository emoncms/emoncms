<?php

//1 #### Mysql database settings
    $server   = "localhost";
    $database = "emoncms";
    $username = "emoncms";
    $password = "emonpiemoncmsmysql2016";
    $port     = "3306";
    // Skip database setup test - set to false once database has been setup.
    $dbtest = true;


//2 #### Redis
    $redis_enabled = true;
    $redis_server = array( 'host'   => 'localhost',
                           'port'   => 6379,
                           'auth'   => '',
                           'prefix' => '');


//3 #### MQTT
    // The 'subscriber' topic format is rx/* - where * is the emoncms input node number.
    // The 'publisher' topic format is user selectable from the 'Publish to MQTT' input process, for example power/solar
    $mqtt_enabled = true;            // Activate MQTT by changing to true
    $mqtt_server = array( 'host'     => 'localhost',
                          'port'     => 1883,
                          'user'     => 'emonpi',
                          'password' => 'emonpimqtt2016',
                          'basetopic'=> 'emon'
                          );


//4 #### Engine settings
    $feed_settings = array(
        // Supported engines. Uncommented engines will not be available for user to create a new feed using it. Existing feeds with a hidden engine still work.
        // Place a ',' as the first character on all uncommented engines lines but first.
        // If using emoncms in low-write mode, ensure that PHPFIWA is disabled by removing the leading //, from the PHPFIWA entry
        'engines_hidden'=>array(
            Engine::MYSQL,           // 0  Mysql traditional
            //Engine::MYSQLMEMORY,   // 8  Mysql with MEMORY tables on RAM. All data is lost on shutdown
            //Engine::PHPTIMESERIES, // 2
            //Engine::PHPFINA,       // 5
            Engine::PHPFIWA          // 6  PHPFIWA disabled for compatibility with Low-write mode
        ),

        // Redis Low-write mode
        'redisbuffer'=>array(
            'enabled' => true      // If enabled is true, requires redis enabled and feedwriter service running
            ,'sleep' => 60          // Number of seconds to wait before write buffer to disk - user selectable option
        ),

        'csvdownloadlimit_mb' => 25,     // Max csv download size in MB

        // Engines working folder. Default is /var/lib/phpfiwa,phpfina,phptimeseries
        // On windows or shared hosting you will likely need to specify a different data directory--
        // Make sure that emoncms has write permission's to the datadirectory folders
        'phpfiwa'=>array(
            'datadir' => '/home/pi/data/phpfiwa/'
        ),
        'phpfina'=>array(
            'datadir' => '/home/pi/data/phpfina/'
        ),
        'phptimeseries'=>array(
            'datadir' => '/home/pi/data/phptimeseries/'
        )
    );
    
    $homedir = "/home/pi";

    // Max number of allowed different inputs per user. For limiting garbage rf data
    $max_node_id_limit = 32;


//5 #### User Interface settings
    // Theme location (folder located under Theme/, and must have the same structure as the basic one)
    $theme = "basic";
    $themecolor = "standard";

    // Favicon filenme in Theme/$theme
    $favicon = "favicon_emonpi.png";

    // Use full screen width
    $fullwidth = true;

    // Main menu collapses on lower screen widths
    $menucollapses = false;

    // Enable multi user emoncms.
    // If set to false, emoncms will automatically remove the register form and
    // ability to create further users after the first user has been created
    $enable_multi_user = false;

    // Enable remember me feature
    $enable_rememberme = true;

    // Allow user to reset his password
    $enable_password_reset = false;

    // (OPTIONAL) Email SMTP, used for password reset or other email functions
    $smtp_email_settings = array(
      'host'=>"smtp.gmail.com",
      'port'=>"465",  // 25, 465, 587
      'from'=>array('noreply@emoncms.org' => 'EmonCMS'),
      // comment lines below that dont apply
      'encryption'=>"ssl", // ssl, tls
      'username'=>"yourusername@gmail.com",
      'password'=>"yourpassword"
    );

    // Default controller and action if none are specified and user is anonymous
    $default_controller = "user";
    $default_action = "login";

    // Default controller and action if none are specified and user is logged in
    $default_controller_auth = "feed";
    $default_action_auth = "list";

    // Public profile functionality
    // Allows http://yourdomain.com/[username]/[dash alias] or ?id=[dash id]
    // Alternative to http://yourdomain.com/dashboard/view?id=[dash id]
    // Add optional '&embed=1' in the end to remove header and footer
    $public_profile_enabled = true;
    $public_profile_controller = "dashboard";
    $public_profile_action = "view";
    
    // Default feed viewer: "vis/auto?feedid=" or "graph/" - requires module https://github.com/emoncms/graph
    $feedviewpath = "graph/";


//6 #### Other settings
    // Log file configuration
    $log_enabled = true;
    $log_filename = '/var/log/emoncms.log';
    // Log Level: 1=INFO, 2=WARN, 3=ERROR
    $log_level = 2;

    // If installed on Emonpi, allow admin menu tools
    $allow_emonpi_admin = true;

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

    // set true on docker installations
    $allow_config_env_vars = false;

    // Dont change - developer updates this when the config format changes
    $config_file_version = "9";
