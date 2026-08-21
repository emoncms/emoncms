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
    private $feed;

    public function __construct($mysqli, $feed = null)
    {
        $this->mysqli = $mysqli;
        $this->feed = $feed;
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
        $feedlist = preg_replace('/[^\p{L}_\p{N}\s\-.",:{}\[\]]/u','',$feedlist);
        $name = preg_replace('/[^\p{L}_\p{N}\s\-.]/u','',$name);

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
        $result = $this->mysqli->query("SELECT userid, name, feedlist FROM multigraph WHERE `id`='$id'");
        $result = $result->fetch_array();
        if (!$result) return array('success'=>false, 'message'=>'Multigraph does not exist');

        $feedlist = json_decode($result['feedlist']);

        // Access control: the owner may always read the config. Otherwise the
        // config is only returned if every feed it references is public. The feed
        // data itself is separately access-controlled per feed; this stops a
        // non-owner learning the feed ids/names/layout of a private multigraph.
        //
        // Fails closed: a feedlist that is empty, does not parse as a list, or
        // holds an entry without a feed id proves nothing about being public, so
        // it is treated as private rather than shared on the strength of the
        // entries that did parse.
        if ($userid < 1 || (int) $result['userid'] !== $userid) {
            $private = !$this->feed || !is_array($feedlist) || !count($feedlist);

            $feedids = array();
            if (!$private) {
                foreach ($feedlist as $f) {
                    if (!is_object($f) || !isset($f->id)) { $private = true; break; }
                    $feedids[] = $f->id;
                }
            }

            if ($private || !$this->feed->all_feeds_public($feedids)) {
                return array('success'=>false, 'message'=>'this multigraph is not public');
            }
        }

        $row['name'] = $result['name'];
        $row['feedlist'] = $feedlist;
        return $row;
    }

    public function getlist($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT id,name,feedlist FROM multigraph WHERE `userid`='$userid'");

        $multigraphs = array();
        while ($row = $result->fetch_object())
        {
            $multigraphs[] = array('id'=>$row->id,'name'=>$row->name,'feedlist'=>json_decode($row->feedlist));
        }
        return $multigraphs;
    }

}
