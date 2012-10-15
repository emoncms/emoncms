<?php

$schema['input'] = array(
  'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
  'userid' => array('type' => 'text'),
  'name' => array('type' => 'text'),
  'nodeid' => array('type' => 'int(11)'),
  'processList' => array('type' => 'text'),
  'time' => array('type' => 'datetime'),
  'value' => array('type' => 'float')
);

?>
