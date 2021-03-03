<?php
# ------------------------------------------------------------
# Default emoncms settings.php - DO NOT EDIT!!
# ------------------------------------------------------------
    
$_settings = array(
// Suggested installation path for symlinked emoncms modules /opt/emoncms
"emoncms_dir" => "/opt/emoncms",
// Suggested installation path for emonpi and EmonScripts repository: /opt/openenergymonitor
"openenergymonitor_dir" => "/opt/openenergymonitor",

// Show all fatal PHP errors
"display_errors" => true,

// Set to true to run database update without logging in
// URL Example: http://localhost/emoncms/admin/db
"updatelogin" => false,

// Mysql database settings
"sql"=>array(
    "server"   => "localhost",
    "database" => "emoncms",
    "username" => "_DB_USER_",
    "password" => "_DB_PASSWORD_",
    "port"     => 3306,
     // Skip database setup test - set to false once database has been setup.
    "dbtest"   => true
),

// Redis
"redis"=>array(
    'enabled' => false,
    'host'    => 'localhost',
    'port'    => 6379,
    'auth'    => '',
    'dbnum'   => '',
    'prefix'  => 'emoncms'
),

// MQTT
"mqtt"=>array(
    // The 'subscriber' topic format is rx/* - where * is the emoncms input node number.
    // The 'publisher' topic format is user selectable from the 'Publish to MQTT' input process, for example power/solar
    // Activate MQTT by changing to true
    'enabled'   => false,
    'host'      => 'localhost',
    'port'      => 1883,
    'user'      => '',
    'password'  => '',
    'basetopic' => 'emon',
    'client_id' => 'emoncms'
),

// Input
"input"=>array(
    // Max number of allowed different inputs per user. For limiting garbage rf data
    'max_node_id_limit' => 32
),

// Feed settings
"feed"=>array(
    // Supported engines. Uncommented engines will not be available for user to create a new feed using it. Existing feeds with a hidden engine still work.
    // Place a ',' as the first character on all uncommented engines lines but first.
    // If using emoncms in low-write mode, ensure that PHPFIWA is disabled by removing the leading //, from the PHPFIWA entry
    'engines_hidden'=>array(
     Engine::MYSQL         // 0  Mysql traditional
    ,Engine::MYSQLMEMORY   // 8  Mysql with MEMORY tables on RAM. All data is lost on shutdown
    //,Engine::PHPTIMESERIES // 2
    //,Engine::PHPFINA      // 5
    ,Engine::PHPFIWA      // 6
    ,Engine::CASSANDRA    // 10 Apache Cassandra
    ),

    // Redis Low-write mode
    'redisbuffer'   => array(
        // If enabled is true, requires redis enabled and feedwriter service running
        'enabled' => false,
        // Number of seconds to wait before write buffer to disk - user selectable option
        'sleep' => 60
    ),
    
    // Engines working folder. Default is /var/lib/phpfiwa,phpfina,phptimeseries
    // On windows or shared hosting you will likely need to specify a different data directory--
    // Make sure that emoncms has write permission's to the datadirectory folders
    'phpfiwa'       => array('datadir'  => '/var/lib/phpfiwa/'),
    'phpfina'       => array('datadir'  => '/var/lib/phpfina/'),
    'phptimeseries' => array('datadir'  => '/var/lib/phptimeseries/'),
    'cassandra'     => array('keyspace' => 'emoncms'),
    // experimental feature for virtual feeds average, default is true, set to false to activate average agregation with all data points, will be slower
    'virtualfeed'   => array('data_sampling' => false),
    'mysqltimeseries'   => array('data_sampling' => false),
    // Datapoint limit. Increasing this effects system performance but allows for more data points to be read from one api call
    'max_datapoints'        => 8928,
    
    // CSV export options for the number of decimal_places, decimal_place_separator and field_separator
    // The thousands separator is not used (specified as "nothing")
    // NOTE: don't make $csv_decimal_place_separator == $csv_field_separator
    // Adjust as appropriate for your location
    // number of decimal places
    'csv_decimal_places' => 2,

    // decimal place separator
    'csv_decimal_place_separator' => ".",

    // field separator
    'csv_field_separator' => ",",
    
    // Max csv download size in MB
    'csv_downloadlimit_mb' => 25
),

// User Interface settings
"interface"=>array(

    // Applicaton name
    'appname' => "emoncms",

    // gettext  translations are found under each Module's locale directory
    'default_language' => 'en_GB',

    // Theme location (folder located under Theme/, and must have the same structure as the basic one)
    'theme' => "basic",
    
    // Theme colour options: "standard", "blue", "sun"
    'themecolor' => "blue",

    // Favicon filenme in Theme/$theme
    'favicon' => "favicon.png",

    // Main menu collapses on lower screen widths
    'menucollapses' => false,
    
    // Show menu titles
    'show_menu_titles' => true,
    
    // Default controller and action if none are specified and user is anonymous
    'default_controller' => "user",
    'default_action' => "login",

    // Default controller and action if none are specified and user is logged in
    'default_controller_auth' => "feed",
    'default_action_auth' => "list",
    
    // Default feed viewer: "vis/auto?feedid=" or "graph/" - requires module https://github.com/emoncms/graph
    'feedviewpath' => "vis/auto?feedid=",

    // Enable multi user emoncms.
    // If set to false, emoncms will automatically remove the register form and
    // ability to create further users after the first user has been created
    'enable_multi_user' => false,

    // Enable remember me feature
    'enable_rememberme' => true,

    // Allow user to reset password
    'enable_password_reset' => false,
    
    // If installed on Emonpi, allow admin menu tools
    'enable_admin_ui' => false,
    
    // Show update section in admin
    'enable_update_ui' => true,
    
    // Email verification
    'email_verification' => false
),

"public_profile"=>array(
    // Public profile functionality
    // Allows http://yourdomain.com/[username]/[dash alias] or ?id=[dash id]
    // Alternative to http://yourdomain.com/dashboard/view?id=[dash id]
    // Add optional '&embed=1' in the end to remove header and footer
    'enabled' => true,
    'controller' => "dashboard",
    'action' => "view"
),

// (OPTIONAL) Email SMTP, used for password reset or other email functions
"smtp"=>array(
    // Email address to email proccessed input values
    'default_emailto' => '',
    
    'host'=>"",
    // 25, 465, 587
    'port'=>"",
    'from_email' => '',
    'from_name' => '',
    // comment lines below that dont apply
    // ssl, tls
    'encryption'=>"",
    'username'=>"",
    'password'=>""
),

// Log file configuration
"log"=>array(
    "enabled" => true,
    // On windows or shared hosting you will likely need to specify a
    // different logfile directory
    "location" => "/var/log/emoncms",
    // Log Level: 1=INFO, 2=WARN, 3=ERROR
    "level" => 2
),

"device"=>array(
    "enable_UDP_broadcast" => true
),

"cydynni"=>array()
);
