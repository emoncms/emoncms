<?php

/*
     All Emoncms code is released under the GNU Affero General Public License.
     See COPYRIGHT.txt and LICENSE.txt.

     Schedule module contributed by Nuno Chaveiro nchaveiro(at)gmail.com 2015
     ---------------------------------------------------------------------
     Emoncms - open source energy visualisation
     Part of the OpenEnergyMonitor project:
     http://openenergymonitor.org

*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Schedule
{
    private $mysqli;
    private $log;
    
    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
        $this->log = new EmonLogger(__FILE__);
    }
    
    public function exist($id)
    {
        $id = intval($id);
        $result = $this->mysqli->query("SELECT id FROM schedule WHERE id = '$id'");
        if ($result->num_rows>0) return true; else return false;
    }
    
    public function get($id)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Schedule does not exist');

        $result = $this->mysqli->query("SELECT * FROM schedule WHERE id = '$id'");
        $row = (array) $result->fetch_object();

        return $row;        
    }
    
    public function get_list($userid)
    {
        $userid = (int) $userid;
        $schedules = array();
        
        $result = $this->mysqli->query("SELECT `id`, `userid`, `name`, `expression`, `public`, CASE `userid` WHEN '$userid' THEN '1' ELSE '0' END AS `own` FROM schedule WHERE (userid = '$userid' OR public = '1')");
        while ($row = (array)$result->fetch_object())
        {
            $schedules[] = $row;
        }
        return $schedules;
    }
    
    public function get_expression($id)
    {
        $id = (int) $id;

        $result = $this->mysqli->query("SELECT `expression` FROM schedule WHERE id = '$id'");
        $row = $result->fetch_array();
        $get_expression = array('expression'=>$row['expression']);
        return $get_expression;        
    }
    
    public function create($userid)
    {
        $userid = intval($userid);
        $this->mysqli->query("INSERT INTO schedule (`userid`,`name`,`expression`,`public`) VALUES ('$userid','New Schedule','',0)");
        return $this->mysqli->insert_id;  
    }

    public function delete($id)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Schedule does not exist');
        
        $result = $this->mysqli->query("DELETE FROM schedule WHERE `id` = '$id'");
    }
    
    public function set_fields($id,$fields)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Schedule does not exist');

        $fields = json_decode(stripslashes($fields));

        $array = array();

        // Repeat this line changing the field name to add fields that can be updated:
        if (isset($fields->name)) $array[] = "`name` = '".preg_replace('/[^\w\s-:]/','',$fields->name)."'";
        if (isset($fields->expression)) $array[] = "`expression` = '".preg_replace('/[^\/\|\,\w\s-:]/','',$fields->expression)."'";
        if (isset($fields->public)) $array[] = "`public` = '".intval($fields->public)."'";
        
        // Convert to a comma seperated string for the mysql query
        $fieldstr = implode(",",$array);
        $this->mysqli->query("UPDATE schedule SET ".$fieldstr." WHERE `id` = '$id'");


        if ($this->mysqli->affected_rows>0){
            return array('success'=>true, 'message'=>'Field updated');
        } else {
            return array('success'=>false, 'message'=>'Field could not be updated');
        }
    }
	
    public function test_expression($expression) {
        $time = time();
		$result = $this->match_engine($expression,$time,true);
        return $result;
    }
	
	public function match($expression, $time) {
		return $this->match_engine($expression,$time,false);
	}

	// Private
	
	// used by expression builder for help debuging an expression
	// support were: http://openenergymonitor.org/emon/node/10019
    private function match_engine($expression, $time, $debug) {
        // Check if input string is in range of day, month, week day and hour. White spaces are ignored and can be ommited.
        // Returns true if in range, else returns 0.
        // All dates must be in GMT as epow time is always GMT.
        //
        // Examples: '12:00-24:00'
        //           'Mon-Fri | 00:00-24:00'
        //           'Mon,Wed | 00:00-06:00, 12:00-00:00, Fri-Sun | 00:00-06:00, 12:00-00:00'
        //           '25/12 | 00:00-24:00'
        //           '01/12 - 31/12 | Sat,Sun | 09:00-12:00, 13:00-20:00'
        //           '15/01, 29/02, 01/01-18/02, 01/08-25/12, 19/09 | Mon-Fri | 12:00-14:00, 18:00-22:30, Thu | 18:00-22:00'
        //           '00:00-08:00,22:00-24:00'                              <- Diary Winter Empty 
        //           '08:00-09:00,10:30-18:00,20:30-22:00'                  <- Diary Winter Full
        //           '09:00-10:30,18:00-20:30'                              <- Diary Winter Top 
        
        //           '00:00-08:00,22:00-24:00'                              <- Diary Summer Empty
        //           '08:00-10:30,13:00-19:30,21:00-22:00'                  <- Diary Summer Full
        //           '10:30-13:00,19:30-21:00'                              <- Diary Summer Top
        
        //           'Mon-Fri|00:00-07:00, Sat|00:00-09:30,13:00-18:30,22:00-24:00, Sun|00:00-24:00'    <- Weekly Winter Empty 
        //           'Mon-Fri|07:00-09:30,12:00-18:30,21:00-24:00, Sat|09:30-13:00,18:30-22:00'         <- Weekly Winter Full
        //           'Mon-Fri|09:30-12:00,18:30-21:00'                                                  <- Weekly Winter Top
        
        //           'Mon-Fri|00:00-07:00, Sat|00:00-09:00,14:00-20:00,22:00-24:00, Sun|00:00-24:00'    <- Weekly Summer Empty 
        //           'Mon-Fri|07:00-09:15,12:15-24:00, Sat|09:00-14:00,20:00-22:00'                     <- Weekly Summer Full
        //           'Mon-Fri|09:15-12:15'                                                              <- Weekly Summer Top

        $timezone = 'GMT';
        $timeFull = DateTime::createFromFormat("U", $time);
        $timeDay = DateTime::createFromFormat("!d/m/Y T",  $timeFull->format('d/m/Y T'), new DateTimeZone('GMT'));
        $timeWeekDay = $timeFull->format('D');
        $timeHrMin = DateTime::createFromFormat("!H:i",  $timeFull->format('H:i'), new DateTimeZone('GMT'));

        $inrange_day = false;
        $inrange_dayweek = false;
        $inrange_hour = false;
        $debugval = "";
        
        if ($debug) $debugval.=  $timeFull->format("H:i:s D d-m-Y T") . "<BR>";
        if ($debug) $debugval.= $timeDay->format("H:i:s D d-m-Y T") . "<BR>";
        if ($debug) $debugval.= $timeWeekDay . "<BR>";
        if ($debug) $debugval.= $timeHrMin->format("H:i:s D d-m-Y T") . "<BR>";
        if ($debug) $debugval.= $expression . "<br>";
        
        preg_match_all('/((?<days>(([\d\s\/]*)\s*[-,]?)+)\|?\s*)((?<daysweek>((Mon|Tue|Wed|Thu|Fri|Sat|Sun)?\s*[-,]?\s*)+)\|?\s*)(?<times>((\d\d:\d\d\s*-\s*\d\d:\d\d),?\s*)+)/x', $expression, $matches, PREG_SET_ORDER);

        if ($debug) $debug_schedule = array();
        foreach($matches as $match) {
            $days = str_replace(" ", "", $match['days']);
            $days = explode(',', $days);
            $daysweek = str_replace(" ", "", $match['daysweek']);
            $daysweek = explode(',', $daysweek);
            $times = str_replace(" ", "", $match['times']);
            $times = array_filter(explode(',', $times));
            
            foreach($days as $day) {
                if ($debug) $debugval.= "<br>";
                if ($debug) $debugval.= print_r($day,true);
                $inrange_day = false;
                if (!empty($day)) {
                    if (strpos($day, '-') !== false) {  // Is a day range
                        list($start, $end) = explode('-', $day, 2);
                        $start = DateTime::createFromFormat("!d/m", $start, new DateTimeZone($timezone));
                        $end = DateTime::createFromFormat("!d/m", $end, new DateTimeZone($timezone));
                        if ($timeDay >= $start && $timeDay <= $end) {
                            $inrange_day = true;
                        }
                    } else {                            // Is just one day
                        $start = DateTime::createFromFormat("!d/m", $day, new DateTimeZone($timezone));
                        if ($timeDay == $start) {
                            $inrange_day = true;
                        }
                    }
                } else {
                    $inrange_day = true;                // No day give, assume all
                }
                if ($inrange_day ) {
                    if ($debug) $debugval.=("<----------- FOUND DAY");
                    foreach($daysweek as $dayweek) {
                        $inrange_dayweek = false;
                        if ($debug) { $debugval.=("<br>&nbsp;&nbsp;"); $debugval.=print_r($dayweek,true); }
                        if (!empty($dayweek)) {
                            if (strpos($dayweek, '-') !== false) {      // Is a dayweek range
                                // Gets the daysweek of the week in a range. e.g. given Mon-Wed
                                list($start, $end) = explode('-', $dayweek, 2);
                                $start = DateTime::createFromFormat("!D", $start, new DateTimeZone($timezone));
                                for($i= 0 ; $i <= 6 ; $i++ ) {
                                    if ($timeWeekDay == $start->format('D')) {
                                        $inrange_dayweek = true;
                                        break;
                                    } else if ($start->format('D') == $end) {
                                        break;
                                    }
                                    $start->modify('+1 day');
                                }
                            } else {                                    // Is just one day
                                if ($timeWeekDay == $dayweek) {
                                    $inrange_dayweek = true;
                                }
                            }
                        }
                        else {
                            $inrange_dayweek = true;                    // No day give, assume all
                        }

                        if ($inrange_dayweek ) {
                            if ($debug) $debugval.=("<----------- FOUND WEEKDAY");
                            foreach($times as $time) {
                                $inrange_hour = false;
                                if ($debug) $debugval.="<br>&nbsp;&nbsp;&nbsp;&nbsp;";
								if ($debug) $debugval.=print_r($time,true);
                                list($start, $end) = explode('-', $time, 2);
                                $startTime = DateTime::createFromFormat("!H:i", $start, new DateTimeZone($timezone));
                                $endTime = DateTime::createFromFormat("!H:i", $end, new DateTimeZone($timezone));
                                if ($debug) $debugval.=("  ---->". $startTime->format('D Y-m-d H:i:s T')   . " <-> " . $endTime->format('D Y-m-d H:i:s T') . " ? ". $timeHrMin->format('D Y-m-d H:i:s T'));
                                if ($timeHrMin >= $startTime && $timeHrMin <= $endTime) {
                                    $inrange_hour = true;
                                    if ($debug) $debugval.=("<----------- FOUND TIME");
									break;
                                }
                            }
                        }
						if ($debug) $debug_schedule[] = array ("day" => $day, "dayweek" => $dayweek, "time" => $time);
                    }
                }
            }
        }
        if ($debug) { 
			$debugval.=print_r($debug_schedule,true);
			return array ("result" => $inrange_hour, "debug" => $debugval);
		}
		return $inrange_hour;
    }
	
}