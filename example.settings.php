<?php
# ------------------------------------------------------------
# Example emoncms settings.php - DO NOT EDIT!!
#
# 1. copy example.settings.php and rename to settings.php
# 2. edit entries in settings.php as required
# 3. copy settings from default-settings.php into settings.php as required
#    Keep each setting in the block it belongs to
# ------------------------------------------------------------

$settings = array(

// IMPORTANT: Installation domain: If your emoncms install is available on the public internet
// please set this to your fixed domain e.g myemoncmsinstall.org to secure your install.
"domain" => false,

// Path to the symlinked emoncms modules
"emoncms_dir" => "/opt/emoncms",

// Path to the EmonScripts repository
"openenergymonitor_dir" => "/opt/openenergymonitor",

// MySQL database
"sql"=>array(
    "server"   => "localhost",
    "database" => "emoncms",
    "username" => "emoncms",
    "password" => "password",
    "port"     => 3306,
    // Skip the database setup test, set to false once the database is set up
    "dbtest"   => true
),

// Redis, used as a cache to reduce SD card/disk write wear and improve performance
"redis"=>array(
    'enabled' => true,
    'prefix'  => ''
),

// MQTT, used by the emoncms_mqtt service to send and receive data
// Restart the service after a change: sudo systemctl restart emoncms_mqtt.service
"mqtt"=>array(
    'enabled'  => false,
    'user'     => 'username',
    'password' => 'password'
),

"feed"=>array(
    // Engines hidden from feed creation, existing feeds will still work
    // MYSQL:0, PHPTIMESERIES:2, PHPFINA:5, PHPFIWA:6, MYSQLMEMORY:8, CASSANDRA:10
    'engines_hidden' => array(0,6,8,10),
    'redisbuffer'    => array(
        // Buffer feed data in redis, needs redis enabled and the feedwriter service
        'enabled' => true,
        // Seconds to wait before writing the buffer to disk
        'sleep' => 300
    ),
    // Feed data directories, ensure write permission on both
    'phpfina'        => array('datadir' => '/var/opt/emoncms/phpfina/'),
    'phptimeseries'  => array('datadir' => '/var/opt/emoncms/phptimeseries/')
),

"interface"=>array(
    'enable_admin_ui' => false,
    'feedviewpath'    => "graph/",
    'favicon'         => "favicon.png"
),

"log"=>array(
    // Log Level: 1=INFO, 2=WARN, 3=ERROR
    "level" => 2
)
);
