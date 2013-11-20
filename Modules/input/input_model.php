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
    private $conn;
    private $feed;

    public function __construct($conn,$feed)
    {
        $this->conn = $conn;
        $this->feed = $feed;
    }

    public function create_input($userid, $nodeid, $name)
    {
        $userid = (int) $userid;
        $nodeid = (int) $nodeid;
        $name = preg_replace('/[^\w\s-.]/','',$name);
        $sql = ("INSERT INTO input (userid, name, nodeid) VALUES ('$userid', '$name', '$nodeid');");
        db_query($this->conn, $sql);
    }

    public function set_timevalue($id, $time, $value)
    {
        $id = (int) $id;
        $time = (int) $time;
        $value = (float) $value;

        $time = date("Y-n-j H:i:s", $time);
        $sql = ("UPDATE input SET time = '$time', value = '$value' WHERE id = '$id';");
        db_query($this->conn, $sql);
    }

    public function belongs_to_user($userid, $inputid)
    {
        $userid = (int) $userid;
        $inputid = (int) $inputid;

        $sql = ("SELECT id FROM input WHERE userid = '$userid' AND id = '$inputid';");
        $result = db_query($this->conn, $sql);
        if (db_fetch_array($result)) return true; else return false;
    }

    public function record($id,$state)
    {
        $id = (int) $id;
        $state = (bool) $state;
        $sql = ("UPDATE input SET record = '$state' WHERE id = '$id';");
        db_query($this->conn, $sql);
    }

    private function set_processlist($id, $processlist)
    {
        $sql = ("UPDATE input SET processlist = '$processlist' WHERE id = '$id';");
        db_query($this->conn, $sql);
    }

    public function set_fields($id,$fields)
    {
        $id = intval($id);
        $fields = json_decode(stripslashes($fields));

        $array = array();

        // Repeat this line changing the field name to add fields that can be updated:
        if (isset($fields->description)) $array[] = "description = '".preg_replace('/[^\w\s-]/','',$fields->description)."'";
        if (isset($fields->name)) $array[] = "name = '".preg_replace('/[^\w\s-.]/','',$fields->name)."'";
        // Convert to a comma seperated string for the mysql query
        $fieldstr = implode(",",$array);
        $sql = ("UPDATE input SET " . $fieldstr . " WHERE id = '$id';");
        $result = db_query($this->conn, $sql);

        if (db_affected_rows($this->conn, $result)>0){
            return array('success'=>true, 'message'=>'Field updated');
        } else {
            return array('success'=>false, 'message'=>'Field could not be updated');
        }
    }

    public function add_process($process_class,$userid, $inputid, $processid, $arg, $newfeedname,$newfeedinterval)
    {
        $userid = (int) $userid;
        $inputid = (int) $inputid;	
        $processid = (int) $processid;			                              // get process type (ProcessArg::)
        $arg = (float) $arg;                                              // This is: actual value (i.e x0.01), inputid or feedid
        $newfeedname = preg_replace('/[^\w\s-.]/','',$newfeedname);	      // filter out all except for alphanumeric white space and dash
        $newfeedinterval = (int) $newfeedinterval;

        $process = $process_class->get_process($processid);
        $processtype = $process[1];                                       // Array position 1 is the processtype: VALUE, INPUT, FEED
        $datatype = $process[4];                                          // Array position 4 is the datatype

        switch ($processtype) {
            case ProcessArg::VALUE:                                           // If arg type value
                $arg = floatval($arg);
                $id = $arg;
                if ($arg == '') return array('success'=>false, 'message'=>'Argument must be a valid number greater or less than 0.');
                break;
            case ProcessArg::INPUTID:                 // If arg type input
                if (!$this->exists($arg)) return array('success'=>false, 'message'=>'Input does not exist!');
                $this->record($arg,true);
                break;
            case ProcessArg::FEEDID:                  // If arg type feed
                $name = ''; if ($arg!=-1) $name = $this->feed->get_field($arg,'name');  // First check if feed exists of given feed id and user.
                $id = $this->feed->get_id($userid,$name);
                if (($name == '') || ($id == '')) {
                    $result = $this->feed->create($userid,$newfeedname, $datatype, $newfeedinterval);
                    if ($result['success']==true) $arg = $result['feedid']; else return $result;
                }
                break;
            case ProcessArg::NONE:                                           // If arg type none
                $arg = 0;
                $id = $arg;
                break;
        }

        $list = $this->get_processlist($inputid);
        if ($list) $list .= ',';
        $list .= $processid . ':' . $arg;
        $this->set_processlist($inputid, $list);

        return array('success'=>true, 'message'=>'Process added');
    }

    public function exists($inputid)
    {
      $inputid = (int) $inputid;
      $sql = ("SELECT count(id) AS inputid FROM input WHERE id = '$inputid';");
      $result = db_query($this->conn, $sql);
      $exists = db_fetch($this->conn, $result);
      return ($exists['inputid'] > 0) ?TRUE : FALSE;
    }

    /******
    * delete input process by index
    ******/
    public function delete_process($inputid, $index)
    {
        $inputid = (int) $inputid;
        $index = (int) $index;

        $success = false;
        $index--; // Array is 0-based. Index from process page is 1-based.

        // Load process list
        $array = explode(",", $this->get_processlist($inputid));

        // Delete process
        if (count($array)>$index && $array[$index]) {unset($array[$index]); $success = true;}

        // Save new process list
        $this->set_processlist($inputid, implode(",", $array));

        return $success;
    }

    /******
    * move_input_process - move process up/down list of processes by $moveby (eg. -1, +1)
    ******/
    public function move_process($id, $index, $moveby)
    {
        $id = (int) $id;
        $index = (int) $index;
        $moveby = (int) $moveby;

        if (($moveby > 1) || ($moveby < -1)) return false;  // Only support +/-1 (logic is easier)

        $process_list = $this->get_processlist($id);
        $array = explode(",", $process_list);
        $index = $index - 1; // Array is 0-based. Index from process page is 1-based.
        
        $newindex = $index + $moveby; // Calc new index in array
        // Check if $newindex is greater than size of list
        if ($newindex > (count($array)-1)) $newindex = (count($array)-1);
        // Check if $newindex is less than 0
        if ($newindex < 0) $newindex = 0;
        
        $replace = $array[$newindex]; // Save entry that will be replaced
        $array[$newindex] = $array[$index];
        $array[$index] = $replace;

        // Save new process list
        $this->set_processlist($id, implode(",", $array));
        return true;
    }

    public function reset_process($id)
    {
       $id = (int) $id;
       $this->set_processlist($id, "");
    }

    public function get_inputs($userid)
    {
        $userid = (int) $userid;
        $sql = ("SELECT id, nodeid, name, processlist, record FROM input WHERE userid = '$userid';");
        $result = db_query($this->conn, $sql);

        $dbinputs = array();
        while ($row = db_fetch_object($result)) {
            if ($row->nodeid==null) $row->nodeid = 0;
            if (!isset($dbinputs[$row->nodeid])) $dbinputs[$row->nodeid] = array();
            $dbinputs[$row->nodeid][$row->name] = array('id'=>$row->id, 'processlist'=>$row->processlist, 'record'=>$row->record);
        }
        return $dbinputs;
    }

      //-----------------------------------------------------------------------------------------------
      // This public function gets a users input list, its used to create the input/list page
      //-----------------------------------------------------------------------------------------------
    public function getlist($userid)
    {
        $userid = (int) $userid;
        $sql = ("SELECT * FROM input WHERE userid = '$userid';");
        $result = db_query($this->conn, $sql);
        $inputs = array();
        while ($row = db_fetch_object($result)) $inputs[] = $row;
        return $inputs;
    }

    public function get_id($userid, $nodeid, $name)
    {
        $userid = (int) $userid;
        $sql = ("SELECT id FROM input WHERE nodeid='$nodeid' AND name='$name' AND userid='$userid';");
        $result = db_query($this->conn, $sql);
        if ($result)
        {
            $array = db_fetch_array($result);
            return $array['id'];
        }
        else
            return 0;
    }

    public function get_by_name($userid, $nodeid, $name)
    {
        $userid = (int) $userid;
        $nodeid = (int) $nodeid;
        $name = (int) $name;

        $sql = ("SELECT id, processlist, record FROM input WHERE nodeid = '$nodeid' AND name = '$name' AND userid = '$userid';");
        $result = db_query($this->conn, $sql);
        if ($result) {
            $row = db_fetch_object($result);
            return array('id'=>$row->id, 'processlist'=>$row->processlist, 'record'=>$row->record);
        }
        else
            return false;
    }

    public function get_name($id)
    {
        $id = (int) $id;
        $sql = ("SELECT name FROM input WHERE id='$id';");
        $result = db_query($this->conn, $sql);
        if ($result)
        {
            $array = db_fetch_array($result);
            return $array['name'];
        }
        else
            return 0;
    }

    public function get_processlist($id)
    {
        $id = (int) $id;
        $sql = ("SELECT processlist FROM input WHERE id='$id';");
        $result = db_query($this->conn, $sql);
	$array = db_fetch_array($result);
        return $array['processlist'];
    }

    //-----------------------------------------------------------------------------------------------
    // Gets the inputs process list and converts id's into descriptive text
    //-----------------------------------------------------------------------------------------------
    public function get_processlist_desc($process_class,$id)
    {
        $id = (int) $id;
        $process_list = $this->get_processlist($id);
        // Get the input's process list

        $list = array();
        if ($process_list)
        {
            $array = explode(",", $process_list);
            // input process list is comma seperated
            foreach ($array as $row)// For all input processes
            {
                $row = explode(":", $row);
                // Divide into process id and arg
                $processid = $row[0];
                $arg = $row[1];
                // Named variables
                $process = $process_class->get_process($processid);
                // gets process details of id given

                $processDescription = $process[0];
                // gets process description
                if ($process[1] == ProcessArg::INPUTID)
                  $arg = $this->get_name($arg);
                // if input: get input name
                elseif ($process[1] == ProcessArg::FEEDID)
                  $arg = $this->feed->get_field($arg,'name');
                // if feed: get feed name

                $list[] = array(
                  $processDescription,
                  $arg
                );
                // Populate list array
            }
        }
        return $list;
    }

    public function delete($userid, $inputid)
    {
        $userid = (int) $userid;
        $inputid = (int) $inputid;
        // Inputs are deleted permanentely straight away rather than a soft delete
        // as in feeds - as no actual feed data will be lost
        $sql = ("DELETE FROM input WHERE userid = '$userid' AND id = '$inputid';");
        db_query($this->conn, $sql);
    }
    
    public function delete_node($userid, $nodeid)
    {
        $userid = (int) $userid;
        $nodeid = (int) $nodeid;
        // Inputs are deleted permanentely straight away rather than a soft delete
        // as in feeds - as no actual feed data will be lost
        $sql = ("DELETE FROM input WHERE userid = '$userid' AND nodeid = '$nodeid';");
        db_query($this->conn, $sql);
    }
}
