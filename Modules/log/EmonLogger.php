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
@define("LOG4PHP_INSTALLED", include_once( $log4php_includePath ));
if (LOG4PHP_INSTALLED)
    @Logger::configure( $log4php_configPath );

class EmonLogger
{
    private $concreteLogger;

    public function __construct($clientFileName)
    {
        $clientFileNameWithoutPath = basename($clientFileName);
        if (LOG4PHP_INSTALLED)
            $this->concreteLogger = Logger::getLogger($clientFileNameWithoutPath);
    }

    public function info ($message){
        if (LOG4PHP_INSTALLED)
            $this->concreteLogger->info($message);
    }
}
