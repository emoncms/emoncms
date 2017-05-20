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

class Input
{
    private $mysqli;
    private $feed;
    private $redis;

    public function __construct($mysqli,$redis,$feed)
    {
        $this->mysqli = $mysqli;
        $this->feed = $feed;

        $this->redis = $redis;
    }

    // USES: redis input & user
    public function create_input($userid, $nodeid, $name)
    {
        $userid = (int) $userid;
        $nodeid = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$nodeid);
        $name = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$name);
        $this->mysqli->query("INSERT INTO input (userid,name,nodeid,description,processList) VALUES ('$userid','$name','$nodeid','','')");
        $id = $this->mysqli->insert_id;

        if ($this->redis) {
            $this->redis->sAdd("user:inputs:$userid", $id);
            $this->redis->hMSet("input:$id",array('id'=>$id,'nodeid'=>$nodeid,'name'=>$name,'description'=>"", 'processList'=>""));
        }
        return $id;

    }

    public function exists($inputid)
    {
        $inputid = (int) $inputid;
        $result = $this->mysqli->query("SELECT id FROM input WHERE `id` = '$inputid'");
        if ($result->num_rows == 1) return true; else return false;
    }
    
    public function exists_nodeid_name($userid,$nodeid,$name)
    {
        $userid = (int) $userid;
        $nodeid = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$nodeid);
        $name = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$name);
        $result = $this->mysqli->query("SELECT id FROM input WHERE `userid` = '$userid' AND `nodeid` = '$nodeid' AND `name` = '$name'");
        if ($result->num_rows==0) return false;
        $row = $result->fetch_array();
        return $row["id"]; 
    }    

    public function validate_access($dbinputs, $nodeid)
    {
        global $session, $max_node_id_limit;
        $success=true;
        $message = "";
        if (isset($session['deviceid']) && isset($session['nodeid'])) {
            if (!isset($dbinputs[$nodeid])) {
                $success = false;
                $message = "Device not initialized.";
            } else if ($nodeid != $session['nodeid']) {
                $success = false;
                $message = "Node '$nodeid' does not belong to device.";
            }
        } else if (!isset($dbinputs[$nodeid]) && (count($dbinputs) >= $max_node_id_limit )) {
            $success = false;
            $message = "Reached the maximal allowed number of diferent NodeIds, limit is $max_node_id_limit. Node '$nodeid' was ignored.";
        }
        return array('success'=>$success, 'message'=>$message);
    }
    
    // USES: redis input
    public function set_timevalue($id, $time, $value)
    {
        $id = (int) $id;
        $time = (int) $time;
        $value = (float) $value;

        if ($this->redis) {
            $this->redis->hMset("input:lastvalue:$id", array('value' => $value, 'time' => $time));
        } else {
            $this->mysqli->query("UPDATE input SET time='$time', value = '$value' WHERE id = '$id'");
        }
    }

    // used in conjunction with controller before calling another method
    public function belongs_to_user($userid, $inputid)
    {
        $userid = (int) $userid;
        $inputid = (int) $inputid;

        $result = $this->mysqli->query("SELECT id FROM input WHERE userid = '$userid' AND id = '$inputid'");
        if ($result->fetch_array()) return true; else return false;
    }

    // USES: redis input
    public function set_fields($id,$fields)
    {
        $id = intval($id);
        $fields = json_decode(stripslashes($fields));

        $array = array();

        // Repeat this line changing the field name to add fields that can be updated:
        if (isset($fields->description)) $array[] = "`description` = '".preg_replace('/[^\p{L}_\p{N}\s-]/u','',$fields->description)."'";
        if (isset($fields->name)) $array[] = "`name` = '".preg_replace('/[^\p{L}_\p{N}\s-.]/u','',$fields->name)."'";
        // Convert to a comma seperated string for the mysql query
        $fieldstr = implode(",",$array);
        $this->mysqli->query("UPDATE input SET ".$fieldstr." WHERE `id` = '$id'");

        // CHECK REDIS?
        // UPDATE REDIS
        if (isset($fields->name) && $this->redis) $this->redis->hset("input:$id",'name',$fields->name);
        if (isset($fields->description) && $this->redis) $this->redis->hset("input:$id",'description',$fields->description);

        if ($this->mysqli->affected_rows>0){
            return array('success'=>true, 'message'=>'Field updated');
        } else {
            return array('success'=>false, 'message'=>'Field could not be updated');
        }
    }

    public function get_inputs($userid)
    {
        if ($this->redis) {
            return $this->redis_get_inputs($userid);
        } else {
            return $this->mysql_get_inputs($userid);
        }
    }

    // USES: redis input & user
    private function redis_get_inputs($userid)
    {
        $userid = (int) $userid;
        if (!$this->redis->exists("user:inputs:$userid")) $this->load_to_redis($userid);

        $dbinputs = array();
        $inputids = $this->redis->sMembers("user:inputs:$userid");

        foreach ($inputids as $id)
        {
            $row = $this->redis->hGetAll("input:$id");
            if ($row['nodeid']==null) $row['nodeid'] = 0;
            if (!isset($dbinputs[$row['nodeid']])) $dbinputs[$row['nodeid']] = array();
            $dbinputs[$row['nodeid']][$row['name']] = array('id'=>$row['id'], 'processList'=>$row['processList']);
        }

        return $dbinputs;
    }

    private function mysql_get_inputs($userid)
    {
        $userid = (int) $userid;
        $dbinputs = array();
        $result = $this->mysqli->query("SELECT id,nodeid,name,description,processList FROM input WHERE `userid` = '$userid' ORDER BY nodeid,name asc");
        while ($row = (array)$result->fetch_object())
        {
            if ($row['nodeid']==null) $row['nodeid'] = 0;
            if (!isset($dbinputs[$row['nodeid']])) $dbinputs[$row['nodeid']] = array();
            $dbinputs[$row['nodeid']][$row['name']] = array('id'=>$row['id'], 'processList'=>$row['processList']);
        }
        return $dbinputs;
    }

    //-----------------------------------------------------------------------------------------------
    // This public function gets a users input list, its used to create the input/list page
    //-----------------------------------------------------------------------------------------------
    // USES: redis input & user & lastvalue

    public function getlist($userid)
    {
        if ($this->redis) {
            return $this->redis_getlist($userid);
        } else {
            return $this->mysql_getlist($userid);
        }
    }

    private function redis_getlist($userid)
    {
        $userid = (int) $userid;
        if (!$this->redis->exists("user:inputs:$userid")) $this->load_to_redis($userid);

        $inputs = array();
        $inputids = $this->redis->sMembers("user:inputs:$userid");
        foreach ($inputids as $id)
        {
            $row = $this->redis->hGetAll("input:$id");
            $row["description"] = utf8_encode($row["description"]);
         
            $lastvalue = $this->redis->hmget("input:lastvalue:$id",array('time','value'));
            // Fix break point where value is NAN
            $lastvalue['time'] = $lastvalue['time'] * 1; 
            $row['time'] = (int) $lastvalue['time'];
            if (is_nan($row['time'])) $row['time'] = 0;
         
            $lastvalue['value'] = $lastvalue['value'] * 1; 
            $row['value'] = (float) $lastvalue['value'];
            if (is_nan($row['value'])) $row['value'] = 0;
         
            $inputs[] = $row;
        }
        return $inputs;
    }

    private function mysql_getlist($userid)
    {
        $userid = (int) $userid;
        $inputs = array();

        $result = $this->mysqli->query("SELECT id,nodeid,name,description,processList,time,value FROM input WHERE `userid` = '$userid' ORDER BY nodeid,name asc");
        while ($row = (array)$result->fetch_object())
        {
            $inputs[] = $row;
        }
        return $inputs;
    }

    // USES: redis input
    public function get_name($id)
    {
        // LOAD REDIS
        $id = (int) $id;

        if ($this->redis) {
            if (!$this->redis->exists("input:$id")) $this->load_input_to_redis($id);
            return $this->redis->hget("input:$id",'name');
        } else {
            $result = $this->mysqli->query("SELECT name FROM input WHERE `id` = '$id'");
            $row = $result->fetch_array();
            return $row['name'];
        }
    }

    public function get_details($id)
    {
        // LOAD REDIS
        $id = (int) $id;
        if ($this->redis) {
            if (!$this->redis->exists("input:$id")) $this->load_input_to_redis($id);
            return $this->redis->hGetAll("input:$id");
        } else {
            $result = $this->mysqli->query("SELECT nodeid,name,description FROM input WHERE `id` = '$id'");
            return $result->fetch_array();
        }
    }

    public function get_last_value($id)
    {
        $id = (int) $id;

        if ($this->redis) {
            return $this->redis->hget("input:lastvalue:$id",'value');
        } else {
            $result = $this->mysqli->query("SELECT value FROM input WHERE `id` = '$id'");
            $row = $result->fetch_array();
            return $row['value'];
        }
    }


    // USES: redis input & user
    public function delete($userid, $inputid)
    {
        $userid = (int) $userid;
        $inputid = (int) $inputid;
        // Inputs are deleted permanentely straight away rather than a soft delete
        // as in feeds - as no actual feed data will be lost
        $this->mysqli->query("DELETE FROM input WHERE userid = '$userid' AND id = '$inputid'");

        if ($this->redis) {
            $this->redis->del("input:$inputid");
            $this->redis->srem("user:inputs:$userid",$inputid);
        }
    }

    public function clean($userid)
    {
        $result = "";
        $qresult = $this->mysqli->query("SELECT * FROM input WHERE `userid` = '$userid'");
        while ($row = $qresult->fetch_array())
        {
            $inputid = $row['id'];
            if ($row['processList']==NULL || $row['processList']=='')
            {
                $result = $this->mysqli->query("DELETE FROM input WHERE userid = '$userid' AND id = '$inputid'");

                if ($this->redis) {
                    $this->redis->del("input:$inputid");
                    $this->redis->srem("user:inputs:$userid",$inputid);
                }
                $result .= "Deleted input: $inputid <br>";
            }
        }
        return $result;
    }

    //------------------------
    // Processlist functions
    //------------------------
    // USES: redis input
    public function get_processlist($id)
    {
        // LOAD REDIS
        $id = (int) $id;

        if ($this->redis) {
            if (!$this->redis->exists("input:$id")) $this->load_input_to_redis($id);
            return $this->redis->hget("input:$id",'processList');
        } else {
            $result = $this->mysqli->query("SELECT processList FROM input WHERE `id` = '$id'");
            $row = $result->fetch_array();
            if (!$row['processList']) $row['processList'] = "";
            return $row['processList'];
        }
    }

    // USES: redis input
    public function set_processlist($id, $processlist)
    {
        $stmt = $this->mysqli->prepare("UPDATE input SET processList=? WHERE id=?");
        $stmt->bind_param("si", $processlist, $id);
        if (!$stmt->execute()) {
            return array('success'=>false, 'message'=>_("Error setting processlist"));
        }
        
        if ($this->mysqli->affected_rows>0){
            // CHECK REDIS
            if ($this->redis) $this->redis->hset("input:$id",'processList',$processlist);
            return array('success'=>true, 'message'=>'Input processlist updated');
        } else {
            return array('success'=>false, 'message'=>'Input processlist was not updated');
        }
    }

    // USES: redis input
    public function reset_processlist($id)
    {
        $id = (int) $id;
        return $this->set_processlist($id, "");
    }


    // Redis cache loaders
    private function load_input_to_redis($inputid)
    {
        $result = $this->mysqli->query("SELECT id,nodeid,name,description,processList FROM input WHERE `id` = '$inputid' ORDER BY nodeid,name asc");
        $row = $result->fetch_object();

        $this->redis->sAdd("user:inputs:$userid", $row->id);
        $this->redis->hMSet("input:$row->id",array(
            'id'=>$row->id,
            'nodeid'=>$row->nodeid,
            'name'=>$row->name,
            'description'=>$row->description,
            'processList'=>$row->processList
        ));
    }

    private function load_to_redis($userid)
    {
        $result = $this->mysqli->query("SELECT id,nodeid,name,description,processList FROM input WHERE `userid` = '$userid' ORDER BY nodeid,name asc");
        while ($row = $result->fetch_object())
        {
            $this->redis->sAdd("user:inputs:$userid", $row->id);
            $this->redis->hMSet("input:$row->id",array(
                'id'=>$row->id,
                'nodeid'=>$row->nodeid,
                'name'=>$row->name,
                'description'=>$row->description,
                'processList'=>$row->processList
            ));
        }
    }

}
