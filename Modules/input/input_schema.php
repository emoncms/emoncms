<?php

$schema['input'] = array(
  'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
  'userid' => array('type' => 'int(11)'),
  'name' => array('type' => 'text'),
  'description' => array('type' => 'text', 'Null'=>'NO', 'default'=>''),
  'nodeid' => array('type' => 'int(11)'),
  'processList' => array('type' => 'text')
);

?>
