<?php

$settings = array(

    "sql"=>array(
        "server" => "127.0.0.1",
        "database" => "emoncms",
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
        "phpfina"=>array("datadir"=>"/home/pi/data/phpfina/"),
        "phptimeseries"=>array("datadir"=>"/home/pi/data/phptimeseries/")
    ),
    
    "interface"=>array(
        'enable_admin_ui' => true,
        "feedviewpath" => "graph/"
    )
);
