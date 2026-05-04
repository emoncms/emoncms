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
    private $logfile = "";
    private $caller = "";
    private $logenabled = false;
    private $log_level = 2;
    private $debug = false;
    private $debug_maxlen = 10;
    public $stout = false;

    private $log_levels = array(
            1 =>'INFO',
            2 =>'WARN', // default
            3 =>'ERROR'
        );

    public function __construct($clientFileName)
    {
        global $settings;

        if (!$settings['log']['enabled']) {
            $this->logenabled = false;
        } else {
            $this->logfile = $settings['log']['location']."/emoncms.log";
            if ($settings['log']['level']) {
                $this->log_level = $settings['log']['level'];
            }
            $path = pathinfo($clientFileName);
            $this->caller = $path['basename'];
            if (!file_exists($this->logfile)) {
                $fh = @fopen($this->logfile, "a");
                if (!$fh) {
                   error_log("Log file could not be created");
                } else {
                   @fclose($fh);
                }
            }
            $this->logenabled = is_writable($this->logfile);

            if (array_key_exists('debug', $settings['log'])) {
                $debuglist = preg_split("/[\s,]+/", $settings['log']['debug']);
                $this->debug = in_array($path['filename'], $debuglist);
            }
            if (array_key_exists('debug_maxlen', $settings['log']))
                $this->debug_maxlen = $settings['log']['debug_maxlen'];
        }
    }

    public function set($logfile, $log_level)
    {
        $this->logfile = $logfile;
        $this->log_enabled = true;
        $this->log_level = $log_level;
    }

    private function dump($param)
    {
        $msg = "";
        $type = gettype($param);
        switch ($type) {
            case "NULL":
                $msg .= "null";
                break;
            case "string":
                $msg .= $param;
                break;
            case "boolean":
                $msg .= $param ? "true":"false";
                break;
            case "integer":
                $msg .= strval($param);
                break;
            case "double":
                $msg .= round($param,4);
                break;
            case "array":
                $msg .= $this->dumparray($param);
                break;
            default:
                $msg .= strval($param); // just shows type of object
                break;
        }
        return $msg;
    }

    private function dumparray($array) {
        $len = sizeof($array);
        $res = [];
        $n = 0;
        foreach ($array as $key => $entry) {
            ++$n;
            if ($n == $this->debug_maxlen && $n < $len) {
                $res[] = "...";
                continue;
            }
            if ($n > $this->debug_maxlen && $n < $len) continue;
            $res[] = is_integer($key) ? $this->dump($entry) : "[$key=".$this->dump($entry)."]";
        }
        return "[".implode(",",$res)."]";
    }

    public function debug(...$params)
    {
        if (!$this->debug) return;
        $msg = "";
        foreach ($params as $param) $msg .= $this->dump($param);
        $this->write("DEBUG", $msg);
    }

    public function info($message)
    {
        if ($this->log_level <= 1) {
            $this->write("INFO", $message);
        }
    }

    public function warn($message)
    {
        if ($this->log_level <= 2) {
            $this->write("WARN", $message);
        }
    }

    public function error($message)
    {
        if ($this->log_level <= 3) {
            $this->write("ERROR", $message);
        }
    }

    public function levels()
    {
        return $this->log_levels;
    }

    private function write($type, $message)
    {
        if (!$this->logenabled) {
            return;
        }
        
        if ($this->stout) {
            print $type." ".$message."\n";
        }

        $now = microtime(true);
        $micro = sprintf("%03d", ($now - floor($now)) * 1000);
        $now = DateTime::createFromFormat('U', (int)$now); // Only use UTC for logs
        $now = $now->format("Y-m-d H:i:s").".$micro";
        // Clear log file if more than 256MB (temporary solution)
        if (filesize($this->logfile)>(1024*1024*256)) {
            $fh = @fopen($this->logfile, "w");
            @fclose($fh);
        }
        if ($fh = @fopen($this->logfile, "a")) {
            @fwrite($fh, $now."|$type|$this->caller|".$message."\n");
            @fclose($fh);
        }
    }
}
