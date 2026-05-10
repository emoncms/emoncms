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

class Admin
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

    // --- Public shell commands called from the controller ---

    public function shutdown_system() {
        shell_exec('sudo shutdown -h now 2>&1');
    }

    public function reboot_system() {
        shell_exec('sudo shutdown -r now 2>&1');
    }

    public function set_filesystem_ro() {
        passthru('rpi-ro');
    }

    public function set_filesystem_rw() {
        passthru('rpi-rw');
    }
}