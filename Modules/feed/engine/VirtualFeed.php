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

    public function update($feedid,$time,$value)
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

    // 1 - Calculates date slots for given start, end and interval. Representing about a pixel on the x axis of the graph for each time slot.
    // 2 - If feed is realtime slots are calculated based on interval, if daily, slots date is at its datetime midnight of user timezone.
    // 3 - Executes virtual feed processlist for each slot individually.
    // 4-  First processor of virtual feed processlist should be the source_feed_data_time() this will get data from a slot.
    // 5 - Agreggates all slots time and processed data.
    // 6 - Returns data to the graph.
    public function get_data_combined($feedid,$start,$end,$interval,$average=0,$timezone="UTC",$timeformat="unix",$csv=false,$skipmissing=0,$limitinterval=1)
    {
        $feedid = (int) $feedid;
        $processList = $this->feed->get_processlist($feedid);
        if ($processList == '' || $processList == null) return false;
        
        $start = $start * 0.001;
        $end = $end * 0.001;

        $interval = (int) $interval; // time gap in seconds
        if ($interval<1) $interval = 1;
        $dp = ceil(($end - $start) / $interval); // datapoints for desied range with set interval time gap
        $end = $start + ($dp * $interval);
        if ($dp<1) return false;

        $data = array();
        $dataValue = null;
                
        // Lets instantiate a new class of process so we can run many proceses recursively without interference
        global $session,$user;
        require_once "Modules/process/process_model.php";
        $process = new Process($this->mysqli,$this->input,$this->feed,$user->get_timezone($userid));

        if ($dp > 0) 
        {
            $range = $end - $start; // windows duration in seconds
            $td = $range / $dp;    // time duration for each datapoint
            $t = $start; $tb = 0;  // time between t and tb
            for ($i=0; $i<$dp; $i++)
            {
                $tb = $start + intval(($i+1)*$td); //next end time
                $opt_timearray = array('start' => $t, 'end' => $tb, 'interval' => $interval, 'sourcetype' => ProcessOriginType::VIRTUALFEED, 'sourceid'=>$feedid);
                $dataValue = $process->input($t, $dataValue, $processList, $opt_timearray); // execute processlist 
                    
                if ($dataValue!=NULL || $skipmissing===0) { // Remove this to show white space gaps in graph
                    $time = $t * 1000;
                    if ($dataValue !== null) $dataValue = (float) $dataValue ;
                    $data[] = array($time, $dataValue);
                }
                $t = $tb; // next start time
            }
        }
        else {
            //daily virtual feed
             $startslot=$process->process__getstartday($start); // start of day for user timezone
             $endslot=$process->process__getstartday($end); // end of day for user timezone
            
             if ($endslot < $startslot) $endslot = $endslot + 86400; // one day range
             while ($startslot<$endslot)
             {
                $opt_timearray = array('start' => $startslot, 'end' => $startslot+86400, 'interval' => $interval, 'sourcetype' => ProcessOriginType::VIRTUALFEED, 'sourceid'=>$feedid);
                $dataValue = $process->input($startslot, $dataValue, $processList, $opt_timearray); // execute processlist 
                    
                if ($dataValue!=NULL || $skipmissing===0) { // Remove this to show white space gaps in graph
                    $time = $startslot * 1000;
                    if ($dataValue !== null) $dataValue = (float) $dataValue ;
                    $data[] = array($time, $dataValue);
                }
                $startslot +=86400; // inc a day
             }
        }

        return $data;
    }


    public function export($feedid,$start)
    {
        return false; // TBD
    }

    public function csv_export($feedid,$start,$end,$outinterval,$usertimezone)
    {
        global $settings;
        
        require_once "Modules/feed/engine/shared_helper.php";
        $helperclass = new SharedHelper();
        
        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: application/octet-stream");
        $filename = $feedid.".csv";
        header("Content-Disposition: attachment; filename={$filename}");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $exportfh = @fopen( 'php://output', 'w' );
        
        $data = $this->get_data_combined($feedid,$start*1000,$end*1000,$outinterval,0,"UTC","unix",false,0,0);
        $max = sizeof($data);
        for ($i=0; $i<$max; $i++){
            $timenew = $helperclass->getTimeZoneFormated($data[$i][0]/1000,$usertimezone);
            $value = $data[$i][1];
            if ($value != null) $value = number_format($value,$settings['feed']['csv_decimal_places'],$settings['feed']['csv_decimal_place_separator'],'');
            fwrite($exportfh, $timenew.$settings['feed']['csv_field_separator'].$value."\n");
        }
        fclose($exportfh);
        exit;
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
