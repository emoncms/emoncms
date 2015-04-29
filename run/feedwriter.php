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
    
    $log = new EmonLogger(__FILE__);
    $log->set_logfile("/var/log/emoncms.log");
    $log->set_topic("FEEDWRITER");
    $log->info("Starting feedwriter process");
    
    // Connect to redis
    $redis = new Redis();
    while (!$redis->connect("127.0.0.1")) {
        sleep(1);
        $log->warn("Could not connect to redis, retrying");
    }
    
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
            $padding_mode = (int) $f[3];
        
            $e = $redis->hget("feed:$feedid",'engine');
            
            if ($padding_mode==1) $engine[Engine::PHPFINA]->padding_mode = 'join';
            $engine[$e]->prepare($feedid,$timestamp,$value);
            $engine[Engine::PHPFINA]->padding_mode = 'nan';
        }
        
        $log->info("PHPTimeSeries bytes written: ".$engine[Engine::PHPTIMESERIES]->save());
        $log->info("PHPFina bytes written: ".$engine[Engine::PHPFINA]->save());
        sleep(60);
    }
