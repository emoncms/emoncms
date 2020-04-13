<?php
/**
 * @package EmonCMS.Site
 * Emoncms - open source energy visualisation
 *
 * @copyright OpenEnergyMonitor project; See COPYRIGHT.txt
 * @license GNU Affero General Public License; see LICENSE.txt
 * @link http://openenergymonitor.org
 */

defined('EMONCMS_EXEC') or die;

class TemplateEngine implements engine_methods
{
    private $log;
    private $writebuffer = array();

    /**
     * TemplateEngine constructor.
     * @param $options
     */
    public function __construct($options)
    {
        $this->log = new EmonLogger(__FILE__);
    }

// #### \/ Below are required methods outlined in the engine_methods interface in shared_helper.php

    /**
     * Create feed
     *
     * @param integer $feedid The id of the feed to be created
     * @param array $options for the engine
     * @return bool
     */
    public function create($feedid,$options)
    {
        $this->log->info("create() dummy feed feedid=$feedid");
        return true; // if successful 
    }

    /**
     * Delete feed
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function delete($feedid)
    {

    }

    /**
     * Gets engine metadata
     *
     * @param integer $feedid The id of the feed to be created
     * @return stdClass
     */
    public function get_meta($feedid)
    {
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
     * @param integer $feedid The id of the feed to be created
     * @return int
     */
    public function get_feed_size($feedid)
    {
        return 0;
    }

    /**
     * Adds a data point to the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @param array $arg optional padding mode argument
    */
    public function post($feedid,$time,$value,$arg=null)
    {

    }

    /**
     * Updates a data point in the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function update($feedid,$time,$value)
    {
    
    }

    /**
     * Get array with last time and value from a feed
     *
     * @param integer $feedid The id of the feed
     * @return array
     */
    public function lastvalue($feedid)
    {
        return array('time'=>time(), 'value'=>0);
    }

    /**
     * Return the data for the given timerange - cf shared_helper.php
     *
     * please note that unix timestamps should be expressed in ms cause coming from the js
     * @param $feedid
     * @param $start
     * @param $end
     * @param $interval
     * @param $skipmissing
     * @param $limitinterval
     * @return array
     */
    public function get_data($feedid,$start,$end,$interval,$skipmissing,$limitinterval)
    {
        $data = array();

        // example of datapoint format
        $time = time() * 1000; // time in milliseconds
        $value = 123.4; 
        $data[] = array($time,$value);

        return $data;
    }

    /**
     * @param $feedid
     * @param $start
     */
    public function export($feedid,$start)
    {

    }

    /**
     * @param int $feedid
     * @param int $start
     * @param int $end
     * @param int $outinterval
     * @param string $usertimezone
     * @return mixed|void
     */
    public function csv_export($feedid,$start,$end,$outinterval,$usertimezone)
    {

    }

// #### /\ Above are required methods


// #### \/ Below are buffer write methods

    /**
     * Insert data in post write buffer, parameters like post()
     *
     * @param $feedid
     * @param $time
     * @param $value
     * @param null $arg
     */
    public function post_bulk_prepare($feedid,$time,$value,$arg=null)
    {
        $this->writebuffer[(int)$feedid][] = array((int)$time,$value);
    }

    /**
     * Saves post buffer to engine in bulk
     * Writing data in larger blocks saves reduces disk write load
     */
    public function post_bulk_save()
    {
        foreach ($this->writebuffer as $feedid=>$data) {
        // $this->someSaveMechanism->array($data[$p][0],$data[$p][1]);
        }
    }


// #### \/ Below engine public specific methods


// #### \/ Bellow are engine private methods    

    /**
     * @inheritDoc
     */
    public function clear($feedid)
    {
        // TODO: Implement clear() method.
    }

    /**
     * @inheritDoc
     */
    public function trim($feedid, $start_time)
    {
        // TODO: Implement trim() method.
    }
}
