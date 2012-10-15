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

function set_multigraph($userid, $feedlist)
{
  $result = db_query("SELECT * FROM multigraph WHERE userid = '$userid'");
  $row = db_fetch_array($result);

  if ($row)
  {
    db_query("UPDATE multigraph SET feedlist = '$feedlist' WHERE userid='$userid'");
  }
  else
  {
    db_query("INSERT INTO multigraph (`userid`,`feedlist`) VALUES ('$userid','$feedlist')");
  }
}

function get_multigraph($userid)
{
  $result = db_query("SELECT feedlist FROM multigraph WHERE userid='$userid'");
  $result = db_fetch_array($result);
  $feedlist = $result['feedlist'];

  return $feedlist;
}
