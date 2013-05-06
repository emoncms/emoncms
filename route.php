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

class Route
{
    public $controller = '';
    public $action = '';
    public $subaction = '';
    public $format = "html"; 

    public function __construct($q)
    {
        $this->decode($q);
    }

    public function decode($q)
    {
        // filter out the applications root to prevent invalid route-parsing
        $q = str_replace(APPLICATION_ROOT, '', $q);
        $q = trim($q, '/');
	
        // filter out all except a-z and / .
        $q = preg_replace('/[^.\/A-Za-z0-9-]/', '', $q);

        // Split by /
        $args = preg_split('/[\/]/', $q);

        // get format (part of last argument after . i.e view.json)
        $lastarg = sizeof($args) - 1;
        $lastarg_split = preg_split('/[.]/', $args[$lastarg]);
        if (count($lastarg_split) > 1) { $this->format = $lastarg_split[1]; }
        $args[$lastarg] = $lastarg_split[0];

        if (count($args) > 0) { $this->controller = $args[0]; }
        if (count($args) > 1) { $this->action = $args[1]; }
        if (count($args) > 2) { $this->subaction = $args[2]; }
    }
}
