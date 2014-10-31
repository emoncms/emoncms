<?php

    chdir("/var/www/emoncms");
    require("Lib/phpMQTT.php");

    $mqtt = new phpMQTT("127.0.0.1", 1883, "Emoncms input pub example");

    if ($mqtt->connect()) {
    
        $mqtt->publish("emoncms/input/5","100,200,300",0);
        sleep(1);
        
        $mqtt->publish("emoncms/input/10/power",350.3,0);
        sleep(1);
        
        $mqtt->publish("emoncms/input/house/power",2500,0);
        sleep(1);
        
        $mqtt->publish("emoncms/input/house/temperature",18.2,0);
        sleep(1);
        
        $m = array(
            'apikey'=>"d8e9fa2ccc5c2a9c24bc75cd8596404e",
            'time'=>time(),
            'node'=>1,
            'csv'=>array(200,300,400)
        );
  
        $mqtt->publish("emoncms/input",json_encode($m),0);
        sleep(1);
        
        
        $mqtt->close();
    }

