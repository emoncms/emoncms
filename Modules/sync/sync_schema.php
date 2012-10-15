<?php

$schema['importqueue'] = array(
  'queid' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
  'baseurl' => array('type' => 'text'),
  'apikey' => array('type' => 'text'),
  'feedid' => array('type' => 'int(11)'),
  'localfeedid' => array('type' => 'int(11)')
);

?>
