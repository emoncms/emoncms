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
