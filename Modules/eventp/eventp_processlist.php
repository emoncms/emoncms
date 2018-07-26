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
