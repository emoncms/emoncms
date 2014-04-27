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

// It's important to suppress warnings here, so that adding logging is backwards compatible to old installs.
@define("LOG4PHP_INSTALLED", include_once( 'log4php/Logger.php' ));
if (LOG4PHP_INSTALLED)
    @Logger::configure( $log4php_configPath );

class EmonLogger
{
    private $concreteLogger;
    private $loggerConfigured = false;
    
    public function __construct($clientFileName)
    {
        global $log4php_configPath;
        if (!$log4php_configPath || !file_exists($log4php_configPath)){
            $this->loggerConfigured = false;
            return;
        }
        
        
        if (LOG4PHP_INSTALLED){
            Logger::configure( $log4php_configPath );
            $clientFileNameWithoutPath = basename($clientFileName);
            $this->concreteLogger = Logger::getLogger($clientFileNameWithoutPath);
            $this->loggerConfigured = true;
        }
    }

    public function info ($message){
        if ($this->loggerConfigured)
            $this->concreteLogger->info($message);
    }
    
    public function warn ($message){
        if ($this->loggerConfigured)
            $this->concreteLogger->warn(date("Y-n-j H:i:s", time()).", ".$message);
    }
}
