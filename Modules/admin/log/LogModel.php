<?php

// Emoncms Log model

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

class LogModel
{
    private $settings;
    private $log;
    private $emoncms_logfile;

    public function __construct($settings)
    {
        $this->settings = $settings;
        $this->log = new EmonLogger(__FILE__);
        $this->emoncms_logfile = $settings['log']['location'] . "/emoncms.log";
    }

    public function emoncms_logfile()
    {
        return $this->emoncms_logfile;
    }

    public function is_enabled()
    {
        return $this->settings['log']['enabled'];
    }

    public function get_log_level()
    {
        return $this->settings['log']['level'];
    }

    /**
     * Read the last N lines from log file
     * PHP replacement for tail command
     */
    private function read_file($file, $lines)
    {
        $handle = fopen($file, "r");
        if (!$handle) {
            return array();
        }

        $linecounter = $lines;
        $pos = -2;
        $beginning = false;
        $text = array();

        while ($linecounter > 0) {
            $t = " ";
            while ($t != "\n") {
                if (fseek($handle, $pos, SEEK_END) == -1) {
                    $beginning = true;
                    break;
                }
                $t = fgetc($handle);
                $pos--;
            }
            $linecounter--;
            if ($beginning) {
                rewind($handle);
            }
            $text[$lines - $linecounter - 1] = fgets($handle);
            if ($beginning) break;
        }
        fclose($handle);
        return array_reverse($text);
    }

    /**
     * Get log content as text
     */
    public function get_log_content($num_lines = 25)
    {
        if (!$this->is_enabled()) {
            return array('success' => false, 'message' => "Log is disabled");
        }

        if (!file_exists($this->emoncms_logfile)) {
            return array('success' => false, 'message' => "Log file does not exist");
        }

        $lines = $this->read_file($this->emoncms_logfile, $num_lines);
        $content = '';
        foreach ($lines as $line) {
            $content .= $line;
        }

        return array('success' => true, 'log' => $content);
    }

    /**
     * Get log view data
     */
    public function get_view_data()
    {
        return array(
            'log_enabled' => $this->is_enabled(),
            'emoncms_logfile' => $this->emoncms_logfile,
            'log_level' => $this->get_log_level(),
            'log_location' => $this->settings['log']['location']
        );
    }

    /**
     * Get log file size in MB
     */
    public function get_log_size()
    {
        if (!file_exists($this->emoncms_logfile)) {
            return 0;
        }
        return round(filesize($this->emoncms_logfile) / 1024 / 1024, 2);
    }

    /**
     * Download log file
     */
    public function download()
    {
        if (!$this->is_enabled()) {
            return array('success' => false, 'message' => "Log is disabled");
        }

        if (!file_exists($this->emoncms_logfile)) {
            return array('success' => false, 'message' => $this->emoncms_logfile . " does not exist");
        }

        header("Content-Type: application/octet-stream");
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($this->emoncms_logfile) . "\"");
        header("Pragma: no-cache");
        header("Expires: 0");
        flush();
        readfile($this->emoncms_logfile);
        exit;
    }
}