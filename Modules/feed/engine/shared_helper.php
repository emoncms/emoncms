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
    private $export_fh;
    private $csv_field_separator;
    private $csv_decimal_places;
    private $csv_decimal_place_separator;
    private $timezone;
    private $timeformat;
    private $date;
    
    public function __construct($feed_settings=false)
    {
        if ($feed_settings) {
            $this->csv_field_separator = $feed_settings["csv_field_separator"];
            $this->csv_dp = $feed_settings["csv_decimal_places"];
            $this->csv_dp_separator = $feed_settings["csv_decimal_place_separator"];
        }
    }
    
    public function set_time_format($timezone,$timeformat) {
        $this->timezone = $timezone;
        $this->timeformat = $timeformat;

        $this->date = new DateTime();
        $this->date->setTimezone(new DateTimeZone($timezone));
    }
    
    public function format_time($timestamp) {
        if ($this->timeformat=="excel") {
            $this->date->setTimestamp($timestamp);
            return $this->date->format("d/m/Y H:i:s");
        } else if ($this->timeformat=="iso8601") {
            $this->date->setTimestamp($timestamp);
            return $this->date->format("c");
        } else {
            return $timestamp;
        }
    }
        
    public function csv_header($feedid) {
        // check for cli here allows removes header errors when testing with command line
        if (php_sapi_name() != 'cli') {
            // There is no need for the browser to cache the output
            header("Cache-Control: no-cache, no-store, must-revalidate");
            // Tell the browser to handle output as a csv file to be downloaded
            header('Content-Description: File Transfer');
            header("Content-type: application/octet-stream");
            $filename = $feedid.".csv";
            header("Content-Disposition: attachment; filename={$filename}");
            header("Expires: 0");
            header("Pragma: no-cache");
        }
        // Write to output stream
        $this->export_fh = @fopen( 'php://output', 'w' );
    }
    
    public function csv_write($time,$value) {
        $time = $this->format_time($time);
        if ($value!=null) {
            $value = number_format($value,$this->csv_dp,$this->csv_dp_separator,'');
        } else {
            $value = 'null';
        }
        fwrite($this->export_fh,$time.$this->csv_field_separator.$value."\n");
    }
    
    public function csv_write_multi($values) {
        // $values[0] = $this->format_time($values[0]);
        
        for ($z=1; $z<count($values); $z++) {
            if ($values[$z]==null) {
                $values[$z] = 'null';
            } else {
                $values[$z] = number_format($values[$z],$this->csv_dp,$this->csv_dp_separator,'');
            }
        }
        fwrite($this->export_fh,implode($this->csv_field_separator,$values)."\n");
    }
    
    public function csv_close() {
        fclose($this->export_fh);
    }

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
     * Get value at specified time
     *
     * @param integer $feedid The id of the feed
     * @param integer $time in seconds
    */
    // public function get_value($feedid,$time);
    
    /**
     * Return the data for the given timerange
     *
     * @param integer $id The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $interval output data point interval
     * @param integer $average enabled/disable averaging
     * @param string $timezone a name for a php timezone eg. "Europe/London"
     * @param string $timeformat csv datetime format e.g: unix timestamp, excel, iso8601
     * @param integer $csv pipe output as csv
     * @param integer $skipmissing skip null datapoints
     * @param integer $limitinterval limit interval to feed interval
     * @return void or array
     */
    public function get_data_combined($feedid,$start,$end,$interval,$average,$timezone,$timeformat,$csv,$skipmissing,$limitinterval);
    
    /**
     * delete all past data for a feed. keeping all the feed settings the same
     * 
     * a new feed starttime of "[CURRENT_TIMESTAMP]" is created 
     *
     * @param integer $feedid The id of the feed to fetch from
     * @return array associative array with success and message
     */
    public function clear($feedid);
    
    /**
     * delete past data for a feed up to a point.
     * 
     * a new feed starttime of "$start_time" is created 
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start_time The unix timestamp in ms of the start of the data range
     * @return array associative array with success and message
     * 
     */
    public function trim($feedid, $start_time);
}
