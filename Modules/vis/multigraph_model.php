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
        $this->mysqli->query("INSERT INTO multigraph (`userid`,`feedlist`, `name`) VALUES ('$userid','', 'New Multigraph')");
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
        $feedlist = preg_replace('/[^\p{L}_\p{N}\s-.",:{}\[\]]/u','',$feedlist);
        $name = preg_replace('/[^\p{L}_\p{N}\s-.]/u','',$name);
        $this->mysqli->query("UPDATE multigraph SET `name` = '$name', `feedlist` = '$feedlist' WHERE `id`='$id' AND `userid`='$userid'");
        if ($this->mysqli->affected_rows>0){
            return array('success'=>true, 'message'=>'Multigraph updated');
        } else {
            return array('success'=>false, 'message'=>'Multigraph was not updated');
        }
    }

    /*
    userid not used
    need to implement public multigraph feature, only return feedlist if multigraph is public or user session
    */
    public function get($id, $userid)
    {
        $id = intval($id);
        $userid = intval($userid);
        $result = $this->mysqli->query("SELECT name, feedlist FROM multigraph WHERE `id`='$id'");
        $result = $result->fetch_array();
        if (!$result) return array('success'=>false, 'message'=>'Multigraph does not exist');
        $row['name'] = $result['name'];
        $row['feedlist'] = json_decode($result['feedlist']);
        return $row;
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

}
