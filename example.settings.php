<?php
// -----------------------------------------------------
// Example emoncms settings.php file
//
// default-settings.php contains the default settings.
//
// Settings entered here override the default settings.
// Enter only the settings you wish to customise here.
//
// The following is a barebones example, copy across
// other settings from default-settings.php as required
// Maintain the section structure (items in [])
// -----------------------------------------------------

$settings = array(
// Set Emoncms installation domain here to secure installation e.g domain = myemoncmsinstall.org
"domain" => false,

// MYSQL Database settings
"sql"=>array(
    "server"   => "localhost",
    "database" => "emoncms",
    "username" => "_DB_USER_",
    "password" => "_DB_PASSWORD_",
    "port"     => 3306,
     // Skip database setup test - set to false once database has been setup.
    "dbtest"   => true
),

// Redis Database (used as a cache for improved performance)
"redis"=>array(
    'enabled' => false
),

// MQTT Used with emoncms_mqtt service to send and receive data over MQTT
// If MQTT settings are changed reboot or execute "sudo systemctl restart emoncms_mqtt.service"
"mqtt"=>array(
    'enabled'   => false,
    'user'      => '',
    'password'  => ''
),

// Feed engine settings
"feed"=>array(
    // Supported engines. List engines by id to disable feed creation.
    // Existing feeds with a hidden engine still work

    // MYSQL:0, MYSQLMEMORY:8, PHPTIMESERIES:2, PHPFINA:5, CASSANDRA:10
    // 5:phpfina and 2:phptimeseries are the recommended emoncms feed engines
    'engines_hidden'=>array(0,8,10),
    // Buffer data to be written to
    'redisbuffer'   => array(
        'enabled' => false,
        'sleep' => 60
    ),
    'phpfina'       => array('datadir'  => '/var/opt/emoncms/phpfina/'),
    'phptimeseries' => array('datadir'  => '/var/opt/emoncms/phptimeseries/')
),

// Enable the graph module if you have it installed
"interface"=>array(
    'feedviewpath' => "graph/"
),

"public_profile"=>array(

),

"smtp"=>array(
    // Email address to email proccessed input values
    // 'default_emailto' => 'root@localhost',
    // 'host'=>"smtp.gmail.com",
    // 25, 465, 587
    // 'port'=>"465",
    // 'from_email' => 'noreply@emoncms.org',
    // 'from_name' => 'EmonCMS',
    // comment lines below that dont apply
    // ssl, tls
    // 'encryption'=>"ssl",
    // 'username'=>"yourusername@gmail.com",
    // 'password'=>"yourpassword"
),

"log"=>array(
    // Log Level: 1=INFO, 2=WARN, 3=ERROR
    "level" => 2
)
);
