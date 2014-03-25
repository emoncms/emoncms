<?php

  $c = stream_context_create(array('dio' =>
    array('data_rate' => 9600,
          'data_bits' => 8,
          'stop_bits' => 1,
          'parity' => 0,
          'flow_control' => 0,
          'is_canonical' => 1)));

  if (PATH_SEPARATOR != ";") {
    $filename = "dio.serial:///dev/ttyUSB0";
  } else {
    $filename = "dio.serial://dev/ttyUSB0";
  }

  $f = fopen($filename, "r+", false, $c);
  stream_set_timeout($f, 0,1000);

  while(true)
  {
    $data = trim(fgets($f));
    if ($data && $data!="\n")
    {
      $parts = explode(' ',$data);
      
      if ($parts[0]=='OK')
      {
        $nodeid = (int) $parts[1];
        unset($parts[0]);
        unset($parts[1]);
        echo "nodeid=".$nodeid."&data=".implode($parts,',')."\n";
        file_get_contents("http://localhost/emoncmsd/node/set.json?apikey=589e18d070e7fe4a198582b4d278d6e2&nodeid=".$nodeid."&data=".implode($parts,','));
      }
    }
  }

  fclose($f);

