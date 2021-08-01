<?php
// ------------------------------------------------------------------------------
// Processes old settings format into new settings object
// ------------------------------------------------------------------------------
$settings = array(
// Set Emoncms installation domain here to secure installation e.g domain = myemoncmsinstall.org
"domain" => isset($domain)?$domain:false,
// Suggested installation path for symlinked emoncms modules /opt/emoncms/modules
"emoncms_dir" => isset($emoncms_dir)?$emoncms_dir:"/home/pi",
// Suggested installation path for emonpi and EmonScripts repository:
"openenergymonitor_dir" => isset($openenergymonitor_dir)?$openenergymonitor_dir:"/home/pi",

// Show all fatal PHP errors
"display_errors" => isset($display_errors)?$display_errors:true,

// Set to true to run database update without logging in
// URL Example: http://localhost/emoncms/admin/db
"updatelogin" => isset($updatelogin)?$updatelogin:false,

// Mysql database settings
"sql"=>array(
    "server"   => isset($server)?$server:"localhost",
    "database" => isset($database)?$database:"emoncms",
    "username" => isset($username)?$username:"_DB_USER_",
    "password" => isset($password)?$password:"_DB_PASSWORD_",
    "port"     => isset($port)?$port:3306,
     // Skip database setup test - set to false once database has been setup.
    "dbtest"   => isset($dbtest)?$dbtest:true
),

// Redis
"redis"=>array(
    'enabled' => isset($redis_enabled)?$redis_enabled:false,
    'host'    => isset($redis_server["host"])?$redis_server["host"]:'localhost',
    'port'    => isset($redis_server["port"])?$redis_server["port"]:6379,
    'auth'    => isset($redis_server["auth"])?$redis_server["auth"]:'',
    'dbnum'   => isset($redis_server["dbnum"])?$redis_server["dbnum"]:'',
    'prefix'  => isset($redis_server["prefix"])?$redis_server["prefix"]:'emoncms'
),

// MQTT
"mqtt"=>array(
    // The 'subscriber' topic format is rx/* - where * is the emoncms input node number.
    // The 'publisher' topic format is user selectable from the 'Publish to MQTT' input process, for example power/solar
    // Activate MQTT by changing to true
    'enabled'   => isset($mqtt_enabled)?$mqtt_enabled:false,
    'host'      => isset($mqtt_server["host"])?$mqtt_server["host"]:'localhost',
    'port'      => isset($mqtt_server["port"])?$mqtt_server["port"]:1883,
    'user'      => isset($mqtt_server["user"])?$mqtt_server["user"]:'',
    'password'  => isset($mqtt_server["password"])?$mqtt_server["password"]:'',
    'basetopic' => isset($mqtt_server["basetopic"])?$mqtt_server["basetopic"]:'emon',
    'client_id' => isset($mqtt_server["client_id"])?$mqtt_server["client_id"]:'emoncms',
    'capath'    => isset($mqtt_server["capath"])?$mqtt_server["capath"]:null,
    'certpath'  => isset($mqtt_server["certpath"])?$mqtt_server["certpath"]:null,
    'keypath'   => isset($mqtt_server["keypath"])?$mqtt_server["keypath"]:null,
    'keypw'     => isset($mqtt_server["keypwpath"])?$mqtt_server["keypw"]:null
),

// Input
"input"=>array(
    // Max number of allowed different inputs per user. For limiting garbage rf data
    'max_node_id_limit' => isset($max_node_id_limit)?$max_node_id_limit:32
),

// Feed settings
"feed"=>array(
    // Supported engines. Uncommented engines will not be available for user to create a new feed using it. Existing feeds with a hidden engine still work.
    // Place a ',' as the first character on all uncommented engines lines but first.
    'engines_hidden'=>isset($feed_settings["engines_hidden"])?$feed_settings["engines_hidden"]:array(
    // Engine::MYSQL         // 0  Mysql traditional
    //,Engine::MYSQLMEMORY   // 8  Mysql with MEMORY tables on RAM. All data is lost on shutdown
    //,Engine::PHPTIMESERIES // 2
    //,Engine::PHPFINA      // 5
    //,Engine::CASSANDRA    // 10 Apache Cassandra
    ),

    // Redis Low-write mode
    'redisbuffer'   => array(
        // If enabled is true, requires redis enabled and feedwriter service running
        'enabled' => isset($feed_settings["redisbuffer"]["enabled"])?$feed_settings["redisbuffer"]["enabled"]:false,
        // Number of seconds to wait before write buffer to disk - user selectable option
        'sleep' => isset($feed_settings["redisbuffer"]["sleep"])?$feed_settings["redisbuffer"]["sleep"]:600
    ),

    // Engines working folder. Default is /var/lib/phpfina,phptimeseries
    // On windows or shared hosting you will likely need to specify a different data directory--
    // Make sure that emoncms has write permission's to the datadirectory folders
    'phpfina'       => array('datadir'  => isset($feed_settings["phpfina"]["datadir"])?$feed_settings["phpfina"]["datadir"]:'/var/lib/phpfina/'),
    'phptimeseries' => array('datadir'  => isset($feed_settings["phptimeseries"]["datadir"])?$feed_settings["phptimeseries"]["datadir"]:'/var/lib/phptimeseries/'),
    'cassandra'     => array('keyspace' => isset($feed_settings["cassandra"]["keyspace"])?$feed_settings["cassandra"]["keyspace"]:'emoncms'),
    // experimental feature for virtual feeds average, default is true, set to false to activate average agregation with all data points, will be slower
    'virtualfeed'   => array('data_sampling' => false),
    'mysqltimeseries' => array(
        'data_sampling' => false,
        'datadir'       => isset($feed_settings["mysql"]["datadir"])?$feed_settings["mysql"]["datadir"]:'',
        'prefix'        => isset($feed_settings["mysql"]["prefix"])?$feed_settings["mysql"]["prefix"]:'feed_',
        'generic'       => isset($feed_settings["mysql"]["generic"])?$feed_settings["mysql"]["generic"]:true,
        'database'      => isset($feed_settings["mysql"]["database"])?$feed_settings["mysql"]["database"]:null,
        'username'      => isset($feed_settings["mysql"]["username"])?$feed_settings["mysql"]["username"]:null,
        'password'      => isset($feed_settings["mysql"]["password"])?$feed_settings["mysql"]["password"]:null
    ),
    // Datapoint limit. Increasing this effects system performance but allows for more data points to be read from one api call
    'max_datapoints' => isset($max_datapoints)?$max_datapoints:8928,

    // CSV export options for the number of decimal_places, decimal_place_separator and field_separator
    // The thousands separator is not used (specified as "nothing")
    // NOTE: don't make $csv_decimal_place_separator == $csv_field_separator
    // Adjust as appropriate for your location
    // number of decimal places
    'csv_decimal_places' => isset($csv_decimal_places)?$csv_decimal_places:2,

    // decimal place separator
    'csv_decimal_place_separator' => isset($csv_decimal_place_separator)?$csv_decimal_place_separator:".",

    // field separator
    'csv_field_separator' => isset($csv_field_separator)?$csv_field_separator:",",
    
    // Max csv download size in MB
    'csv_downloadlimit_mb' => isset($feed_settings["csv_downloadlimit_mb"])?$feed_settings["csv_downloadlimit_mb"]:25
),

// User Interface settings
"interface"=>array(

    // Applicaton name
    'appname' => isset($appname)?$appname:"emoncms",

    // gettext  translations are found under each Module's locale directory
    'default_language' => isset($default_language)?$default_language:'en_GB',

    // Theme location (folder located under Theme/, and must have the same structure as the basic one)
    'theme' => isset($theme)?$theme:"basic",
    
    // Theme colour options: "standard", "blue", "sun"
    'themecolor' => isset($themecolor)?$themecolor:"blue",

    // Favicon filenme in Theme/$theme
    'favicon' => isset($favicon)?$favicon:"favicon.png",

    // Main menu collapses on lower screen widths
    'menucollapses' => isset($menucollapses)?$menucollapses:false,
    
    // Show menu titles
    'show_menu_titles' => isset($show_menu_titles)?$show_menu_titles:true,
    
    // Default controller and action if none are specified and user is anonymous
    'default_controller' => isset($default_controller)?$default_controller:"user",
    'default_action' => isset($default_action)?$default_action:"login",

    // Default controller and action if none are specified and user is logged in
    'default_controller_auth' => isset($default_controller_auth)?$default_controller_auth:"feed",
    'default_action_auth' => isset($default_action_auth)?$default_action_auth:"list",
    
    // Default feed viewer: "vis/auto?feedid=" or "graph/" - requires module https://github.com/emoncms/graph
    'feedviewpath' => isset($feedviewpath)?$feedviewpath:"vis/auto?feedid=",

    // Enable multi user emoncms.
    // If set to false, emoncms will automatically remove the register form and
    // ability to create further users after the first user has been created
    'enable_multi_user' => isset($enable_multi_user)?$enable_multi_user:false,

    // Enable remember me feature
    'enable_rememberme' => isset($enable_rememberme)?$enable_rememberme:true,

    // Allow user to reset password
    'enable_password_reset' => isset($enable_password_reset)?$enable_password_reset:false,
    
    // If installed on Emonpi, allow admin menu tools
    'enable_admin_ui' => isset($allow_emonpi_admin)?$allow_emonpi_admin:false,
    
    // Show update section in admin
    'enable_update_ui' => isset($admin_show_update)?$admin_show_update:true,
    
    // Email verification
    'email_verification' => isset($email_verification)?$email_verification:false
),

"public_profile"=>array(
    // Public profile functionality
    // Allows http://yourdomain.com/[username]/[dash alias] or ?id=[dash id]
    // Alternative to http://yourdomain.com/dashboard/view?id=[dash id]
    // Add optional '&embed=1' in the end to remove header and footer
    'enabled' => isset($public_profile_enabled)?$public_profile_enabled:true,
    'controller' => isset($public_profile_controller)?$public_profile_controller:"dashboard",
    'action' => isset($public_profile_action)?$public_profile_action:"view"
),

// (OPTIONAL) Email SMTP, used for password reset or other email functions
"smtp"=>array(
    // Email address to email proccessed input values
    'default_emailto' => isset($default_emailto)?$default_emailto:'root@localhost',
    
    'host'=>isset($smtp_email_settings["host"])?$smtp_email_settings["host"]:"smtp.gmail.com",
    // 25, 465, 587
    'port'=>isset($smtp_email_settings["port"])?$smtp_email_settings["port"]:"465",
    'from_email' =>isset($smtp_email_settings["from_email"])?$smtp_email_settings["from_email"]:'noreply@emoncms.org',
    'from_name' =>isset($smtp_email_settings["from_name"])?$smtp_email_settings["from_name"]:'EmonCMS',

    // comment lines below that dont apply
    // ssl, tls
    'encryption'=>isset($smtp_email_settings["encryption"])?$smtp_email_settings["encryption"]:"ssl",
    'username'=>isset($smtp_email_settings["username"])?$smtp_email_settings["username"]:"yourusername@gmail.com",
    'password'=>isset($smtp_email_settings["password"])?$smtp_email_settings["password"]:"yourpassword"
),

// Log file configuration
"log"=>array(
    "enabled" => isset($log_enabled)?$log_enabled:true,
    // On windows or shared hosting you will likely need to specify a
    // different logfile directory
    "location" => isset($log_location)?$log_location:"/var/log/emoncms",
    // Log Level: 1=INFO, 2=WARN, 3=ERROR
    "level" => isset($log_level)?$log_level:2
)
);
