<?php
    /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
    */

    define('EMONCMS_EXEC', 1);

    //Config this:
    $userid = 1;        // user id
    $outinterval = 300; // export interval in seconds
    $time = time();     // generate export for last month of this time
    $exportpath = './exports';     // where to save export backup file (relative to base emoncms path)

    // Dont change bellow this  

    $fp = fopen("/var/lock/export_daily.lock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

    chdir(dirname(__FILE__)."/../");
    require "Lib/EmonLogger.php";
    require "process_settings.php";

    $log = new EmonLogger(__FILE__);
    $log->info("Starting export task script");

    $mysqli = @new mysqli($server,$username,$password,$database,$port);
    if ($mysqli->connect_error) { $log->error("Can't connect to database:". $mysqli->connect_error);  die('Check log\n'); }

    if ($redis_enabled) {
        $redis = new Redis();
        if (!$redis->connect($redis_server['host'], $redis_server['port'])) { 
            $log->error("Could not connect to redis at ".$redis_server['host'].":".$redis_server['port']);  die('Check log\n'); 
        }
        if (!empty($redis_server['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix']);
        if (!empty($redis_server['auth'])) {
            if (!$redis->auth($redis_server['auth'])) { 
                $log->error("Could not connect to redis at ".$redis_server['host'].", autentication failed"); die('Check log\n');
            }
        }
    } else {
        $redis = false;
    }
    
    require("Modules/user/user_model.php");
    $user = new User($mysqli,$redis,null);

    include "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $feed_settings);
    
    require_once "Modules/feed/engine/shared_helper.php";
    $helperclass = new SharedHelper();
    
    $session['userid'] = $userid; // required 
    $emailto = $user->get_email($session['userid']);
    $usertimezone = $user->get_timezone($session['userid']);
    $now = DateTime::createFromFormat("U", $time);
    $now->setTimezone(new DateTimeZone($usertimezone));
    $now->setTime(23,59,59);     // at 23:59:59
    $now->modify("last day of previous month");
    $end = $now->format("U");
    $endText= $now->format("YmdHis"); // today
    $now->setTime(0,0);     // at 00:00
    $now->modify("first day of this month");
    $start = $now->format("U");
    $startText= $now->format("YmdHis");

    
    // Get user feeds
    $userfeeds = $feed->get_user_feeds($session['userid']);
    $groups = array();
    foreach($userfeeds as $f){
        $groups[$f['tag']][] = $f['id'];
    }

    echo "Starting export job ($startText-$endText) interval $outinterval for user '$userid'.\n";
    $log->info("Starting export job ($startText-$endText) interval $outinterval for user '$userid'.\n");
     
    // Get feed ids grouped by tags
    foreach($groups as $tag => $ids){
        echo "    Processing '$tag' tag with feeds: " . implode(",",$ids) . "\n";
        $log->info("    Processing '$tag' tag with feeds: " . implode(",",$ids));

        // Write to output stream
        //$filename = $exportpath . "/". $startText."_".$endText."_".$tag."_".implode("_",$ids).".csv";
        $filename = $exportpath . "/". $startText."_".$endText."_".$tag.".csv";
        $fh = @fopen( $filename, 'w' );
        if (!$fh) {
            echo "ERROR: Cant create file '$filename'.\n";
            $log->error("Cant create file '$filename'.");
        } else {
            $exportdata = $feed->csv_export_multi_prepare($ids,$start,$end,$outinterval);
            if (isset($exportdata['success']) && !$exportdata['success']) {
                echo "ERROR: ".$exportdata['message']."\n";
                $log->error($exportdata['message']);
            }
            $firstline=true;
            foreach ($exportdata as $time => $data) {
                $dataline = array();
                foreach ($exportdata['Timestamp'] as $feedid => $name) {
                    if ($firstline) {
                        $dataline[$feedid] = $data[$feedid];
                    } else if (isset($data[$feedid])) {
                        $dataline[$feedid] = number_format((float)$data[$feedid],$csv_decimal_places,$csv_decimal_place_separator,'');
                    } else {
                        $dataline[$feedid] = "";
                    }
                }
                if (!$firstline) {
                    $time = $helperclass->getTimeZoneFormated($time,$usertimezone);
                }
                fputcsv($fh, array($time)+$dataline,$csv_field_separator);
                $firstline = false;
            }
            fclose($fh);
            
            if ($firstline == false) {
                echo "Sending Email to $emailto ...\n";
                $log->info("Sending Email to $emailto ...");
                $emailbody = "Attached is CSV for '". $tag . "' tag.";
                $emailbody .= "\nTime range: ".$startText." to ".$endText;
                require_once "Lib/email.php";
                $email = new Email();
                //$email->from(from);
                $email->to($emailto);
                $email->subject('Emoncms CSV Export '. $tag . " (".$startText."-".$endText.")");
                $email->body($emailbody);
                $email->attach($filename);
                $result = $email->send();
                if (!$result['success']) {
                    echo "Email send returned error. message='" . $result['message'] . "'\n";
                    $log->error("Email send returned error. message='" . $result['message'] . "'");
                } else {
                    $log->info("Email sent to $emailto");
                }
            }
        }
    }
