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
    public $mysqli;
    public $redis;
    private $log;
    
    private $templates = array();

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
        
        $deviceid = $this->exists_nodeid($userid, $nodeid);
        
        if (!$deviceid) {
            $deviceid = $this->create($userid, $nodeid, null, null, null);
            if (!$deviceid) return array("success"=>false, "message"=>"Device creation failed");
        }
        
        $result = $this->set_fields($deviceid,json_encode(array("name"=>$name,"nodeid"=>$nodeid,"type"=>$type)));
        if ($result["success"]==true) {
            return $this->init_template($deviceid);
        } else {
            return $result;
        }
    }   
    
    public function create($userid, $nodeid, $name, $description, $type)
    {
        $userid = intval($userid);
        $nodeid = preg_replace('/[^\p{L}_\p{N}\s-:]/u', '', $nodeid);
        if (isset($name)) {
            $name = preg_replace('/[^\p{L}_\p{N}\s-:]/u', '', $name);
        }
        else $name = $nodeid;
        
        if (isset($description)) {
            $description= preg_replace('/[^\p{L}_\p{N}\s-:]/u', '', $description);
        }
        else $description = '';
        
        if (!$this->exists_nodeid($userid,$nodeid)) {
            $devicekey = md5(uniqid(mt_rand(), true));
            $this->mysqli->query("INSERT INTO device (`userid`, `nodeid`, `name`, `description`, `type`, `devicekey`) VALUES ('$userid','$nodeid','$name','$description','$type','$devicekey')");
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

    public function get_template_list()
    {
        return $this->load_modules();
    }

    public function get_template_list_short()
    {
        if (empty($this->templates)) { // Cache it now
            $this->load_modules();
        }
        return $this->templates;
    }
    
    public function get_template($device)
    {
        if (empty($this->templates)) { // Cache it now
            $this->load_modules();
        }
        
        if (isset($this->templates[$device])) {
            $module = $this->templates[$device]['module'];
            $class = $this->get_module_class($module);
            if ($class != null) {
                return $class->get($device);
            }
        }
        else {
            return array('success'=>false, 'message'=>'Device template does not exist');
        }
        
        return array('success'=>false, 'message'=>'Unknown error while loading device template details');
    }

    public function init_template($id)
    {
        if (empty($this->templates)) { // Cache it now
            $this->load_modules();
        }
        
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'Device does not exist');
        
        $device = $this->get($id);
        if (isset($device['type']) && $device['type'] != 'null' && $device['type']) {
            if (isset($this->templates[$device['type']])) {
                $module = $this->templates[$device['type']]['module'];
                $class = $this->get_module_class($module);
                if ($class != null) {
                    return $class->init($device['userid'], $device['nodeid'], $device['name'], $device['type']);
                }
            }
            else {
                return array('success'=>false, 'message'=>'Device template does not exist');
            }
        }
        else {
            return array('success'=>false, 'message'=>'Device type not specified');
        }
        
        return array('success'=>false, 'message'=>'Unknown error while initializing device');
    }

    private function load_modules()
    {
        $list = array();
        $dir = scandir("Modules");
        for ($i=2; $i<count($dir); $i++) {
            if (filetype("Modules/".$dir[$i])=='dir' || filetype("Modules/".$dir[$i])=='link') {
                $class = $this->get_module_class($dir[$i]);
                if ($class != null) {
                    $module_templates = $class->get_list();
                    foreach($module_templates as $key => $value){
                        $list[$key] = $value;
                        
                        $device = array(
                                'module'=>$dir[$i]
                        );
                        $device["name"] = ((!isset($value->name) || $value->name == "" ) ? $key : $value->name);
                        $device["category"] = ((!isset($value->category) || $value->category== "" ) ? "General" : $value->category);
                        $device["group"] = ((!isset($value->group) || $value->group== "" ) ? "Miscellaneous" : $value->group);
                        $device["description"] = (!isset($value->description) ? "" : $value->description);
                        $device["control"] = (!isset($value->control) ? false : true);
                        $this->templates[$key] = $device;
                    }
                }
            }
        }
        return $list;
    }

    private function get_module_class($module)
    {
        /*
         magic function __call (above) MUST BE USED with this.
         Load additional template module files.
         Looks in the folder Modules/modulename/ for a file modulename_template.php
         (module_name all lowercase but class ModulenameTemplate in php file that is CamelCase)
         */
        $module_file = "Modules/".$module."/".$module."_template.php";
        $module_class = null;
        if(file_exists($module_file)){
            require_once($module_file);
            
            $module_class_name = ucfirst(strtolower($module)."Template");
            $module_class = new $module_class_name($this);
        }
        return $module_class;
    }
}
