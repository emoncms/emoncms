<?php
/*
     Released under the GNU Affero General Public License.
     See COPYRIGHT.txt and LICENSE.txt.

     EventProcesses module contributed by Nuno Chaveiro nchaveiro(at)gmail.com 2015
     ---------------------------------------------------------------------
     Sponsored by http://archimetrics.co.uk/
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

// Schedule Processlist Module
class Eventp_ProcessList
{
    private $log;
    private $parentProcessModel;

    // Module required constructor, receives parent as reference
    public function __construct(&$parent)
    {
        $this->log = new EmonLogger(__FILE__);
        $this->parentProcessModel = &$parent;
    }

    // \/ Below are functions of this module processlist, same name must exist on process_list()
    
    public function process_list() {
        
        $list = array(
            array(
              "name"=>"If rate >=, skip next",
              "short"=>"?rate>=",
              "argtype"=>ProcessArg::VALUE,
              "function"=>"ifRateGtEqualSkip",
              "datafields"=>0,
              "datatype"=>false,
              "unit"=>"",
              "group"=>"Conditional - Event",
              "requireredis"=>true,
              "nochange"=>true,
              "description"=>"<p>If value from last process has an absolute change from previous time it was calculated higher or equal to the specified value, processlist execution will skip the next process.<\/p>"
           ),
           array(
              "name"=>"If rate <, skip next",
              "short"=>"?rate<",
              "argtype"=>ProcessArg::VALUE,
              "function"=>"ifRateLtSkip",
              "datafields"=>0,
              "datatype"=>false,
              "unit"=>"",
              "group"=>"Conditional - Event",
              "requireredis"=>true,
              "nochange"=>true,
              "description"=>"<p>If value from last process has an absolute change from previous time it was calculated lower than the specified value, processlist execution will skip the next process.<\/p>"
           ),
           array(
              "name"=>"If Mute, skip next",
              "short"=>"?mute",
              "argtype"=>ProcessArg::VALUE,
              "function"=>"ifMuteSkip",
              "datafields"=>0,
              "datatype"=>false,
              "unit"=>"",
              "group"=>"Conditional - Event",
              "requireredis"=>true,
              "nochange"=>true,
              "description"=>"<p>A time elapsed dependent condition, first time a processlist passes here the flow is unchanged. Next times the same processlist passes here, if the specified value time (in seconds) has not elapsed, flow will skip next process.<\/p>"
           ),
           array(
              "name"=>"If !Mute, skip next",
              "short"=>"?!mute",
              "argtype"=>ProcessArg::VALUE,
              "function"=>"ifNotMuteSkip",
              "datafields"=>0,
              "datatype"=>false,
              "unit"=>"",
              "group"=>"Conditional - Event",
              "requireredis"=>true,
              "nochange"=>true,
              "description"=>"<p>A time elapsed dependent condition, first time a processlist passes here the flow skips next. Next times the same processlist passes here, if the specified value time (in seconds) has elapsed, flow will skip next process.<\/p>"
           ),
           array(
              "name"=>"Send Email",
              "short"=>"email",
              "argtype"=>ProcessArg::TEXT,
              "function"=>"sendEmail",
              "datafields"=>0,
              "datatype"=>false,
              "unit"=>"",
              "group"=>"Event",
              "nochange"=>true,
              "description"=>"<p>Send an email to the user with the specified body. Email sent to user's email address or default set in config.<\/p><p>Supported template tags to customize body: {type}, {id}, {key}, {name}, {node}, {time}, {value}<\/p><p>Example body text: At {time} your {type} from {node} with key {key} named {name} had value {value}.<\/p>"
           )
        ); 
        return $list;
    }
    
    public function sendEmail($emailbody, $time, $value, $options) {
        global $user, $session, $default_emailto;

        $timeformated = DateTime::createFromFormat("U", (int)$time);
        if(!empty($this->parentProcessModel->timezone)) $timeformated->setTimezone(new DateTimeZone($this->parentProcessModel->timezone));
        $timeformated = $timeformated->format("Y-m-d H:i:s");

        $tag = array("{id}","{type}","{time}","{value}");
        $replace = array($options['sourceid'],$options['sourcetype'],$timeformated, $value);
        $emailbody = str_replace($tag, $replace, $emailbody);

        if ($options['sourcetype'] == "INPUT"){
            $inputdetails = $this->parentProcessModel->input->get_details($options['sourceid']);
            $tag = array("{key}","{name}","{node}");
            $replace = array($inputdetails['name'], $inputdetails['description'], $inputdetails['nodeid']);
            $emailbody = str_replace($tag, $replace, $emailbody);
        } else if ($options['sourcetype'] == "VIRTUALFEED") {
            // Not suported for VIRTUAL FEEDS
        }

        //need to get an email address from the config file or the form ?
        $emailto = $default_emailto;

        if (!empty($emailto)) { 
            require_once "Lib/email.php";
            $email = new Email();
            //$email->from(from);
            $email->to($emailto);
            $email->subject('Emoncms event alert');
            $email->body($emailbody);
            $result = $email->send();
            if (!$result['success']) {
                $this->log->error("Email send returned error. message='" . $result['message'] . "'");
            } else {
                $this->log->info("Email sent to $emailto");
            }
        } else {
            $this->log->error("No email address specified");
        }
    }
    
    public function ifMuteSkip($ttl, $time, $value, $options)
    {
        global $redis;
        if ($redis) {
            $timenow = time();
            $redispath = "process:ifMuteSkip:".$options['sourcetype'].":".$options['sourceid']."_".$this->parentProcessModel->proc_goto;
            //$this->log->info("ifMuteSkip() timenow=$timenow ttl=$ttl redispath=$redispath");
            if ($redis->exists($redispath)) {
                $this->parentProcessModel->proc_skip_next = true;
            } else {
                $redis->set($redispath, $timenow);
                $redis->setTimeout($redispath, $ttl); // removed in $ttl seconds.
            }
        }
        return $value;
    }

    public function ifNotMuteSkip($ttl, $time, $value, $options)
    {
        global $redis;
        if ($redis) {
            $timenow = time();
            $redispath = "process:ifNotMuteSkip:".$options['sourcetype'].":".$options['sourceid']."_".$this->parentProcessModel->proc_goto;
            //$this->log->info("ifNotMuteSkip() timenow=$timenow ttl=$ttl redispath=$redispath");
            if (!$redis->exists($redispath)) {
                $this->parentProcessModel->proc_skip_next = true;
                $redis->set($redispath, $timenow);
                $redis->setTimeout($redispath, $ttl); // removed in $ttl seconds.
            }
        }
        return $value;
    }
    
    public function ifRateGtEqualSkip($arg, $time, $value, $options)
    {
        global $redis;
        if ($redis) {
            $redispath = "process:ifRateGtEqualSkip:".$options['sourcetype'].":".$options['sourceid']."_".$this->parentProcessModel->proc_goto;
            //$this->log->info("ifRateGtEqualSkip() time=$time value=$value redispath=$redispath");
            if ($redis->exists($redispath)) {
                $lastvalue = $redis->hmget($redispath,array('time','value'));
                $change = abs($value - $lastvalue['value']);
                if ($change >= $arg)
                    $this->parentProcessModel->proc_skip_next = true;
            }
            $redis->hMset($redispath, array('time' => $time, 'value' => $value));
        }
        return $value;
    }
    
    public function ifRateLtSkip($arg, $time, $value, $options)
    {
        global $redis;
        if ($redis) {
            $redispath = "process:ifRateLtSkip:".$options['sourcetype'].":".$options['sourceid']."_".$this->parentProcessModel->proc_goto;
            //$this->log->info("ifRateLtSkip() time=$time value=$value redispath=$redispath");
            if ($redis->exists($redispath)) {
                $lastvalue = $redis->hmget($redispath,array('time','value'));
                $change = abs($value - $lastvalue['value']);
                if ($change < $arg)
                    $this->parentProcessModel->proc_skip_next = true;
            }
            $redis->hMset($redispath, array('time' => $time, 'value' => $value));
        }
        return $value;
    }

}
