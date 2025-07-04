<?php
    /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
    */

	// CLI only
	if (php_sapi_name() !== 'cli') {
		echo "This script is for CLI use only.\n";
		die;
	}

    define('EMONCMS_EXEC', 1);

    //Config this:
    $userid = 4;        // user id
    $outinterval = 300; // export interval in seconds
    $time = time();     // generate export for last month of this time
    $exportpath = './exports/2024';     // where to save export backup file (relative to base emoncms path)
//    $sendmails = true;
    $sendmails = false;

    // Dont change bellow this  

    $fp = fopen("/var/lock/export_daily.lock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

    chdir(dirname(__FILE__)."/../");
    require "Lib/EmonLogger.php";
    require "process_settings.php";

    $log = new EmonLogger(__FILE__);
    $log->info("Starting export task script");

    $mysqli = @new mysqli(
        $settings["sql"]["server"],
        $settings["sql"]["username"],
        $settings["sql"]["password"],
        $settings["sql"]["database"],
        $settings["sql"]["port"]
    );

    if ($mysqli->connect_error) { $log->error("Can't connect to database:". $mysqli->connect_error);  die('Check log\n'); }


    if ($settings['redis']['enabled']) {
        # Check Redis PHP modules is loaded
        if (!extension_loaded('redis')) {
            echo "Your PHP installation appears to be missing the <b>Redis</b> extension which is required by Emoncms current settings. <br> See <a href='". $path. "php-info.php'>PHP Info</a> (restricted to local access)";
            die;
        }
        $redis = new Redis();
        $connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);
        if (!$connected) {
            echo "Can't connect to redis at ".$settings['redis']['host'].":".$settings['redis']['port']." , it may be that redis-server is not installed or started see readme for redis installation";
            die;
        }
        if (!empty($settings['redis']['prefix'])) {
            $redis->setOption(Redis::OPT_PREFIX, $settings['redis']['prefix']);
        }
        if (!empty($settings['redis']['auth'])) {
            if (!$redis->auth($settings['redis']['auth'])) {
                echo "Can't connect to redis at ".$settings['redis']['host'].", autentication failed";
                die;
            }
        }
        if (!empty($settings['redis']['dbnum'])) {
            $redis->select($settings['redis']['dbnum']);
        }
    } else {
        $redis = false;
    }

    require("Modules/user/user_model.php");
    $user = new User($mysqli,$redis,null);

    include "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $settings["feed"]);
    
    require_once "Modules/feed/engine/shared_helper.php";
    $helperclass = new SharedHelper();
    
    $session['userid'] = $userid; // required 
    $emailto = $user->get_email($session['userid']);
    $usertimezone = $user->get_timezone($session['userid']);


	for ($month = 1; $month <= 12; $month++) {
		// Define start and end dates for the current month
		$startDate = DateTime::createFromFormat("Y-n-j H:i:s", "2024-$month-1 00:00:00", new DateTimeZone($usertimezone));
		$endDate = clone $startDate;
		$endDate->modify("last day of this month")->setTime(23, 59, 59);

		$start = $startDate->format("U");
		$startText = $startDate->format("YmdHis");

		$end = $endDate->format("U");
		$endText = $endDate->format("YmdHis");
		
		// Get user feeds
		$userfeeds = $feed->get_user_feeds($session['userid']);
		$groups = array();
		foreach($userfeeds as $f){
			$groups[$f['tag']][] = $f['id'];
		}

		consolelog("","Starting export job ($startText-$endText) interval $outinterval for user '$userid'.");
		$log->info("Starting export job ($startText-$endText) interval $outinterval for user '$userid'.");
        //consolelog(""," userfeeds=" . var_export($userfeeds, true));

		// Get feed ids grouped by tags
		foreach($groups as $tag => $ids){
			consolelog("","    Processing '$tag' tag with feeds: " . implode(",",$ids) . "");
			$log->info("    Processing '$tag' tag with feeds: " . implode(",",$ids));

			// Write to output stream
			//$filename = $exportpath . "/". $startText."_".$endText."_".$tag."_".implode("_",$ids).".csv";
			$filename = $exportpath . "/". $startText."_".$endText."_".$tag.".csv";
			if (file_exists($filename) && filesize($filename) > 0) {
				consolelog("WARN","The file '$filename' exists, skiping...");
				$log->warn("The file '$filename' exists, skiping...");
			} else {
				$fh = @fopen( $filename, 'w' );
				if (!$fh) {
					consolelog("ERROR","Cant create file '$filename'.");
					$log->error("Cant create file '$filename'.");
				} else {
					$exportdata = csv_export_multi_prepare($feed,$ids,$start,$end,$outinterval);
					if (isset($exportdata['success']) && !$exportdata['success']) {
						consolelog("ERROR",$exportdata['message']);
						$log->error($exportdata['message']);
					}

					consolelog("","      Got data, now parsing to csv...");
					$firstline=true;
					$headerLine = ["timestamp","linuxtime"];
					$headerLineId = ["timestamp","linuxtime"];
					foreach ($exportdata as $time => $data) {
						$dataline = array();
						foreach ($exportdata['Timestamp'] as $feedid => $name) {
							if ($firstline) {
								$headerLine[] = $name;
								$headerLineId[] = $feedid;
							} 
							if (isset($data[$feedid])) {
								//$dataline[$feedid] = number_format((float)$data[$feedid],$settings["feed"]["csv_decimal_places"],$settings["feed"]["csv_decimal_place_separator"],'');

								$formattedValue = (float)$data[$feedid];
								$dataline[$feedid] = is_float($formattedValue) ? rtrim(rtrim(number_format($formattedValue, $settings["feed"]["csv_decimal_places"], $settings["feed"]["csv_decimal_place_separator"], ''), '0'), $settings["feed"]["csv_decimal_place_separator"]) : $formattedValue;
							} else {
								$dataline[$feedid] = "";
							}
						}
						if ($firstline) {
							fputcsv($fh, $headerLine, $settings["feed"]["csv_field_separator"]);
							fputcsv($fh, $headerLineId, $settings["feed"]["csv_field_separator"]);
						}
						if ($time == "Timestamp") continue;

						$timef = $helperclass->getTimeZoneFormated($time,$usertimezone);
						fputcsv($fh, array($timef, $time)+$dataline,$settings["feed"]["csv_field_separator"]);
						$firstline = false;
					}
					fclose($fh);

					// Free memory after each file generation
					unset($exportdata);
					gc_collect_cycles();
				
					if ($firstline == false && $sendmails) {
						consolelog("","Sending Email to $emailto ...");
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
							consolelog("","Email send returned error. message='" . $result['message'] . "'");
							$log->error("Email send returned error. message='" . $result['message'] . "'");
						} else {
							$log->info("Email sent to $emailto");
						}
					}
				}
			}
		}

		// Free memory after each month
		unset($userfeeds, $groups);
		gc_collect_cycles();
	}

    
    function consolelog($type, $message) {
        $now = microtime(true);
        $micro = sprintf("%03d", ($now - floor($now)) * 1000);
        $now = DateTime::createFromFormat('U', floor($now)); // Only use UTC for logs
        $now = $now->format("Y-m-d H:i:s").".$micro";
        echo $now."|$type|".$message."\n";
    }


    function csv_export_multi_prepare($feedmodel,$feedids,$start,$end,$outinterval)
    {
        if ($end<=$start) return array('success'=>false, 'message'=>"Request end time before start time");
        $exportdata = array();
        for ($i=0; $i<count($feedids); $i++) {
            $feedid = $feedids[$i];
            $feedname = $feedmodel->get_field($feedid,'name');
			$feedunit = $feedmodel->get_field($feedid,'unit');
            if (isset($feedname['success']) && !$feedname['success']) return $feedname;
            $feeddata = $feedmodel->get_data($feedid,$start*1000,$end*1000,$outinterval);
            if (isset($feeddata['success']) && !$feeddata['success']) return $feeddata;

            if (isset($exportdata['Timestamp'])) {
               $exportdata['Timestamp'] = $exportdata['Timestamp'] + array($feedid => $feedname . "($feedunit)");
            } else {
               $exportdata['Timestamp'] = array($feedid => $feedname . "($feedunit)");
            }
			if (!is_array($feeddata)){
				 consolelog("Warning","\$feeddata not countable. Type=" . gettype($feeddata) . " value=" . var_export($feeddata, true));
			} else {
				for ($d=0;$d<count($feeddata); $d++) {
					if (isset($feeddata[$d]['0'])) {
						$time = floor($feeddata[$d]['0']/1000);
						$value = $feeddata[$d]['1'];
						if (isset($exportdata[$time])) {
						   $exportdata[$time] = $exportdata[$time] + array($feedid => $value);
						} else {
							$exportdata[$time] = array($feedid => $value);
						}
					}
				}
			}
			// Free memory after each file generation
			unset($feeddata);
			gc_collect_cycles();
        }
        ksort($exportdata); // Sort timestamps
        return $exportdata;
    }

