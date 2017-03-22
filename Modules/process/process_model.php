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
}

class ProcessOriginType {
    const INPUT = 1;
    const VIRTUALFEED = 2;
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

    public function __construct($mysqli,$input,$feed,$timezone)
    {
        $this->mysqli = $mysqli;
        $this->input = $input;
        $this->feed = $feed;
        if (!($timezone === NULL)) $this->timezone = $timezone;
        $this->log = new EmonLogger(__FILE__);
        $this->get_process_list(); // Load modules modules
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
        }
        return $list;
    }


    public function input($time, $value, $processList, $options = null)
    {
        //$this->log->info("input() received time=$time\tvalue=$value");

        $this->proc_initialvalue = $value; // save the input value at beginning of the processes list execution
        $this->proc_skip_next = false;     // skip execution of next process in process list

        $process_list = $this->get_process_list();
        $pairs = explode(",",$processList);
        $total = count($pairs);
        $steps=0;
        for($this->proc_goto = 0 ; $this->proc_goto < $total ; $this->proc_goto++) {
            $steps++;
            $inputprocess = explode(":", $pairs[$this->proc_goto]);  // Divide into process key and arg
            $processkey = $inputprocess[0];                          // Process id
            if (!isset($process_list[$processkey])) {
                $this->log->error("input() Processor '".$processkey."' does not exists. Module missing?");
                return false;
            }

            $arg = 0;
            if (isset($inputprocess[1])) $arg = $inputprocess[1];          // Can be value or feed id

            $process_function = $processkey;                               // get process key 'module.function'
            if (strpos($processkey, '__') === FALSE)
                $process_function = $process_list[$processkey][2];         // for backward compatibility -> get process function name
            $value = $this->$process_function($arg,$time,$value,$options); // execute process function

            if ($this->proc_skip_next) {
                $this->proc_skip_next = false; $this->proc_goto++;
            }

            if ($steps > $total*2) {
                // We are executing a looping processlist or too much gotos
                // need to add 'error_found' process to this processList.
                $this->runtime_error = ProcessError::TOO_MANY_ITERATIONS;
                $this->log->error("input() DEACTIVATED processList due to too many steps. steps=$steps proc_goto=".$this->proc_goto." processkey=$processkey sourcetype=" . $options['sourcetype'] . " sourceid=" . $options['sourceid'] );
                switch ($options['sourcetype']) {
                    case ProcessOriginType::INPUT:
                         $this->input->set_processlist($options['sourceid'],"process__error_found:0,".$processList);
                         break;
                         
                    case ProcessOriginType::VIRTUALFEED:
                         $this->feed->set_processlist($options['sourceid'],"process__error_found:0,".$processList);
                         break;
                }
                return false;
            }
        }
        return $value;
    }


    private function load_modules() {
        $list = array();
        $dir = scandir("Modules");
        for ($i=2; $i<count($dir); $i++) {
            if (filetype("Modules/".$dir[$i])=='dir' || filetype("Modules/".$dir[$i])=='link') {
                $class = $this->get_module_class($dir[$i]);
                if ($class != null) {
                    $mod_process_list = $class->process_list();
                    foreach($mod_process_list as $k => $v) {
                        $processkey = strtolower($dir[$i]."__".$v[2]);
                        $list[$processkey] = $v; // set list key as "module__function"
                        //$this->log->info("load_modules() module=$dir[$i] function=$v[2]");
                    }
                }
            }
        }
        // Loads core process list from process module (with integer key for backward_compatibility)
        $backward_compatible_list = "process__core_process_list"; 
        $list+=$this->$backward_compatible_list();
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

}
