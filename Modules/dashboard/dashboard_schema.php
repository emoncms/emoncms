<?php

$schema['dashboard'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'userid' => array('type' => 'int(11)'),
    'content' => array('type' => 'text'),
    'height' => array('type' => 'int(11)'),
    'name' => array('type' => "varchar(30)", 'default'=>'no name'),
    'alias' => array('type' => "varchar(10)"),
    'description' => array('type' => "varchar(255)", 'default'=>'no description'),
    'main' => array('type' => 'tinyint(1)', 'default'=>false),
    'public' => array('type' => 'tinyint(1)', 'default'=>false),
    'published' => array('type' => 'tinyint(1)', 'default'=>false),
    'showdescription' => array('type' => 'tinyint(1)', 'default'=>false)
);

