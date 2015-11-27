<?php

$schema['device'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'userid' => array('type' => 'int(11)'),
    'name' => array('type' => 'text', 'default'=>''),
    'description' => array('type' => 'text','default'=>''),
	'type' => array('type' => 'varchar(32)'),
    'nodeid' => array('type' => 'text'),
	'devicekey' => array('type' => 'varchar(64)'),
	'time' => array('type' => 'int(10)')
);