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

class SerialModel
{
    private $redis;
    private $log;

    public function __construct($settings, $redis)
    {
        $this->redis = $redis;
        $this->log = new EmonLogger(__FILE__);
    }

    public function listSerialPorts()
    {
        $ports = array();
        for ($i = 0; $i < 5; $i++) {
            try {
                if (file_exists("/dev/ttyAMA$i")) $ports[] = "ttyAMA$i";
                if (file_exists("/dev/ttyUSB$i")) $ports[] = "ttyUSB$i";
                if (file_exists("/dev/ttyS$i"))   $ports[] = "ttyS$i";
            } catch (Exception $e) {
                // no ports available
            }
        }
        if (count($ports) == 0) $ports[] = "none";
        return $ports;
    }

    public function serialmonitor_pid()
    {
        $output = [];
        @exec('pidof -x start.sh', $output);
        return isset($output[0]) ? $output[0] : false;
    }

    public function start($serialport, $baudrate)
    {
        if (!in_array($serialport, $this->listSerialPorts())) {
            return array('success' => false, 'message' => "invalid serial port");
        }
        if (!in_array($baudrate, array(9600, 38400, 115200))) {
            return array('success' => false, 'message' => "invalid baud rate");
        }
        return $this->pushAction("serialmonitor-start", [(string)$baudrate, "/dev/" . $serialport]);
    }

    public function stop()
    {
        if (!$this->redis) return array('success' => false, 'message' => "Redis not enabled");
        $this->redis->rpush("serialmonitor", "exit");
        return array('success' => true, 'message' => "serialmonitor stop command sent");
    }

    public function getLog()
    {
        if (!$this->redis) return false;
        $out = "";
        while ($this->redis->llen('serialmonitor-log')) {
            $out .= $this->redis->lpop('serialmonitor-log') . "\n";
        }
        return $out;
    }

    public function sendCmd($cmd)
    {
        if (!$this->redis) return array('success' => false, 'message' => "Redis not enabled");
        if ($cmd === "") return array('success' => false, 'message' => "no command");
        $this->redis->rpush("serialmonitor", $cmd);
        return array('success' => true, 'message' => "serialmonitor cmd sent");
    }

    private function pushAction(string $action, array $args, ?string $log = null): array
    {
        if ($this->redis) {
            $payload = json_encode(['run' => $action, 'args' => $args, 'log' => $log]);
            $this->redis->rpush("service-runner", $payload);
            $this->log->info("SerialModel::pushAction() service-runner trigger sent for action '$action'");
            return array('success' => true, 'message' => "service-runner trigger sent for action '$action'");
        } else {
            $this->log->error("SerialModel::pushAction() Redis not enabled. Cannot execute action '$action' safely.");
            return array('success' => false, 'message' => "Redis is required to run service commands");
        }
    }
}
