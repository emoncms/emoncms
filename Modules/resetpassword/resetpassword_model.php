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

class ResetPasswd
{

    private $mysqli;
    private $rememberme;
    private $enable_rememberme = false;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
        //$this->rememberme = $rememberme;
    }

    public function resetpasswd($email)
    {      
     //   $userid = intval($userid);
        $result = $this->mysqli->query("SELECT `apikey_write` FROM users WHERE `email`='$email'");
        $row = $result->fetch_object();              
        
        return TRUE; 
    }
    
 
}

