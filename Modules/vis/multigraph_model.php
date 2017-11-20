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
        $userid = (int) $userid;
        $this->mysqli->query("INSERT INTO multigraph (`userid`,`feedlist`, `name`) VALUES ('$userid','', 'New Multigraph')");
        return $this->mysqli->insert_id;
    }

    public function delete($id,$userid)
    {
        $id = (int) $id;
        $userid = (int) $userid;
        
        $stmt = $this->mysqli->prepare("DELETE FROM multigraph WHERE id=? AND userid=?");
        $stmt->bind_param("ii", $id, $userid);
        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected_rows>0){
            return array('success'=>true, 'message'=>'Multigraph deleted');
        } else {
            return array('success'=>false, 'message'=>'Multigraph was not deleted');
        }
    }

    public function set($id, $userid, $feedlist, $name)
    {
        $id = (int) $id;
        $userid = (int) $userid;
        $feedlist = preg_replace('/[^\p{L}_\p{N}\s-.",:{}\[\]]/u','',$feedlist);
        $name = preg_replace('/[^\p{L}_\p{N}\s-.]/u','',$name);

        $stmt = $this->mysqli->prepare("UPDATE multigraph SET name=?, feedlist=? WHERE id=? AND userid=?");
        $stmt->bind_param("ssii", $name, $feedlist, $id, $userid);
        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected_rows>0){
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
        $id = (int) $id;
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT name, feedlist FROM multigraph WHERE `id`='$id'");
        $result = $result->fetch_array();
        if (!$result) return array('success'=>false, 'message'=>'Multigraph does not exist');
        $row['name'] = $result['name'];
        $row['feedlist'] = json_decode($result['feedlist']);
        return $row;
    }

    public function getlist($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT id,name,feedlist FROM multigraph WHERE `userid`='$userid'");

        $multigraphs = array();
        while ($row = $result->fetch_object())
        {
            $multigraphs[] = array('id'=>$row->id,'name'=>$row->name,'feedlist'=>$row->feedlist);
        }
        return $multigraphs;
    }

}
