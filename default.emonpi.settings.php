<?php

$server   = "127.0.0.1";
$database = "emoncms";
$username = "emoncms";
$password = "emonpiemoncmsmysql2016";
$port     = "3306";

$redis_enabled = true;
$redis_server['prefix'] = '';

$mqtt_enabled = true;
$mqtt_server['user'] = 'emonpi';
$mqtt_server['password'] = 'emonpimqtt2016';

$feed_settings['engines_hidden'] = array(
    Engine::MYSQL,
    Engine::PHPFIWA,
    Engine::CASSANDRA
);

$feed_settings['redisbuffer'] = array(
    'enabled' => true
    ,'sleep' => 60
);

$feed_settings['phpfiwa']['datadir'] = '/home/pi/data/phpfiwa/';
$feed_settings['phpfina']['datadir'] = '/home/pi/data/phpfina/';
$feed_settings['phptimeseries']['datadir'] = '/home/pi/data/phptimeseries/';

// Favicon filenme in Theme/$theme
$favicon = "favicon_emonpi.png";
