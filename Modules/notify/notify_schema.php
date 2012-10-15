<?php

$schema['notify'] = array(
  'userid' => array('type' => 'int(11)'),
  'feedid' => array('type' => 'int(11)'),
  'onvalue' => array('type' => 'float'),
  'onvalue_sent' => array('type' => 'tinyint(1)'),
  'oninactive' => array('type' => 'tinyint(1)'),
  'oninactive_sent' => array('type' => 'tinyint(1)'),
  'periodic' => array('type' => 'tinyint(1)')
);

$schema['notify_mail'] = array(
  'userid' => array('type' => 'int(11)'),
  'recipients' => array('type' => 'text')
);

?>
