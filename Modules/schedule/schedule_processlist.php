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

// Schedule Processlist Module
class Schedule_ProcessList
{
    private $log;
    private $schedule;

    // Module required constructor, receives parent as reference
    public function __construct(&$parent)
    {
        $this->log = new EmonLogger(__FILE__);

        include_once "Modules/schedule/schedule_model.php";
        $this->schedule = new Schedule($parent->mysqli, $parent->timezone);
    }

    public function process_list() {
        
        $list = array(
          array(
            "name"=>"If !schedule, ZERO",
            "short"=>"!sched 0",
            "argtype"=>ProcessArg::SCHEDULEID,
            "function"=>"if_not_schedule_zero",
            "datafields"=>0,
            "datatype"=>DataType::UNDEFINED,
            "unit"=>"",
            "group"=>"Schedule",
            "description"=>"<p>Validates if time is NOT in range of schedule. If NOT in schedule, value is ZEROed.<\/p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same processing list<\/p>"
          ),
          array(
            "name"=>"If !schedule, NULL",
            "short"=>"!sched N",
            "argtype"=>ProcessArg::SCHEDULEID,
            "function"=>"if_not_schedule_null",
            "datafields"=>0,
            "datatype"=>DataType::UNDEFINED,
            "unit"=>"",
            "group"=>"Schedule",
            "description"=>"<p>Validates if time is NOT in range of schedule. If NOT in schedule, value is NULLed.<\/p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same processing list<\/p>"
          ),
          array(
            "name"=>"If schedule, ZERO",
            "short"=>"sched 0",
            "argtype"=>ProcessArg::SCHEDULEID,
            "function"=>"if_schedule_zero",
            "datafields"=>0,
            "datatype"=>DataType::UNDEFINED,
            "unit"=>"",
            "group"=>"Schedule",
            "description"=>"<p>Validates if time is in range of schedule. If in schedule, value is ZEROed.<\/p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same processing list<\/p>"
          ),
          array(
            "name"=>"If schedule, NULL",
            "short"=>"sched N",
            "argtype"=>ProcessArg::SCHEDULEID,
            "function"=>"if_schedule_null",
            "datafields"=>0,
            "datatype"=>DataType::UNDEFINED,
            "unit"=>"",
            "group"=>"Schedule",
            "description"=>"<p>Validates if time is in range of schedule. If in schedule, value is NULLed.<\/p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same processing list<\/p>"
          )
        );
        return $list;
    }
    // \/ Below are functions of this module processlist, same name must exist on process_list()
    
    public function if_not_schedule_zero($scheduleid, $time, $value) {
        $result = $this->schedule->match($scheduleid, $time);
        return ($result ? $value : 0);
    }
    public function if_not_schedule_null($scheduleid, $time, $value) {
        $result = $this->schedule->match($scheduleid, $time);
        return ($result ? $value : null);
    }
    public function if_schedule_zero($scheduleid, $time, $value) {
        $result = $this->schedule->match($scheduleid, $time);
        return ($result ? 0 : $value);
    }
    public function if_schedule_null($scheduleid, $time, $value) {
        $result = $this->schedule->match($scheduleid, $time);
        return ($result ? null : $value);
    }
}
