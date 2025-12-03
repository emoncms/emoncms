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
        try {
            $this->log = new EmonLogger(__FILE__);
        } catch (Exception $e) {
            error_log("Logger initialization failed: " . $e->getMessage());
        }
    }

// #### \/ Below are required methods outlined in the engine_methods interface in shared_helper.php

    /**
     * Create feed
     *
     * @param integer $feedid The id of the feed to be created
     * @param array $options for the engine
    */
 public function create($feedid, $options)
    {
        try {
            // Your create logic
            return true;
        } catch (Exception $e) {
            $this->log->error("Create feed failed for feed $feedid: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete feed
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function delete($feedid)
    {
        try {
            // Delete logic
        } catch (Exception $e) {
            $this->log->error("Delete feed failed for feed $feedid: " . $e->getMessage());
        }
    }

    /**
     * Gets engine metadata
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_meta($feedid)
    {
        try {
            $meta = new stdClass();
            $meta->id = $feedid;
            $meta->start_time = 0;
            $meta->nlayers = 1;
            $meta->npoints = -1;
            $meta->interval = 1;
            return $meta;
        } catch (Exception $e) {
            $this->log->error("Get meta failed for feed $feedid: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Returns engine occupied size in bytes
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function get_feed_size($feedid)
    {
        try {
            return 0;
        } catch (Exception $e) {
            $this->log->error("Get feed size failed for feed $feedid: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Adds or updates a data point
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @param array $padding_mode optional padding mode argument
    */
      public function post($feedid, $time, $value, $padding_mode = null)
    {
        try {
            // Post logic
        } catch (Exception $e) {
            $this->log->error("Post failed for feed $feedid at $time: " . $e->getMessage());
        }
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
    public function scalerange($id, $start, $end, $scale)
    {
        try {
            // Scale logic
        } catch (Exception $e) {
            $this->log->error("Scalerange failed for feed $id: " . $e->getMessage());
        }
    }

    /**
     * Get array with last time and value from a feed
     *
     * @param integer $feedid The id of the feed
    */
    public function lastvalue($feedid)
    {
        try {
            return array('time' => time(), 'value' => 0);
        } catch (Exception $e) {
            $this->log->error("Last value failed for feed $feedid: " . $e->getMessage());
            return null;
        }
    }
    /**
     * Get value at specified time
     *
     * @param integer $feedid The id of the feed
     * @param integer $time in seconds
    */
    public function get_value($feedid, $time)
    {
        try {
            return null;
        } catch (Exception $e) {
            $this->log->error("Get value failed for feed $feedid at $time: " . $e->getMessage());
            return null;
        }
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
public function get_data_combined($id, $start, $end, $interval, $average=0, $timezone="UTC", $timeformat="unix", $csv=false, $skipmissing=0, $limitinterval=1)
    {
        try {
            $id = (int)$id;
            $skipmissing = (int)$skipmissing;
            $limitinterval = (int)$limitinterval;

            $start = (int)$start;
            $end = (int)$end;

            if ($end <= $start) return array('success'=>false, 'message'=>"request end time before start time");

            if ($timezone === 0) $timezone = "UTC";

            if ($csv) {
                require_once "Modules/feed/engine/shared_helper.php";
                $helperclass = new SharedHelper($settings['feed']);
                $helperclass->set_time_format($timezone, $timeformat);
            }

            $notime = ($timeformat === "notime");

            if (in_array($interval, array("weekly","daily","monthly","annual"))) {
                $fixed_interval = false;
                $date = new DateTime();
                $date->setTimezone(new DateTimeZone($timezone));
                $date->setTimestamp($start);
                $date->modify("midnight");
                $modify = "+1 day";
                if ($interval=="weekly") {
                    $date->modify("this monday");
                    $modify = "+1 week";
                } elseif ($interval=="monthly") {
                    $date->modify("first day of this month");
                    $modify = "+1 month";
                } elseif ($interval=="annual") {
                    $date->modify("first day of january this year");
                    $modify = "+1 year";
                }
                $time = $date->getTimestamp();
            } else {
                $fixed_interval = true;
                $interval = max(1, (int)$interval);
                $time = $start;
            }

            if ($csv) {
                $helperclass->csv_header($id);
            } else {
                $data = array();
            }

            while($time <= $end) {
                $div_start = $time;
                $div_end = $fixed_interval ? $time + $interval : $date->modify($modify)->getTimestamp();
                $value = 100; // Placeholder

                if ($csv) {
                    $helperclass->csv_write($div_start, $value);
                } else if ($notime) {
                    $data[] = $value;
                } else {
                    $data[] = array($div_start, $value);
                }

                $time = $div_end;
            }

            if ($csv) {
                $helperclass->csv_close();
                exit;
            } else {
                return $data;
            }
        } catch (Exception $e) {
            $this->log->error("Get data combined failed for feed $id: " . $e->getMessage());
            return array('success'=>false, 'message'=>$e->getMessage());
        }
    }

    public function get_data_DMY_time_of_day($id, $start, $end, $mode, $timezone, $timeformat, $split)
    {
        try {
            // Logic
        } catch (Exception $e) {
            $this->log->error("Get DMY time of day failed for feed $id: " . $e->getMessage());
            return null;
        }
    }

    public function export($feedid, $start)
    {
        try {
            // Export logic
        } catch (Exception $e) {
            $this->log->error("Export failed for feed $feedid: " . $e->getMessage());
            return false;
        }
    }
// #### /\ Above are required methods


// #### \/ Below are buffer write methods

    // Insert data in post write buffer, parameters like post()
    public function post_bulk_prepare($feedid, $time, $value, $padding_mode=null)
    {
        try {
            $this->writebuffer[(int)$feedid][] = array((int)$time, $value);
        } catch (Exception $e) {
            $this->log->error("Post bulk prepare failed for feed $feedid at $time: " . $e->getMessage());
        }
    }

    // Saves post buffer to engine in bulk
    // Writing data in larger blocks saves reduces disk write load
    public function post_bulk_save()
    {
        try {
            foreach ($this->writebuffer as $feedid => $data) {
                // Save logic
            }
        } catch (Exception $e) {
            $this->log->error("Post bulk save failed: " . $e->getMessage());
        }
    }

    public function upload_fixed_interval($id, $start, $interval, $npoints)
    {
        try {
            // Logic
        } catch (Exception $e) {
            $this->log->error("Upload fixed interval failed for feed $id: " . $e->getMessage());
        }
    }
    public function upload_variable_interval($feedid, $npoints)
    {
        try {
            // Logic
        } catch (Exception $e) {
            $this->log->error("Upload variable interval failed for feed $feedid: " . $e->getMessage());
        }
    }


    /**
     * Clear feed
     *
     * @param integer $feedid
     * @return boolean true == success
     */
    public function clear($feedid)
    {
        try {
            // Clear logic
        } catch (Exception $e) {
            $this->log->error("Clear feed failed for feed $feedid: " . $e->getMessage());
            return false;
        }
    }

    /**
     * clear out data from file before $start_time
     *
     * @param integer $feedid
     * @param integer $start_time new timestamp to start the feed data from
     * @return boolean
     */
    public function trim($feedid, $start_time)
    {
        try {
            // Trim logic
        } catch (Exception $e) {
            $this->log->error("Trim feed failed for feed $feedid: " . $e->getMessage());
            return false;
        }
    }
}
// #### \/ Below engine public specific methods


// #### \/ Bellow are engine private methods


