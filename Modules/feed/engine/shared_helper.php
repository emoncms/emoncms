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

class SharedHelper
{
    public function getTimeZoneFormated($time_in,$timezone) {
        if ($timezone) {
            $time = DateTime::createFromFormat("U", (int)$time_in);
            $time->setTimezone(new DateTimeZone($timezone));
            return $time->format("d/m/Y H:i:s");
        } else {
            return $time_in;
        }
    }
}