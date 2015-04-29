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
    
    private $timezoneoffset = 0;

    public function __construct($mysqli,$input,$feed)
    {
            $this->mysqli = $mysqli;
            $this->input = $input;
            $this->feed = $feed;
            $this->log = new EmonLogger(__FILE__);
            
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
    
    public function set_timezone_offset($timezoneoffset)
    {
        $this->timezoneoffset = $timezoneoffset;
    }

    public function get_process_list()
    {
        
        $list = array();
        
        // Note on engine selection
        
        // The engines listed against each process are the recommended engines for each process - and is only used in the input and node config GUI dropdown selectors
        // By using the create feed api and add input process its possible to create any feed type and add any process to it - this needs to be improved so that only 
        // feeds capable of using a particular processor can be used. 

        // description | Arg type | function | No. of datafields if creating feed | Datatype | Engine

        $list['1'] = array(_("Log to feed"),ProcessArg::FEEDID,"log_to_feed",1,DataType::REALTIME,"Main",array(Engine::PHPFINA,Engine::PHPTIMESERIES)); 
        $list['34'] = array(_("Wh Accumulator"),ProcessArg::FEEDID,"wh_accumulator",1,DataType::REALTIME,"Main",array(Engine::PHPFINA,Engine::PHPTIMESERIES));
                         
        $list['2'] = array(_("x"),ProcessArg::VALUE,"scale",0,DataType::UNDEFINED,"Calibration");                           
        $list['3'] = array(_("+"),ProcessArg::VALUE,"offset",0,DataType::UNDEFINED,"Calibration");                    
              
        $list['4'] = array(_("Power to kWh"),ProcessArg::FEEDID,"power_to_kwh",1,DataType::REALTIME,"Power",array(Engine::PHPFINA,Engine::PHPTIMESERIES));
        $list['21'] = array(_("kWh to Power"),ProcessArg::FEEDID,"kwh_to_power",1,DataType::REALTIME,"Power",array(Engine::PHPFINA,Engine::PHPTIMESERIES));
                           
        $list['6'] = array(_("x input"),ProcessArg::INPUTID,"times_input",0,DataType::UNDEFINED,"Input");                       
        $list['12'] = array(_("/ input"),ProcessArg::INPUTID,"divide_input",0,DataType::UNDEFINED,"Input");
        $list['11'] = array(_("+ input"),ProcessArg::INPUTID,"add_input",0,DataType::UNDEFINED,"Input");     
        $list['22'] = array(_("- input"),ProcessArg::INPUTID,"subtract_input",0,DataType::UNDEFINED,"Input");
        
        $list['14'] = array(_("Accumulator"),ProcessArg::FEEDID,"accumulator",1,DataType::REALTIME,"Misc",array(Engine::PHPFINA,Engine::PHPTIMESERIES));                 
        $list['15'] = array(_("Rate of change"),ProcessArg::FEEDID,"ratechange",1,DataType::REALTIME,"Misc",array(Engine::PHPFINA,Engine::PHPTIMESERIES));               
        $list['26'] = array(_("Signed to unsigned"),ProcessArg::NONE,"signed2unsigned",0,DataType::UNDEFINED,"Misc");
        $list['33'] = array(_("Reset to ZERO"),ProcessArg::NONE,"reset2zero",0,DataType::UNDEFINED,"Misc");
        
        $list['20'] = array(_("Total pulse count to pulse increment"),ProcessArg::FEEDID,"pulse_diff",1,DataType::REALTIME,"Pulse",array(Engine::PHPFINA,Engine::PHPTIMESERIES));

        $list['24'] = array(_("Allow positive"),ProcessArg::NONE,"allowpositive",0,DataType::UNDEFINED,"Limits");           
        $list['25'] = array(_("Allow negative"),ProcessArg::NONE,"allownegative",0,DataType::UNDEFINED,"Limits");           
                                      
        $list['29'] = array(_(" + feed"),ProcessArg::FEEDID,"add_feed",0,DataType::UNDEFINED,"Feed");        // Klaus 24.2.2014
        $list['30'] = array(_(" - feed"),ProcessArg::FEEDID,"sub_feed",0,DataType::UNDEFINED,"Feed");        // Klaus 24.2.2014
        $list['31'] = array(_(" * feed"),ProcessArg::FEEDID,"multiply_by_feed",0,DataType::UNDEFINED,"Feed");
        $list['32'] = array(_(" / feed"),ProcessArg::FEEDID,"divide_by_feed",0,DataType::UNDEFINED,"Feed");

        $list['35'] = array(_("Publish to MQTT"),ProcessArg::TEXT,"publish_to_mqtt",1,DataType::UNDEFINED,"Main");  

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
            if (isset($inputprocess[1])) {
                $arg = $inputprocess[1];               // Can be value or feed id
            }
            
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
    }

    public function accumulator($feedid, $time, $value)
    {
        $last = $this->feed->get_timevalue($feedid);
        $value = $last['value'] + $value;
        $this->feed->insert_data($feedid, $time, $time, $value);
        $padding_mode = "join";
        $this->feed->insert_data_padding_mode($feedid, $time, $time, $value, $padding_mode);
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
            $this->feed->insert_data_padding_mode($feedid,$time,$time,$totalwh,$padding_mode);
            
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
}
