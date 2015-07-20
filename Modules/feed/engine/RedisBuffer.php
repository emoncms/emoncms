<?php

class RedisBuffer
{
    private $log;
    private $redis;
    private $feed;
    
    /**
     * Constructor.
     *
     * @api
    */
    public function __construct($redis,$options,$feed)
    {
        $this->redis = $redis;
        $this->feed = $feed;
        $this->log = new EmonLogger(__FILE__);
    }

    /**
     * Create feed
     *
     * @param integer $feedid The id of the feed to be created
    */
    public function create($feedid,$options)
    {
        return true;
    }


    /**
     * Adds a data point to the buffer feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @param args array of optional arguments
    */
    public function post($feedid,$time,$value,$args=null)
    {
        $arg = $args['arg'];
        $engine = $args['engine'];
        $updatetime = $args['updatetime']; // This is time it was received not time for value
        if ($arg != null) $arg="|".json_encode($arg); // passes arg to redis
        $this->redis->zAdd("feed:$feedid:buffer",(int)$time,$updatetime."|".$value.$arg);
        //$this->log->info("post() engine=$engine feed=$feedid updatetime=$updatetime time=$time value=$value arg=$arg");
    }
    
    /**
     * Updates a data point in the buffer feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function update($feedid,$time,$value,$args=null)
    {
        $engine = $args['engine'];
        $updatetime = $args['updatetime']; // This is time it was received not time for value
        //TODO: Next 2 redis should be atomic
        $remcnt = $this->redis->zRemRangeByScore("feed:$feedid:buffer", (int)$time, (int)$time); // Remove for buffer existing time, return num of removed
        $this->redis->zAdd("feed:$feedid:buffer",(int)$time,$updatetime."|".$value);   // Add new value to buffer
        $this->log->info("update() engine=$engine feed=$feedid updatetime=$updatetime time=$time value=$value remcnt=$remcnt");
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
        $meta = new stdClass();
        $meta->id = $feedid;
        $meta->start_time = 0;//tbd
        $meta->nlayers = 1;
        $meta->npoints = -1; //tbd
        $meta->interval = 1;
        return $meta;
    }
    
    public function csv_export($feedid,$start,$end,$outinterval)
    {
        return false; // TBD
    }
    
    // Write data in buffer to all feeds
    public function process_buffers(){
        $feedids = $this->redis->sMembers("feed:active");
        foreach ($feedids as $feedid) {
            $this->process_feed_buffer($feedid);
        }
    }
    
    // Write data in buffer to feed
    private function process_feed_buffer($feedid){
        $feeddata = $this->redis->hGetAll("feed:$feedid");
        if (isset($feeddata['engine'])) {
            $engine = $feeddata['engine'];
            $len = $this->redis->zCount("feed:$feedid:buffer","-inf","+inf");
            if ($len > 0 ) {
                echo "Processing feed=$feedid len=$len :\n";
                $this->log->info("process_buffer() engine=$engine feed=$feedid len=$len");
                $lasttime=0;
                $range = 50000; // step range number of points to extract on each iteration 50k-100k is ok
                for ($i=$range; $i<=$len+$range; $i++)
                {
                    echo " Reading block $i\n";
                    if ($i > $len)  $range =  $range-($i-$len);
                    $i = $i + $range-1;
                    //$this->redis->zRangeByScore($key, '-inf', $range, array('withscores' => TRUE, 'limit' => array(1, 1)));
                    $buf_item = $this->redis->zRange("feed:$feedid:buffer", 0, $range-1, true);

                    $matchcnt=0;
                    foreach($buf_item as $rawvalue => $time) {
                        $f = explode("|",$rawvalue);    
                        $updatetime = $f[0]; // This is time it was received not time for value
                        $value = $f[1];
                        $arg = (isset($f[2]) ? $f[2] : "");
                        if ($lasttime == $time) {
                            echo " Invoking update engine=" . $engine . " time=$time rawvalue=$rawvalue\n";
                            $this->feed->EngineClass($engine)->update($feedid,$time,$value);
                        } else {
                            echo "  Invoking post_bulk_prepare engine=" . $engine . " time=$time rawvalue=$rawvalue\n";
                            $this->feed->EngineClass($engine)->post_bulk_prepare($feedid,$time,$value,$arg);
                            //$this->feed->EngineClass($engine)->post($feedid,$time,$value,$arg);
                        }
                        $lasttime=$time;
                        $matchcnt++;
                    }
                    if ($matchcnt > 0) {
                        echo " Invoking post_bulk_save engine=" . $engine . "\n";
                        $this->feed->EngineClass($engine)->post_bulk_save();
                    }
                    
                    if ($range != $matchcnt) { echo "WARN: expected $range but found $matchcnt items\n"; }
                    $remcnt = $this->redis->zRemRangeByRank("feed:$feedid:buffer", 0, $range-1); // Remove processed range
                    if ($remcnt != $matchcnt) { echo "WARN: found $matchcnt but deleted $remcnt items\n"; }
                }
            }
        }
    }
}
