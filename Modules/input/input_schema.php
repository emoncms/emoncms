<?php

$schema['input'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'userid' => array('type' => 'int(11)'),
    'name' => array('type' => 'text'),
    'description' => array('type' => 'text','default'=>''),
    'nodeid' => array('type' => 'text'),
    'processList' => array('type' => 'text'),
    'time' => array('type' => 'int(10)'),
    'value' => array('type' => 'float')
);
