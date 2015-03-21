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

class Multigraph
{
    private $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function create($userid)
    {
        $userid = intval($userid);
        $this->mysqli->query("INSERT INTO multigraph (`userid`,`feedlist`) VALUES ('$userid','')");
        return $this->mysqli->insert_id;  
    }

    public function delete($id,$userid)
    {
        $userid = intval($userid);
        $this->mysqli->query("DELETE FROM multigraph WHERE `id` = '$id' AND `userid` = '$userid'");
    }

    public function set($id, $userid, $feedlist, $name)
    {
        $id = intval($id);
        $userid = intval($userid);
        $feedlist = preg_replace('/[^\w\s-.",:{}\[\]]/','',$feedlist);
        $name = preg_replace('/[^\w\s-.]/','',$name);
        $this->mysqli->query("UPDATE multigraph SET `name` = '$name', `feedlist` = '$feedlist' WHERE `id`='$id' AND `userid`='$userid'");
    }

    /*
    userid not used
    need to implement public multigraph feature, only return feedlist if multigraph is public or user session
    */
    public function get($id, $userid)
    {
        $id = intval($id);
        $userid = intval($userid);
        $result = $this->mysqli->query("SELECT feedlist FROM multigraph WHERE `id`='$id'");
        $result = $result->fetch_array();
        $feedlist = json_decode($result['feedlist']);
        return $feedlist;
    }

    public function getlist($userid)
    {
        $userid = intval($userid);
        $result = $this->mysqli->query("SELECT id,name,feedlist FROM multigraph WHERE `userid`='$userid'");

        $multigraphs = array();
        while ($row = $result->fetch_object())
        {
            $multigraphs[] = array('id'=>$row->id,'name'=>$row->name,'feedlist'=>$row->feedlist);
        }
        return $multigraphs;
    }
    
    /*
    userid not used
    need to implement public multigraph feature, only return feedlist if multigraph is public or user session
    */
    public function getname($id, $userid)
    {
        $id = intval($id);
        $userid = intval($userid);
        $result = $this->mysqli->query("SELECT name FROM multigraph WHERE `id`='$id'");
        $result = $result->fetch_array();
        return $result['name'];
    }
}
