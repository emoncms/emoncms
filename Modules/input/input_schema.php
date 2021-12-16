<?php

$schema['input'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>false, 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'userid' => array('type' => 'int(11)'),
    'nodeid' => array('type' => 'text'),
    'name' => array('type' => 'text'),
    'indx' => array('type' => 'int(11)','default'=>-1),
    'description' => array('type' => 'text'),
    'processList' => array('type' => 'text'),
    'time' => array('type' => 'int(10)'),
    'value' => array('type' => 'float')
);
