<?php

$schema['feeds'] = array(
  'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
  'name' => array('type' => 'text'),
  'userid' => array('type' => 'int(11)'),
  'tag' => array('type' => 'text'),
  'time' => array('type' => 'datetime'),
  'value' => array('type' => 'float'),
  'datatype' => array('type' => 'int(11)', 'Null'=>'NO'),
  'public' => array('type' => 'tinyint(1)', 'default'=>false),


  'dpinterval' => array('type' => 'int(11)', 'Null'=>'NO', 'default'=>10),
  'size' => array('type' => 'int(11)', 'Null'=>'NO', 'default'=>0),

  'timestore' => array('type' => 'int(11)', 'Null'=>'NO', 'default'=>0),
  'convert' => array('type' => 'int(11)', 'Null'=>'NO', 'default'=>0)


);

//ALTER TABLE  `feeds` ADD `timestore` TINYINT NOT NULL;
//ALTER TABLE  `feeds` ADD  `dpinterval` INT NOT NULL DEFAULT  '10';
///ALTER TABLE `users` ADD `convert` INT NOT NULL DEFAULT '0';

?>
