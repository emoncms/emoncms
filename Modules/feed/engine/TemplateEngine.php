<?php

class TemplateEngine
{
    private $dir = "";

    /**
     * Constructor.
     *
     * @api
    */
    public function __construct($options)
    {

    }

    /**
     * Create feed
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function create($feedid,$options)
    {
    
        return true; // if successful 
    }


    /**
     * Adds a data point to the feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function post($feedid,$time,$value)
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
     * Return the data for the given timerange
     *
     * @param integer $feedid The id of the feed to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $dp The number of data points to return (used by some engines)
    */
    public function get_data($feedid,$start,$end,$dp)
    {
        $data = array();

        // example of datapoint format
        $time = time() * 1000; // time in milliseconds
        $value = 123.4; 
        $data[] = array($time,$value);

        return $data;
    }

    /**
     * Get the last value from a feed
     *
     * @param integer $feedid The id of the feed
    */
    public function lastvalue($feedid)
    {
        // time returned as date (to be changed to unixtimestamp in future)
        return array('time'=>date("Y-n-j H:i:s",0), 'value'=>0);
    }
    
    public function export($feedid,$start)
    {
    
    }
    
    public function delete($feedid)
    {
    
    }
    
    public function get_feed_size($feedid)
    {
    
    }
    
    public function get_meta($feedid)
    {
    
    }
    
    public function csv_export($feedid,$start,$end,$outinterval)
    {
    
    }

}
