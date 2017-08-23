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
    private $feed;
    private $redis;

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
    // AES-128-CBC Encrypted Input Method
    // ------------------------------------------------------------------------------------
    public function encrypted()
    {
        // Check that username and data parameters are present
        if (!isset($_POST['username']) || !isset($_POST['data'])) return "Missing username or data parameters";
        
        // Load username if exists
        $username = $_POST['username'];
        $u = $this->user->get_user_from_username($username);
        if (!$u || !isset($u->apikey_write)) return "User not found";
        
        $apikey = $u->apikey_write;
        $userid = $u->id;

        // The encrypted data in base64 is passed using the keyword data.
        $base64EncryptedData = $_POST["data"];

        // The base64 is converted from "URL safe" code to standard base64 (RFC2045 etc),
        // then it is decoded into the binary encrypted data
        $encryptedData = base64_decode(str_replace(array('-','_'),array('+','/'),$base64EncryptedData));

        // The binary encrypted data is decrypted using the apikey.
        // Note that the first 16 bytes of the encrypted data string are the IV and
        // the actual data follows
        $jsonstring = @openssl_decrypt(substr($encryptedData,16), 'AES-128-CBC', hex2bin($apikey), OPENSSL_RAW_DATA, substr($encryptedData,0,16));
        
        // Decode json data
        $data = json_decode($jsonstring);
        if ($data==null) return "Invalid data string or encryption key";
                
        $time_ref = 0;
                    
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

                $inputs = array();
                $name = 1;
                for ($i=2; $i<count($item); $i++)
                {
                    if (is_object($item[$i]))
                    {
                        $value = (float) current($item[$i]);
                        $inputs[key($item[$i])] = $value;
                        continue;
                    }
                    if (strlen($item[$i]))
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
        
        // Now we develop a sha256 hash of the data to send back in the reply as proof
        // that the recipient was able to decode the message
        $sha256 = hash("sha256", $jsonstring, true);
        $sha256base64 = str_replace(array('+','/'),array('-','_'), base64_encode($sha256));
        return $sha256base64;
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
        global $route;
        
        // Default nodeid is zero
        $nodeid = 0;
        
        if ($route->subaction) {
            $nodeid = $route->subaction;
        } else if (isset_prop('node')) {
            $nodeid = prop('node');
        }
        $nodeid = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$nodeid);
        
        // Time
        if (isset_prop('time')) $time = (int) prop('time'); else $time = time();

        // Data
        $datain = false;
        /* The code below processes the data regardless of its type,
         * unless fulljson is used in which case the data is decoded
         * from JSON.  The previous 'json' type is retained for
         * backwards compatibility, since some strings would be parsed
         * differently in the two cases. */
        if (isset_prop('json')) $datain = prop('json');
        else if (isset_prop('fulljson')) $datain = prop('fulljson');
        else if (isset_prop('csv')) $datain = prop('csv');
        else if (isset_prop('data')) $datain = prop('data');

        if ($datain=="") return "Request contains no data via csv, json or data tag";
        
        if (isset_prop('fulljson')) {
            $inputs = json_decode($datain, true, 2);
            if (is_null($inputs)) {
                return "Error decoding JSON string (invalid or too deeply nested)";
            } else if (!is_array($inputs)) {
                return "Input must be a JSON object";
            }
        } else {
            $json = preg_replace('/[^\p{N}\p{L}_\s-.:,]/u','',$datain);
            $datapairs = explode(',', $json);
            
            $inputs = array();
            $csvi = 0;
            for ($i=0; $i<count($datapairs); $i++)
            {
                $keyvalue = explode(':', $datapairs[$i]);

                if (isset($keyvalue[1])) {
                    if ($keyvalue[0]=='') return "Format error, json key missing or invalid character";
                    if (!is_numeric($keyvalue[1])) return "Format error, json value is not numeric";
                    $inputs[$keyvalue[0]] = (float) $keyvalue[1];
                } else {
                    if (!is_numeric($keyvalue[0])) return "Format error: csv value is not numeric";
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
        $data = json_decode(prop('data'));

        $len = count($data);
        
        if ($len==0) return "Format error, json string supplied is not valid";
        
        if (!isset($data[$len-1][0])) return "Format error, last item in bulk data does not contain any data";

        // Sent at mode: input/bulk.json?data=[[45,16,1137],[50,17,1437,3164],[55,19,1412,3077]]&sentat=60
        if (isset_prop('sentat')) {
            $time_ref = time() - (int) prop('sentat');
        }
        // Offset mode: input/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&offset=-10
        elseif (isset_prop('offset')) {
            $time_ref = time() - (int) prop('offset');
        }
        // Time mode: input/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&time=1387729425
        elseif (isset_prop('time')) {
            $time_ref = (int) prop('time');
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

                $inputs = array();
                $name = 1;
                for ($i=2; $i<count($item); $i++)
                {
                    if (is_object($item[$i]))
                    {
                        $value = (float) current($item[$i]);
                        $inputs[key($item[$i])] = $value;
                        continue;
                    }
                    if (strlen($item[$i]))
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
        
        $validate_access = $this->input->validate_access($dbinputs, $nodeid);
        if (!$validate_access['success']) return "Error: ".$validate_access['message'];

        if (!isset($dbinputs[$nodeid])) {
            $dbinputs[$nodeid] = array();
            if ($this->device) $this->device->create($userid,$nodeid);
        }
                
        $tmp = array();
        foreach ($inputs as $name => $value)
        {
            if (!isset($dbinputs[$nodeid][$name]))
            {
                $inputid = $this->input->create_input($userid, $nodeid, $name);
                $dbinputs[$nodeid][$name] = true;
                $dbinputs[$nodeid][$name] = array('id'=>$inputid, 'processList'=>'');
                $this->input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
            }
            else
            {
                $this->input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                
                if ($dbinputs[$nodeid][$name]['processList']) $tmp[] = array(
                    'value'=>$value,
                    'processList'=>$dbinputs[$nodeid][$name]['processList'],
                    'opt'=>array('sourcetype' => ProcessOriginType::INPUT,
                    'sourceid'=>$dbinputs[$nodeid][$name]['id'])
                );
            }
        }

        foreach ($tmp as $i) $this->process->input($time,$i['value'],$i['processList'],$i['opt']);
        
        return true;
    }

    // ------------------------------------------------------------------------------------
    // Fall back for older PHP versions
    // ------------------------------------------------------------------------------------
    private function hex2bin($hexstr) 
    { 
        $n = strlen($hexstr); 
        $sbin="";   
        $i=0; 
        while($i<$n) 
        {       
            $a =substr($hexstr,$i,2);           
            $c = pack("H*",$a); 
            if ($i==0){$sbin=$c;} 
            else {$sbin.=$c;} 
            $i+=2; 
        } 
        return $sbin; 
    }
}
