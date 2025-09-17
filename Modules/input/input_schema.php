<?php

$schema['input'] = array(
    'id' => array('type' => 'int', 'Null'=>false, 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'userid' => array('type' => 'int'),
    'nodeid' => array('type' => 'text'),
    'name' => array('type' => 'text'),
    'description' => array('type' => 'text'),
    'processList' => array('type' => 'text'),
    'time' => array('type' => 'int'),
    'value' => array('type' => 'float')
);

// Table of users with input creation disabled
$schema['input_disable'] = array(
    'userid' => array('type' => 'int', 'Null'=>false, 'Key'=>'PRI'),
    'time' => array('type' => 'int')
);