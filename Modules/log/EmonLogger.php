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

class EmonLogger
{
    private $logfile = "/var/log/emoncms.log";
    private $topic = "MAIN";

    public function __construct()
    {
    }
    
    public function set_logfile($logfile)
    {
        $this->logfile = $logfile;
    }
    
    public function set_topic($topic)
    {
        $this->topic = $topic;
    }

    public function info ($message){
        $fh = fopen($this->logfile,"a");
        fwrite($fh,date("Y-n-j H:i:s", time())." $this->topic INFO ".$message."\n");
        fclose($fh);
    }

    public function warn ($message){
        $fh = fopen($this->logfile,"a");
        fwrite($fh,date("Y-n-j H:i:s", time())." $this->topic WARN ".$message."\n");
        fclose($fh);
    }
}
