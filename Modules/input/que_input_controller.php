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

function input_controller()
{
    //return array('content'=>"ok");

    global $mysqli, $redis, $user, $session, $route;

    // There are no actions in the input module that can be performed with less than write privileges
    if (!$session['write']) return array('content'=>false);

    global $feed, $timestore_adminkey;
    $result = false;

    include "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $timestore_adminkey);

    require "Modules/input/input_model.php"; // 295
    $input = new Input($mysqli,$redis, $feed);

    require "Modules/input/process_model.php"; // 886
    $process = new Process($mysqli,$input,$feed);



    if ($route->format == 'html')
    {
        if ($route->action == 'api') $result = view("Modules/input/Views/input_api.php", array());
        if ($route->action == 'node') $result =  view("Modules/input/Views/input_node.php", array());
        if ($route->action == 'process')
        {
            $result = view("Modules/input/Views/process_list.php",
            array(
                    'inputid'=> intval(get('inputid')),
                    'processlist' => $process->get_process_list(),
                    'inputlist' => $input->getlist($session['userid']),
                    'feedlist'=> $feed->get_user_feeds($session['userid'],0)
            ));
        }
    }

    if ($route->format == 'json')
    {
        // input/post.json?node=10&csv=100,200,300
        // input/post.json?node=10&json={power:100,solar:200}
        // input/bulk.json?data=[[0,10,100,200],[5,10,100,200],[10,10,100,200]]

        // input/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]
        // The first number of each node is the time offset, so for the first node it is 0 which means the packet
        // for the first node arrived at 0 seconds. The second node arrived at 2 seconds and 3rd 4 seconds.
        // The second number is the node id, this is the unqiue identifer for the wireless node.
        // All the numbers after the first two are data values. The first node here (node 16) has only once data value: 1137.

        if ($route->action == 'bulk')
        {
            $valid = true;
            $data = json_decode(get('data'));

            $userid = $session['userid'];

            $len = count($data);
            if ($len>0)
            {
                if (isset($data[$len-1][0]))
                {
                    $offset = (int) $data[$len-1][0];
                    if ($offset>=0)
                    {
                        $start_time = time() - $offset;

                        foreach ($data as $item)
                        {
                            if (count($item)>2)
                            {
                                // check for correct time format
                                $itemtime = (int) $item[0];
                                if ($itemtime>=0)
                                {
                                    $time = $start_time + (int) $itemtime;
                                    $nodeid = $item[1];

                                    $inputs = array();
                                    $name = 1;
                                    for ($i=2; $i<count($item); $i++)
                                    {
                                        $value = (float) $item[$i];
                                        $inputs[$name] = $value;
                                        $name ++;
                                    }

                                    $array = array(
                                        'userid'=>$userid,
                                        'time'=>$time,
                                        'nodeid'=>$nodeid,
                                        'data'=>$inputs
                                    );

                                    $str = json_encode($array);

                                    if ($redis->llen('buffer')<10000) {
                                        $redis->rpush('buffer',$str);
                                    } else {
                                        $valid = false; $error = "Too many connections, input queue is full";
                                    }

                                } else { $valid = false; $error = "Format error, time index given is negative"; }
                            } else { $valid = false; $error = "Format error, bulk item needs at least 3 values"; }
                        }
                    } else { $valid = false; $error = "Format error, time index given is negative"; }
                } else { $valid = false; $error = "Format error, last item in bulk data does not contain any data"; }
            } else { $valid = false; $error = "Format error, json string supplied is not valid"; }

            if ($valid) $result = 'ok';
            else $result = "Error: $error\n";
        }

        // input/post.json?node=10&json={power1:100,power2:200,power3:300}
        // input/post.json?node=10&csv=100,200,300

        if ($route->action == 'post')
        {
            $valid = true; $error = "";

            $nodeid = get('node');
            if ($nodeid && !is_numeric($nodeid)) { $valid = false; $error = "Nodeid must be an integer between 0 and 30, nodeid given was not numeric"; }
            if ($nodeid<0 || $nodeid>30) { $valid = false; $error = "nodeid must be an integer between 0 and 30, nodeid given was out of range"; }
            $nodeid = (int) $nodeid;

            if (isset($_GET['time'])) $time = (int) $_GET['time']; else $time = time();

            $data = array();

            $datain = false;
            // code below processes input regardless of json or csv type
            if (isset($_GET['json'])) $datain = get('json');
            if (isset($_GET['csv'])) $datain = get('csv');
            if (isset($_GET['data'])) $datain = get('data');
            if (isset($_POST['data'])) $datain = post('data');

            if ($datain!="")
            {
                $json = preg_replace('/[^\w\s-.:,]/','',$datain);
                $datapairs = explode(',', $json);

                $csvi = 0;
                for ($i=0; $i<count($datapairs); $i++)
                {
                    $keyvalue = explode(':', $datapairs[$i]);

                    if (isset($keyvalue[1])) {
                        if ($keyvalue[0]=='') {$valid = false; $error = "Format error, json key missing or invalid character"; }
                        if (!is_numeric($keyvalue[1])) {$valid = false; $error = "Format error, json value is not numeric"; }
                        $data[$keyvalue[0]] = (float) $keyvalue[1];
                    } else {
                        if (!is_numeric($keyvalue[0])) {$valid = false; $error = "Format error: csv value is not numeric"; }
                        $data[$csvi+1] = (float) $keyvalue[0];
                        $csvi ++;
                    }
                }

                $packet = array(
                    'userid' => $session['userid'],
                    'time' => $time,
                    'nodeid' => $nodeid,
                    'data'=>$data
                );

                if (count($data)>0 && $valid) {
                    $str = json_encode($packet);
                    if ($redis->llen('buffer')<10000) {
                        $redis->rpush('buffer',$str);
                    } else {
                        $valid = false; $error = "Too many connections, input queue is full";
                    }
                }
            }
            else
            {
                $valid = false;
                $error = "Request contains no data via csv, json or data tag";
            }

            if ($valid) $result = 'ok';
            else $result = "Error: $error\n";
        }

        if ($route->action == "clean") $result = $input->clean($session['userid']);
        if ($route->action == "list") $result = $input->getlist($session['userid']);
        if ($route->action == "getinputs") $result = $input->get_inputs($session['userid']);

        if (isset($_GET['inputid']) && $input->belongs_to_user($session['userid'],get("inputid")))
        {
            if ($route->action == "delete") $result = $input->delete($session['userid'],get("inputid"));

            if ($route->action == 'set') $result = $input->set_fields(get('inputid'),get('fields'));

            if ($route->action == "process")
            {
                if ($route->subaction == "add") $result = $input->add_process($process,$session['userid'], get('inputid'), get('processid'), get('arg'), get('newfeedname'), get('newfeedinterval'));
                if ($route->subaction == "list") $result = $input->get_processlist_desc($process, get("inputid"));
                if ($route->subaction == "delete") $result = $input->delete_process(get("inputid"),get('processid'));
                if ($route->subaction == "move") $result = $input->move_process(get("inputid"),get('processid'),get('moveby'));
                if ($route->subaction == "reset") $result = $input->reset_process(get("inputid"));
            }
        }
    }

    return array('content'=>$result);
}

?>
