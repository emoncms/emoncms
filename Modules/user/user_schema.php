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
  'uphits' => array('type' => 'int(11)', 'Null'=>'NO'),
  'dnhits' => array('type' => 'int(11)', 'Null'=>'NO'),
  'admin' => array('type' => 'int(11)', 'Null'=>'NO'),
  'lang' => array('type' => 'varchar(5)'),
  'timeoffset' => array('type' => 'int(11)', 'Null'=>'NO'),
  'settingsarray' => array('type' => 'text')
);

?>
