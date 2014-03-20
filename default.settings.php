<?php
/**
 * Debug setting
 */
Configure::write('debug', 0);

/**
 * Database Configuration
 */
Configure::write('DB_CONFIG', array(
    'username' => null,
    'password' => null,
    'server' => 'localhost',
    'database' => null,
    
    
    'dbtest' => true, // Skip database setup test - set to false once database has been setup.
));

/**
 * redis configuration
 */
Configure::write('Redis', array(
    'host' => '127.0.0.1',
    'enabled' => true,
));

/**
 * SMTP configuration
 */
Configure::write('Smtp', array(
    'host' => '',
    'port' => 26,
    'username' => '',
    'password' => '',
    'from' => array('email' => 'name'),
));

/**
 * EmonCMS Configuration
 */
Configure::write('EmonCMS', array(
    'theme' => 'default',
    'enable_password_reset' => false,
    'max_node_id_limit' => 32,
    'Auth' => array(
        // Default controller and action if none are specified and user is anonymous
        'default_controller' => 'user',
        'default_action' => 'login',

        // Default controller and action if none are specified and user is logged in
        'default_controller_auth' => 'user',
        'default_action_auth' => 'view',
        
        'allowusersregister' => true,
        'enable_rememberme' => true,
    ),
    'Profile' => array(
        'public_profile_enabled' => true,
        'public_profile_controller' => 'dashboard',
        'public_profile_action' => 'view',
    ),
));

/**
 * Security related settings
 */
Configure::write('Security', array(
    'salt' => 'random chars here, any length',
));

/**
 * Feed Configuration
 */
Configure::write('Feed', array(
    'engines' => array('MYSQL', 'TIMESTORE', 'PHPTIMESERIES', 'GRAPHITE', 'PHPTIMESTORE'),
    'timestre' => array(
        'adminkey' => ''
    ),
    'graphite' => array(
        'port' => 0,
        'host' => 0
    ),
    
    // The default data directory is /var/lib/phpfiwa,phpfina,phptimeseries on windows or shared hosting you will likely need to specify a different data directory.
    // Make sure that emoncms has write permission's to the datadirectory folders
    
    'phpfiwa' => array(
        //'datadir'=> '/home/username/emoncmsdata/phpfiwa/',
    ),
    'phpfina' => array(
        //'datadir'=> '/home/username/emoncmsdata/phpfina/',
    ),
    'phptimeseries' => array(
        //'datadir' => '/home/username/emoncmsdata/phptimeseries/',
    )
));
