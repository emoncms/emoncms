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
/**
 * Required methods for each engine (Template/Interface for what methods are required)
 * 
 * custom engine methods can be added by extending a new interface with this one. 
 * use the newly extended interface to implement the custom engine class
 */
interface engine_methods{
    
    /**
     * Create feed
     *
     * @param integer $feedid The id of the feed to be created
     * @param array $options for the engine
    */
    public function create($feedid,$options);
    
    /**
     * Delete feed
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function delete($feedid);
    
    /**
     * Gets engine metadata
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_meta($feedid);
    
    /**
     * Returns engine occupied size in bytes
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_feed_size($feedid);
    
    /**
     * Adds a data point to the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @param array $arg optional padding mode argument
    */
    public function post($feedid,$feedtime,$value,$arg);
    
    /**
     * Updates a data point in the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function update($feedid,$feedtime,$value);
    
    /**
     * Return the data for the given timerange
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $interval The number os seconds for each data point to return (used by some engines)
     * @param integer $skipmissing Skip null values from returned data (used by some engines)
     * @param integer $limitinterval Limit datapoints returned to this value (used by some engines)
    */
    public function get_data($feedid,$start,$end,$interval,$skipmissing,$limitinterval);
    
    /**
     * return data in csv format
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $outinterval The number of seconds for each data point to return 
     * @param mixed One of the supported timezone names or an offset value (+0200). 
     * @return void
     */

    /**
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $outinterval output data point interval
     * @param string $usertimezone a name for a php timezone eg. "Europe/London"
     * @see http://php.net/manual/en/timezones.php
     * @return void
     */
    public function csv_export($feedid,$start,$end,$outinterval,$usertimezone);    
    
    /**
     * delete all past data for a feed. keeping all the feed settings the same
     * 
     * a new feed starttime of "[CURRENT_TIMESTAMP]" is created 
     *
     * @param integer $feedid The id of the feed to fetch from
     * @return void
     */
    public function clear($feedid);
    
    /**
     * delete past data for a feed up to a point.
     * 
     * a new feed starttime of "$start_time" is created 
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start_time The unix timestamp in ms of the start of the data range
     * @return void
     */
    public function trim($feedid, $start_time);
}