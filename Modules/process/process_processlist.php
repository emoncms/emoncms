<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.
 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project: http://openenergymonitor.org
 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

// This is core Process list module
class Process_ProcessList
{
    private $mysqli;
    private $input;
    private $feed;
    private $timezone;

    private $proc_initialvalue;  // save the input value at beginning of the processes list execution
    private $proc_skip_next;     // skip execution of next process in process list
    private $proc_goto;          // goto step in process list

    private $log;
    private $mqtt = false;
    
    // Module required constructor, receives parent as reference
    public function __construct(&$parent)
    {
        $this->mysqli = &$parent->mysqli;
        $this->input = &$parent->input;
        $this->feed = &$parent->feed;
        $this->timezone = &$parent->timezone;
        $this->proc_initialvalue = &$parent->proc_initialvalue;
        $this->proc_skip_next = &$parent->proc_skip_next;
        $this->proc_goto = &$parent->proc_goto;

        $this->log = new EmonLogger(__FILE__);

        // Load MQTT if enabled
        // Publish value to MQTT topic, see: http://openenergymonitor.org/emon/node/5943
        global $mqtt_enabled, $mqtt_server, $log;
        
        if ($mqtt_enabled && !$this->mqtt)
        {
            // @see: https://github.com/emoncms/emoncms/blob/master/docs/RaspberryPi/MQTT.md
            if (class_exists("Mosquitto\Client")) {
                /*
                    new Mosquitto\Client($id,$cleanSession)
                    $id (string) – The client ID. If omitted or null, one will be generated at random.
                    $cleanSession (boolean) – Set to true to instruct the broker to clean all messages and subscriptions on disconnect. Must be true if the $id parameter is null.
                 */ 
                $mqtt_client = new Mosquitto\Client(null, true);
                
                $mqtt_client->onDisconnect(function($responseCode) use ($log) {
                    if ($responseCode > 0) $log->info('unexpected disconnect from mqtt server');
                });

                $this->mqtt = $mqtt_client;
            }
        }
    }
    
    // List of core process module with hard coded integer keys, for backward compatibility only
    // Not used on other modules, use process_list() function instead
    public function core_process_list_map()
    {
        $map = array(
            "1"=>"log_to_feed",
            "2"=>"scale",
            "3"=>"offset",
            "4"=>"power_to_kwh",
            "5"=>"power_to_kwhd",
            "6"=>"times_input",
            "7"=>"input_ontime",
            "8"=>"whinc_to_kwhd",
            "9"=>"kwh_to_kwhd_old",
            "10"=>"update_feed_data",
            "11"=>"add_input",
            "12"=>"divide_input",
            "13"=>"phaseshift",
            "14"=>"accumulator",
            "15"=>"ratechange",
            "16"=>"histogram",
            "17"=>"average",
            "18"=>"heat_flux",
            "19"=>"power_acc_to_kwhd",
            "20"=>"pulse_diff",
            "21"=>"kwh_to_power",
            "22"=>"subtract_input",
            "23"=>"kwh_to_kwhd",
            "24"=>"allowpositive",
            "25"=>"allownegative",
            "26"=>"signed2unsigned",
            "27"=>"max_value",
            "28"=>"min_value",
            "29"=>"add_feed",
            "30"=>"sub_feed",
            "31"=>"multiply_by_feed",
            "32"=>"divide_by_feed",
            "33"=>"reset2zero",
            "34"=>"wh_accumulator",
            "35"=>"publish_to_mqtt",
            "36"=>"reset2null",
            "37"=>"reset2original",
            "42"=>"if_zero_skip",
            "43"=>"if_not_zero_skip",
            "44"=>"if_null_skip",
            "45"=>"if_not_null_skip",
            "46"=>"if_gt_skip",
            "47"=>"if_gt_equal_skip",
            "48"=>"if_lt_skip",
            "49"=>"if_lt_equal_skip",
            "50"=>"if_equal_skip",
            "51"=>"if_not_equal_skip",
            "52"=>"goto_process",
            "53"=>"source_feed_data_time",
            "55"=>"add_source_feed",
            "56"=>"sub_source_feed",
            "57"=>"multiply_by_source_feed",
            "58"=>"divide_by_source_feed",
            "59"=>"reciprocal_by_source_feed"
        );
                                
        return $map;
    }


    // \/ Below are functions of this module processlist

    public function scale($arg, $time, $value)
    {
        return $value * $arg;
    }

    public function divide($arg, $time, $value)
    {
        if ($arg!=0) {
            return $value / $arg;
        } else {
            return null;
        }
    }

    public function offset($arg, $time, $value)
    {
        return $value + $arg;
    }

    public function allowpositive($arg, $time, $value)
    {
        if ($value<0) $value = 0;
        return $value;
    }

    public function allownegative($arg, $time, $value)
    {
        if ($value>0) $value = 0;
        return $value;
    }
    
     public function max_value_allowed($arg, $time, $value)
    {
        if ($value>$arg) $value = $arg;
        return $value;
    }
    
    public function min_value_allowed($arg, $time, $value)
    {
        if ($value<$arg) $value = $arg;
        return $value;
    }

    public function reset2zero($arg, $time, $value)
    {
         $value = 0;
         return $value;
    }

    public function reset2original($arg, $time, $value)
    {
         return $this->proc_initialvalue;
    }

    public function reset2null($arg, $time, $value)
    {
         return null;
    }

    public function signed2unsigned($arg, $time, $value)
    {
        if($value < 0) $value = $value + 65536;
        return $value;
    }

    public function log_to_feed($id, $time, $value)
    {
        $this->feed->insert_data($id, $time, $time, $value);

        return $value;
    }

    //---------------------------------------------------------------------------------------
    // Times value by current value of another input
    //---------------------------------------------------------------------------------------
    public function times_input($id, $time, $value)
    {
        return $value * $this->input->get_last_value($id);
    }

    public function divide_input($id, $time, $value)
    {
        $lastval = $this->input->get_last_value($id);
        if($lastval > 0){
            return $value / $lastval;
        } else {
            return null; // should this be null for a divide by zero?
        }
    }
    
    public function update_feed_data($id, $time, $value)
    {
        $time = $this->getstartday($time);

        $feedname = "feed_".trim($id)."";
        $result = $this->mysqli->query("SELECT time FROM $feedname WHERE `time` = '$time'");
        $row = $result->fetch_array();

        if (!$row)
        {
            $this->mysqli->query("INSERT INTO $feedname (time,data) VALUES ('$time','$value')");
        }
        else
        {
            $this->mysqli->query("UPDATE $feedname SET data = '$value' WHERE `time` = '$time'");
        }
        return $value;
    } 

    public function add_input($id, $time, $value)
    {
        return $value + $this->input->get_last_value($id);
    }

    public function subtract_input($id, $time, $value)
    {
        return $value - $this->input->get_last_value($id);
    }

    //---------------------------------------------------------------------------------------
    // Power to kwh
    //---------------------------------------------------------------------------------------
    public function power_to_kwh($feedid, $time_now, $value)
    {
        $new_kwh = 0;

        // Get last value
        $last = $this->feed->get_timevalue($feedid);

        if (!isset($last['value'])) $last['value'] = 0;
        $last_kwh = $last['value']*1;
        $last_time = $last['time']*1;

        // only update if last datapoint was less than 2 hour old
        // this is to reduce the effect of monitor down time on creating
        // often large kwh readings.
        $time_elapsed = ($time_now - $last_time);   
        if ($time_elapsed>0 && $time_elapsed<7200) { // 2hrs
            // kWh calculation
            $kwh_inc = ($time_elapsed * $value) / 3600000.0;
            $new_kwh = $last_kwh + $kwh_inc;
            $this->log->info("power_to_kwh() feedid=$feedid last_kwh=$last_kwh kwh_inc=$kwh_inc new_kwh=$new_kwh last_time=$last_time time_now=$time_now");
        } else {
            // in the event that redis is flushed the last time will
            // likely be > 7200s ago and so kwh inc is not calculated
            // rather than enter 0 we enter the last value
            $new_kwh = $last_kwh;
        }

        $padding_mode = "join";
        $this->feed->insert_data($feedid, $time_now, $time_now, $new_kwh, $padding_mode);
        
        return $value;
    }

    public function power_to_kwhd($feedid, $time_now, $value)
    {
        $new_kwh = 0;

        // Get last value
        $last = $this->feed->get_timevalue($feedid);

        if (!isset($last['value'])) $last['value'] = 0;
        if (!isset($last['time'])) $last['time'] = $time_now;
        $last_kwh = $last['value']*1;
        $last_time = $last['time']*1;

        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);    

        $time_elapsed = ($time_now - $last_time);   
        if ($time_elapsed>0 && $time_elapsed<7200) { // 2hrs
            // kWh calculation
            $kwh_inc = ($time_elapsed * $value) / 3600000.0;
        } else {
            // in the event that redis is flushed the last time will
            // likely be > 7200s ago and so kwh inc is not calculated
            // rather than enter 0 we dont increase it
            $kwh_inc = 0;
        }

        if($last_slot == $current_slot) {
            $new_kwh = $last_kwh + $kwh_inc;
        } else {
            # We are working in a new slot (new day) so don't increment it with the data from yesterday
            $new_kwh = $kwh_inc;
        }
        $this->log->info("power_to_kwhd() feedid=$feedid last_kwh=$last_kwh kwh_inc=$kwh_inc new_kwh=$new_kwh last_slot=$last_slot current_slot=$current_slot");
        $this->feed->update_data($feedid, $time_now, $current_slot, $new_kwh);

        return $value;
    }

    public function kwh_to_kwhd($feedid, $time_now, $value)
    {
        global $redis;
        if (!$redis) return $value; // return if redis is not available
        
        $currentkwhd = $this->feed->get_timevalue($feedid);
        $last_time = $currentkwhd['time'];
        
        //$current_slot = floor($time_now / 86400) * 86400;
        //$last_slot = floor($last_time / 86400) * 86400;
        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);

        if ($redis->exists("process:kwhtokwhd:$feedid")) {
            $lastkwhvalue = $redis->hmget("process:kwhtokwhd:$feedid",array('time','value'));
            $kwhinc = $value - $lastkwhvalue['value'];

            // kwh values should always be increasing so ignore ones that are less
            // assume they are errors
            if ($kwhinc<0) { $kwhinc = 0; $value = $lastkwhvalue['value']; }
            
            if($last_slot == $current_slot) {
                $new_kwh = $currentkwhd['value'] + $kwhinc;
            } else {
                $new_kwh = $kwhinc;
            }

            $this->feed->update_data($feedid, $time_now, $current_slot, $new_kwh);
        }
        
        $redis->hMset("process:kwhtokwhd:$feedid", array('time' => $time_now, 'value' => $value));

        return $value;
    }

    //---------------------------------------------------------------------------------------
    // input on-time counter
    //---------------------------------------------------------------------------------------
    public function input_ontime($feedid, $time_now, $value)
    {
        // Get last value
        $last = $this->feed->get_timevalue($feedid);
        $last_time = $last['time'];
        
        //$current_slot = floor($time_now / 86400) * 86400;
        //$last_slot = floor($last_time / 86400) * 86400;
        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);
        
        if (!isset($last['value'])) $last['value'] = 0;
        $ontime = $last['value'];
        $time_elapsed = 0;
        
        if ($value > 0 && (($time_now-$last_time)<7200))
        {
            $time_elapsed = $time_now - $last_time;
            $ontime += $time_elapsed;
        }
        
        if($last_slot != $current_slot) $ontime = $time_elapsed;

        $this->feed->update_data($feedid, $time_now, $current_slot, $ontime);

        return $value;
    }

    //--------------------------------------------------------------------------------
    // Display the rate of change for the current and last entry
    //--------------------------------------------------------------------------------
    public function ratechange($feedid, $time, $value)
    {
        global $redis;
        if (!$redis) return $value; // return if redis is not available
        
        if ($redis->exists("process:ratechange:$feedid")) {
            $lastvalue = $redis->hmget("process:ratechange:$feedid",array('time','value'));
            $ratechange = $value - $lastvalue['value'];
            $this->feed->insert_data($feedid, $time, $time, $ratechange);
        }
        $redis->hMset("process:ratechange:$feedid", array('time' => $time, 'value' => $value));

        // return $ratechange;
    }

    public function save_to_input($inputid, $time, $value)
    {
        $this->input->set_timevalue($inputid, $time, $value);
        return $value;
    }

    public function whinc_to_kwhd($feedid, $time_now, $value)
    {
        $last = $this->feed->get_timevalue($feedid);
        $last_time = $last['time'];
        
        //$current_slot = floor($time_now / 86400) * 86400;
        //$last_slot = floor($last_time / 86400) * 86400;
        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);
               
        $new_kwh = $last['value'] + ($value / 1000.0);
        if ($last_slot != $current_slot) $new_kwh = ($value / 1000.0);
        
        $this->feed->update_data($feedid, $time_now, $current_slot, $new_kwh);

        return $value;
    }

    public function accumulator($feedid, $time, $value)
    {
        $last = $this->feed->get_timevalue($feedid);
        $value = $last['value'] + $value;
        $padding_mode = "join";
        $this->feed->insert_data($feedid, $time, $time, $value, $padding_mode);
        return $value;
    }
    /*
    public function accumulator_daily($feedid, $time_now, $value)
    {
        $last = $this->feed->get_timevalue($feedid);
        $value = $last['value'] + $value;
        $feedtime = $this->getstartday($time_now);
        $this->feed->update_data($feedid, $time_now, $feedtime, $value);
        return $value;
    }*/

    //---------------------------------------------------------------------------------
    // This method converts power to energy vs power (Histogram)
    //---------------------------------------------------------------------------------
    public function histogram($feedid, $time_now, $value)
    {

        ///return $value;

        $feedname = "feed_" . trim($feedid) . "";
        $new_kwh = 0;
        // Allocate power values into pots of varying sizes
        if ($value < 500) {
            $pot = 50;

        } elseif ($value < 2000) {
            $pot = 100;

        } else {
            $pot = 500;
        }

        $new_value = round($value / $pot, 0, PHP_ROUND_HALF_UP) * $pot;

        $time = $this->getstartday($time_now);

        // Get the last time
        $lastvalue = $this->feed->get_timevalue($feedid);
        $last_time = $lastvalue['time'];

        // kWh calculation
        $time_elapsed = ($time_now - $last_time);   
        if ($time_elapsed>0 && $time_elapsed<7200) { // 2hrs
            $kwh_inc = ($time_elapsed * $value) / 3600000;
        } else {
            $kwh_inc = 0;
        }

        // Get last value
        $result = $this->mysqli->query("SELECT * FROM $feedname WHERE time = '$time' AND data2 = '$new_value'");

        if (!$result) return $value;

        $last_row = $result->fetch_array();

        if (!$last_row)
        {
            $result = $this->mysqli->query("INSERT INTO $feedname (time,data,data2) VALUES ('$time','0.0','$new_value')");

            $this->feed->set_timevalue($feedid, $new_value, $time_now);
            $new_kwh = $kwh_inc;
        }
        else
        {
            $last_kwh = $last_row['data'];
            $new_kwh = $last_kwh + $kwh_inc;
        }

        // update kwhd feed
        $this->mysqli->query("UPDATE $feedname SET data = '$new_kwh' WHERE time = '$time' AND data2 = '$new_value'");

        $this->feed->set_timevalue($feedid, $new_value, $time_now);
        return $value;
    }

    public function pulse_diff($feedid,$time_now,$value)
    {
        $value = $this->signed2unsigned(false,false, $value);

        if($value>0)
        {
            $pulse_diff = 0;
            $last = $this->feed->get_timevalue($feedid);
            if ($last['time']) {
                // Need to handle resets of the pulse value (and negative 2**15?)
                if ($value >= $last['value']) {
                    $pulse_diff = $value - $last['value'];
                } else {
                    $pulse_diff = $value;
                }
            }

            // Save to allow next difference calc.
            $this->feed->insert_data($feedid,$time_now,$time_now,$value);

            return $pulse_diff;
        }
    }

    public function kwh_to_power($feedid,$time,$value)
    {
        global $redis;
        if (!$redis) return $value; // return if redis is not available
        
        $power = 0;
        if ($redis->exists("process:kwhtopower:$feedid")) {
            $lastvalue = $redis->hmget("process:kwhtopower:$feedid",array('time','value'));
            $kwhinc = $value - $lastvalue['value'];
            $joules = $kwhinc * 3600000.0;
            $timeelapsed = ($time - $lastvalue['time']);
            if ($timeelapsed>0) {     //This only avoids a crash, it's not ideal to return "power = 0" to the next process.
                $power = $joules / $timeelapsed;
                $this->feed->insert_data($feedid, $time, $time, $power);
            } // should have else { log error message }
        }
        $redis->hMset("process:kwhtopower:$feedid", array('time' => $time, 'value' => $value));

        return $power;
    }

    public function max_value($feedid, $time_now, $value)
    {
        // Get last values
        $last = $this->feed->get_timevalue($feedid);
        $last_val = $last['value'];
        $last_time = $last['time'];
        $feedtime = $this->getstartday($time_now);
        $time_check = $this->getstartday($last_time);

        // Runs on setup and midnight to reset current value - (otherwise db sets 0 as new max)
        if ($time_check != $feedtime) {
            $this->feed->insert_data($feedid, $time_now, $feedtime, $value);
        } else {
            if ($value > $last_val) $this->feed->update_data($feedid, $time_now, $feedtime, $value);
        }
        return $value;
    }

    public function min_value($feedid, $time_now, $value)
    {
        // Get last values
        $last = $this->feed->get_timevalue($feedid);
        $last_val = $last['value'];
        $last_time = $last['time'];
        $feedtime = $this->getstartday($time_now);
        $time_check = $this->getstartday($last_time);

        // Runs on setup and midnight to reset current value - (otherwise db sets 0 as new min)
        if ($time_check != $feedtime) {
            $this->feed->insert_data($feedid, $time_now, $feedtime, $value);
        } else {
            if ($value < $last_val) $this->feed->update_data($feedid, $time_now, $feedtime, $value);
        }
        return $value;

    }
    
    public function add_feed($feedid, $time, $value)
    {
        $last = $this->feed->get_timevalue($feedid);
        $value = $last['value'] + $value;
        return $value;
    }

    public function sub_feed($feedid, $time, $value)
    {
        $last  = $this->feed->get_timevalue($feedid);
        $myvar = $last['value'] *1;
        return $value - $myvar;
    }
    
    public function multiply_by_feed($feedid, $time, $value)
    {
        $last = $this->feed->get_timevalue($feedid);
        $value = $last['value'] * $value;
        return $value;
    }

   public function divide_by_feed($feedid, $time, $value)
    {
        $last  = $this->feed->get_timevalue($feedid);
        $myvar = $last['value'] *1;
        
        if ($myvar!=0) {
            return $value / $myvar;
        } else {
            return null;
        }
    }
    
    public function wh_accumulator($feedid, $time, $value)
    {
        $max_power = 25000;
        $totalwh = $value;
        
        global $redis;
        if (!$redis) return $value; // return if redis is not available

        if ($redis->exists("process:whaccumulator:$feedid")) {
            $last_input = $redis->hmget("process:whaccumulator:$feedid",array('time','value'));
    
            $last_feed  = $this->feed->get_timevalue($feedid);
            $totalwh = $last_feed['value'];
            
            $time_diff = $time - $last_feed['time'];
            $val_diff = $value - $last_input['value'];
            
            if ($time_diff>0) {
                $power = ($val_diff * 3600) / $time_diff;
            
                if ($val_diff>0 && $power<$max_power) $totalwh += $val_diff;
            }
             
            $padding_mode = "join";
            $this->feed->insert_data($feedid, $time, $time, $totalwh, $padding_mode);
            
        }
        $redis->hMset("process:whaccumulator:$feedid", array('time' => $time, 'value' => $value));

        return $totalwh;
    }
    
    public function publish_to_mqtt($topic, $time, $value)
    {
        global $redis;
        // saves value to redis
        // phpmqtt_input.php is then used to publish the values
        if ($this->mqtt){
            $data = array('topic'=>$topic,'value'=>$value,'timestamp'=>$time);
            $redis->hset("publish_to_mqtt",$topic,$value);
            // $redis->rpush('mqtt-pub-queue', json_encode($data));
        }
        return $value;
    }
    

    // Conditional process list flow
    public function if_zero_skip($noarg, $time, $value) {
        if ($value == 0)
            $this->proc_skip_next = true;
        return $value;
    }
    public function if_not_zero_skip($noarg, $time, $value) {
        if ($value != 0)
            $this->proc_skip_next = true;
        return $value;
    }
    public function if_null_skip($noarg, $time, $value) {
        if ($value === NULL)
            $this->proc_skip_next = true;
        return $value;
    }
    public function if_not_null_skip($noarg, $time, $value) {
        if (!($value === NULL))
            $this->proc_skip_next = true;
        return $value;
    }

    public function if_gt_skip($arg, $time, $value) {
        if ($value > $arg)
            $this->proc_skip_next = true;
        return $value;
    }
    public function if_gt_equal_skip($arg, $time, $value) {
        if ($value >= $arg)
            $this->proc_skip_next = true;
        return $value;
    }
    public function if_lt_skip($arg, $time, $value) {
        if ($value < $arg)
            $this->proc_skip_next = true;
        return $value;
    }
    public function if_lt_equal_skip($arg, $time, $value) {
        if ($value <= $arg)
            $this->proc_skip_next = true;
        return $value;
    }
    
    public function if_equal_skip($arg, $time, $value) {
        if ($value == $arg)
            $this->proc_skip_next = true;
        return $value;
    }
    public function if_not_equal_skip($arg, $time, $value) {
        if ($value != $arg)
            $this->proc_skip_next = true;
        return $value;
    }
    
    public function goto_process($proc_no, $time, $value){
        $this->proc_goto = $proc_no - 2;
        return $value;
    }

    public function error_found($arg, $time, $value){
        $this->proc_goto = PHP_INT_MAX;
        return $value;
    }


    // Used as Virtual feed source of data (read from other feeds). Gets feed data for the specified time range in $options variable, 
    // Set data_sampling to false in settings.php to allow precise average feed data calculation. It will be 10x slower!
    public function source_feed_data_time($feedid, $time, $value, $options)
    {
        global $data_sampling;
        $starttime = microtime(true);
        $value = null;
        if (isset($options['start']) && isset($options['end'])) {
            $start = $options['start']; // if option array has start and end time, use it
            $end = $options['end'];
            if (isset($options['interval'])) {
                $interval=$options['interval'];
            } else {
                $interval = ($end - $start);
            }
            if ($data_sampling) {
                // To speed up reading, but will miss some average data
                $meta=$this->feed->get_meta($feedid);
                if (isset($meta->interval) && (int)$meta->interval > 1) {
                    $interval = (int)$meta->interval; // set engine interval 
                    $end = $start; 
                    $start = $end - ($interval * 2); // search past x interval secs
                } else if ($interval > 5000) { //83m interval is high a table scan will happen in engine
                    $end = $start; 
                    $start = $end - 60; // force search past 1m 
                    $interval = 1;
                } else if ($interval > 300) { // 5m
                    $end = $start; 
                    $start = $end - 20; //  search past 20s
                    $interval = 1;
                } else if ($interval < 5) { // 5s
                    $end = $start; 
                    $start = $end - 10; //  search past 10s
                    $interval = 1;
                }
            }
            $start*=1000; // convert to milliseconds for engine
            $end*=1000;
            $data = $this->feed->get_data($feedid,$start,$end,$interval,1,1); // get data from feed engine with skipmissing and limit interval options
        } else {
            
            $data = $this->feed->get_timevalue($feedid); // get last data from feed engine 
            $data = array(array($data['time'], $data['value'])); // convert last data
            $end = $time; 
            $start = $end;
            $interval = ($end - $start);
        }

        //$this->log->info("source_feed_data_time() ". ($data_sampling ? "SAMPLING ":"") ."feedid=$feedid start=$start end=$end len=".(($end - $start))." int=$interval - BEFORE GETDATA");

        if ($data) {
            $cnt=count($data);
            if ($cnt>0) {
                $p = 0;
                $sum = 0;
                while($p<$cnt) {
                    if (isset($data[$p][1]) && is_numeric($data[$p][1])) {
                        $sum += $data[$p][1];
                    }
                    $p++;
                }
                $value = ($sum / $cnt); // return average value
            }
            // logging 
            $endtime = microtime(true);
            $timediff = $endtime - $starttime;
            $this->log->info("source_feed_data_time() ". ($data_sampling ? "SAMPLING ":"") ."feedid=$feedid start=".($start/1000)." end=".($end/1000)." len=".(($end - $start)/1000)." int=$interval cnt=$cnt value=$value took=$timediff ");
        } else {
            $this->log->info("source_feed_data_time() NODATA feedid=$feedid start=".($start/1000)." end=".($end/1000)." len=".(($end - $start)/1000)." int=$interval value=$value ");
        }
        return $value;
    }

    public function add_source_feed($feedid, $time, $value, $options)
    {
        $last = $this->source_feed_data_time($feedid, $time, $value, $options);
        $value = $last + $value;
        return $value;
    }
    
    public function sub_source_feed($feedid, $time, $value, $options)
    {
        $last = $this->source_feed_data_time($feedid, $time, $value, $options);
        $myvar = $last*1;
        return $value - $myvar;
    }
    
    public function multiply_by_source_feed($feedid, $time, $value, $options)
    {
        $last = $this->source_feed_data_time($feedid, $time, $value, $options);
        $value = $last * $value;
        return $value;
    }
    
    public function divide_by_source_feed($feedid, $time, $value, $options)
    {
        $last = $this->source_feed_data_time($feedid, $time, $value, $options);
        $myvar = $last*1;

        if ($myvar!=0) {
            return $value / $myvar;
        } else {
            return null;
        }
    }
    
    public function reciprocal_by_source_feed($feedid, $time, $value, $options)
    {
        $last = $this->source_feed_data_time($feedid, $time, $value, $options);
        $myvar = $last*1;

        if ($myvar!=0) {
            return 1 / $myvar;
        } else {
            return null;
        }
    }


    //CHAVEIRO TBD: virtual feed daily - not required on sql engine but needs tests for other engines
    public function get_feed_data_day($id, $time, $value, $options)
    {
        if ($options['start'] && $options['end']) {
            $time = $this->getstartday($options['start']);
        } else {
            $time = $this->getstartday($time);
        }

        $feedname = "feed_".trim($id)."";
        $result = $this->mysqli->query("SELECT data FROM $feedname WHERE `time` = '$time'");
        if ($result != null ) $row = $result->fetch_array();
        if (isset($row))
        {
            return $row['data'];
        }
        else
        {
            return null;
        }
    }

    
    
    // No longer used
    public function average($feedid, $time_now, $value) { return $value; } // needs re-implementing    
    public function phaseshift($id, $time, $value) { return $value; }
    public function kwh_to_kwhd_old($feedid, $time_now, $value) { return $value; }
    public function power_acc_to_kwhd($feedid,$time_now,$value) { return $value; } // Process can now be achieved with allow positive process before power to kwhd

    //------------------------------------------------------------------------------------------------------
    // Calculate the energy used to heat up water based on the rate of change for the current and a previous temperature reading
    // See http://harizanov.com/2012/05/measuring-the-solar-yield/ for more info on how to use it
    //------------------------------------------------------------------------------------------------------
    public function heat_flux($feedid,$time_now,$value) { return $value; } // Removed to be reintroduced as a post-processing based visualisation calculated on the fly.
    
    // Get the start of the day
    public function getstartday($time_now)
    {
        $now = DateTime::createFromFormat("U", (int)$time_now);
        $now->setTimezone(new DateTimeZone($this->timezone));
        $now->setTime(0,0);    // Today at 00:00
        return $now->format("U");
    }

}
