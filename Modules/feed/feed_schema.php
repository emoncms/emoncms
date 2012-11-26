<?php

$schema['feeds'] = array(
  'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
  'name' => array('type' => 'text'),
  'userid' => array('type' => 'int(11)'),
  'tag' => array('type' => 'text'),
  'time' => array('type' => 'datetime'),
  'value' => array('type' => 'float'),
  'status' => array('type' => 'int(11)'),
  'datatype' => array('type' => 'int(11)', 'Null'=>'NO'),
  'public' => array('type' => 'tinyint(1)', 'default'=>false)
);

?>
