<?php

    /*

    Database connection settings

    */

    $username = "";
    $password = "";
    $server   = "localhost";
    $database = "";

    // By setting the default engine to MYSQL, realtime feeds will be created as MYSQL feeds providing full backwards compatibility

    // PHPTIMESERIES is another feed engine option that might be of interest, faster than MYSQL but maintaining the data in the same
    // form as mysql data is stored.

    // TIMESTORE is the default engine and requires installation of timestore, timestore is the fastest engine and also has other advantages like in built averaging.

    $default_engine = Engine::TIMESTORE;


    // Checks for limiting garbage data?
    $max_node_id_limit = 32;

    $timestore_adminkey = "";

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

