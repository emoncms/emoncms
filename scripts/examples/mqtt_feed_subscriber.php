<?php
    
    chdir("/var/www/emoncms");
    
    require("Lib/phpMQTT.php");
    $mqtt = new phpMQTT("127.0.0.1", 1883, "Emoncms feed subscriber");
    
    if(!$mqtt->connect()){
	    exit(1);
    }
    
    $topics["emoncms/#"] = array("qos"=>0, "function"=>"procmsg");
    $mqtt->subscribe($topics,0);
    while($mqtt->proc()){ }
    $mqtt->close();
    
    function procmsg($topic,$value)
    { 
        $time = time();
        print $topic." ".$value."\n";
    }
