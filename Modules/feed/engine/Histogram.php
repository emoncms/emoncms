<?php
/**
 * @package EmonCMS.Feeds
 * Emoncms - open source energy visualisation
 *
 * @copyright OpenEnergyMonitor project; See COPYRIGHT.txt
 * @license GNU Affero General Public License; see LICENSE.txt
 * @link http://openenergymonitor.org
 */

defined('EMONCMS_EXEC') or die;

// engine_methods interface in shared_helper.php
include_once dirname(__FILE__) . '/shared_helper.php';

class Histogram implements engine_methods
{
    private $mysqli;

    /**
     * Histogram constructor.
     *
     * @param $mysqli - Instance of mysqli
     */
    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * Creates a histogram type mysql table.
     *
     * @param int $feedid The feedid of the histogram table to be created
     * @param array $options
     * @return bool
     */
    public function create($feedid, $options)
    {
        $feedname = "feed_" . $feedid;
        $result = $this->mysqli->query(
            "CREATE TABLE $feedname (
        time INT UNSIGNED, data float, data2 float,
        INDEX ( `time` )) ENGINE=MYISAM");

        return true;
    }

    /**
     * @param int $feedid
     */
    public function get_meta($feedid)
    {

    }

    /**
     * Get total kwh used at different powers over a time window
     *
     * @param integer $feedid The feedid of the histogram table
     * @param integer $start Start time UNIX Timestamp
     * @param integer $end Start time UNIX Timestamp
     * @return array of power vs kwh
     */
    public function get_power_vs_kwh($feedid, $start, $end)
    {
        $feedid = intval($feedid);
        $start = intval($start);
        $end = intval($end);

        if ($end == 0) $end = time() * 1000;
        $feedname = "feed_" . trim($feedid) . "";
        $start = $start / 1000;
        $end = $end / 1000;
        $data = array();

        // Histogram has an extra dimension so a sum and group by needs to be used.
        $result = $this->mysqli->query("select data2, sum(data) as kWh from $feedname WHERE time>='$start' AND time<'$end' group by data2 order by data2 Asc");

        $data = array();                                    // create an array for them
        while ($row = $result->fetch_array())                // for all the new lines
        {
            $dataValue = $row['kWh'];                       // get the datavalue
            $data2 = $row['data2'];                         // and the instant watts
            $data[] = array($data2, $dataValue);           // add time and data to the array
        }
        return $data;
    }

    /**
     * Get daily kwh used in a power range
     *
     * @param integer $feedid The feedid of the histogram table
     * @param integer $min Minimum power to take into account
     * @param integer $max Maximum power to take into account
     * @return array of kwh per day
     */
    public function get_kwhd_atpower($feedid, $min, $max)
    {
        $feedid = intval($feedid);
        $min = intval($min);
        $max = intval($max);

        $feedname = "feed_" . trim($feedid) . "";
        $result = $this->mysqli->query("SELECT time, sum(data) as kWh FROM `$feedname` WHERE `data2`>='$min' AND `data2`<='$max' group by time");

        $data = array();
        while ($row = $result->fetch_array()) $data[] = array($row['time'] * 1000, $row['kWh']);

        return $data;
    }

    /**
     * Get daily kwh used in multiple power ranges, similar to above.
     *
     * @param integer $feedid The feedid of the histogram table
     * @param array $points Power range division points, ie: 0-1000W-2000W-5000W
     * @return array of multiple kwh per day used at each requested point
     */
    public function get_kwhd_atpowers($feedid, $points)
    {
        $feedid = intval($feedid);
        $feedname = "feed_" . trim($feedid) . "";

        $points = json_decode(stripslashes($points));

        $data = array();

        for ($i = 0; $i < count($points) - 1; $i++) {
            $min = intval($points[$i]);
            $max = intval($points[$i + 1]);

            $result = $this->mysqli->query("SELECT time, sum(data) as kWh FROM `$feedname` WHERE `data2`>='$min' AND `data2`<='$max' group by time");

            while ($row = $result->fetch_array()) {
                if (!isset($data[$row['time']])) {
                    $data[$row['time']] = array(0, 0, 0, 0, 0);
                    $data[$row['time']][0] = (int)$row['time'];
                }
                $data[$row['time']][$i + 1] = (float)$row['kWh'];
            }
        }
        $out = array();
        foreach ($data as $item) $out[] = $item;

        return $out;
    }

    /**
     * @param int $feedid
     * @param int $start
     * @param int $end
     * @param int $outinterval
     * @param string $usertimezone
     * @return mixed|void
     */
    public function csv_export($feedid, $start, $end, $outinterval, $usertimezone)
    {

    }

    /**
     * @param int $feedid
     * @param int $start_time
     * @return array
     */
    public function trim($feedid, $start_time)
    {
        return array('success' => false, 'message' => '"Trim" not available for this storage engine');
    }

    /**
     * @param int $feedid
     * @return array
     */
    public function clear($feedid)
    {
        return array('success' => false, 'message' => '"Clear" not available for this storage engine');
    }

    /**
     * @param int $feedid
     * @return array
     */
    public function delete($feedid)
    {
        return array('success' => false, 'message' => '"Delete" not available for this storage engine');
    }

    /**
     * @param int $feedid
     * @param int $time
     * @param float $value
     * @param null $arg
     * @return array
     */
    public function post($feedid, $time, $value, $arg = null)
    {
        return array('success' => false, 'message' => '"Post" not available for this storage engine');
    }

    /**
     * @param int $feedid
     * @return array
     */
    public function get_feed_size($feedid)
    {
        return array('success' => false, 'message' => '"Get_Feed_Size" not available for this storage engine');
    }

    /**
     * @param int $feedid
     * @param int $time
     * @param float $value
     * @return array
     */
    public function update($feedid, $time, $value)
    {
        return array('success' => false, 'message' => '"Update" not available for this storage engine');
    }

    /**
     * @param int $feedid
     * @param int $start
     * @param int $end
     * @param int $interval
     * @param int $skipmissing
     * @param int $limitinterval
     * @return array
     */
    public function get_data($feedid, $start, $end, $interval, $skipmissing, $limitinterval)
    {
        return array('success' => false, 'message' => '"Get_Data" not available for this storage engine');
    }
}
