<?php

  error_reporting(E_ALL ^ (E_NOTICE | E_WARNING)); 
  
  require('SAM/php_sam.php');
  
  $conn = new SAMConnection();
  $conn->connect(SAM_MQTT, array(SAM_HOST => '127.0.0.1', SAM_PORT => 1883));
  
  $m = array(
    'time'=>time(),
    'node'=>10,
    'csv'=>array(200,300,400)
  );
  
  $msg = new SAMMessage(json_encode($m));
  $conn->send('topic://emoncms/input', $msg);

