<?php
/*
     Released under the GNU Affero General Public License.
     See COPYRIGHT.txt and LICENSE.txt.

     Device module contributed by Nuno Chaveiro nchaveiro(at)gmail.com 2015
     ---------------------------------------------------------------------
     Sponsored by http://archimetrics.co.uk/
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Device
{
    private $mysqli;
    private $redis;
    private $log;

    public function __construct($mysqli,$redis)
    {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        $this->log = new EmonLogger(__FILE__);
    }

    public function devicekey_session($devicekey_in)
    {
        $devicekey_in = $this->mysqli->real_escape_string($devicekey_in);
        $session = array();
        $time = time();

        //----------------------------------------------------
        // Check for devicekey login
        //----------------------------------------------------
        if($this->redis && $this->redis->exists("device:key:$devicekey_in"))
        {
            $session['userid'] = $this->redis->get("device:key:$devicekey_in:user");
            $session['read'] = 0;
            $session['write'] = 1;
            $session['admin'] = 0;
            $session['lang'] = "en"; // API access is always in english
            $session['username'] = "API";
            $session['deviceid'] = $this->redis->get("device:key:$devicekey_in:device");
            $session['nodeid'] = $this->redis->get("device:key:$devicekey_in:node");
            $this->redis->hMset("device:lastvalue:".$session['device'], array('time' => $time));
        }
        else
        {
            $result = $this->mysqli->query("SELECT id, userid, nodeid FROM device WHERE devicekey='$devicekey_in'");
            if ($result->num_rows == 1)
            {
                $row = $result->fetch_array();
                if ($row['id'] != 0)
                {
                    $session['userid'] = $row['userid'];
                    $session['read'] = 0;
                    $session['write'] = 1;
                    $session['admin'] = 0;
                    $session['lang'] = "en"; // API access is always in english
                    $session['username'] = "API";
                    $session['deviceid'] = $row['id'];
                    $session['nodeid'] = $row['nodeid'];
                    
                    if ($this->redis) {
                        $this->redis->set("device:key:$devicekey_in:user",$row['userid']);
                        $this->redis->set("device:key:$devicekey_in:device",$row['id']);
                        $this->redis->set("device:key:$devicekey_in:node",$row['nodeid']);
                        $this->redis->hMset("device:lastvalue:".$row['id'], array('time' => $time));
                    } else {
                        //$time = date("Y-n-j H:i:s", $time);
                        $this->mysqli->query("UPDATE device SET time='$time' WHERE id = '".$row['id']."'");
                    }
                }
            }
        }

        return $session;
    }

    public function exist($id)
    {
        $id = intval($id);
        static $device_exists_cache = array(); // Array to hold the cache
        if (isset($device_exists_cache[$id])) {
            $deviceexist = $device_exists_cache[$id]; // Retrieve from static cache
        } else {
            $result = $this->mysqli->query("SELECT id FROM device WHERE id = '$id'");
            $deviceexist = $result->num_rows>0;
            $device_exists_cache[$id] = $deviceexist; // Cache it
            $this->log->info("exist() $id");
        }
        return $deviceexist;
    }
    
    
    public function exists_name($userid,$name)
    {
        $userid = intval($userid);
        $name = preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$name);
        $result = $this->mysqli->query("SELECT id FROM device WHERE userid = '$userid' AND name = '$name'");
        if ($result->num_rows>0) { $row = $result->fetch_array(); return $row['id']; } else return false;
    }
    
    public function exists_nodeid($userid,$nodeid)
    {
        $userid = intval($userid);
        $nodeid = preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$nodeid);
        $result = $this->mysqli->query("SELECT id FROM device WHERE userid = '$userid' AND nodeid = '$nodeid'");
        if ($result->num_rows>0) { $row = $result->fetch_array(); return $row['id']; } else return false;
    }

    public function get($id)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Device does not exist');

        $result = $this->mysqli->query("SELECT * FROM device WHERE id = '$id'");
        $row = (array) $result->fetch_object();

        return $row;
    }
    
    public function get_list($userid)
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
        if (!$this->redis->exists("user:device:$userid")) $this->load_to_redis($userid);

        $devices = array();
        $deviceids = $this->redis->sMembers("user:device:$userid");
        foreach ($deviceids as $id)
        {
            $row = $this->redis->hGetAll("device:$id");
            $lastvalue = $this->redis->hMget("device:lastvalue:".$id,array('time'));
            $row['time'] = $lastvalue['time'];
            $devices[] = $row;
        }
        return $devices;
    }

    private function mysql_getlist($userid)
    {
        $userid = (int) $userid;
        $devices = array();

        $result = $this->mysqli->query("SELECT `id`, `userid`, `name`, `description`, `type`, `nodeid`, `devicekey`, `time` FROM device WHERE userid = '$userid'");
        while ($row = (array)$result->fetch_object())
        {
            $devices[] = $row;
        }
        return $devices;
    }

    private function load_to_redis($userid)
    {
        $this->redis->delete("user:device:$userid");
        $result = $this->mysqli->query("SELECT `id`, `name`, `description`, `type`, `nodeid`, `devicekey` FROM device WHERE userid = '$userid'");
        while ($row = $result->fetch_object())
        {
            $this->redis->sAdd("user:device:$userid", $row->id);
            $this->redis->hMSet("device:".$row->id,array(
                'id'=>$row->id,
                'name'=>$row->name,
                'description'=>$row->description,
                'type'=>$row->type,
                'nodeid'=>$row->nodeid,
                'devicekey'=>$row->devicekey
            ));
        }
    }
    
    public function autocreate($userid,$_nodeid,$_type)
    {
        $userid = intval($userid);
        
        $nodeid = preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$_nodeid);
        if ($_nodeid!=$nodeid) return array("success"=>false, "message"=>"Invalid nodeid");
        $type = preg_replace('/[^\/\|\,\w\s-:]/','',$_type);
        if ($_type!=$type) return array("success"=>false, "message"=>"Invalid type");
        
        $name = "$nodeid:$type";
        
        $deviceid = $this->exists_nodeid($userid,$nodeid);
        
        if (!$deviceid) {
            $deviceid = $this->create($userid,$nodeid);
            if (!$deviceid) return array("success"=>false, "message"=>"Device creation failed");
        }
        
        $result = $this->set_fields($deviceid,json_encode(array("name"=>$name,"nodeid"=>$nodeid,"type"=>$type)));
        if ($result["success"]==true) {
            return $this->init_template($deviceid);
        } else {
            return $result;
        }
    }   
    
    public function create($userid,$nodeid)
    {
        $userid = intval($userid);
        $nodeid = preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$nodeid);
        
        if (!$this->exists_nodeid($userid,$nodeid)) {
            $devicekey = md5(uniqid(mt_rand(), true));
            $this->mysqli->query("INSERT INTO device (`userid`, `name`, `description`, `nodeid`, `devicekey`) VALUES ('$userid','$nodeid','','$nodeid','$devicekey')");
            if ($this->redis) $this->load_to_redis($userid);
            return $this->mysqli->insert_id;
        } else {
            return false;
        }
    }

    public function delete($id)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Device does not exist');

        if ($this->redis) {
            $result = $this->mysqli->query("SELECT userid FROM device WHERE `id` = '$id'");
            $row = (array) $result->fetch_object();
        }

        $result = $this->mysqli->query("DELETE FROM device WHERE `id` = '$id'");
        if (isset($device_exists_cache[$id])) { unset($device_exists_cache[$id]); } // Clear static cache
        
        if ($this->redis) {
            if (isset($row['userid']) && $row['userid']) {
                $this->redis->delete("device:".$id);
                $this->load_to_redis($row['userid']);
            }
        }
    }
    
    public function set_fields($id,$fields)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Device does not exist');

        $fields = json_decode(stripslashes($fields));

        $array = array();

        // Repeat this line changing the field name to add fields that can be updated:
        if (isset($fields->name)) $array[] = "`name` = '".preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$fields->name)."'";
        if (isset($fields->description)) $array[] = "`description` = '".preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$fields->description)."'";
        if (isset($fields->nodeid)) $array[] = "`nodeid` = '".preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$fields->nodeid)."'";
        if (isset($fields->devicekey)) {
            $devicekey = preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$fields->devicekey);
            $result = $this->mysqli->query("SELECT devicekey FROM device WHERE devicekey='$devicekey'");
            if ($result->num_rows > 0)
            {
                return array('success'=>false, 'message'=>'Field devicekey is invalid'); // is duplicate
            }
            $array[] = "`devicekey` = '".$devicekey."'";
        }
        if (isset($fields->type)) $array[] = "`type` = '".preg_replace('/[^\/\|\,\w\s-:]/','',$fields->type)."'";

        // Convert to a comma seperated string for the mysql query
        $fieldstr = implode(",",$array);
        $this->mysqli->query("UPDATE device SET ".$fieldstr." WHERE `id` = '$id'");

        if ($this->mysqli->affected_rows>0){
            if ($this->redis) {
                $result = $this->mysqli->query("SELECT userid FROM device WHERE id='$id'");
                $row = (array) $result->fetch_object();
                if (isset($row['userid']) && $row['userid']) {
                    $this->load_to_redis($row['userid']);
                }
            }
            return array('success'=>true, 'message'=>'Field updated');
        } else {
            return array('success'=>false, 'message'=>'Field could not be updated');
        }
    }

    public function get_templates()
    {
        $devices = array();
        $devices = $this->load_devices_template();
        return $devices;
    }

    private function load_devices_template() {
        $list = array();
        foreach (glob("Modules/device/data/*.json") as $file) {
            $content = json_decode(file_get_contents($file));
            $list[basename($file, ".json")] = $content;
        }
        return $list;
    }

    public function get_template($device) {
        $device = preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$device);
        if (file_exists("Modules/device/data/$device.json")) {
            return json_decode(file_get_contents("Modules/device/data/$device.json"));
        }
    }

    public function init_template($id)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Device does not exist');

        $result = $this->mysqli->query("SELECT * FROM device WHERE id = '$id'");
        $row = (array) $result->fetch_object();

        if (isset($row['type']) && $row['type']) {
            $file = "Modules/device/data/".$row['type'].".json";
            if (file_exists($file)) {
                $template = json_decode(file_get_contents($file));
            } else {
                return array('success'=>false, 'message'=>"Template file not found '" . $file . "'");
            }

            $userid = $row['userid'];
            $node = $row['nodeid'];
            $feeds = $template->feeds;
            $inputs = $template->inputs;

            // Create feeds
            $result = $this->create_feeds($userid, $node, $feeds);
            if ($result["success"] !== true) {
              return array('success'=>false, 'message'=>'Error while creating the feeds. ' . $result['message']);
            }

            // Create inputs
            $result = $this->create_inputs($userid, $node, $inputs);
            if ($result !== true) {
              return array('success'=>false, 'message'=>'Error while creating the inputs.');
            }

            // Create inputs processes
            $result = $this->create_inputs_processes($feeds, $inputs);
            if ($result["success"] !== true) {
              return array('success'=>false, 'message'=>'Error while creating the inputs process list. ' . $result['message']);
            }
            
            // Create feeds processes
            $result = $this->create_feeds_processes($feeds, $inputs);
            if ($result["success"] !== true) {
              return array('success'=>false, 'message'=>'Error while creating the feeds process list. ' . $result['message']);
            }
        }
        return array('success'=>true, 'message'=>'Device initialized');
    }

    // Create the feeds
    private function create_feeds($userid, $node, &$feedArray) {
        global $feed_settings;

        require_once "Modules/feed/feed_model.php";
        $feed = new Feed($this->mysqli,$this->redis,$feed_settings);
        
        $result = array("success"=>true);
        
        foreach($feedArray as $f) {
            // Create each feed
            $name = $f->name;
            if (property_exists($f, "tag")) {
                $tag = $f->tag;
            } else {
                $tag = $node;
            }
            $datatype = constant($f->type); // DataType::
            $engine = constant($f->engine); // Engine::
            $options_in = new stdClass();
            if (property_exists($f, "interval")) {
                $options_in->interval = $f->interval;
            }
            $this->log->info("create_feeds() userid=$userid tag=$tag name=$name datatype=$datatype engine=$engine");
            $result = $feed->create($userid,$tag,$name,$datatype,$engine,$options_in);
            if($result["success"] !== true) {
                return $result;
            }
            $f->feedId = $result["feedid"]; // Assign the created feed id to the feeds array
        }
        return $result;
    }

    // Create the inputs
    private function create_inputs($userid, $node, &$inputArray) {
        require_once "Modules/input/input_model.php";
        $input = new Input($this->mysqli,$this->redis, null);

        foreach($inputArray as $i) {
          // Create each input
          $name = $i->name;
          $description = $i->description;
          if(property_exists($i, "node")) {
            $nodeid = $i->node;
          } else {
            $nodeid = $node;
          }
          
          $inputId = $input->exists_nodeid_name($userid,$nodeid,$name);
          
          if ($inputId==false) {
            $this->log->info("create_inputs() userid=$userid nodeid=$nodeid name=$name description=$description");
            $inputId = $input->create_input($userid, $nodeid, $name);
            if(!$input->exists($inputId)) {
                return false;
            }
            $input->set_fields($inputId, '{"description":"'.$description.'"}');
          }
          $i->inputId = $inputId; // Assign the created input id to the inputs array
        }
        return true;
    }

    // Create the inputs process lists
    private function create_inputs_processes($feedArray, $inputArray) {
        require_once "Modules/input/input_model.php";
        $input = new Input($this->mysqli,$this->redis, null);

        foreach($inputArray as $i) {
            // for each input
            if (isset($i->processList)) {
                $inputId = $i->inputId;
                $result = $this->convertTemplateProcessList($feedArray, $inputArray, $i->processList);
                if (isset($result["success"])) {
                    return $result; // success is only filled if it was an error
                }

                $processes = implode(",", $result);
                if ($processes != "") {
                    $this->log->info("create_inputs_processes() calling input->set_processlist inputId=$inputId processes=$processes");
                    $input->set_processlist($inputId, $processes);
                }
            }
        }

        return array('success'=>true);
    }

    private function create_feeds_processes($feedArray, $inputArray) {
        global $feed_settings;

        require_once "Modules/feed/feed_model.php";
        $feed = new Feed($this->mysqli,$this->redis,$feed_settings);

        foreach($feedArray as $f) {
            // for each feed
            if (($f->engine == Engine::VIRTUALFEED) && isset($f->processList)) {
                $feedId = $f->feedId;
                $result = $this->convertTemplateProcessList($feedArray, $inputArray, $f->processList);
                if (isset($result["success"])) {
                    return $result; // success is only filled if it was an error
                }

                $processes = implode(",", $result);
                if ($processes != "") {
                    $this->log->info("create_feeds_processes() calling feed->set_processlist feedId=$feedId processes=$processes");
                    $feed->set_processlist($feedId, $processes);
                }
            }
        }

        return array('success'=>true);
    }
    
    // Converts template processList
    private function convertTemplateProcessList($feedArray, $inputArray, $processArray){
        $resultProcesslist = array();
        if (is_array($processArray)) {
            require_once "Modules/process/process_model.php";
            $process = new Process(null,null,null,null);
            $process_list = $process->get_process_list(); // emoncms supported processes

            $process_list_by_name = array();
            foreach ($process_list as $process_id=>$process_item) {
                $name = $process_item[2];
                $process_list_by_name[$name] = $process_id;
            }

            // create each processlist
            foreach($processArray as $p) {
                $proc_name = $p->process;
                
                // If process names are used map to process id
                if (isset($process_list_by_name[$proc_name])) $proc_name = $process_list_by_name[$proc_name];
                
                if (!isset($process_list[$proc_name])) {
                    $this->log->error("convertProcess() Process '$proc_name' not supported. Module missing?");
                    return array('success'=>false, 'message'=>"Process '$proc_name' not supported. Module missing?");
                }

                // Arguments
                if(isset($p->arguments)) {
                    if(isset($p->arguments->type)) {
                        $type = @constant($p->arguments->type); // ProcessArg::
                        $process_type = $process_list[$proc_name][1]; // get emoncms process ProcessArg

                        if ($process_type != $type) {
                            $this->log->error("convertProcess() Bad device template. Missmatch ProcessArg type. Got '$type' expected '$process_type'. process='$proc_name' type='".$p->arguments->type."'");
                            return array('success'=>false, 'message'=>"Bad device template. Missmatch ProcessArg type. Got '$type' expected '$process_type'. process='$proc_name' type='".$p->arguments->type."'");
                        }

                        if (isset($p->arguments->value)) {
                            $value = $p->arguments->value;
                        } else if ($type === ProcessArg::NONE) {
                            $value = 0;
                        } else {
                            $this->log->error("convertProcess() Bad device template. Undefined argument value. process='$proc_name' type='".$p->arguments->type."'");
                            return array('success'=>false, 'message'=>"Bad device template. Undefined argument value. process='$proc_name' type='".$p->arguments->type."'");
                        }

                        if ($type === ProcessArg::VALUE) {
                        } else if ($type === ProcessArg::INPUTID) {
                            $temp = $this->searchArray($inputArray,'name',$value); // return input array that matches $inputArray[]['name']=$value
                            if ($temp->inputId > 0) {
                                $value = $temp->inputId;
                            } else {
                                $this->log->error("convertProcess() Bad device template. Input name '$value' was not found. process='$proc_name' type='".$p->arguments->type."'");
                                return array('success'=>false, 'message'=>"Bad device template. Input name '$value' was not found. process='$proc_name' type='".$p->arguments->type."'");
                            }
                        } else if ($type === ProcessArg::FEEDID) {
                            $temp = $this->searchArray($feedArray,'name',$value); // return feed array that matches $feedArray[]['name']=$value
                            if ($temp->feedId > 0) {
                                $value = $temp->feedId;
                            } else {
                                $this->log->error("convertProcess() Bad device template. Feed name '$value' was not found. process='$proc_name' type='".$p->arguments->type."'");
                                return array('success'=>false, 'message'=>"Bad device template. Feed name '$value' was not found. process='$proc_name' type='".$p->arguments->type."'");
                            }
                        } else if ($type === ProcessArg::NONE) {
                            $value = 0;
                        } else if ($type === ProcessArg::TEXT) {
//                      } else if ($type === ProcessArg::SCHEDULEID) { //not supporte for now
                        } else {
                                $this->log->error("convertProcess() Bad device template. Unsuported argument type. process='$proc_name' type='".$p->arguments->type."'");
                                return array('success'=>false, 'message'=>"Bad device template. Unsuported argument type. process='$proc_name' type='".$p->arguments->type."'");
                        }

                    } else {
                        $this->log->error("convertProcess() Bad device template. Argument type is missing, set to NONE if not required. process='$proc_name' type='".$p->arguments->type."'");
                        return array('success'=>false, 'message'=>"Bad device template. Argument type is missing, set to NONE if not required. process='$proc_name' type='".$p->arguments->type."'");
                    }

                    $this->log->info("convertProcess() process process='$proc_name' type='".$p->arguments->type."' value='" . $value . "'");
                    $resultProcesslist[] = $proc_name.":".$value;

                } else {
                    $this->log->error("convertProcess() Bad device template. Missing processlist arguments. process='$proc_name'");
                    return array('success'=>false, 'message'=>"Bad device template. Missing processlist arguments. process='$proc_name'");
                }
            }
        }
        return $resultProcesslist;
    }

    private function searchArray($array, $key, $val) {
        foreach ($array as $item)
            if (isset($item->$key) && $item->$key == $val)
                return $item;
        return null;
    }
}
