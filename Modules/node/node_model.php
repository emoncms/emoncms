<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Node
{
    private $mysqli;
    private $redis;
    private $process;

    public function __construct($mysqli,$redis,$process)
    {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        $this->process = $process;
    }

    public function set($userid,$nodeid,$time,$data)
    {
        $userid = (int) $userid;
        $nodeid = (int) $nodeid;

        if (!$time) $time = time();

        if ($this->redis) {
            $nodes = json_decode($this->redis->get("nodes:$userid"));
            if ($nodes==NULL) $nodes = $this->get_mysql($userid);
        } else {
            $nodes = $this->get_mysql($userid);
        }
        
        if ($nodes==false) $nodes = new stdClass();
        
        if (!isset($nodes->$nodeid)) $nodes->$nodeid = new stdClass();
        $nodes->$nodeid->data = $data;
        $nodes->$nodeid->time = $time;
        
        if ($this->redis) {
            $this->redis->set("nodes:$userid",json_encode($nodes));
        } else {
            $this->set_mysql($userid,$nodes);
        }

        $this->process($userid,$nodes,$nodeid,$data);

        return true;
    }

    public function set_decoder($userid,$nodeid,$decoder)
    {
        $userid = (int) $userid;
        $nodeid = (int) $nodeid;
        $decoder = json_decode($decoder);

        if ($this->redis) {
            $nodes = json_decode($this->redis->get("nodes:$userid"));
        } else {
            $nodes = $this->get_mysql($userid);
        }

        if ($nodes!=NULL && isset($nodes->$nodeid)) 
        {
            $nodes->$nodeid->decoder = $decoder;
            if ($this->redis) $this->redis->set("nodes:$userid",json_encode($nodes));
            $this->set_mysql($userid,$nodes);
        }

        return true;
    }

    public function get_all($userid)
    {
        $userid = (int) $userid;
        if ($this->redis) {
            return json_decode($this->redis->get("nodes:$userid"));
        } else {
            return $this->get_mysql($userid);
        }
    }

    //----------------------------------------------------------------------------------------------

    public function process($userid,$nodes,$nodeid,$data)
    {    
        $bytes = explode(',',$data);
        $pos = 0;

        if (isset($nodes->$nodeid->decoder) && sizeof($nodes->$nodeid->decoder->variables)>0)
        {
            foreach($nodes->$nodeid->decoder->variables as $variable)
            {
                $value = null; 
                if ($variable->type==0)
                {
                    $value = (int) $bytes[$pos];
                    $pos += 1;
                }

                if ($variable->type==1)
                {
                    $value = (int) $bytes[$pos] + (int) $bytes[$pos+1]*256;
                    if ($value>32768) $value += -65536;  
                    $pos += 2;
                }

                if ($variable->type==2)
                {
                 
                    $value = (int) $bytes[$pos] + (int) $bytes[$pos+1]*256 + (int) $bytes[$pos+2]*65536 + (int) $bytes[$pos+3]*16777216;
                    //if ($value>32768) $value += -65536;  
                    $pos += 4;
                }

                if (isset($variable->scale)) $value *= $variable->scale;

                $time = time();

                //log_to_fiwa(1,node:10:1) 
                //if (node:10:1 > 25.0) email("trystan.lea@gmail.com","Node 10 temperature is above 25C",10);

                if (isset($variable->processlist) && $variable->processlist!='') $this->process->input($time,$value,$variable->processlist);
            }
        }
    }
    
    public function set_mysql($userid,$data)
    {
        $json = json_encode($data);
        $result = $this->mysqli->query("SELECT `userid` FROM node WHERE `userid`='$userid'");
        if ($result->num_rows) {
            $this->mysqli->query("UPDATE node SET `data`='$json' WHERE `userid`='$userid'");
        } else {
            $this->mysqli->query("INSERT INTO node (`userid`,`data`) VALUES ('$userid','$json')");
        }
    }
    
    public function get_mysql($userid)
    {
        $result = $this->mysqli->query("SELECT `data` FROM node WHERE `userid`='$userid'");
        if ($row = $result->fetch_array()) {
          return json_decode($row['data']);
        } else {
          return false;
        }
        
    }

}
