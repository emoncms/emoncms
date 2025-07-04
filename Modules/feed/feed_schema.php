<?php

$schema['feeds'] = array(
    'id' => array('type' => 'int', 'Null'=>false, 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'name' => array('type' => 'text'),
    'userid' => array('type' => 'int'),
    'tag' => array('type' => 'text'),
    'time' => array('type' => 'int'),
    'value' => array('type' => 'double'),
    'datatype' => array('type' => 'int', 'Null'=>false), 
    'public' => array('type' => 'tinyint(1)', 'default'=>0),
    'size' => array('type' => 'int'),
    'engine' => array('type' => 'int', 'Null'=>false, 'default'=>0),
    'server' => array('type' => 'int', 'Null'=>false, 'default'=>0),
    'processList' => array('type' => 'text'),
    'unit' => array('type' => 'varchar(10)','default'=>'')
);
