<?php

$schema['schedule'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'userid' => array('type' => 'int(11)'),
    'name' => array('type' => 'varchar(30)'),
    'expression' => array('type' => 'text','default'=>''),
    'timezone' => array('type'=>'varchar(64)', 'default'=>'UTC'),
    'public' => array('type' => 'tinyint(1)', 'default'=>0)
);