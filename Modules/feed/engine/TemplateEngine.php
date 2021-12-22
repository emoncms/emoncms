<?php

// engine_methods interface in shared_helper.php
include_once dirname(__FILE__) . '/shared_helper.php';

class TemplateEngine implements engine_methods
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

// #### \/ Below are required methods outlined in the engine_methods interface in shared_helper.php

    /**
     * Create feed
     *
     * @param integer $feedid The id of the feed to be created
     * @param array $options for the engine
    */
    public function create($feedid,$options)
    {
        return true;
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
     * Adds or updates a data point
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
     * scale a portion of a feed
     * added by Alexandre CUER - january 2019 
     *
     * @param integer $feedid The id of the feed
     * @param integer $start unix time stamp in ms of the start of the data range
     * @param integer $end unix time stamp in ms of the end of the data rage
     * @param float $scale : numeric value for the scaling 
    */
    public function scalerange($id,$start,$end,$scale)
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
     * Get value at specified time
     *
     * @param integer $feedid The id of the feed
     * @param integer $time in seconds
    */
    public function get_value($feedid,$time)
    {
        return null;
    }

    /**
     * Return the data for the given timerange - cf shared_helper.php
     *
     * please note that unix timestamps should be expressed in ms cause coming from the js
     *
     * It is important that the response to this function adheers to the convention outlined below so that data can then be used consistently within the rest of the emoncms application.
     * 
     * The request defines the timestamps and number of datapoints that should be returned rather than necessarily the exact timestamp of the recorded data
     * It is the goal of the function below to find the closest data point/s that represent the request timestamp/interval. 
     *
     * Aligned timestamps returned across multiple feeds allows for easy post processing such as calculating grid import/export from solar generation and consumption data or
     * heat pump COP from electric consumption and heat output data. CSV export in multiple columns and stacking of feeds in graphs are also made easier.
     *
     * While there are applications where returning the exact timestamp of the recorded data is important, this is currently outside of the design goals of the emoncms application.
    */
    public function get_data_combined($id,$start,$end,$interval,$average=0,$timezone="UTC",$timeformat="unix",$csv=false,$skipmissing=0,$limitinterval=1)
    {
        $id = (int) $id;
        $skipmissing = (int) $skipmissing;
        $limitinterval = (int) $limitinterval;
        
        $start = (int) $start;
        $end = (int) $end;

        if ($end<=$start) return array('success'=>false, 'message'=>"request end time before start time");
        
        if ($timezone===0) $timezone = "UTC";
       
        if ($csv) {
            require_once "Modules/feed/engine/shared_helper.php";
            $helperclass = new SharedHelper($settings['feed']);
            $helperclass->set_time_format($timezone,$timeformat);
        }

        // The first section here deals with the timezone aligned interval codes
        // the start time is modified to align to the nearest day, week, month or year
        // later the while loop is advanced by the value in the $modify string
        // all using php DateTime aligned to user/feed timezone
        if (in_array($interval,array("weekly","daily","monthly","annual"))) {
            $fixed_interval = false;
            // align to day, month, year
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone($timezone));
            $date->setTimestamp($start);
            $date->modify("midnight");
            $modify = "+1 day";
            if ($interval=="weekly") {
                $date->modify("this monday");
                $modify = "+1 week";
            } else if ($interval=="monthly") {
                $date->modify("first day of this month");
                $modify = "+1 month";
            } else if ($interval=="annual") {
                $date->modify("first day of january this year");
                $modify = "+1 year";
            }
            // Set time to start 
            $time = $date->getTimestamp();
        } else {
            // If interval codes are not specified then we advanced by a fixed numeric interval 
            $fixed_interval = true;
            // Interval must be integer
            $interval = (int) $interval;
            // Interval should not be less than 1 second
            if ($interval<1) $interval = 1;
            // May want to limit to data interval here if feed engine has a fixed interval type
            // Set time to start  
            $time = $start;
        }

        if ($csv) {
            $helperclass->csv_header($id);
        } else {
            $data = array();
        }

        while($time<=$end)
        {
            // Start time of interval/division
            $div_start = $time;
            
            // calculate start of next interval 
            if ($fixed_interval) {
                $div_end = $time + $interval;
            } else {
                $date->modify($modify);
                $div_end = $date->getTimestamp();
            }
            
            // Read in value here from data file, database, timeseries interface
            // If average = 0, find nearest value that is >= div_start && < div_end
            // If average = 1, find average of values that are >= div_start && < div_end
            $value = 100;
            
            // Write as csv or array
            if ($csv) { 
                $helperclass->csv_write($div_start,$value);
            } else {
                $data[] = array($div_start,$value);
            }

            // Advance position 
            $time = $div_end;        
        }
        
        if ($csv) {
            $helperclass->csv_close();
            exit;
        } else {
            return $data;
        }
    }
    
    public function get_data_DMY_time_of_day($id,$start,$end,$mode,$timezone,$split) 
    {
    
    }

    public function export($feedid,$start)
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
    
    public function upload_fixed_interval($id,$start,$interval,$npoints)
    {
    
    }
    
    public function upload_variable_interval($feedid,$npoints)
    {
    
    }
    
    /**
     * Clear feed
     *
     * @param integer $feedid
     * @return boolean true == success
     */
    public function clear($feedid)
    {
    
    }
    
    /**
     * clear out data from file before $start_time
     *
     * @param integer $feedid
     * @param integer $start_time new timestamp to start the feed data from
     * @return boolean
     */
    public function trim($feedid,$start_time) 
    {
    
    }
// #### \/ Below engine public specific methods


// #### \/ Bellow are engine private methods    

}
