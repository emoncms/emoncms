<?php

$schema['schedule'] = array(
    'id' => array('type' => 'int', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'userid' => array('type' => 'int'),
    'name' => array('type' => 'varchar(30)'),
    'expression' => array('type' => 'text'),
    'timezone' => array('type'=>'varchar(64)', 'default'=>'UTC'),
    'public' => array('type' => 'tinyint(1)', 'default'=>0)
);
