<?php
// Internal engine for low-write functionality
// Maintains a buffer in redis with latest feed data
// Written by: Chaveiro Portugal Jul-2015
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

// #### \/ Below are required methods
    public function create($feedid,$options)
    {
        return true;
    }

    public function delete($feedid)
    {
        $this->redis->srem("feed:bufferactive",$feedid); // remove from feedlist
        $this->redis->zRemRangeByRank('feed:$feedid:buffer', 0, -1); // remove buffer
    }

    public function get_meta($feedid)
    {
        $meta = new stdClass();
        $meta->id = $feedid;
        $meta->start_time = 0;//tbd
        $meta->nlayers = 1;
        $meta->interval = 1;
        return $meta;
    }

    public function get_feed_size($feedid)
    {
        // Return number of points in buffer, not size. Estimated 24 bytes + num of digits of value in string format for each date point.
        $feeddata = $this->redis->hGetAll("feed:$feedid");
        if (isset($feeddata['engine'])) {
            $engine = $feeddata['engine'];
            return $this->redis->zCount("feed:$feedid:buffer","-inf","+inf");
        }
        return 0;
    }

    /**
     * Adds a data point to the buffer feed
     *
     * @param integer $feedid The id of the feed to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
     * @param array $arg optional arguments
    */
    public function post($feedid,$time,$value,$args=null)
    {
        $arg = $args['arg'];
        $engine = $args['engine'];
        $updatetime = $args['updatetime']; // This is time it was received not time for value
        if ($arg != null) $arg="|".json_encode($arg); // passes arg to redis

        $this->redis->zAdd("feed:$feedid:buffer",(int)$time,dechex((int)$updatetime)."|".$value.$arg);
        $this->redis->sAdd("feed:bufferactive",$feedid); // save feed id to feedlist redis used on feedwriter
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
        $updatetime = $args['updatetime']; // This is time it was received not time for value, used as score order
        
        $this->setLock($feedid,"write"); // set write lock

        // A value update on a range being processed may get deleted without being saved, so check lock and wait for release
        $this->checkLock_blocking($feedid,"read");

        $remcnt = $this->redis->zRemRangeByScore("feed:$feedid:buffer", (int)$time, (int)$time); // Remove for buffer existing time, return num of removed
        $this->redis->zAdd("feed:$feedid:buffer",(int)$time,dechex((int)$updatetime)."|".$value."|U");   // Add new value to buffer
        $this->redis->sAdd("feed:bufferactive",$feedid); // save feed id to feedlist redis used on feedwriter

        $this->removeLock($feedid,"write"); // remove write lock
        //$this->log->info("update() engine=$engine feed=$feedid updatetime=$updatetime time=$time value=$value remcnt=$remcnt");
    }

    /**
     * Get array with last time and value from a feed
     *
     * @param integer $feedid The id of the feed
    */
    public function lastvalue($feedid)
    {
        $buf_item = $this->redis->zRevRangeByScore("feed:$feedid:buffer", "+inf","-inf", array('withscores' => true, 'limit' => array(0, 1)));
        foreach($buf_item as $rawvalue => $time) {
            $f = explode("|",$rawvalue);    
            $value = $f[1];
            return array('time'=>(int)$time, 'value'=>(float)$value);   
        }
        return false;
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
        $feedid = intval($feedid);
        $start = round($start/1000);
        $end = round($end/1000);
        $data = array();

        $len = $this->redis->zCount("feed:$feedid:buffer",$start,$end);
        // process if there is data on buffer for the range
        if ($len > 0) {
            $this->log->info("get_data() feed=$feedid len=$len start=$start end=$end");
            $range = 50000; // step range number of points to extract on each iteration 50k-100k is ok
            for ($i=0; $i<=$len; $i = $i + $range)
            {
                //$this->log->info("get_data() Reading block $i");
                $buf_item = $this->redis->zRangeByScore("feed:$feedid:buffer", $start, $end, array('withscores' => true, 'limit' => array($i, $range)));
                foreach($buf_item as $rawvalue => $time) {
                    //$this->log->info("get_data() time=$time rawvalue=$rawvalue");
                    $f = explode("|",$rawvalue);
                    $value = $f[1];
                    $time=$time*1000;
                    $data[$time] = array($time,(float)$value);
                }
            }
            $data = array_values($data); // re-index array
        }
        return $data;
    }

    public function export($feedid,$start)
    {
        return false; // Not supported
    }

    public function csv_export($feedid,$start,$end,$outinterval,$usertimezone)
    {
        return false; // Not supported
    }

// #### /\ Above are required methods


// #### \/ Below engine specific methods

    // Write data in buffer to all feeds
    public function process_buffers(){
        $feedids = $this->redis->sMembers("feed:bufferactive");
        foreach ($feedids as $feedid) {
            $this->process_feed_buffer($feedid);
        }
    }


// #### \/ Bellow are engine private methods      

    // Write data in buffer to feed
    private function process_feed_buffer($feedid){
        $feeddata = $this->redis->hGetAll("feed:$feedid");
        if (isset($feeddata['engine'])) {
            $engine = $feeddata['engine'];
            $len = $this->redis->zCount("feed:$feedid:buffer","-inf","+inf");
            // process if there is data on buffer and no write lock from real data
            if ($len > 0 && !$this->checkLock($feedid,"write")) {
                $this->setLock($feedid,"read"); // set read lock
                echo "Processing feed=$feedid len=$len :\n";
                $this->log->info("process_buffer() engine=$engine feed=$feedid len=$len");
                $lasttime=0;
                $range = 50000; // step range number of points to extract on each iteration 50k-100k is ok
                for ($i=$range; $i<=$len+$range; $i++)
                {
                    echo " Reading block $i\n";
                    if ($i > $len)  $range =  $range-($i-$len);
                    $i = $i + $range-1;
                    $buf_item = $this->redis->zRange("feed:$feedid:buffer", 0, $range-1, true);

                    $matchcnt=0;
                    foreach($buf_item as $rawvalue => $time) {
                        $f = explode("|",$rawvalue);    
                        $updatetime = hexdec((string)$f[0]); // This is time it was received not time for value
                        $value = $f[1];
                        $arg = (isset($f[2]) ? $f[2] : "");
                        if ($arg == "U" || $lasttime == $time) {
                            //echo " Invoking update engine=" . $engine . " time=$time rawvalue=$rawvalue\n";
                            $this->feed->EngineClass($engine)->update($feedid,$time,$value);
                        } else {
                            //echo "  Invoking post_bulk_prepare engine=" . $engine . " time=$time rawvalue=$rawvalue\n";
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
            $this->removeLock($feedid,"read"); // remove read lock
        }
    }

    //Checks redis locks and wait if locked
    private function checkLock_blocking($feedid,$type)
    {
        $lock = $this->checkLock($feedid,$type);
        if ($lock) {
            $this->log->info("checkLock_blocking() Redis buffer has a $type lock on feed=$feedid waiting for release...");
            while ($this->checkLock($feedid,$type)) {
                sleep(1);
            }
        }
    }
    
    //Checks redis lock
    private function checkLock($feedid,$type)
    {
        $lock = $this->redis->hGet("feed:$feedid:bufferstatus",$type);
        //$this->log->info("checkLock() $type lock on feed=$feedid is $lock");
        return $lock == "1";
    }
    
    //Set redis lock
    private function setLock($feedid,$type)
    {
        $this->redis->hSet("feed:$feedid:bufferstatus",$type,"1"); 
        //$this->log->info("setLock() $type lock on feed=$feedid");
    }
    
    //Remove redis lock
    public function removeLock($feedid,$type)
    {
        $this->redis->hSet("feed:$feedid:bufferstatus",$type,"0"); 
        //$this->log->info("removeLock() $type lock on feed=$feedid");
    }
    
}
