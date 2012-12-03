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

function create_multigraph($userid)
{
  db_query("INSERT INTO multigraph (`userid`,`feedlist`) VALUES ('$userid','')");
  return db_insert_id();  
}

function delete_multigraph($id,$userid)
{
  db_query("DELETE FROM multigraph WHERE `id` = '$id' AND `userid` = '$userid'");
}

function set_multigraph($id, $userid, $name, $feedlist)
{
  db_query("UPDATE multigraph SET `feedlist` = '$feedlist' , `name` = '$name' WHERE `id`='$id' AND `userid`='$userid'");
}

/*
userid not used
need to implement public multigraph feature, only return feedlist if multigraph is public or user session
*/
function get_multigraph($id, $userid)
{
  $result = db_query("SELECT feedlist FROM multigraph WHERE `id`='$id'");
  $result = db_fetch_array($result);
  $feedlist = json_decode($result['feedlist']);
  return $feedlist;
}

function get_user_multigraph($userid)
{
  $result = db_query("SELECT id,name,feedlist FROM multigraph WHERE `userid`='$userid'");

  $multigraphs = array();
  while ($row = db_fetch_object($result))
  {
    $multigraphs[] = array('id'=>$row->id,'name'=>$row->name,'feedlist'=>$row->feedlist);
  }
  return $multigraphs;
}
