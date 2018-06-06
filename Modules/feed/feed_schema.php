<?php

$schema['feeds'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>false, 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'name' => array('type' => 'text'),
    'userid' => array('type' => 'int(11)'),
    'tag' => array('type' => 'text'),
    'time' => array('type' => 'int(10)'),
    'value' => array('type' => 'double'),
    'datatype' => array('type' => 'int(11)', 'Null'=>false),
    'public' => array('type' => 'tinyint(1)', 'default'=>0),
    'size' => array('type' => 'int(11)'),
    'engine' => array('type' => 'int(11)', 'Null'=>false, 'default'=>0),
    'processList' => array('type' => 'text'),
    'unit' => array('type' => 'varchar(10)')
);
