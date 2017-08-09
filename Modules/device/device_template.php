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

class DeviceTemplate
{
    private $mysqli;
    private $redis;
    private $log;
    
    // Module required constructor, receives parent as reference
    public function __construct(&$parent) {
        $this->mysqli = &$parent->mysqli;
        $this->redis = &$parent->redis;
        $this->log = new EmonLogger(__FILE__);
    }

    public function get_list() {
        return $this->load_templates();
    }

    private function load_templates() {
        $list = array();
        foreach (glob("Modules/device/data/*.json") as $file) {
            $content = json_decode(file_get_contents($file));
            $list[basename($file, ".json")] = $content;
        }
        return $list;
    }

    public function get($device) {
        $device = preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$device);
        
        if (file_exists("Modules/device/data/$device.json")) {
            return json_decode(file_get_contents("Modules/device/data/$device.json"));
        }
    }

    public function init($userid, $nodeid, $name, $type) {
        $file = "Modules/device/data/".$type.".json";
        if (file_exists($file)) {
            $template = json_decode(file_get_contents($file));
        } else {
            return array('success'=>false, 'message'=>"Template file not found '" . $file . "'");
        }

        $feeds = $template->feeds;
        $inputs = $template->inputs;

        // Create feeds
        $result = $this->create_feeds($userid, $nodeid, $feeds);
        if ($result["success"] !== true) {
            return array('success'=>false, 'message'=>'Error while creating the feeds. ' . $result['message']);
        }

        // Create inputs
        $result = $this->create_inputs($userid, $nodeid, $inputs);
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
                $result = $this->convert_processes($feedArray, $inputArray, $i->processList);
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
                $result = $this->convert_processes($feedArray, $inputArray, $f->processList);
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
    private function convert_processes($feed_array, $input_array, $process_array){
        $result = array();
        
        if (is_array($process_array)) {
            require_once "Modules/process/process_model.php";
            $process = new Process(null,null,null,null);
            $process_list = $process->get_process_list(); // emoncms supported processes

            $process_list_by_name = array();
            foreach ($process_list as $process_id=>$process_item) {
                $name = $process_item[2];
                $process_list_by_name[$name] = $process_id;
            }

            // create each processlist
            foreach($process_array as $p) {
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
                            $temp = $this->search_array($input_array,'name',$value); // return input array that matches $inputArray[]['name']=$value
                            if ($temp->inputId > 0) {
                                $value = $temp->inputId;
                            } else {
                                $this->log->error("convertProcess() Bad device template. Input name '$value' was not found. process='$proc_name' type='".$p->arguments->type."'");
                                return array('success'=>false, 'message'=>"Bad device template. Input name '$value' was not found. process='$proc_name' type='".$p->arguments->type."'");
                            }
                        } else if ($type === ProcessArg::FEEDID) {
                            $temp = $this->search_array($feed_array,'name',$value); // return feed array that matches $feedArray[]['name']=$value
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
                    $result[] = $proc_name.":".$value;

                } else {
                    $this->log->error("convertProcess() Bad device template. Missing processlist arguments. process='$proc_name'");
                    return array('success'=>false, 'message'=>"Bad device template. Missing processlist arguments. process='$proc_name'");
                }
            }
        }
        return $result;
    }

    private function search_array($array, $key, $val) {
        foreach ($array as $item)
            if (isset($item->$key) && $item->$key == $val)
                return $item;
        return null;
    }
}
