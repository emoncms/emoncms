<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.
 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project: http://openenergymonitor.org
 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class ProcessError {
    const NONE = 0;
    const TOO_MANY_ITERATIONS = 1;
    const ACCESS_FORBIDDEN = 2;
}

class ProcessOriginType {
    const INPUT = 1;
    const VIRTUALFEED = 2;
    const TASK = 3;
}

class Process
{
    public $mysqli;
    public $input;
    public $feed;
    public $timezone = 'UTC';

    public $proc_initialvalue;  // save the input value at beginning of the processes list execution
    public $proc_skip_next;     // skip execution of next process in process list
    public $proc_goto;          // goto step in process list
    
    public $runtime_error = ProcessError::NONE;  // Errors that occured at runtime
    
    private $log;
    private $modules_functions = array();
    
    private $process_list = array();
    public $process_map = array();
    public $process_map_reverse = array(); // Reverse map for process ids to process keys
    
    public function __construct($mysqli,$input,$feed,$timezone)
    {
        $this->mysqli = $mysqli;
        $this->input = $input;
        $this->feed = $feed;
        if (!($timezone === NULL)) $this->timezone = $timezone;
        $this->log = new EmonLogger(__FILE__);
        
        $this->process_list = $this->get_process_list(); // Load modules modules
    
        // Build map of processids where set
        $this->process_map = array();
        foreach ($this->process_list as $k=>$v) {
            if (isset($v['id_num'])) $this->process_map[$v['id_num']] = $k;
        }

        // Build reverse map of process keys to process ids
        $this->process_map_reverse = array();
        foreach ($this->process_map as $id_num => $process_key) {
            $this->process_map_reverse[$process_key] = $id_num;
        }
    }

    // Triggered when invoking inaccessible methods in this class context, it must be a module function then
    public function __call($method, $args){
        if (strpos($method, '__') === FALSE) {
            $module = "process";              // default to core module 'process'
        } else {
            $mod_fun = explode('__',$method);  // if method contains a '__', assume the format is module__function
            $module = $mod_fun[0];
            $method = $mod_fun[1];
        }
        if(isset($this->modules_functions[$module][$method])) {
            $args[] = &$this;
            return call_user_func_array(array($this->modules_functions[$module][$method], $method), $args);
        } else {
            $this->log->error("__call() Call to undefined module method. Missing function on module? method=$method ");
            throw new Exception("ABORTED: Call to undefined module method. Missing function on module? method=$method");
        }
    }

    public function get_process_list()
    {
        static $list = array(); // Array to hold the cache

        if (empty($list) || empty($this->modules_functions)) {     // Cache it now
            $list=$this->load_modules();  

            // Convert singular arg definitions to args array (this could be removed by hard-coding this in the process list)
            $list = $this->convert_arg_structure($list);
        }
        return $list;
    }

    public function get_info($processkey)
    {
        if (isset($this->process_list[$processkey])) {
            return $this->process_list[$processkey];
        } else {
            $this->log->error("get_process_info() Process key '$processkey' does not exist in process list.");
            return null;
        }
    }

    public function input($time, $value, $processList, $options = null)
    {
        $this->proc_initialvalue = $value; // save the input value at beginning of the processes list execution
        $this->proc_skip_next = false;     // skip execution of next process in process list

        $process_list = $this->get_process_list();
        $pairs = explode(",",$processList);
        $total = count($pairs);
        $steps=0;

        for ($this->proc_goto=0; $this->proc_goto<$total; $this->proc_goto++) {
            $steps++;
            $inputprocess = explode(":", $pairs[$this->proc_goto]);  // Divide into process key and arg

            $id_and_arg_count = count($inputprocess);
            // if less than 1, skip this process
            if ($id_and_arg_count < 1) continue;

            // processkey may be an id or a module function name
            $processkey = $inputprocess[0];
            
            // Map ids to process key names
            if (isset($this->process_map[$processkey])) $processkey = $this->process_map[$processkey];
            
            // Check if processkey exists in the process list
            if (!isset($process_list[$processkey])) {
                $this->log->error("input() Processor '".$processkey."' does not exists. Module missing?");
                return false;
            }

            $arg_count = $id_and_arg_count - 1;

            if ($arg_count == 1) {
                // Singular arg, just the value
                $args = $inputprocess[1];
            } else if ($arg_count > 1) {
                // Multiple args (remove the process id)
                $args = array_slice($inputprocess, 1);
            } else {
                $args = null;
            }
            
            $process_function = $processkey;
            
            // Perhaps a more comprehensive check for valid process functions required here
            // or rely on validation when setting up the process list?
            $not_for_virtual_feeds = array('publish_to_mqtt','eventp__sendemail');
            if (in_array($process_function, $not_for_virtual_feeds) && isset($options['sourcetype']) && $options['sourcetype']==ProcessOriginType::VIRTUALFEED) {
                $this->log->error('Publish to MQTT and SendMail blocked for Virtual Feeds');
            } else {
                $value = $this->$process_function($args,$time,$value,$options); // execute process function
            }
            
            if ($this->proc_skip_next) {
                $this->proc_skip_next = false; 
                $this->proc_goto++;
            }

            if ($steps > $total*2) {
                // We are executing a looping processlist or too much gotos
                // need to add 'error_found' process to this processList.
                $this->runtime_error = ProcessError::TOO_MANY_ITERATIONS;
                $this->log->error("input() DEACTIVATED processList due to too many steps. steps=$steps proc_goto=".$this->proc_goto." processkey=$processkey sourcetype=" . $options['sourcetype'] . " sourceid=" . $options['sourceid'] );
                switch ($options['sourcetype']) {
                    case ProcessOriginType::INPUT:
                         $this->input->set_processlist_error_found($options['sourceid']);
                         break;
                    case ProcessOriginType::VIRTUALFEED:
                         $this->feed->set_processlist_error_found($options['sourceid']);
                         break;
                    /*
                    // Task module is deprecated, code reflects a much earlier version of emoncms, commented out for now
                    case ProcessOriginType::TASK:
                        if (file_exists("Modules/task/task_model.php")) {
                            global $session, $redis;
                            require_once "Modules/task/task_model.php";
                            $this->task = new Task($this->mysqli, $redis, null);
                            $this->task->set_processlist($session['userid'], $options['sourceid'], "process__error_found:0," . $processList);
                            
                        }
                    */
                }
                return false;
            }
        }

        return $value;
    }

    private function load_modules() {
        $list = array();

        // Always load the process module processes first
        $modules = array("process");
        
        // Scan all other modules for process lists
        $dir = scandir("Modules");        
        for ($i=2; $i<count($dir); $i++) {
            $module = $dir[$i];
            if (filetype("Modules/$module")=='dir' || filetype("Modules/$module")=='link') {
                if ($module!="process") $modules[] = $module;
            }
        }
        
        // Load processes from selected modules
        for ($i=0; $i<count($modules); $i++) {
            $class = $this->get_module_class($modules[$i]);
            if ($class != null) {
                
                $mod_process_list = $class->process_list();
                
                foreach($mod_process_list as $k => $v) {
                    $processkey = strtolower($modules[$i]."__".$v['function']);
                    $list[$processkey] = $v; // set list key as "module__function"
                    //$this->log->info("load_modules() module=$dir[$i] function=$v[2]"); 
                }
            }
        }
        return $list;
    }

    private function get_module_class($module_name){
        /*
            magic function __call (above) MUST BE USED with this.
            Load additional processlist module files.
            Looks in the folder Modules/modulename/ for a file modulename_processlist.php 
            (module_name all lowercase but class Modulename_ProcessList in php file that is with upper case first letter)
        */
        $module_file = "Modules/".$module_name."/".$module_name."_processlist.php";
        $module_class=null;
        if(file_exists($module_file)){
            require_once($module_file);
            $module_class_name = ucfirst(strtolower($module_name)."_ProcessList");
            $module_class = new $module_class_name($this); // passes this class as reference
            $module_class_functions = get_class_methods($module_class);
            foreach($module_class_functions as $key => $function_name){
                if (substr($function_name, 0, 2) == "__" || $function_name == "process_list") continue;
                $this->modules_functions[strtolower($module_name)][strtolower($function_name)] = &$module_class;
            }
        }
        return $module_class;
    }

    /**
     * 1. Convert singular argument definitions to an args array for each process.
     * Ensures each process has an 'args' array, converting from 'argtype' if needed.
     *
     * @param array $processes The array of processes to convert.
     * @return array The updated array of processes.
     */
    private function convert_arg_structure($processes) {
        foreach ($processes as $key => $process) {
            // If 'args' does not exist or is not an array, create it from 'argtype'
            if (!isset($process['args']) || !is_array($process['args'])) {
                if (isset($process['argtype'])) {
                    // Base type
                    $singular_arg = array("type" => $process['argtype']);
                    // Remove 'argtype' as it is no longer needed
                    unset($process['argtype']);

                    // Copy over 'engines' if available
                    if (isset($process['engines']) && is_array($process['engines'])) {
                        $singular_arg['engines'] = $process['engines'];
                        // remove 'engines' from process as it is now in the singular arg
                        unset($process['engines']);
                    }

                    // Copy over 'default' if available
                    if (isset($process['default'])) {
                        $singular_arg['default'] = $process['default'];
                    }

                    // Copy over 'unit' if available
                    if (isset($process['unit']) && $process['unit'] != "") {
                        $singular_arg['unit'] = $process['unit'];
                    }

                    $process['args'] = array($singular_arg);
                    
                } else {
                    // If no 'argtype', initialize 'args' as an empty array
                    $process['args'] = array();
                }
            }
            $processes[$key] = $process;
        }
        return $processes;
    }

    /**
     * 2. Filter the process list to only include processes that are valid for the given context type.
     * 
     * @param array $process_list The list of processes to filter.
     * @param int $context_type The context type (0 for input, 1 for virtual feed).
     * @return array The filtered list of valid processes.
     */
    public function filter_valid($process_list, $context_type = 0)
    {
        // Filter the process list to only include processes that are valid for the given source type
        $valid_processes = array();
        foreach ($process_list as $key => $process) {

            if (isset($process['deleted']) && $process['deleted'] === true) {
                // Skip deleted processes
                continue;
            }

            if (!isset($process['input_context'])) $process['input_context'] = true; // Default to true if not set
            if (!isset($process['virtual_feed_context'])) $process['virtual_feed_context'] = true; // Default to true if not set

            if ($context_type == 0 && !$process['input_context']) {
                // If context type is input and process is not valid for input context, skip it
                continue;
            } elseif ($context_type == 1 && !$process['virtual_feed_context']) {
                // If context type is virtual feed and process is not valid for virtual feed context, skip it
                continue;
            }
            // If no context type is specified, include all processes

            $valid_processes[$key] = $process; // Add valid process to the list
        }
        return $valid_processes;
    }

    // ---------------- Input and Feed Process List Methods ----------------

    /**
     * Validate a process list for a given user and context type.
     * 
     * @param int $userid The user ID.
     * @param int $id The ID of the input or feed.
     * @param string $processlist The process list to validate.
     * @param int $context_type The context type (0 for input, 1 for feed).
     * @return array An array containing 'success' status and either 'processlist' or 'message'.
     */
    public function validate_processlist($userid, $id, $processlist, $context_type = 0)
    {
        $userid = (int) $userid;
        $id = (int) $id;

        $processes = $this->process_list; // Get the process list
        $processes = $this->filter_valid($processes, $context_type); // Filter the process list based on context type

        // Process list expected in new JSON format
        $processlist = json_decode($processlist, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array('success' => false, 'message' => _("Invalid process list format: "));
        }
        if (!is_array($processlist)) {
            return array('success' => false, 'message' => _("Process list must be an array"));
        }

        foreach ($processlist as $index => $inputprocess) {
            if (!isset($inputprocess['fn'])) {
                return array('success' => false, 'message' => _("Missing process key in process list at index $index"));
            }

            if (!isset($inputprocess['args']) || !is_array($inputprocess['args'])) {
                return array('success' => false, 'message' => _("Invalid or missing args in process list at index $index"));
            }

            // Verify process key
            $process_key = $inputprocess['fn'];
            if (!isset($processes[$process_key])) {
                return array('success' => false, 'message' => _("Invalid process process key:$process_key"));
            }

            // Process arguments can be defined with an args array or a singular argtype
            // 1. Check if args array has been defined (this over-rides argtype if also present).
            // 2. If no args array and argtype is defined, convert argtype to an array with a single value.
            if (isset($processes[$process_key]['args']) && is_array($processes[$process_key]['args'])) {
                $arg_types = $processes[$process_key]['args'];
            } elseif (isset($processes[$process_key]['argtype'])) {
                $arg_types = array(array("type" => $processes[$process_key]['argtype']));
            } else {
                $arg_types = array(array("type" => ProcessArg::NONE));
            }

            $args = $inputprocess['args']; // Get the args from the input process

            // Validate number of args against arg_types
            if (count($args) != count($arg_types)) {
                return array('success' => false, 'message' => _("Invalid number of arguments for process: $processkey"));
            }

            // Validate each arg against its type
            for ($i = 0; $i < count($arg_types); $i++) {
                $arg_validate_result = $this->validate_arg($userid, $args[$i], $arg_types[$i]['type']);
                if (!$arg_validate_result['success']) {
                    return $arg_validate_result;
                }
            }
        }

        $processlist_out = $this->encode_processlist($processlist); // Encode the process list to a string

        return array('success' => true, 'processlist' => $processlist_out);
    }

    /**
     * Helper method for validate_processlist: Validate an argument against its type.
     * 
     * @param int $userid The user ID.
     * @param mixed $arg The argument to validate.
     * @param int $arg_type The type of the argument (ProcessArg constants).
     * @return array An array containing 'success' status and either 'message' or 'arg'.
     */
    private function validate_arg($userid, $arg, $arg_type)
    {
        // Check argument against process arg type
        switch ($arg_type) {

            case ProcessArg::FEEDID:
                $feedid = (int) $arg;
                if (!$this->arg_access("feeds", $userid, $feedid)) {
                    return array('success' => false, 'message' => _("Invalid feed"));
                }
                break;

            case ProcessArg::INPUTID:
                $inputid = (int) $arg;
                if (!$this->arg_access("input", $userid, $inputid)) {
                    return array('success' => false, 'message' => _("Invalid input"));
                }
                break;

            case ProcessArg::VALUE:
                if (!is_numeric($arg)) {
                    return array('success' => false, 'message' => 'Value is not numeric');
                }
                break;

            case ProcessArg::TEXT:
                if (preg_replace('/[^{}\p{N}\p{L}_\s\/.\-]/u', '', $arg) != $arg)
                    return array('success' => false, 'message' => 'Invalid characters in arg');
                break;

            case ProcessArg::SCHEDULEID:
                $scheduleid = (int) $arg;
                if (!$this->arg_access("schedule", $userid, $scheduleid)) {
                    return array('success' => false, 'message' => 'Invalid schedule');
                }
                break;

            case ProcessArg::NONE:
            default:
                $arg = false;
                break;
        }

        return array('success' => true, 'message' => 'Arg is valid');
    }

    /**
     * Helper method for validate_arg: Check if the user has access to the specified table and id.
     * 
     * @param string $table_name The name of the table (feeds, input, schedule).
     * @param int $userid The user ID.
     * @param int $id The ID to check.
     * @return bool True if access is granted, false otherwise.
     */
    private function arg_access($table_name, $userid, $id)
    {
        $userid = (int) $userid;
        $id = (int) $id;

        // Table name can be 'feed', 'input', or 'schedule'
        $table_name = strtolower($table_name);
        if (!in_array($table_name, array('feeds', 'input', 'schedule'))) {
            return false;
        }

        $stmt = $this->mysqli->prepare("SELECT id FROM $table_name WHERE userid=? AND id=?");
        $stmt->bind_param("ii",$userid,$id);
        $stmt->execute();
        $stmt->bind_result($id);
        $result = $stmt->fetch();
        $stmt->close();
        if ($result && $id>0) return true; else return false;
    }

    /**
     * Decode a process list string into an array of process items.
     * Example input: "process__log_to_feed_join:2095,4:2096,29:1564,47:10,24:,schedule__if_not_schedule_zero:3"
     * Returns: [ ['fn' => 'process__log_to_feed_join', 'args' => ['2095', '4']], ... ]
     *
     * @param string $process_list
     * @return array
     */
    public function decode_processlist($process_list) {
        $decoded_process_list = array();

        if ($process_list === null || $process_list === '' || !is_string($process_list)) {
            // If process_list is empty, return an empty array
            return $decoded_process_list;
        }

        // 1. Split the process list by commas
        $segments = explode(',', $process_list);
        foreach ($segments as $segment) {
            // 2. Split each segment by colon
            $parts = explode(':', $segment);
            $id_and_arg_count = count($parts);
            // skip if no parts
            if ($id_and_arg_count < 1) {
                continue;
            }

            // 3. Get the process id
            $process_id = $parts[0];
            // The process_id could be the module__function name or an id_num
            // Check if it is an id_num

            $process_key = null;
            // Check if process_id is in process_map first (id_num)
            if (isset($this->process_map[$process_id])) {
                $process_key = $this->process_map[$process_id];
            } else {
                // If not, check if it is a process key
                if (isset($this->process_list[$process_id])) {
                    $process_key = $process_id;
                }
            }

            if (!$process_key) {
                // Optionally log a warning here
                // $this->log->warn("Process not found for id: $process_id");
                continue; // Skip if process not found
            }

            // 4. Get the arguments
            $args = array_slice($parts, 1);

            $process_item = array(
                'fn' => $process_key,
                'args' => $args
            );

            $decoded_process_list[] = $process_item;
        }

        return $decoded_process_list;
    }

    /**
     * Encode a process list to a string.
     * Example input: [{fn: 'process__log_to_feed_join', args: ['2095', '4']}, {fn: 'schedule__if_not_schedule_zero', args: ['3']}]
     * Returns a string like "process__log_to_feed_join:2095,4:schedule__if_not_schedule_zero:3".
     * 
     * @param array $process_list The list of processes to encode.
     * @return string The encoded process list as a string.
     */
    public function encode_processlist($process_list) {
        // Encode a process list to a string
        $encoded_process_list = array();
        foreach ($process_list as $process_item) {
            $process_key = $process_item['fn'];

            // Get id_num if it exists
            if (isset($this->process_map_reverse[$process_key])) {
                $process_key = $this->process_map_reverse[$process_key];
            }

            $args = implode(':', $process_item['args']);
            $encoded_process_list[] = $process_key . ':' . $args;
        }
        return implode(',', $encoded_process_list);
    }

}
