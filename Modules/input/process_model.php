<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Process
{
    private $mysqli;
    private $input;
    private $feed;
    private $log;
    private $mqtt = false;
    
    private $timezone = 'UTC';

    public function __construct($mysqli,$input,$feed,$timezone)
    {
        $this->mysqli = $mysqli;
        $this->input = $input;
        $this->feed = $feed;
        $this->log = new EmonLogger(__FILE__);
        if (!($timezone === NULL)) $this->timezone = $timezone;
            
        // Load MQTT if enabled
        // Publish value to MQTT topic, see: http://openenergymonitor.org/emon/node/5943
        global $mqtt_enabled, $mqtt;
        if (isset($mqtt_enabled) && $mqtt_enabled == true & $mqtt == false)
        {
            require("Lib/phpMQTT.php");
            $mqtt = new phpMQTT("127.0.0.1", 1883, "Emoncms Publisher");
            $this->mqtt = $mqtt;
        }
    }

    public function get_process_list()
    {
        
        $list = array();
        
        // Note on engine selection
        
        // The engines listed against each process are the recommended engines for each process - and is only used in the input and node config GUI dropdown selectors
        // By using the create feed api and add input process its possible to create any feed type and add any process to it - this needs to be improved so that only 
        // feeds capable of using a particular processor can be used. 

        // description | Arg type | function | No. of datafields if creating feed | Datatype | Engine

        $list[1] = array(_("Log to feed"),ProcessArg::FEEDID,"log_to_feed",1,DataType::REALTIME,"Main",array(Engine::PHPFIWA,Engine::PHPFINA,Engine::PHPTIMESERIES));                  
        $list[2] = array(_("x"),ProcessArg::VALUE,"scale",0,DataType::UNDEFINED,"Calibration");                           
        $list[3] = array(_("+"),ProcessArg::VALUE,"offset",0,DataType::UNDEFINED,"Calibration");                          
        $list[4] = array(_("Power to kWh"),ProcessArg::FEEDID,"power_to_kwh",1,DataType::REALTIME,"Power",array(Engine::PHPFINA,Engine::PHPTIMESERIES));               
        $list[5] = array(_("Power to kWh/d"),ProcessArg::FEEDID,"power_to_kwhd",1,DataType::DAILY,"Power",array(Engine::PHPTIMESERIES));               
        $list[6] = array(_("x input"),ProcessArg::INPUTID,"times_input",0,DataType::UNDEFINED,"Input");                   
        $list[7] = array(_("Input on-time"),ProcessArg::FEEDID,"input_ontime",1,DataType::DAILY,"Input",array(Engine::PHPTIMESERIES));                 
        $list[8] = array(_("Wh increments to kWh/d"),ProcessArg::FEEDID,"kwhinc_to_kwhd",1,DataType::DAILY,"Power",array(Engine::PHPTIMESERIES));      
        $list[9] = array(_("kWh to kWh/d (OLD)"),ProcessArg::FEEDID,"kwh_to_kwhd_old",1,DataType::DAILY,"Deleted",array(Engine::PHPTIMESERIES));       // need to remove
        $list[10] = array(_("update feed @time"),ProcessArg::FEEDID,"update_feed_data",1,DataType::DAILY,"Input",array(Engine::MYSQL));           
        $list[11] = array(_("+ input"),ProcessArg::INPUTID,"add_input",0,DataType::UNDEFINED,"Input");                    
        $list[12] = array(_("/ input"),ProcessArg::INPUTID,"divide_input",0,DataType::UNDEFINED,"Input");                 
        $list[13] = array(_("Phaseshift"),ProcessArg::VALUE,"phaseshift",0,DataType::UNDEFINED,"Deleted");                             // need to remove
        $list[14] = array(_("Accumulator"),ProcessArg::FEEDID,"accumulator",1,DataType::REALTIME,"Misc",array(Engine::PHPFINA,Engine::PHPTIMESERIES));                 
        $list[15] = array(_("Rate of change"),ProcessArg::FEEDID,"ratechange",1,DataType::REALTIME,"Misc",array(Engine::PHPFIWA,Engine::PHPFINA,Engine::PHPTIMESERIES));               
        $list[16] = array(_("Histogram"),ProcessArg::FEEDID,"histogram",2,DataType::HISTOGRAM,"Power",array(Engine::MYSQL));                   
        $list[17] = array(_("Daily Average"),ProcessArg::FEEDID,"average",2,DataType::HISTOGRAM,"Deleted",array(Engine::PHPTIMESERIES));               // need to remove
        
        // to be reintroduced in post-processing
        $list[18] = array(_("Heat flux"),ProcessArg::FEEDID,"heat_flux",1,DataType::REALTIME,"Deleted",array(Engine::PHPFIWA,Engine::PHPFINA,Engine::PHPTIMESERIES));                  
        
        // need to remove - result can be achieved with allow_positive & power_to_kwhd
        $list[19] = array(_("Power gained to kWh/d"),ProcessArg::FEEDID,"power_acc_to_kwhd",1,DataType::DAILY,"Deleted",array(Engine::PHPTIMESERIES));              
        
        // - look into implementation that doesnt need to store the ref feed
        $list[20] = array(_("Total pulse count to pulse increment"),ProcessArg::FEEDID,"pulse_diff",1,DataType::REALTIME,"Pulse",array(Engine::PHPFINA,Engine::PHPTIMESERIES));
        
        // fixed works now with redis - look into state implementation without feed
        $list[21] = array(_("kWh to Power"),ProcessArg::FEEDID,"kwh_to_power",1,DataType::REALTIME,"Power",array(Engine::PHPFIWA,Engine::PHPFINA,Engine::PHPTIMESERIES));
        
        $list[22] = array(_("- input"),ProcessArg::INPUTID,"subtract_input",0,DataType::UNDEFINED,"Input");               
        $list[23] = array(_("kWh to kWh/d"),ProcessArg::FEEDID,"kwh_to_kwhd",2,DataType::DAILY,"Power",array(Engine::PHPTIMESERIES));                  // fixed works now with redis
        $list[24] = array(_("Allow positive"),ProcessArg::NONE,"allowpositive",0,DataType::UNDEFINED,"Limits");           
        $list[25] = array(_("Allow negative"),ProcessArg::NONE,"allownegative",0,DataType::UNDEFINED,"Limits");           
        $list[26] = array(_("Signed to unsigned"),ProcessArg::NONE,"signed2unsigned",0,DataType::UNDEFINED,"Misc");       
        $list[27] = array(_("Max value"),ProcessArg::FEEDID,"max_value",1,DataType::DAILY,"Misc",array(Engine::PHPTIMESERIES));                        
        $list[28] = array(_("Min value"),ProcessArg::FEEDID,"min_value",1,DataType::DAILY,"Misc",array(Engine::PHPTIMESERIES));  
                              
        $list[29] = array(_(" + feed"),ProcessArg::FEEDID,"add_feed",0,DataType::UNDEFINED,"Feed");        // Klaus 24.2.2014
        $list[30] = array(_(" - feed"),ProcessArg::FEEDID,"sub_feed",0,DataType::UNDEFINED,"Feed");        // Klaus 24.2.
        $list[31] = array(_(" * feed"),ProcessArg::FEEDID,"multiply_by_feed",0,DataType::UNDEFINED,"Feed");
        $list[32] = array(_(" / feed"),ProcessArg::FEEDID,"divide_by_feed",0,DataType::UNDEFINED,"Feed");
        $list[33] = array(_("Reset to ZERO"),ProcessArg::NONE,"reset2zero",0,DataType::UNDEFINED,"Misc");
        
        $list[34] = array(_("Wh Accumulator"),ProcessArg::FEEDID,"wh_accumulator",1,DataType::REALTIME,"Main",array(Engine::PHPFINA,Engine::PHPTIMESERIES));
        
        
        $list[35] = array(_("Publish to MQTT"),ProcessArg::TEXT,"publish_to_mqtt",1,DataType::UNDEFINED,"Main");     
        // $list[29] = array(_("save to input"),ProcessArg::INPUTID,"save_to_input",1,DataType::UNDEFINED);

        return $list;
    }

    public function input($time, $value, $processList)
    {
        $this->log->info("input() received time=$time, value=$value");
           
        $process_list = $this->get_process_list();
        $pairs = explode(",",$processList);
        foreach ($pairs as $pair)
        {
            $inputprocess = explode(":", $pair);                                // Divide into process id and arg
            $processid = (int) $inputprocess[0];                                    // Process id

            $arg = 0;
            if (isset($inputprocess[1]))
                $arg = $inputprocess[1];               // Can be value or feed id

            $process_public = $process_list[$processid][2];             // get process public function name

            $value = $this->$process_public($arg,$time,$value);           // execute process public function
        }
    }

    public function get_process($id)
    {
        $list = $this->get_process_list();
        if ($id>0 && $id<count($list)+1) return $list[$id];
    }

    public function scale($arg, $time, $value)
    {
        return $value * $arg;
    }

    public function divide($arg, $time, $value)
    {
        return $value / $arg;
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

    public function reset2zero($arg, $time, $value)
     {
         $value = 0;
         return $value;
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
        $time = mktime(0, 0, 0, date("m",$time), date("d",$time), date("Y",$time));

        $feedname = "feed_".trim($id)."";
        $result = $this->mysqli->query("SELECT * FROM $feedname WHERE `time` = '$time'");
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
        if ($last_time && (time()-$last_time)<7200)
        {
            // kWh calculation
            $time_elapsed = ($time_now - $last_time);
            $kwh_inc = ($time_elapsed * $value) / 3600000.0;
            $new_kwh = $last_kwh + $kwh_inc;
        } else {
            // in the event that redis is flushed the last time will
            // likely be > 7200s ago and so kwh inc is not calculated
            // rather than enter 0 we enter the last value
            $new_kwh = $last_kwh;
        }

        $padding_mode = "join";
        $this->feed->insert_data_padding_mode($feedid, $time_now, $time_now, $new_kwh, $padding_mode);
        
        return $value;
    }

    public function power_to_kwhd($feedid, $time_now, $value)
    {
        $new_kwh = 0;

        // Get last value
        $last = $this->feed->get_timevalue($feedid);

        if (!isset($last['value'])) $last['value'] = 0;
        $last_kwh = $last['value']*1;
        $last_time = $last['time']*1;
        
        //$current_slot = floor($time_now / 86400) * 86400;
        //$last_slot = floor($last_time / 86400) * 86400;
        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);    

        if ($last_time && ((time()-$last_time)<7200)) {
            // kWh calculation
            $time_elapsed = ($time_now - $last_time);
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

    public function kwhinc_to_kwhd($feedid, $time_now, $value)
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
        $this->feed->insert_data_padding_mode($feedid, $time, $time, $value, $padding_mode);
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
        if ((time()-$last_time)<7200) {
            $time_elapsed = ($time_now - $last_time);
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
        
        if ($redis->exists("process:kwhtopower:$feedid")) {
            $lastvalue = $redis->hmget("process:kwhtopower:$feedid",array('time','value'));
            $kwhinc = $value - $lastvalue['value'];
            $joules = $kwhinc * 3600000.0;
            $timeelapsed = ($time - $lastvalue['time']);
            $power = $joules / $timeelapsed;
            $this->feed->insert_data($feedid, $time, $time, $power);
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
            return 0;
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
            
            $power = ($val_diff * 3600) / $time_diff;
            
            if ($val_diff>0 && $power<$max_power) $totalwh += $val_diff;
            
            $padding_mode = "join";
            $this->feed->insert_data_padding_mode($feedid, $time, $time, $totalwh, $padding_mode);
            
        }
        $redis->hMset("process:whaccumulator:$feedid", array('time' => $time, 'value' => $value));

        return $totalwh;
    }
    
    public function publish_to_mqtt($topic, $time, $value)
    {
        // Publish value to MQTT topic, see: http://openenergymonitor.org/emon/node/5943
        if ($this->mqtt && $this->mqtt->connect()) {
            $this->mqtt->publish($topic,$value,0);
            $this->mqtt->close();
        }
        
        return $value;
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
    private function getstartday($time_now)
    {
        $now = DateTime::createFromFormat("U", $time_now);
        $now->setTimezone(new DateTimeZone($this->timezone));
        $now->setTime(0,0);    // Today at 00:00

        return $now->format("U");
    }

}
