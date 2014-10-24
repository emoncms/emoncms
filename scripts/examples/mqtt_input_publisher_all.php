<?php

  error_reporting(E_ALL ^ (E_NOTICE | E_WARNING)); 
  
  require('SAM/php_sam.php');
  
  $conn = new SAMConnection();
  $conn->connect(SAM_MQTT, array(SAM_HOST => '127.0.0.1', SAM_PORT => 1883));
  
  $m = array(
    'apikey'=>"d8e9fa2ccc5c2a9c24bc75cd8596404e",
    'time'=>time(),
    'node'=>1,
    'csv'=>array(200,300,400)
  );
  
  $msg = new SAMMessage(json_encode($m));
  $conn->send('topic://emoncms/input', $msg);
  sleep(1);
  
  $msg = new SAMMessage("100,200,300");
  $conn->send('topic://emoncms/input/5', $msg);
  sleep(1);
  
  $msg = new SAMMessage(250.2);
  $conn->send('topic://emoncms/input/10/power', $msg);
  sleep(1);


