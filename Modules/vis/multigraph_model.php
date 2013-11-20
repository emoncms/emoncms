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
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function create($userid)
    {
        $userid = intval($userid);
        $sql = ("INSERT INTO multigraph (userid, feedlist) VALUES('$userid', '');");
	$result = db_query($this->conn, $sql);

        return db_lastval($this->conn, $result);
    }

    public function delete($id,$userid)
    {
        $userid = intval($userid);
        $sql = ("DELETE FROM multigraph WHERE id = '$id' AND userid = '$userid';");
	db_query($this->conn, $sql);
    }

    public function set($id, $userid, $feedlist)
    {
        $id = intval($id);
        $userid = intval($userid);
        $feedlist = preg_replace('/[^\w\s-.",:{}\[\]]/','',$feedlist);
        $sql = ("UPDATE multigraph SET feedlist = '$feedlist' WHERE id = '$id' AND userid = '$userid';");
	db_query($this->conn, $sql);
    }

    /*
    userid not used
    need to implement public multigraph feature, only return feedlist if multigraph is public or user session
    */
    public function get($id, $userid)
    {
        $id = intval($id);
        $userid = intval($userid);
        $sql = ("SELECT feedlist FROM multigraph WHERE id = '$id';");
	$qresult = db_query($this->conn, $sql);
        $result = db_fetch_array($qresult);
        $feedlist = json_decode($result['feedlist']);

        return $feedlist;
    }

    public function getlist($userid)
    {
        $userid = intval($userid);
        $sql = ("SELECT id, name, feedlist FROM multigraph WHERE userid = '$userid';");
	$result = db_query($this->conn, $sql);

        $multigraphs = array();
        while ($row = db_fetch_object($result))
        {
            $multigraphs[] = array('id'=>$row->id,'name'=>$row->name,'feedlist'=>$row->feedlist);
        }
        return $multigraphs;
    }
}
