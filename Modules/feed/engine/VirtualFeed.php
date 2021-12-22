<?php
// engine_methods interface in shared_helper.php
include_once dirname(__FILE__) . '/shared_helper.php';

class VirtualFeed implements engine_methods
{

    private $mysqli;
    private $input;
    private $feed;
    private $log;

    public function __construct($mysqli,$redis,$feed)
    {
        global $session,$user;
        $this->mysqli = $mysqli;
        $this->feed = $feed;
        $this->log = new EmonLogger(__FILE__);

        require_once "Modules/input/input_model.php";
        $this->input = new Input($mysqli,$redis, $feed);
    }

    public function create($feedid,$options)
    {
        return true;  // Always true
    }

    public function delete($feedid)
    {
        return true; // Always true
    }

    public function get_meta($feedid)
    {
        $meta = new stdClass();
        $meta->id = $feedid;
        $meta->start_time = 0;
        $meta->nlayers = 1;
        $meta->interval = 1;
        return $meta;
    }

    public function get_feed_size($feedid)
    {
        return 0;  // Always 0
    }

    public function post($feedid,$time,$value,$arg=null)
    {
        return false; // Not supported by engine
    }

    /**
     * returns the feed's last value
     *
     * @param int $feedid
     * @return bool|array
     */
    public function lastvalue($feedid)
    {
        $now = time();
        $feedid = intval($feedid);
        $processList = $this->feed->get_processlist($feedid);
        if ($processList == '' || $processList == null) { 
            return array('time'=>(int)$now, 'value'=>null);
        }
        
        $result = $this->mysqli->query("SELECT userid FROM feeds WHERE `id` = '$feedid'");
        $row = $result->fetch_array();
        $userid = $row['userid'];
         
        // Lets instantiate a new class of process so we can run many proceses recursively without interference
        global $session,$user;
        require_once "Modules/process/process_model.php";
        $process = new Process($this->mysqli,$this->input,$this->feed,$user->get_timezone($userid));

        $opt_timearray = array('sourcetype' => ProcessOriginType::VIRTUALFEED, 'sourceid' => $feedid);
        $dataValue = $process->input($now, null, $processList, $opt_timearray); // execute processlist 
        
        if ($dataValue !== null) $dataValue = (float) $dataValue ;
        return array('time'=>(int)$now, 'value'=>$dataValue);  // datavalue can be float or null, dont cast!
    }
    
    // Executes virtual feed processlist for each timestamp in range
    public function get_data_combined($feedid,$start,$end,$interval,$average=0,$timezone="UTC",$timeformat="unix",$csv=false,$skipmissing=0,$limitinterval=1)
    {   
        $feedid = (int) $feedid;
        $skipmissing = (int) $skipmissing;
        $limitinterval = (int) $limitinterval;
        
        $start = (int) $start;
        $end = (int) $end;
        
        $processList = $this->feed->get_processlist($feedid);
        if ($processList == '' || $processList == null) return false;

        if ($csv) {
            global $settings;
            require_once "Modules/feed/engine/shared_helper.php";
            $helperclass = new SharedHelper($settings['feed']);
            $helperclass->set_time_format($timezone,$timeformat);
            $helperclass->csv_header($feedid);
        }
                        
        // Lets instantiate a new class of process so we can run many proceses recursively without interference
        require_once "Modules/process/process_model.php";
        $process = new Process($this->mysqli,$this->input,$this->feed,$timezone);

        $opt_timearray = array(
            'sourceid'=>$feedid, 
            'start' => $start, 
            'end' => $end, 
            'interval' => $interval, 
            'average' => $average, 
            'timezone' => $timezone,
            'sourcetype' => ProcessOriginType::VIRTUALFEED,
            'index' => 0
        );
        
        if (in_array($interval,array("weekly","daily","monthly","annual"))) {
            $fixed_interval = false;
            // align to day, month, year
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone($timezone));
            $date->setTimestamp($start);
            $date->modify("midnight");
            $modify = "+1 day";
            if ($interval=="weekly") {
                $date->modify("this monday");
                $modify = "+1 week";
            } else if ($interval=="monthly") {
                $date->modify("first day of this month");
                $modify = "+1 month";
            } else if ($interval=="annual") {
                $date->modify("first day of this year");
                $modify = "+1 year";
            }
            $time = $date->getTimestamp();
        } else {
            // If interval codes are not specified then we advanced by a fixed numeric interval 
            $fixed_interval = true;
            $interval = (int) $interval;
            if ($interval<1) $interval = 1;
            $time = $start;
        }

        $data = array();
        $dataValue = null;
        
        while($time<=$end)
        {
            $dataValue = $process->input($time, $dataValue, $processList, $opt_timearray); // execute processlist 
                
            if ($dataValue!==null || $skipmissing===0) { // Remove this to show white space gaps in graph
                if ($dataValue !== null) $dataValue = (float) $dataValue;
                                
                if ($csv) { 
                    $helperclass->csv_write($time,$dataValue);
                } else {
                    $data[] = array($time, $dataValue);
                }
            }
            
            // Advance position
            if ($fixed_interval) {
                $time += $interval;
            } else {
                $date->modify($modify);
                $time = $date->getTimestamp();
            }
            $opt_timearray['index']++;
        }

        if ($csv) {
            $helperclass->csv_close();
            exit;
        } else {
            return $data;
        }
    }

    public function export($feedid,$start)
    {
        return false; // TBD
    }

    public function clear($feedid) {
        // clear all feed data but keep meta.
        return array('success'=>false,'message'=>'"Clear" not available for this storage engine');
    }
    
    public function trim($feedid,$start_time) {
        // clear all data upto a start_time
        return array('success'=>false,'message'=>'"Trim" not available for this storage engine');
    }
}
