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

class Feed
{
    private $mysqli;
    private $redis;
    public $engine;
    private $csvdownloadlimit_mb;
    private $log;

    public function __construct($mysqli,$redis,$settings)
    {        
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        $this->log = new EmonLogger(__FILE__);
        
        require "Modules/feed/engine/PHPTimeSeries.php";
        require "Modules/feed/engine/PHPFina.php";

        $this->engine = array();
        $this->engine[Engine::PHPTIMESERIES] = new PHPTimeSeries($settings['phptimeseries']);
        $this->engine[Engine::PHPFINA] = new PHPFina($settings['phpfina']);
        
        if (isset($settings['csvdownloadlimit_mb'])) {
            $this->csvdownloadlimit_mb = $settings['csvdownloadlimit_mb']; 
        } else {
            $this->csvdownloadlimit_mb = 10;
        }
    }

    public function create($userid,$name,$datatype,$engine,$options_in)
    {
        $userid = (int) $userid;
        $name = preg_replace('/[^\w\s-]/','',$name);
        $datatype = (int) $datatype;
        $engine = (int) $engine;
        
        // If feed of given name by the user already exists
        $feedid = $this->get_id($userid,$name);
        if ($feedid!=0) return array('success'=>false, 'message'=>'feed already exists');

        $result = $this->mysqli->query("INSERT INTO feeds (userid,name,datatype,public,engine) VALUES ('$userid','$name','$datatype',false,'$engine')");
        $feedid = $this->mysqli->insert_id;

        if ($feedid>0)
        {
            // Add the feed to redis
            if ($this->redis) {
                $this->redis->sAdd("user:feeds:$userid", $feedid);
                $this->redis->hMSet("feed:$feedid",array(
                    'id'=>$feedid,
                    'userid'=>$userid,
                    'name'=>$name,
                    'datatype'=>$datatype,
                    'tag'=>'',
                    'public'=>false,
                    'size'=>0,
                    'engine'=>$engine
                ));
            }
            
            $options = array();
            if ($engine==Engine::PHPFINA) $options['interval'] = (int) $options_in->interval;
            
            $engineresult = $this->engine[$engine]->create($feedid,$options);

            if ($engineresult == false)
            {
                $this->log->warn("Feed model: failed to create feed model feedid=$feedid");
                // Feed engine creation failed so we need to delete the meta entry for the feed
                
                $this->mysqli->query("DELETE FROM feeds WHERE `id` = '$feedid'");

                if ($this->redis) {
                    $userid = $this->redis->hget("feed:$feedid",'userid');
                    $this->redis->del("feed:$feedid");
                    $this->redis->srem("user:feeds:$userid",$feedid);
                }

                return array('success'=>false, 'message'=>"");
            }

            return array('success'=>true, 'feedid'=>$feedid, 'result'=>$engineresult);
        } else return array('success'=>false);
    }

    public function exist($id)
    {
        $feedexist = false;
        if ($this->redis)
        {
            
            if (!$this->redis->exists("feed:$id")) {
                if ($this->load_feed_to_redis($id))
                {
                    $feedexist = true;
                }
            } else {
                $feedexist = true;
            }
        }
        else 
        {
            $id = intval($id);
            $result = $this->mysqli->query("SELECT id FROM feeds WHERE id = '$id'");
            if ($result->num_rows>0) $feedexist = true;
        }
        return $feedexist;
    }

    public function get_id($userid,$name)
    {
        $userid = intval($userid);
        $name = preg_replace('/[^\w\s-]/','',$name);
        $result = $this->mysqli->query("SELECT id FROM feeds WHERE userid = '$userid' AND name = '$name'");
        if ($result->num_rows>0) { $row = $result->fetch_array(); return $row['id']; } else return false;
    }
    /*

    User Feed lists

    Returns a specified user's feedlist in different forms:
    get_user_feeds: 	all the feeds table data
    get_user_feed_ids: 	only the id's
    get_user_feed_names: 	id's and names

    */

    public function get_user_feeds($userid)
    {
        $userid = (int) $userid;
        
        if ($this->redis) {
            $feeds = $this->redis_get_user_feeds($userid);
        } else {
            $feeds = $this->mysql_get_user_feeds($userid);
        }    
        
        return $feeds;
    }
    
    public function get_user_public_feeds($userid)
    {
        $feeds = $this->get_user_feeds($userid);
        $publicfeeds = array();
        foreach ($feeds as $feed) { if ($feed['public']) $publicfeeds[] = $feed; }
        return $publicfeeds;
    }
    
    public function redis_get_user_feeds($userid)
    {
        $userid = (int) $userid;
        if (!$this->redis->exists("user:feeds:$userid")) $this->load_to_redis($userid);
      
        $feeds = array();
        $feedids = $this->redis->sMembers("user:feeds:$userid");
        foreach ($feedids as $id)
        {
            $row = $this->redis->hGetAll("feed:$id");

            $lastvalue = $this->get_timevalue($id);
            $row['time'] = strtotime($lastvalue['time']);
            $row['value'] = $lastvalue['value'];
            $feeds[] = $row;
        }
        
        return $feeds;
    }
    
    public function mysql_get_user_feeds($userid)
    {
        $userid = (int) $userid;
        $feeds = array();
        $result = $this->mysqli->query("SELECT * FROM feeds WHERE `userid` = '$userid'");
        while ($row = (array)$result->fetch_object())
        {
            $row['time'] = strtotime($row['time']);
            $feeds[] = $row;
        }
        return $feeds;
    }
    
    public function get_user_feed_ids($userid)
    {
        $userid = (int) $userid;
        if ($this->redis) {
            if (!$this->redis->exists("user:feeds:$userid")) $this->load_to_redis($userid);
            $feedids = $this->redis->sMembers("user:feeds:$userid");
        } else {
            $result = $this->mysqli->query("SELECT id FROM feeds WHERE `userid` = '$userid'");
            $feedids = array();
            while ($row = $result->fetch_array()) $feedids[] = $row['id'];
        }
        return $feedids;
    }

    /*

    Feeds table GET public functions

    */

    public function get($id)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');

        if ($this->redis) {
            // Get from redis cache
            $row = $this->redis->hGetAll("feed:$id");
            $lastvalue = $this->redis->hmget("feed:lastvalue:$id",array('time','value'));
            $row['time'] = $lastvalue['time'];
            $row['value'] = $lastvalue['value'];
        } else {
            // Get from mysql db
            $result = $this->mysqli->query("SELECT * FROM feeds WHERE `id` = '$id'");
            $row = (array) $result->fetch_object();
            $row['time'] = strtotime($row['time']);
        }

        return $row;
    }

    public function get_field($id,$field)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');

        if ($field!=NULL) // if the feed exists
        {
            $field = preg_replace('/[^\w\s-]/','',$field);
            
            if ($this->redis) {
                $val = $this->redis->hget("feed:$id",$field);
            } else {
                $result = $this->mysqli->query("SELECT `$field` FROM feeds WHERE `id` = '$id'");
                $row = $result->fetch_array();
                $val = $row[0];
            }
            
            if ($val) return $val; else return 0;
        }
        else return array('success'=>false, 'message'=>'Missing field parameter');
    }

    public function get_timevalue($id)
    {
        $id = (int) $id;

        // Get the timevalue from redis if it exists
        if ($this->redis) 
        {
            if ($this->redis->exists("feed:lastvalue:$id"))
            {
                $lastvalue = $this->redis->hmget("feed:lastvalue:$id",array('time','value'));
            }
            else
            {
                // if it does not load it in to redis from the actual feed data.
                $lastvalue = $this->get_timevalue_from_data($id);
                $this->redis->hMset("feed:lastvalue:$id", array('value' => $lastvalue['value'], 'time' => $lastvalue['time']));
            }
        }
        else 
        {
            $result = $this->mysqli->query("SELECT time,value FROM feeds WHERE `id` = '$id'");
            $row = $result->fetch_array();
            $lastvalue = array('time'=>$row['time'], 'value'=>$row['value']);
        }

        return $lastvalue;
    }

    public function get_timevalue_seconds($id)
    {
        $lastvalue = $this->get_timevalue($id);
        $lastvalue['time'] = strtotime($lastvalue['time']);
        return $lastvalue;
    }

    public function get_value($id)
    {
        $lastvalue = $this->get_timevalue($id);
        return $lastvalue['value'];
    }

    public function get_timevalue_from_data($feedid)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $engine = $this->get_engine($feedid);
        
        // Call to engine lastvalue method
        return $this->engine[$engine]->lastvalue($feedid);
    }

    /*

    Feeds table SET public functions

    */

    public function set_feed_fields($id,$fields)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');

        $fields = json_decode(stripslashes($fields));

        $array = array();

        // Repeat this line changing the field name to add fields that can be updated:
        if (isset($fields->name)) $array[] = "`name` = '".preg_replace('/[^\w\s-]/','',$fields->name)."'";
        if (isset($fields->tag)) $array[] = "`tag` = '".preg_replace('/[^\w\s-]/','',$fields->tag)."'";
        if (isset($fields->public)) $array[] = "`public` = '".intval($fields->public)."'";

        // Convert to a comma seperated string for the mysql query
        $fieldstr = implode(",",$array);
        $this->mysqli->query("UPDATE feeds SET ".$fieldstr." WHERE `id` = '$id'");

        // Update redis
        if ($this->redis && isset($fields->name)) $this->redis->hset("feed:$id",'name',$fields->name);
        if ($this->redis && isset($fields->tag)) $this->redis->hset("feed:$id",'tag',$fields->tag);
        if ($this->redis && isset($fields->public)) $this->redis->hset("feed:$id",'public',$fields->public);

        if ($this->mysqli->affected_rows>0){
            return array('success'=>true, 'message'=>'Field updated');
        } else {
            return array('success'=>false, 'message'=>'Field could not be updated');
        }
    }

    /*

    Feed data public functions

    insert, update, get and specialist histogram public functions

    */

    public function insert_data($feedid,$updatetime,$feedtime,$value)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        if ($feedtime == null) $feedtime = time();
        $updatetime = (int) $updatetime;
        $feedtime = (int) $feedtime;
        $value = (float) $value;

        // $engine = $this->get_engine($feedid);
        // $this->engine[$engine]->post($feedid,$feedtime,$value);
        $this->redis->rpush('feedbuffer',"$feedid,$feedtime,$value");
        
        $this->set_timevalue($feedid, $value, $updatetime);

        return $value;
    }

    public function update_data($feedid,$updatetime,$feedtime,$value) {
        return $this->insert_data($feedid,$updatetime,$feedtime,$value);
    }

    public function get_data($feedid,$start,$end,$dp)
    {
        $feedid = (int) $feedid;
        if ($end == 0) $end = time()*1000;
                
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
  
        $engine = $this->get_engine($feedid);
        
        // Call to engine get_data method
        if ($dp>800) $dp = 800;
        $outinterval = round(($end - $start) / $dp)/1000;
        return $this->engine[$engine]->get_data($feedid,$start,$end,$outinterval);
    }

    public function get_average($feedid,$start,$end,$outinterval)
    {
        $feedid = (int) $feedid;
        if ($end == 0) $end = time()*1000;
        
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $engine = $this->get_engine($feedid);

        // Call to engine get_average method
        return $this->engine[$engine]->get_data($feedid,$start,$end,$outinterval);
    }
    
    public function csv_export($feedid,$start,$end,$outinterval)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $engine = $this->get_engine($feedid);
        
        // Download limit
        $downloadsize = (($end - $start) / $outinterval) * 17; // 17 bytes per dp
        if ($downloadsize>($this->csvdownloadlimit_mb*1048576)) {
            $this->log->warn("Feed model: csv download limit exeeded downloadsize=$downloadsize feedid=$feedid");
            return false;
        }

        // Call to engine get_average method
        return $this->engine[$engine]->csv_export($feedid,$start,$end,$outinterval);
    }


    public function delete($feedid)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        $engine = $this->get_engine($feedid);
        
        // Call to engine delete method
        $this->engine[$engine]->delete($feedid);

        $this->mysqli->query("DELETE FROM feeds WHERE `id` = '$feedid'");

        if ($this->redis) {
            $userid = $this->redis->hget("feed:$feedid",'userid');
            $this->redis->del("feed:$feedid");
            $this->redis->srem("user:feeds:$userid",$feedid);
        }
    }

    public function update_user_feeds_size($userid)
    {
        $userid = (int) $userid;
        $total = 0;
        $result = $this->mysqli->query("SELECT id,engine FROM feeds WHERE `userid` = '$userid'");
        while ($row = $result->fetch_array())
        {
            $size = 0;
            $feedid = $row['id'];
            $engine = $row['engine'];
            
            // Call to engine get_feed_size method
            $size = $this->engine[$engine]->get_feed_size($feedid);
            
            $this->mysqli->query("UPDATE feeds SET `size` = '$size' WHERE `id`= '$feedid'");
            if ($this->redis) $this->redis->hset("feed:$feedid",'size',$size);
            $total += $size;
        }
        return $total;
    }

    public function get_meta($feedid) {
        $feedid = (int) $feedid;
        $engine = $this->get_engine($feedid);
        return $this->engine[$engine]->get_meta($feedid);
    }

    public function phptimeseries_export($feedid,$start) {
        return $this->engine[Engine::PHPTIMESERIES]->export($feedid,$start);
    }
    
    public function phpfina_export($feedid,$start) {
        return $this->engine[Engine::PHPFINA]->export($feedid,$start);
    }

    public function set_timevalue($feedid, $value, $time)
    {
        $updatetime = date("Y-n-j H:i:s", $time);
        if ($this->redis) {
            $this->redis->hMset("feed:lastvalue:$feedid", array('value' => $value, 'time' => $updatetime));
        } else {
            $this->mysqli->query("UPDATE feeds SET `time` = '$updatetime', `value` = '$value' WHERE `id`= '$feedid'");
        }
    }
    
    private function get_engine($feedid)
    {
        if ($this->redis) {
            return $this->redis->hget("feed:$feedid",'engine');
        } else {
            $result = $this->mysqli->query("SELECT engine FROM feeds WHERE `id` = '$feedid'");
            $row = $result->fetch_object();
            return $row->engine;
        }
    }

    public function load_to_redis($userid)
    {
        $result = $this->mysqli->query("SELECT id,userid,name,datatype,tag,public,size,engine FROM feeds WHERE `userid` = '$userid'");
        while ($row = $result->fetch_object())
        {
            $this->redis->sAdd("user:feeds:$userid", $row->id);
            $this->redis->hMSet("feed:$row->id",array(
            'id'=>$row->id,
            'userid'=>$row->userid,
            'name'=>$row->name,
            'datatype'=>$row->datatype,
            'tag'=>$row->tag,
            'public'=>$row->public,
            'size'=>$row->size,
            'engine'=>$row->engine
            ));
        }
    }

    public function load_feed_to_redis($id)
    {
        $result = $this->mysqli->query("SELECT id,userid,name,datatype,tag,public,size,engine FROM feeds WHERE `id` = '$id'");
        $row = $result->fetch_object();

        if (!$row) {
            $this->log->warn("Feed model: Requested feed does not exist feedid=$id");
            return false;
        }

        $this->redis->hMSet("feed:$row->id",array(
            'id'=>$row->id,
            'userid'=>$row->userid,
            'name'=>$row->name,
            'datatype'=>$row->datatype,
            'tag'=>$row->tag,
            'public'=>$row->public,
            'size'=>$row->size,
            'engine'=>$row->engine
        ));

        return true;
    }
}

