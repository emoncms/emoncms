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

class UpdateModel
{
    private $settings;
    private $redis;
    private $log;
    private $update_logfile;
    private $old_update_logfile;

    const FIRMWARE_UPLOAD_DIR = "/opt/openenergymonitor/data/firmware/upload";

    public function __construct($settings, $redis)
    {
        $this->settings = $settings;
        $this->redis = $redis;
        $this->log = new EmonLogger(__FILE__);
        $this->update_logfile = $settings['log']['location'] . "/update.log";
        $this->old_update_logfile = $settings['log']['location'] . "/emonpiupdate.log";
    }

    public function update_logfile()
    {
        return $this->update_logfile;
    }

    public function old_update_logfile()
    {
        return $this->old_update_logfile;
    }

    // -------------------------------------------------------------------------
    // Firmware / serial ports
    // -------------------------------------------------------------------------

    public function firmware_available()
    {
        $localfile = $this->settings['openenergymonitor_dir'] . "/EmonScripts/firmware_available.json";
        if ($response = @file_get_contents("https://raw.githubusercontent.com/openenergymonitor/EmonScripts/master/firmware_available.json?v=" . time())) {
            return json_decode($response);
        } elseif (file_exists($localfile)) {
            return json_decode(file_get_contents($localfile));
        }
        return array('success' => false, 'message' => "Can't get firmware available file");
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
                // no need to do anything here, function will exit with no ports
            }
        }
        if (count($ports) == 0) $ports[] = "none";
        return $ports;
    }

    // -------------------------------------------------------------------------
    // Update actions
    // -------------------------------------------------------------------------

    public function update_start($type, $serial_port, $firmware_key)
    {
        if (!in_array($type, array("all", "emoncms"))) {
            return array('success' => false, 'message' => "Invalid update type");
        }
        if (!in_array($serial_port, $this->listSerialPorts())) {
            return array('success' => false, 'message' => "Invalid serial port");
        }
        $firmware_available = $this->firmware_available();
        if (!isset($firmware_available->$firmware_key) && $firmware_key !== "none") {
            return array('success' => false, 'message' => "Invalid firmware");
        }

        $action = file_exists($this->settings['openenergymonitor_dir'] . "/EmonScripts")
            ? "emoncms-update"
            : "emoncms-update-legacy";

        return $this->pushAction($action, [$type, $firmware_key, $serial_port], "update");
    }

    public function update_firmware($serial_port, $firmware_key)
    {
        if (!in_array($serial_port, $this->listSerialPorts())) {
            return array('success' => false, 'message' => "Invalid serial port");
        }
        $firmware_available = $this->firmware_available();
        if (!isset($firmware_available->$firmware_key)) {
            return array('success' => false, 'message' => "Invalid firmware");
        }

        return $this->pushAction("firmware-upload", [$serial_port, $firmware_key], "update");
    }

    public function upload_custom_firmware($port, $baud_rate, $core, $autoreset, $file)
    {
        if (!in_array($port, $this->listSerialPorts())) {
            return array('success' => false, 'message' => "Invalid serial port");
        }
        if (!in_array((int)$baud_rate, [300, 1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200, 230400, 460800, 921600], true)) {
            return array('success' => false, 'message' => "Invalid baud rate");
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $core)) {
            return array('success' => false, 'message' => "Invalid core");
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $autoreset)) {
            return array('success' => false, 'message' => "Invalid autoreset");
        }

        $clean_filename = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
        $filename_parts = explode('.', $clean_filename);
        $file_extension = strtolower(end($filename_parts));
        if ($file_extension !== 'hex' && $file_extension !== 'bin') {
            return array('success' => false, 'message' => "Only .hex or .bin files are allowed");
        }

        $max_size = 2 * 1024 * 1024; // 2 MB
        if ($file['size'] > $max_size) {
            return array('success' => false, 'message' => "Firmware file exceeds maximum allowed size (2 MB)");
        }

        if (!is_dir(self::FIRMWARE_UPLOAD_DIR)) {
            return array('success' => false, 'message' => "Firmware upload directory does not exist");
        }

        $safe_filename = 'firmware_' . time() . '_' . bin2hex(random_bytes(8)) . '.hex';
        $tmpfile = self::FIRMWARE_UPLOAD_DIR . "/" . $safe_filename;

        if (!move_uploaded_file($file['tmp_name'], $tmpfile)) {
            return array('success' => false, 'message' => "Failed to save uploaded firmware file");
        }

        return $this->pushAction("firmware-upload", [$port, "custom", $safe_filename, (string)$baud_rate, $core, $autoreset], "update");
    }

    // -------------------------------------------------------------------------
    // Update log
    // -------------------------------------------------------------------------

    /**
     * Returns the update log content, or false if no log file exists.
     */
    public function get_update_log()
    {
        if (file_exists($this->update_logfile)) {
            return trim(file_get_contents($this->update_logfile));
        } elseif (file_exists($this->old_update_logfile)) {
            return trim(file_get_contents($this->old_update_logfile));
        }
        return false;
    }

    /**
     * Sends the update log as a file download and exits.
     */
    public function download_update_log()
    {
        $logfile = null;
        if (file_exists($this->update_logfile)) {
            $logfile = $this->update_logfile;
        } elseif (file_exists($this->old_update_logfile)) {
            $logfile = $this->old_update_logfile;
        }

        header("Content-Type: application/octet-stream");
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($this->update_logfile) . "\"");
        header("Pragma: no-cache");
        header("Expires: 0");
        flush();

        if ($logfile !== null) {
            ob_start();
            readfile($logfile);
            echo trim(ob_get_clean());
        } else {
            echo "Update log does not exist";
        }
        exit;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function pushAction(string $action, array $args, ?string $log = null): array
    {
        if ($this->redis) {
            $payload = json_encode(['run' => $action, 'args' => $args, 'log' => $log]);
            $this->redis->rpush("service-runner", $payload);
            $this->log->info("UpdateModel::pushAction() service-runner trigger sent for action '$action'");
            return array('success' => true, 'message' => "service-runner trigger sent for action '$action'");
        } else {
            $this->log->error("UpdateModel::pushAction() Redis not enabled. Cannot execute action '$action' safely.");
            return array('success' => false, 'message' => "Redis is required to run service commands");
        }
    }
}
