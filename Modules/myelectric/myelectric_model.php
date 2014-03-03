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

class MyElectric
{
    private $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }
    
    public function set_mysql($userid,$json)
    {
        $userid = (int) $userid;
        $data = json_decode($json);
        $json = json_encode($data);
        $result = $this->mysqli->query("SELECT `userid` FROM myelectric WHERE `userid`='$userid'");
        if ($result->num_rows) {
            $this->mysqli->query("UPDATE myelectric SET `data`='$json' WHERE `userid`='$userid'");
        } else {
            $this->mysqli->query("INSERT INTO myelectric (`userid`,`data`) VALUES ('$userid','$json')");
        }
    }
    
    public function get_mysql($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT `data` FROM myelectric WHERE `userid`='$userid'");
        if ($row = $result->fetch_array()) {
          return json_decode($row['data']);
        } else {
          return array("powerfeed"=>0, "kwhfeed"=>0);
        }
        
    }

}
