<?php

class TemplateEngine
{
    private $log;
    private $writebuffer = array();

    /**
     * Constructor.
     *
     * @api
    */
    public function __construct($options)
    {
        $this->log = new EmonLogger(__FILE__);
    }

// #### \/ Below are required methods

    /**
     * Create feed
     *
     * @param integer $feedid The id of the feed to be created
     * @param array $options for the engine
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
    */
    public function lastvalue($feedid)
    {
        return array('time'=>time(), 'value'=>0);
    }

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
    public function get_data($feedid,$start,$end,$interval,$skipmissing,$limitinterval)
    {
        $data = array();

        // example of datapoint format
        $time = time() * 1000; // time in milliseconds
        $value = 123.4; 
        $data[] = array($time,$value);

        return $data;
    }

    public function export($feedid,$start)
    {

    }

    public function csv_export($feedid,$start,$end,$outinterval,$usertimezone)
    {

    }

// #### /\ Above are required methods


// #### \/ Below are buffer write methods

    // Insert data in post write buffer, parameters like post()
    public function post_bulk_prepare($feedid,$time,$value,$arg=null)
    {
        $this->writebuffer[(int)$feedid][] = array((int)$time,$value);
    }

    // Saves post buffer to engine in bulk
    // Writing data in larger blocks saves reduces disk write load
    public function post_bulk_save()
    {
        foreach ($this->writebuffer as $feedid=>$data) {
        // $this->someSaveMechanism->array($data[$p][0],$data[$p][1]);
        }
    }


// #### \/ Below engine public specific methods


// #### \/ Bellow are engine private methods    

}
