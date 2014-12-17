MQTT notes:

add below line 107:

    else
    {
        // emoncms/input
        $m = json_decode($value);
        $time = $m->time;
        $nodeid = $m->node;
        $data = $m->csv;
        
        $session = $user->apikey_session($m->apikey);
        if ($session['write'])
        {
            $userid = $session['userid'];
            $dbinputs = $input->get_inputs($userid);

            $name = 0;
            foreach ($data as $value) {
                $inputs[] = array("userid"=>$userid, "time"=>$time, "nodeid"=>$nodeid, "name"=>$name++, "value"=>$value);
            } 
        }
    }
