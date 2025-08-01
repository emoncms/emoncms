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

class InputMethods
{
    private $mysqli;
    private $redis;
    private $user;
    private $input;
    private $feed;
    private $process;
    private $device;

    public function __construct($mysqli,$redis,$user,$input,$feed,$process,$device)
    {
        $this->mysqli = $mysqli;
        $this->redis = $redis;

        $this->user = $user;
        $this->input = $input;
        $this->feed = $feed;
        $this->process = $process;
        $this->device = $device;
    }

    // ------------------------------------------------------------------------------------
    // input/post method
    //
    // input/post.json?node=10&json={power1:100,power2:200,power3:300}
    // input/post.json?node=10&csv=100,200,300
    // ------------------------------------------------------------------------------------
    public function post($userid)
    {
        // Nodeid
        global $route,$param,$log;

        // Default nodeid is zero
        $nodeid = 0;

        if ($route->subaction) {
            $nodeid = $route->subaction;
        } elseif ($param->exists('node')) {
            $nodeid = $param->val('node');
        }
        $nodeid = preg_replace('/[^\p{N}\p{L}_\s\-.]/u','',$nodeid);
        if ($nodeid=="") {
            $nodeid = 0;
        }

        // Time
        //if ($param->exists('time')) $time = (int) $param->val('time'); else $time = time();
        if ($param->exists('time')) {
            $inputtime = $param->val('time');
            // Remove from array so no used as an input
            // Removed as causing unexpected results.
            // unset($jsondataLC['time']);

            // validate time
            if (is_numeric($inputtime)){
                $log->info("Valid time in seconds used ".$inputtime);
                $time = (int) $inputtime;
            } elseif (is_string($inputtime)){
                if (($timestamp = strtotime($inputtime)) === false) {
                    //If time string is not valid, use system time.
                    $log->warn("Time string not valid ".$inputtime);
                    $time = time();
                } else {
                    $log->info("Valid time string used ".$inputtime);
                    $time = $timestamp;
                }
            } else {
                $log->warn("Time parameter not valid ".$inputtime);
                $time = time();
            }
        } else {
            $time = time();
        }

        // Data
        $datain = false;
        /* The code below processes the data regardless of its type,
         * unless fulljson is used in which case the data is decoded
         * from JSON.  The previous 'json' type is retained for
         * backwards compatibility, since some strings would be parsed
         * differently in the two cases. */
        if ($param->exists('json')) {
            $datain = $param->val('json');
        } elseif ($param->exists('fulljson')) {
            $datain = $param->val('fulljson');
        } elseif ($param->exists('csv')) {
            $datain = $param->val('csv');
        } elseif ($param->exists('data')) {
            $datain = $param->val('data');
        }

        if ($datain=="") {
            return "Request contains no data via csv, json or data tag";
        }

        if ($param->exists('fulljson')) {
            $jsondata = json_decode($datain,true,2);
            if ((json_last_error() === JSON_ERROR_NONE) && is_array($jsondata)) {
                // JSON is valid - is it an array
                //$jsoninput = true;
                $log->info("Valid JSON found ");
                //Create temporary array and change all keys to lower case to look for a 'time' key
                $jsondataLC = array_change_key_case($jsondata);

                // If JSON, check to see if there is a time value else set to time now.
                // Time set as a parameter takes precedence.
                if ($param->exists('time')) {
                    $log->info("Time from parameter used");
                } elseif (array_key_exists('time',$jsondataLC)){
                    $inputtime = $jsondataLC['time'];
                    // remove time key so as not to create a related input
                    unset($jsondata['time']);
                    // validate time
                    if (is_numeric($inputtime)){
                        $log->info("Valid time in seconds used ".$inputtime);
                        $time = (int) $inputtime;
                    } elseif (is_string($inputtime)){
                        if (($timestamp = strtotime($inputtime)) === false) {
                            //If time string is not valid, use system time.
                            $log->warn("Time string not valid ".$inputtime);
                            $time = time();
                        } else {
                            $log->info("Valid time string used ".$inputtime);
                            $time = $timestamp;
                        }
                    } else {
                        $log->warn("Time not valid ".$inputtime);
                        $time = time();
                    }
                } else {
                    $log->info("No time element found in JSON - System time used");
                    $time = time();
                }
                $inputs = $jsondata;
                foreach ($inputs as $name => $value) {
                    if (!is_numeric($value) && $value!='null') {
                        $inputs[$name] = (float) $value;
                    }
                }

            } else {
                $log->error("Invalid JSON: $datain");
                return "Input in not a valid JSON object";
            }
        } else {
            $json = preg_replace('/[^\p{N}\p{L}_\s\-.:,]/u','',$datain);
            $datapairs = explode(',', $json);

            $inputs = array();
            $csvi = 0;
            for ($i=0; $i<count($datapairs); $i++)
            {
                $keyvalue = explode(':', $datapairs[$i]);

                if (isset($keyvalue[1])) {
                    if ($keyvalue[0]=='') return "Format error, json key missing or invalid character";
                    if (!is_numeric($keyvalue[1]) && $keyvalue[1]!='null') return "Format error, json value is not numeric";
                    $inputs[$keyvalue[0]] = (float) $keyvalue[1];
                } else {
                    if (!is_numeric($keyvalue[0]) && $keyvalue[0]!='null') return "Format error: csv value is not numeric";
                    $inputs[$csvi+1] = (float) $keyvalue[0];
                    $csvi ++;
                }
            }
        }

        $result = $this->process_node($userid,$time,$nodeid,$inputs);
        if ($result!==true) return $result;

        return "ok";
    }

    /*

    input/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]

    The first number of each node is the time offset (see below).

    The second number is the node id, this is the unique identifer for the wireless node.

    All the numbers after the first two are data values. The first node here (node 16) has only one data value: 1137.

    Optional offset and time parameters allow the sender to set the time
    reference for the packets.
    If none is specified, it is assumed that the last packet just arrived.
    The time for the other packets is then calculated accordingly.

    offset=-10 means the time of each packet is relative to [now -10 s].
    time=1387730127 means the time of each packet is relative to 1387730127
    (number of seconds since 1970-01-01 00:00:00 UTC)

    Examples:

    // legacy mode: 4 is 0, 2 is -2 and 0 is -4 seconds to now.
      input/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]
    // offset mode: -6 is -16 seconds to now.
      input/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&offset=-10
    // time mode: -6 is 1387730121
      input/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&time=1387730127
    // sentat (sent at) mode:
      input/bulk.json?data=[[520,16,1137],[530,17,1437,3164],[535,19,1412,3077]]&offset=543

    See pull request for full discussion:
    https://github.com/emoncms/emoncms/pull/118
    */
    public function bulk($userid)
    {
        global $param;

        $data = $param->val('data');

        if ($param->exists('cb')) {
            // data is compressed binary format
            $data = file_get_contents('php://input');
            $data = gzuncompress($data);
        } elseif ($param->exists('c')) {
            // data is compressed hex format
            $data = gzuncompress(hex2bin($data));
        }

        $data = json_decode($data);

        $len = count($data);

        if ($len==0) return "Format error, json string supplied is not valid";

        if (!isset($data[$len-1][0])) return "Format error, last item in bulk data does not contain any data";

        // Sent at mode: input/bulk.json?data=[[45,16,1137],[50,17,1437,3164],[55,19,1412,3077]]&sentat=60
        if ($param->exists('sentat')) {
            $time_ref = time() - (int) $param->val('sentat');
        }
        // Offset mode: input/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&offset=-10
        elseif ($param->exists('offset')) {
            $time_ref = time() - (int) $param->val('offset');
        }
        // Time mode: input/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&time=1387729425
        elseif ($param->exists('time')) {
            $time_ref = (int) $param->val('time');
        }
        // Legacy mode: input/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]
        else {
            $time_ref = time() - (int) $data[$len-1][0];
        }

        foreach ($data as $item)
        {
            if (count($item)>2)
            {
                // check for correct time format
                $itemtime = (int) $item[0];

                $time = $time_ref + (int) $itemtime;
                if (!is_object($item[1])) {
                    $nodeid = $item[1];
                } else {
                    return "Format error, node must not be an object";
                }
                if ($nodeid=="") $nodeid = 0;

                $inputs = array();
                $name = 1;
                for ($i=2; $i<count($item); $i++)
                {
                    if (is_object($item[$i]))
                    {
                        foreach ($item[$i] as $key=>$value) {
                            $inputs[$key] = (float) $value;
                        }
                        continue;
                    }
                    if ($item[$i]==null || strlen($item[$i]))
                    {
                        $value = (float) $item[$i];
                        $inputs[$name] = $value;
                    }
                    $name ++;
                }

                $result = $this->process_node($userid,$time,$nodeid,$inputs);
                if ($result!==true) return $result;
            }
        }

        return "ok";
    }

    // ------------------------------------------------------------------------------------
    // Register and process the inputs for the node given
    // This function is used by all input methods
    // ------------------------------------------------------------------------------------
    public function process_node($userid,$time,$nodeid,$inputs)
    {
        $dbinputs = $this->input->get_inputs($userid);

        $nodeid = preg_replace('/[^\p{N}\p{L}_\s\-.]/u','',$nodeid);

        $validate_access = $this->input->validate_access($dbinputs, $nodeid);
        if (!$validate_access['success']) return "Error: ".$validate_access['message'];

        if (!isset($dbinputs[$nodeid])) {
            $dbinputs[$nodeid] = array();
            if ($this->device) $this->device->create($userid,$nodeid,null,null,null);
        }

        if ($this->device) {
            $deviceid = $this->device->exists_nodeid($userid,$nodeid);
            if ($deviceid) {
                $ip_address = get_client_ip_env();
                $this->device->set_fields($deviceid,json_encode(array("ip"=>$ip_address)));
            }
        }

        $tmp = array();
        foreach ($inputs as $name => $value)
        {
            $name = preg_replace('/[^\p{N}\p{L}_\s\-.]/u','',$name);

            if (!isset($dbinputs[$nodeid][$name]))
            {
                $inputid = $this->input->create_input($userid, $nodeid, $name);
                $dbinputs[$nodeid][$name] = array('id'=>$inputid, 'processList'=>'');
                $this->input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
            }
            else
            {
                $this->input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);

                if ($dbinputs[$nodeid][$name]['processList']) $tmp[] = array(
                    'value'=>$value,
                    'processList'=>$dbinputs[$nodeid][$name]['processList'],
                    'opt'=>array(
                        'sourcetype' => ProcessOriginType::INPUT,
                        'sourceid'=>$dbinputs[$nodeid][$name]['id']
                    )
                );

                if (isset($_GET['mqttpub'])) $this->process->publish_to_mqtt("emon/$nodeid/$name",$time,$value);
            }
        }

        foreach ($tmp as $i) $this->process->input($time,$i['value'],$i['processList'],$i['opt']);

        return true;
    }
}
