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

class Param
{
    private $route;
    private $user;
    private $params = array();

    public function __construct($route,$user)
    {
        $this->route = $route;
        $this->user = $user;
        $this->load();
    }

    public function load()
    {
        $this->params = array();
        
        foreach ($_GET as $key=>$val) {
            if (get_magic_quotes_gpc()) $val = stripslashes($val);
            $this->params[$key] = $val;
        }
        foreach ($_POST as $key=>$val) {
            if (get_magic_quotes_gpc()) $val = stripslashes($val);
            $this->params[$key] = $val;
        }
    }  
    
    public function val($index)
    {
        if (isset($this->params[$index])) return $this->params[$index]; else return null;
    }

    public function exists($index)
    {
        if (isset($this->params[$index])) return true; else return false;
    }  
}
