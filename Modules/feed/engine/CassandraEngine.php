<?php
/**
 * @package EmonCMS.Feeds
 * Emoncms - open source energy visualisation
 *
 * @copyright OpenEnergyMonitor project; See COPYRIGHT.txt
 * @license GNU Affero General Public License; see LICENSE.txt
 * @link http://openenergymonitor.org
 */

use Cassandra\Rows;

defined('EMONCMS_EXEC') or die;

// engine_methods interface in shared_helper.php
include_once dirname(__FILE__) . '/shared_helper.php';

/**
 * CassandraEngine
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CassandraEngine implements engine_methods
{
    const ONE_TABLE_PER_FEED = false;
    protected $cluster;
    protected $session;
    protected $log;

    /**
     *CassandraEngine constructor.
     *
     * @param $settings
     */
    public function __construct($settings)
    {
        $keyspace = isset($settings['keyspace']) ? $settings['keyspace'] : 'emoncms';

        $this->cluster = Cassandra::cluster()                 // connects to localhost by default
        ->build();
        $this->session = $this->cluster->connect($keyspace);  // create session, optionally scoped to a keyspace

        $this->log = new EmonLogger(__FILE__);
    }

    /**
     * Create feed
     *
     * @param int $feedid The id of the feed to be created
     * @param array $options for the engine
     * @return bool
     * {@inheritdoc}
     */
    public function create($feedid, $options)
    {
        $feedid = (int)$feedid;
        $feedname = $this->feedtable($feedid);
        $this->execCQL("CREATE TABLE IF NOT EXISTS $feedname (feed_id int, day int, time bigint, data float, PRIMARY KEY ((feed_id,day), time)) WITH CLUSTERING ORDER BY (time ASC)");
        return true;
    }

    /**
     * @param $feedid
     * @return string
     */
    private function feedtable($feedid)
    {
        $feedid = (int)$feedid;
        if ($this::ONE_TABLE_PER_FEED) {
            return "feed_" . trim($feedid) . "";
        }
        return "feed";
    }

    /**
     * @param $cql
     * @return Rows|null
     */
    private function execCQL($cql)
    {
        $statement = new Cassandra\SimpleStatement($cql);
        $future = $this->session->executeAsync($statement);  // fully asynchronous and easy parallel execution
        $result = $future->get();                            // wait for the result, with an optional timeout
        return $result;
    }

    /**
     * Delete feed
     *
     * @param int $feedid The id of the feed to be created
     */
    public function delete($feedid)
    {
        $feedid = (int)$feedid;
        $feedname = $this->feedtableToDrop($feedid);
        if ($feedname) {
            $this->execCQL("DROP TABLE $feedname");
        }
    }

    /**
     * @param $feedid
     * @return bool|string
     */
    private function feedtableToDrop($feedid)
    {
        $feedid = (int)$feedid;
        if ($this::ONE_TABLE_PER_FEED) {
            return "feed_" . trim($feedid) . "";
        }
        return false;
    }

    /**
     * Gets engine metadata
     *
     * @param int $feedid The id of the feed to be created
     * @return stdClass
     */
    public function get_meta($feedid)
    {
        $feedid = (int)$feedid;
        $meta = new stdClass();
        $meta->id = $feedid;
        $meta->start_time = 0;
        $meta->nlayers = 1;
        $meta->npoints = -1;
        $meta->interval = 1;
        return $meta;
    }

    /**
     * Returns engine occupied size in bytes
     *
     * @param int $feedid The id of the feed to be created
     * @return int
     */
    public function get_feed_size($feedid)
    {
        $feedid = (int)$feedid;
        $tablesize = 0;
        return $tablesize;
    }

    /**
     * Adds a data point to the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @param null $arg optional padding mode argument
     */
    public function post($feedid, $time, $value, $arg = null)
    {
        $feedid = (int)$feedid;
        $time = (int)$time;
        $value = (float)$value;

        $feedname = $this->feedtable($feedid);
        $day = $this->unixtoday($time);

        $this->execCQL("INSERT INTO $feedname(feed_id,day,time,data) VALUES($feedid,$day,$time,$value)");
    }

    /**
     * @param $unixtime
     * @return false|float
     */
    private function unixtoday($unixtime)
    {
        return floor($unixtime / 86400);
    }

    /**
     * Updates a data point in the feed
     *
     * @param int $feedid The id of the feed to add to
     * @param int $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @return float
     */
    public function update($feedid, $time, $value)
    {
        $feedid = (int)$feedid;
        $time = (int)$time;
        $value = (float)$value;

        $feedname = $this->feedtable($feedid);
        $day = $this->unixtoday($time);
        $this->execCQL("UPDATE $feedname SET data = $value WHERE feed_id = $feedid AND day = $day AND time = $time");
        return $value;
    }

    /**
     * Get array with last time and value from a feed
     *
     * @param $feedid - The id of the feed
     * @return array|bool
     */
    public function lastvalue($feedid)
    {
        $feedid = (int)$feedid;
        $feedname = $this->feedtable($feedid);
        $result = $this->execCQL("SELECT max(day) AS max_day FROM $feedname WHERE feed_id=$feedid");
        if ($result && count($result) > 0) {
            $row = $result[0];
            $max_day = $row['max_day'];
            $result = $this->execCQL("SELECT time, data FROM $feedname WHERE feed_id=$feedid and day=$max_day ORDER BY time DESC LIMIT 1");
            if ($result && count($result) > 0) {
                $row = $result[0];
                if ($row['data'] !== null) $row['data'] = (float)$row['data'];
                return array('time' => (int)$row['time'], 'value' => $row['data']);
            }
        }
        return false;
    }

    /**
     * Return the data for the given timerange - cf shared_helper.php
     *
     * @param int $feedid
     * @param int $start
     * @param int $end
     * @param int $interval
     * @param int $skipmissing
     * @param int $limitinterval not implemented
     * @return array
     */
    public function get_data($feedid, $start, $end, $interval, $skipmissing, $limitinterval)
    {
        global $settings; // max_datapoints;

        $feedid = (int)$feedid;
        $start = round($start / 1000);
        $end = round($end / 1000);
        $interval = intval($interval);
        $feedname = $this->feedtable($feedid);
        // Minimum interval
        if ($interval < 1) $interval = 1;
        // Maximum request size
        $req_dp = round(($end - $start) / $interval);
        if ($req_dp > $settings["feed"]["max_datapoints"]) return array('success' => false, 'message' => "Request datapoint limit reached (" . $settings["feed"]["max_datapoints"] . "), increase request interval or time range, requested datapoints = $req_dp");

        $day_range = range($this->unixtoday($start), $this->unixtoday($end));
        $data = array();
        $result = $this->execCQL("SELECT time, data FROM $feedname WHERE feed_id=$feedid AND day IN (" . implode($day_range, ',') . ") AND time >= $start AND time <= $end");
        $dp_time = $start;
        while ($result) {
            foreach ($result as $row) {
                $time = $row['time'];
                $dataValue = $row['data'];
                if ($time >= $dp_time) {
                    if ($dataValue != NULL || $skipmissing === 0) { // Remove this to show white space gaps in graph
                        if ($dataValue !== null) $dataValue = (float)$dataValue;
                        $data[] = array($time * 1000, $dataValue);
                    }
                    $dp_time += $interval;
                }
            }
            $result = $result->nextPage();
        }
        return $data;
    }

    /**
     * @param $feedid
     * @param $start
     */
    public function export($feedid, $start)
    {
        $feedid = (int)$feedid;
        $start = (int)$start;
        $this->log->info("export($feedid,$start)");
        // TODO implement
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
        $feedid = (int)$feedid;
        $start = (int)$start;
        $end = (int)$end;
        $outinterval = (int)$outinterval;

        $this->log->info("csv_export($feedid,$start,$end,$outinterval)");  // add: $usertimezone
        // TODO implement
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
     * @param $feedid
     * @param $time
     */
    public function delete_data_point($feedid, $time)
    {
        $feedid = (int)$feedid;
        $time = (int)$time;
        $day = $this->unixtoday($time);

        $feedname = $this->feedtable($feedid);
        $this->execCQL("DELETE FROM $feedname WHERE feed_id = $feedid AND day = $day AND time = $time");
    }

    /**
     * @param $feedid
     * @param $start
     * @param $end
     * @return bool
     */
    public function deletedatarange($feedid, $start, $end)
    {
        $feedid = (int)$feedid;
        $start = intval($start / 1000.0);
        $end = intval($end / 1000.0);
        $day_range = range($this->unixtoday($start), $this->unixtoday($end));

        $feedname = $this->feedtable($feedid);
        $this->execCQL("DELETE FROM $feedname WHERE feed_id=$feedid AND day IN (" . implode($day_range, ',') . ") AND time >= $start AND time <= $end");
        return true;
    }

}
