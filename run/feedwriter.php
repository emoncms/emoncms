<?php
    // This code is released under the GNU Affero General Public License.
    // OpenEnergyMonitor project:
    // http://openenergymonitor.org


    define('EMONCMS_EXEC', 1);

    $fp = fopen("/home/pi/data/feedrunlock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }
    
    chdir("/var/www/emoncms");
    require "Modules/log/EmonLogger.php";
    require "process_settings.php";
    
    // Sleep here seemed to be needed to allow redis time to start at startup
    sleep(10);
    $redis = new Redis();
    $connected = $redis->connect("127.0.0.1");
    
    require "Modules/feed/engine/PHPTimeSeries.php";
    require "Modules/feed/engine/PHPFina.php";
    
    $engine = array();
    $engine[Engine::PHPTIMESERIES] = new PHPTimeSeries($feed_settings['phptimeseries']);
    $engine[Engine::PHPFINA] = new PHPFina($feed_settings['phpfina']);
    
    

    while(true)
    {
        $len = $redis->llen("feedbuffer");
        for ($i=0; $i<$len; $i++)
        {
            $f = explode(",",$redis->lpop("feedbuffer"));
            
            $feedid = $f[0];
            $timestamp = $f[1];
            $value = $f[2];
        
            $e = $redis->hget("feed:$feedid",'engine');
            $engine[$e]->prepare($feedid,$timestamp,$value);
        }
        
        print $engine[Engine::PHPTIMESERIES]->save()."\n";
        print $engine[Engine::PHPFINA]->save()."\n";
        print "\n";
        sleep(60);
    }
