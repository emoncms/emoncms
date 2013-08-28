<?php

$schema['users'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'username' => array('type' => 'varchar(30)'),
    'email' => array('type' => 'varchar(30)'),
    'password' => array('type' => 'varchar(64)'),
    'salt' => array('type' => 'varchar(3)'),
    'apikey_write' => array('type' => 'varchar(64)'),
    'apikey_read' => array('type' => 'varchar(64)'),
    'lastlogin' => array('type' => 'datetime'),
    'admin' => array('type' => 'int(11)', 'Null'=>'NO'),

    // User profile fields
    'gravatar' => array('type' => 'varchar(30)', 'default'=>''),
    'name'=>array('type'=>'varchar(30)', 'default'=>''),
    'location'=>array('type'=>'varchar(30)', 'default'=>''),
    'timezone' => array('type' => 'int(11)', 'default'=>0),
    'language' => array('type' => 'varchar(5)', 'default'=>'en_EN'),
    'bio' => array('type' => 'text', 'default'=>'')
);

$schema['rememberme'] = array(
    'userid' => array('type' => 'int(11)'),
    'token' => array('type' => 'varchar(40)'),
    'persistentToken' => array('type' => 'varchar(40)'),
    'expire' => array('type' => 'datetime')
);
