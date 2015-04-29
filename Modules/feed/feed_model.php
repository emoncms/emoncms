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
    private $csvdownloadlimit_mb = 10;
    private $log;
    
    private $max_npoints_returned = 800;
    private $mqtt = false;

    public function __construct($mysqli,$redis,$settings)
    {        
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        $this->log = new EmonLogger(__FILE__);
        $this->log->set_topic("FEED");
        
        require "Modules/feed/engine/PHPTimeSeries.php";
        require "Modules/feed/engine/PHPFina.php";

        $this->engine = array();
        $this->engine[Engine::PHPTIMESERIES] = new PHPTimeSeries($settings['phptimeseries']);
        $this->engine[Engine::PHPFINA] = new PHPFina($settings['phpfina']);
        
        if (isset($settings['csvdownloadlimit_mb'])) {
            $this->csvdownloadlimit_mb = $settings['csvdownloadlimit_mb']; 
        }
        
        if (isset($settings['max_npoints_returned'])) {
            $this->max_npoints_returned = $settings['max_npoints_returned'];
        }
    }

    public function create($userid,$name,$engine,$options_in)
    {
        $userid = (int) $userid;
        $name = preg_replace('/[^\w\s-:]/','',$name);
        $engine = (int) $engine;
        
        if ($engine!=Engine::PHPFINA && $engine!=Engine::PHPTIMESERIES)
            return array('success'=>false, 'message'=>'incorrect engine given, must be 5 or 6');
        
        // If feed of given name by the user already exists
        $feedid = $this->get_id($userid,$name);
        if ($feedid!=0) return array('success'=>false, 'message'=>'feed already exists');

        $result = $this->mysqli->query("INSERT INTO feeds (userid,name,datatype,public,engine) VALUES ('$userid','$name','1',false,'$engine')");
        $feedid = $this->mysqli->insert_id;

        if ($feedid>0)
        {
            $this->redis->sAdd("user:feeds:$userid", $feedid);
            $this->redis->hMSet("feed:$feedid",array(
                'id'=>$feedid,
                'userid'=>$userid,
                'name'=>$name,
                'tag'=>'',
                'public'=>false,
                'size'=>0,
                'engine'=>$engine
            ));
            
            $options = array();
            if ($engine==Engine::PHPFINA) $options['interval'] = (int) $options_in->interval;
            
            $engineresult = $this->engine[$engine]->create($feedid,$options);

            if ($engineresult == false)
            {
                $this->log->warn("Feed model: failed to create feed model feedid=$feedid");
                $this->mysqli->query("DELETE FROM feeds WHERE `id` = '$feedid'");

                $userid = $this->redis->hget("feed:$feedid",'userid');
                $this->redis->del("feed:$feedid");
                $this->redis->srem("user:feeds:$userid",$feedid);

                return array('success'=>false, 'message'=>"");
            }

            return array('success'=>true, 'feedid'=>$feedid, 'result'=>$engineresult);
        } else return array('success'=>false);
    }

    public function exist($id)
    {
        $feedexist = false;
        if (!$this->redis->exists("feed:$id")) {
            if ($this->load_feed_to_redis($id))
            {
                $feedexist = true;
            }
        } else {
            $feedexist = true;
        }
        return $feedexist;
    }

    public function get_id($userid,$name)
    {
        $userid = intval($userid);
        $name = preg_replace('/[^\w\s-:]/','',$name);
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
        if (!$this->redis->exists("user:feeds:$userid")) $this->load_to_redis($userid);
      
        $feeds = array();
        $feedids = $this->redis->sMembers("user:feeds:$userid");
        foreach ($feedids as $id)
        {
            $row = $this->redis->hGetAll("feed:$id");
            $lastvalue = $this->get_timevalue($id);
            $row['time'] = $lastvalue['time'];
            $row['value'] = $lastvalue['value'];
            $feeds[] = $row;
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
    
    public function get_user_feed_ids($userid)
    {
        $userid = (int) $userid;
        if (!$this->redis->exists("user:feeds:$userid")) $this->load_to_redis($userid);
        $feedids = $this->redis->sMembers("user:feeds:$userid");
        return $feedids;
    }

    /*

    Feeds table GET public functions

    */

    public function get($id)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');

        $row = $this->redis->hGetAll("feed:$id");
        $lastvalue = $this->redis->hmget("feed:timevalue:$id",array('time','value'));
        $row['time'] = $lastvalue['time'];
        $row['value'] = $lastvalue['value'];

        return $row;
    }

    public function get_field($id,$field)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Feed does not exist');

        if ($field!=NULL) // if the feed exists
        {
            $field = preg_replace('/[^\w\s-]/','',$field);
            $val = $this->redis->hget("feed:$id",$field);
            
            if ($val) return $val; else return 0;
        }
        else return array('success'=>false, 'message'=>'Missing field parameter');
    }

    public function get_timevalue($id)
    {
        $id = (int) $id;

        if ($this->redis->exists("feed:timevalue:$id"))
        {
            $lastvalue = $this->redis->hmget("feed:timevalue:$id",array('time','value'));
        }
        else
        {
            // if it does not load it in to redis from the actual feed data.
            $lastvalue = $this->get_timevalue_from_data($id);
            $this->redis->hMset("feed:timevalue:$id", array('value' => $lastvalue['value'], 'time' => $lastvalue['time']));
        }

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
        if (isset($fields->name)) $array[] = "`name` = '".preg_replace('/[^\w\s-:]/','',$fields->name)."'";
        if (isset($fields->tag)) $array[] = "`tag` = '".preg_replace('/[^\w\s-:]/','',$fields->tag)."'";
        if (isset($fields->public)) $array[] = "`public` = '".intval($fields->public)."'";

        // Convert to a comma seperated string for the mysql query
        $fieldstr = implode(",",$array);
        $this->mysqli->query("UPDATE feeds SET ".$fieldstr." WHERE `id` = '$id'");

        // Update redis
        if (isset($fields->name)) $this->redis->hset("feed:$id",'name',$fields->name);
        if (isset($fields->tag)) $this->redis->hset("feed:$id",'tag',$fields->tag);
        if (isset($fields->public)) $this->redis->hset("feed:$id",'public',$fields->public);

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
        
        $this->redis->rpush('feedbuffer',"$feedid,$feedtime,$value,0");
        $this->set_timevalue($feedid, $value, $updatetime);

        return $value;
    }
    
    public function insert_data_padding_mode($feedid,$updatetime,$feedtime,$value,$padding_mode)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');

        if ($feedtime == null) $feedtime = time();
        $updatetime = (int) $updatetime;
        $feedtime = (int) $feedtime;
        $value = (float) $value;
        
        $pad = 0;
        if ($padding_mode=="join") $pad = 1;
        $this->redis->rpush('feedbuffer',"$feedid,$feedtime,$value,$pad");
        $this->set_timevalue($feedid, $value, $updatetime);

        return $value;
    }

    public function update_data($feedid,$updatetime,$feedtime,$value) {
        return $this->insert_data($feedid,$updatetime,$feedtime,$value);
    }

    public function get_data($feedid,$start,$end,$outinterval,$skipmissing,$limitinterval)
    {
        $feedid = (int) $feedid;
        if (!$this->exist($feedid)) return array('success'=>false, 'message'=>'Feed does not exist');
        $engine = $this->get_engine($feedid);
        return $this->engine[$engine]->get_data($feedid,$start,$end,$outinterval,$skipmissing,$limitinterval);
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

        $userid = $this->redis->hget("feed:$feedid",'userid');
        $this->redis->del("feed:$feedid");
        $this->redis->srem("user:feeds:$userid",$feedid);
    }

    public function update_user_feeds_size($userid)
    {
        $userid = (int) $userid;
        $total = 0;
        $feeds = $this->get_user_feeds($userid);
        
        
        foreach ($feeds as $feed)
        {
            $size = 0;
            $feedid = $feed['id'];
            $engine = $feed['engine'];
            
            // Call to engine get_feed_size method
            $size = $this->engine[$engine]->get_feed_size($feedid);
            
            $this->mysqli->query("UPDATE feeds SET `size` = '$size' WHERE `id`= '$feedid'");
            $this->redis->hset("feed:$feedid",'size',$size);
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
        $this->redis->hMset("feed:timevalue:$feedid", array('value' => $value, 'time' => $time));
    }
    
    private function get_engine($feedid)
    {
        return $this->redis->hget("feed:$feedid",'engine');
    }

    public function load_to_redis($userid)
    {
        $result = $this->mysqli->query("SELECT id,userid,name,tag,public,size,engine FROM feeds WHERE `userid` = '$userid'");
        while ($row = $result->fetch_object())
        {
            $this->redis->sAdd("user:feeds:$userid", $row->id);
            $this->redis->hMSet("feed:$row->id",array(
            'id'=>$row->id,
            'userid'=>$row->userid,
            'name'=>$row->name,
            'tag'=>$row->tag,
            'public'=>$row->public,
            'size'=>$row->size,
            'engine'=>$row->engine
            ));
        }
    }

    public function load_feed_to_redis($id)
    {
        $result = $this->mysqli->query("SELECT id,userid,name,tag,public,size,engine FROM feeds WHERE `id` = '$id'");
        $row = $result->fetch_object();

        if (!$row) {
            $this->log->warn("Feed model: Requested feed does not exist feedid=$id");
            return false;
        }

        $this->redis->hMSet("feed:$row->id",array(
            'id'=>$row->id,
            'userid'=>$row->userid,
            'name'=>$row->name,
            'tag'=>$row->tag,
            'public'=>$row->public,
            'size'=>$row->size,
            'engine'=>$row->engine
        ));

        return true;
    }
}

