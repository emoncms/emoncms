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

    // Module required process configuration, $list array index position is not used, function name is used instead
    public function process_list()
    {
        // Note on engine selection

        // The engines listed against each process must be the supported engines for each process - and are only used in the input and node config GUI dropdown selectors
        // By using the create feed api and input set processlist its possible to create any feed type with any process list combination.
        // Only feeds capable of using a particular processor are displayed to the user and can be selected from the gui.
        // Daily datatype automaticaly adjust feed interval to 1d and user cant change it from gui.
        // If there is only one engine available for a processor, it is selected and user cant change it from gui.
        // The default selected engine is the first in the array of the supported engines for each processor.
        // Virtual feeds are feeds that are calculed in realtime when queried and use a processlist as post processor. 
        // Processors that write or update a feed are not supported and hidden from the gui on the context of virtual feeds.

        // 0=>Name | 1=>Arg type | 2=>function | 3=>No. of datafields if creating feed | 4=>Datatype | 5=>Group | 6=>Engines | 'requireredis'=>true | 'desc'=>Description
        $list[] = array(_("If !schedule, ZERO"), ProcessArg::SCHEDULEID, "if_not_schedule_zero", 0, DataType::UNDEFINED, "Schedule", 'desc'=>"<p>Validates if time is NOT in range of schedule. If NOT in schedule, value is ZEROed.</p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same processing list</p>");
        $list[] = array(_("If !schedule, NULL"), ProcessArg::SCHEDULEID, "if_not_schedule_null", 0, DataType::UNDEFINED, "Schedule", 'desc'=>"<p>Validates if time is NOT in range of schedule. If NOT in schedule, value is NULLed.</p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same processing list</p>");
        $list[] = array(_("If schedule, ZERO"),  ProcessArg::SCHEDULEID, "if_schedule_zero",     0, DataType::UNDEFINED, "Schedule", 'desc'=>"<p>Validates if time is in range of schedule. If in schedule, value is ZEROed.</p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same processing list</p>");
        $list[] = array(_("If schedule, NULL"),  ProcessArg::SCHEDULEID, "if_schedule_null",     0, DataType::UNDEFINED, "Schedule", 'desc'=>"<p>Validates if time is in range of schedule. If in schedule, value is NULLed.</p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same processing list</p>");
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
