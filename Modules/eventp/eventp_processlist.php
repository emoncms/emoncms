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

    // Module required process configuration, $list array index position is not used, function name is used instead
    public function process_list()
    {
        // Note on engine selection

        // The engines listed against each process must be the supported engines for each process - and are only used in the input and node config GUI dropdown selectors
        // By using the create feed api and input set processlist its possible to create any feed type with any process list combination.
        // Only feeds capable of using a particular processor are displayed to the user and can be selected from the gui.
        // Daily datatype automaticaly adjust feed interval to 1d and user cant change it from gui.
        // If there is only one engine available for a processor, it is selected and user cant change it from gui.
        // The default selected engine is the first in the array of the supported engines for each processor.
        // Virtual feeds are feeds that are calculed in realtime when queried and use a processlist as post processor. 
        // Processors that write or update a feed are not supported and hidden from the gui on the context of virtual feeds.

        // 0=>Name | 1=>Arg type | 2=>function | 3=>No. of datafields if creating feed | 4=>Datatype | 5=>Group | 6=>Engines | 'desc'=>Description | 'requireredis'=>true/false
        $list[] = array(_("If rate >=, skip next"), ProcessArg::VALUE, "ifRateGtEqualSkip", 0, DataType::UNDEFINED, "Conditional - Event", 'requireredis'=>true, 'nochange'=>true, 'desc'=>"<p>If value from last process has an absolute change from previous time it was calculated higher or equal to the specified value, processlist execution will skip the next process.</p>");
        $list[] = array(_("If rate <, skip next"), ProcessArg::VALUE, "ifRateLtSkip", 0, DataType::UNDEFINED, "Conditional - Event", 'requireredis'=>true, 'nochange'=>true, 'desc'=>"<p>If value from last process has an absolute change from previous time it was calculated lower than the specified value, processlist execution will skip the next process.</p>");
        $list[] = array(_("If Mute, skip next"), ProcessArg::VALUE, "ifMuteSkip", 0, DataType::UNDEFINED, "Conditional - Event", 'requireredis'=>true, 'nochange'=>true, 'desc'=>"<p>A time elapsed dependent condition, first time a processlist passes here the flow is unchanged. Next times the same processlist passes here, if the specified value time (in seconds) has not elapsed, flow will skip next process.</p>");
        $list[] = array(_("If !Mute, skip next"), ProcessArg::VALUE, "ifNotMuteSkip", 0, DataType::UNDEFINED, "Conditional - Event", 'requireredis'=>true, 'nochange'=>true, 'desc'=>"<p>A time elapsed dependent condition, first time a processlist passes here the flow skips next. Next times the same processlist passes here, if the specified value time (in seconds) has elapsed, flow will skip next process.</p>");
        $list[] = array(_("Send Email"), ProcessArg::TEXT, "sendEmail", 0, DataType::UNDEFINED, "Event", 'nochange'=>true, 'desc'=>"<p>Send an email to the user with the specified body.</p><p>Supported template tags to customize body: {type}, {id}, {key}, {name}, {node}, {time}, {value}</p><p>Example body text: At {time} your {type} from {node} with key {key} named {name} had value {value}.</p>");
        return $list;
    }


    // \/ Below are functions of this module processlist, same name must exist on process_list()
    
    public function sendEmail($emailbody, $time, $value, $options) {
        global $user, $session;

        $timeformated = DateTime::createFromFormat("U", (int)$time);
        $timeformated->setTimezone(new DateTimeZone($this->parentProcessModel->timezone));
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

        $emailto = $user->get_email($session['userid']);
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
