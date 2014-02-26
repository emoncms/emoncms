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

    public function set($userid,$nodeid,$data)
    {
        $userid = (int) $userid;
        $nodeid = (int) $nodeid;

        $time = time();

        $nodes = json_decode($this->redis->get("nodes:$userid"));

        if ($nodes==NULL) $nodes = new stdClass();
        if (!isset($nodes->$nodeid)) $nodes->$nodeid = new stdClass();
        $nodes->$nodeid->data = $data;
        $nodes->$nodeid->time = $time;
        $this->redis->set("nodes:$userid",json_encode($nodes));

        $this->process($userid,$nodes,$nodeid,$data);

        return true;
    }

    public function set_decoder($userid,$nodeid,$decoder)
    {
        $userid = (int) $userid;
        $nodeid = (int) $nodeid;
        $decoder = json_decode($decoder);


        $nodes = json_decode($this->redis->get("nodes:$userid"));

        if ($nodes!=NULL && isset($nodes->$nodeid)) {
        $nodes->$nodeid->decoder = $decoder;
        $this->redis->set("nodes:$userid",json_encode($nodes));
        }

        return true;
        }

        public function get_all($userid)
        {
        $userid = (int) $userid;

        return json_decode($this->redis->get("nodes:$userid"));
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
                    $value = (int) $bytes[$pos] + (int) $bytes[$pos+1]*256;
                    if ($value>32768) $value += -65536;  
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

}
