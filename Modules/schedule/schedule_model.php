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
    private $timezone = 'UTC';

    public function __construct($mysqli,$timezone)
    {
        $this->mysqli = $mysqli;
        $this->log = new EmonLogger(__FILE__);
        if (!($timezone === NULL)) $this->timezone = $timezone;
    }

    public function exist($id)
    {
        $id = intval($id);
        static $schedule_exists_cache = array(); // Array to hold the cache
        if (isset($schedule_exists_cache[$id])) {
            $scheduleexist = $schedule_exists_cache[$id]; // Retrieve from static cache
        } else {
            $result = $this->mysqli->query("SELECT id FROM schedule WHERE id = '$id'");
            $scheduleexist = $result->num_rows>0;
            $schedule_exists_cache[$id] = $scheduleexist; // Cache it
            $this->log->info("exist() $id");
        }
        return $scheduleexist;
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

        $result = $this->mysqli->query("SELECT `id`, `userid`, `name`, `expression`, `timezone`, `public`, CASE `userid` WHEN '$userid' THEN '1' ELSE '0' END AS `own` FROM schedule WHERE (userid = '$userid' OR public = '1')");
        while ($row = (array)$result->fetch_object())
        {
            $schedules[] = $row;
        }
        return $schedules;
    }

    public function get_expression($id)
    {
        $id = (int)$id;
        static $schedule_exp_cache = array(); // Array to hold the cache
        if (isset($schedule_exp_cache[$id])) {
            $get_expression = $schedule_exp_cache[$id]; // Retrieve from static cache
        } else {
            $this->log->info("get_expression() $id");
            $result = $this->mysqli->query("SELECT `expression`, `timezone` FROM schedule WHERE id = '$id'");
            $row = $result->fetch_array();
            $get_expression = array('expression'=>$row['expression'], 'timezone'=>$row['timezone']);
            $schedule_exp_cache[$id] = $get_expression; // Cache it
        }
        return $get_expression;
    }

    public function create($userid)
    {
        $userid = intval($userid);
        $this->mysqli->query("INSERT INTO schedule (`userid`,`name`,`expression`,`timezone`, `public`) VALUES ('$userid','New Schedule','','".$this->timezone."',0)");
        return $this->mysqli->insert_id;
    }

    public function delete($id)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Schedule does not exist');
        $result = $this->mysqli->query("DELETE FROM schedule WHERE `id` = '$id'");

        if (isset($schedule_exists_cache[$id])) { unset($schedule_exists_cache[$id]); } // Clear static cache
        if (isset($schedule_exp_cache[$id])) { unset($schedule_exp_cache[$id]); } // Clear static cache
    }

    public function set_fields($id,$fields)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Schedule does not exist');

        $fields = json_decode(stripslashes($fields));

        $array = array();
        $array[] = "`timezone` = '".$this->timezone."'";

        // Repeat this line changing the field name to add fields that can be updated:
        if (isset($fields->name)) $array[] = "`name` = '".preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$fields->name)."'";
        if (isset($fields->public)) $array[] = "`public` = '".intval($fields->public)."'";
        if (isset($fields->expression)) {
            $array[] = "`expression` = '".preg_replace('/[^\/\|\,\w\s-:]/','',$fields->expression)."'"; 
            if (isset($schedule_exp_cache[$id])) { unset($schedule_exp_cache[$id]); } // Clear static cache
        }

        // Convert to a comma seperated string for the mysql query
        $fieldstr = implode(",",$array);
        $this->mysqli->query("UPDATE schedule SET ".$fieldstr." WHERE `id` = '$id'");


        if ($this->mysqli->affected_rows>0){
            return array('success'=>true, 'message'=>'Field updated');
        } else {
            return array('success'=>false, 'message'=>'Field could not be updated');
        }
    }

    public function test_expression($scheduleid) {
        $get_expression = $this->get_expression($scheduleid);
        $expression = $get_expression["expression"];
        $exp_timezone = $get_expression["timezone"];
        $time = time(); //epoch is in UTC
        $result = $this->match_engine($expression,$exp_timezone,$time,true);
        return $result;
    }

    public function match($scheduleid, $time) {
        //$this->log->info("match() $scheduleid, $time");
        $get_expression = $this->get_expression($scheduleid);
        $expression = $get_expression["expression"];
        $exp_timezone = $get_expression["timezone"];
        return $this->match_engine($expression,$exp_timezone,$time,false);
    }

    // Private

    // used by expression builder for help debuging an expression
    // support were: http://openenergymonitor.org/emon/node/10019
    private function match_engine($expression, $exp_timezone, $time, $debug) {
        // Check if input string is in range of Day light saving time, day, month, week day and hour. White spaces are ignored and can be ommited.
        // Returns true if in range, else returns 0.
        //
        // Examples: '12:00-24:00'
        //           'Mon-Fri | 00:00-23:59'
        //           'Summer | Mon-Fri | 00:00-24:00'
        //           'Winter | Mon-Fri | 00:00-24:00'
        //           'Winter | Mon-Fri | 09:00-10:00, Summer | Mon-Fri | 08:00-09:00'
        //           'Mon,Wed | 00:00-06:00, 12:00-00:00, Fri-Sun | 00:00-06:00, 12:00-00:00'
        //           '12/25 | 00:00-24:00'
        //           '12/01 - 12/31 | Sat,Sun | 09:00-12:00, 13:00-20:00'
        //           '01/15, 02/29, 01/01-02/18, 08/01-12/25, 09/19 | Mon-Fri | 12:00-14:00, 18:00-22:30, Thu | 18:00-22:00'
        //           '00:00-08:00,22:00-24:00'                              <- Diary Winter Empty
        //           '08:00-09:00,10:30-18:00,20:30-22:00'                  <- Diary Winter Full
        //           '09:00-10:30,18:00-20:30'                              <- Diary Winter Top

        //           '00:00-08:00,22:00-24:00'                              <- Diary Summer Empty
        //           '08:00-10:30,13:00-19:30,21:00-22:00'                  <- Diary Summer Full
        //           '10:30-13:00,19:30-21:00'                              <- Diary Summer Top

        // Tri schedule
        //           'Mon-Fri|00:00-06:59, Sat|00:00-09:29,13:00-18:29,22:00-23:59, Sun|00:00-23:59'    <- Weekly Winter Empty
        //           'Mon-Fri|07:00-09:29,12:00-18:29,21:00-23:59, Sat|09:30-12:59,18:30-21:59'         <- Weekly Winter Full
        //           'Mon-Fri|09:30-11:59,18:30-20:59'                                                  <- Weekly Winter Top

        //           'Mon-Fri|00:00-06:59, Sat|00:00-08:59,14:00-19:59,22:00-23:59, Sun|00:00-23:59'    <- Weekly Summer Empty
        //           'Mon-Fri|07:00-09:14,12:15-23:59, Sat|09:00-13:59,20:00-21:59'                     <- Weekly Summer Full
        //           'Mon-Fri|09:15-12:14'                                                              <- Weekly Summer Top

        // Tri schedule
        // Weekly Empty -> 'Winter|Mon-Fri|00:00-06:59, Winter|Sat|00:00-09:29,13:00-18:29,22:00-23:59, Winter|Sun|00:00-23:59 , Summer|Mon-Fri|00:00-06:59, Summer|Sat|00:00-08:59,14:00-19:59,22:00-23:59, Summer|Sun|00:00-23:59'
        //        Full  -> 'Winter|Mon-Fri|07:00-09:29,12:00-18:29,21:00-23:59, Winter|Sat|09:30-12:59,18:30-21:59 , Summer|Mon-Fri|07:00-09:14,12:15-23:59, Summer|Sat|09:00-13:59,20:00-21:59'
        //        Top   -> 'Winter|Mon-Fri|09:30-11:59,18:30-20:59 , Summer|Mon-Fri|09:15-12:14'

        // Bi schedule
        // Weekly Empty -> 'Winter|Mon-Fri|00:00-06:59, Winter|Sat|00:00-09:29,13:00-18:29,22:00-23:59, Winter|Sun|00:00-23:59 , Summer|Mon-Fri|00:00-06:59, Summer|Sat|00:00-08:59,14:00-19:59,22:00-23:59, Summer|Sun|00:00-23:59'
        //        Full  -> 'Winter|Mon-Fri|07:00-23:59, Winter|Sat|09:30-12:59,18:30-21:59 , Summer|Mon-Fri|07:00-23:59, Summer|Sat|09:00-13:59,20:00-21:59'

        $timeDay =  DateTime::createFromFormat("U", $time);   // epoch is always in GMT
        $timeDay->setTimezone(new DateTimeZone($exp_timezone));
        $timeDST= $timeDay->format("I");
        $timeDay->setTime(0,0);
        $timeWeekDay = $timeDay->format('D');
        $timeHrMin = DateTime::createFromFormat("U", $time); // epoch is always in GMT
        $timeHrMin->setTimezone(new DateTimeZone($exp_timezone));

        $inrange_day = false;
        $inrange_dayweek = false;
        $inrange_hour = false;
        $debugval = "";

        if ($debug) $debugval.= "Expression =" . $expression . "\n";
        if ($debug) $debugval.= "Input =" . DateTime::createFromFormat("U", $time)->format("H:i:s D d-m-Y I T") . "\n";
        if ($debug) $debugval.= "Day =" . $timeDay->format("H:i:s D d-m-Y I e") . "\n";
        if ($debug) $debugval.= "HrMin =" . $timeHrMin->format("H:i:s D d-m-Y I e") . "\n";
        if ($debug) $debugval.= "WeekDay =" . $timeWeekDay . "\n";

        preg_match_all('/((?<dst>((Summer|Winter)?\s*)+)\|?\s*)((?<days>((\d{1,2}\/\d{1,2})?\s*[-,]?\s*)+)\|?\s*)((?<daysweek>((Mon|Tue|Wed|Thu|Fri|Sat|Sun)?\s*[-,]?\s*)+)\|?\s*)(?<hours>((\d\d:\d\d)\s*[-,]?\s*)*)/x', $expression, $matches, PREG_SET_ORDER);

        if ($debug) $debug_schedule = array();
        foreach($matches as $match) {
            $dst =  str_replace(" ", "", $match['dst']);
            $days = str_replace(" ", "", $match['days']);
            $days = explode(',', $days);
            $daysweek = str_replace(" ", "", $match['daysweek']);
            $daysweek = explode(',', $daysweek);
            $hours = str_replace(" ", "", $match['hours']);
            $hours = array_filter(explode(',', $hours));
            if ($debug) $debugval.= "\n\n________________________________________\nMATCH\n". print_r($match,true)."\n";

            if (empty($dst) || ((strpos($dst, "S") === 0 && $timeDST) || (strpos($dst, "W") === 0 && !$timeDST))) {
                foreach($days as $day) {
                    if ($debug) $debugval.= "\n";
                    if ($debug) $debugval.= print_r($day,true);
                    $inrange_day = false;
                    if (!empty($day)) {
                        if (strpos($day, '-') !== false) {  // Is a day range
                            list($start, $end) = explode('-', $day, 2);
                            list($m, $d) = explode('/', $start, 2);
                            $start = clone $timeDay;
                            $start->setDate($start->format('Y') , $m , $d); // set the wanted day and month for 00:00 of input year
                            list($m, $d) = explode('/', $end, 2);
                            $end = clone $timeDay;
                            $end->setDate($end->format('Y') , $m , $d);  // set the wanted day and month for 00:00 of input year
                            if ($debug) $debugval.=("  ---->" . $start->format('D Y-m-d H:i:s e') . " - " . $end->format('D Y-m-d H:i:s e') . " ? ". $timeDay->format('D Y-m-d H:i:s e'));
                            if ($timeDay >= $start && $timeDay <= $end) {
                                $inrange_day = true;
                            }
                        } else {                            // Is just one day
                            list($m, $d) = explode('/', $day, 2);
                            $start = clone $timeDay;
                            $start->setDate($start->format('Y') , $m , $d); // set the wanted day and month for 00:00 of input year
                            if ($debug) $debugval.=("  ---->" . $start->format('D Y-m-d H:i:s e') . " ? ". $timeDay->format('D Y-m-d H:i:s e'));
                            if ($timeDay == $start) {
                                $inrange_day = true;
                            }
                        }
                        if ($debug && $inrange_day) $debugval.=(" <- FOUND DAY");
                    } else {
                        $inrange_day = true;                // No day give, assume all
                    }
                    if ($inrange_day ) {
                        foreach($daysweek as $dayweek) {
                            $inrange_dayweek = false;
                            if ($debug) { $debugval .= "\n\t" . print_r($dayweek,true); }
                            if (!empty($dayweek)) {
                                if (strpos($dayweek, '-') !== false) {      // Is a dayweek range
                                    // Gets the daysweek of the week in a range. e.g. given Mon-Wed
                                    list($start, $end) = explode('-', $dayweek, 2);
                                    $start = DateTime::createFromFormat("!D", $start);
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
                                if ($debug && $inrange_dayweek) $debugval.=(" <- FOUND A WEEKDAY");
                            }
                            else {
                                $inrange_dayweek = true;                    // No day give, assume all
                            }

                            if ($inrange_dayweek ) {
                                if (!empty($hours)) {
                                    foreach($hours as $hour) {
                                        $inrange_hour = false;
                                        if ($debug) { $debugval .= "\nH " . print_r($hour,true); }

                                            if ($debug) $debugval .= "\n\t\t" . print_r($hour,true);
                                            if (strpos($hour, '-') !== false) {      // Is a time range
                                                list($start, $end) = explode('-', $hour, 2);
                                            } else {                                 // Is just one time
                                                $start = $end = $hour;
                                            }
                                            list($h, $m) = explode(':', $start, 2);
                                            $startTime = clone $timeHrMin;
                                            $startTime->setTime($h, $m, 00); // set the time for the input date

                                            list($h, $m) = explode(':', $end, 2);
                                            $endTime = clone $timeHrMin;
                                            $endTime->setTime($h, $m, 59); // set the time for the input date

                                            if ($startTime > $endTime) { $endTime->modify('+1 day'); }

                                            if ($debug) $debugval.=("  ---->". $startTime->format('D Y-m-d H:i:s e')   . " - " . $endTime->format('D Y-m-d H:i:s e') . " ? ". $timeHrMin->format('D Y-m-d H:i:s e'));
                                            if ($timeHrMin >= $startTime && $timeHrMin <= $endTime) {
                                                $inrange_hour = true;
                                                if ($debug && $inrange_hour) $debugval.=(" <- FOUND A HOUR1");
                                                break;
                                            }

                                        if ($debug) $debug_schedule[] = array ("day" => $day, "dayweek" => $dayweek, "hour" => $hour);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($debug) {
            //$debugval.="\n\nDebug dump of matched expressions:\n".print_r($debug_schedule,true);
            return array ("result" => $inrange_hour, "debug" => $debugval);
        }
        return $inrange_hour;
    }

}
