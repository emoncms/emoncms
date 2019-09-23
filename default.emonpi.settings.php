<?php

$settings = array(

    "sql"=>array(
        "username" => "emoncms",
        "password" => "emonpiemoncmsmysql2016",
    ),

    "redis"=>array(
        'enabled' => true,
        'prefix'  => ''
    ),
    
    "mqtt"=>array(
        'enabled'   => true,
        'user'      => 'emonpi',
        'password'  => 'emonpimqtt2016'
    ),
    
    "feed"=>array(
        'engines_hidden'=>array(
            Engine::MYSQL,
            Engine::PHPFIWA,
            Engine::CASSANDRA
        ),
        'redisbuffer'   => array(
            'enabled' => true
        ),
        "phpfina"=>array("datadir"=>"/var/opt/emoncms/phpfina/"),
        "phptimeseries"=>array("datadir"=>"/var/opt/emoncms/phptimeseries/")
    ),
    
    "interface"=>array(
        'enable_admin_ui' => true,
        "feedviewpath" => "graph/"
    )
);
