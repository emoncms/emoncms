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

function feed_controller()
{
  global $mysqli, $session, $route, $timestore_adminkey;
  $result = false;

  require "Modules/feed/timestore_class.php";
  $timestore = new Timestore($timestore_adminkey);

  include "Modules/feed/feed_model.php";
  $feed = new Feed($mysqli,$timestore);

  if ($route->format == 'html')
  {
      if ($route->action == "convert" && $session['write']) $result = view("Modules/feed/Views/feedconvert_view.php",array());
      if ($route->action == "list" && $session['write']) $result = view("Modules/feed/Views/feedlist_view.php",array());
      if ($route->action == "api" && $session['write']) $result = view("Modules/feed/Views/feedapi_view.php",array());
  }

  if ($route->format == 'json')
  {
    // Public actions available on public feeds.
    if ($route->action == "list")
    {
      if (!isset($_GET['userid']) && $session['read']) $result = $feed->get_user_feeds($session['userid']);
      if (isset($_GET['userid']) && $session['read'] && $_GET['userid'] == $session['userid']) $result = $feed->get_user_feeds($session['userid']);
      if (isset($_GET['userid']) && $session['read'] && $_GET['userid'] != $session['userid']) $result = $feed->get_user_public_feeds(get('userid'));
      if (isset($_GET['userid']) && !$session['read']) $result = $feed->get_user_public_feeds(get('userid'));
    }  
    elseif ($route->action == "getid" && $session['read']) {
      $result = $feed->get_id($session['userid'],get('name'));
    }
    elseif ($route->action == "create" && $session['write']) {
      $result = $feed->create($session['userid'],get('name'),get('type'));
    } 
    else {
      $feedid = (int) get('id');
      // Actions that operate on a single existing feed that all use the feedid to select:
      // First we load the meta data for the feed that we want
      $qresult = $mysqli->query("SELECT userid,public,timestore FROM feeds WHERE `id` = '$feedid'");
      $row = $qresult->fetch_array();

      if ($row) // if the feed exists
      {
        // if public or belongs to user
        if ($row['public'] || ($session['userid']>0 && $row['userid']==$session['userid'] && $session['read']))
        {
          if ($route->action == "value") $result = $feed->get_field($feedid,'value');
          if ($route->action == "get") $result = $feed->get_field($feedid,get('field')); // '/[^\w\s-]/'
          if ($route->action == 'histogram') $result = $feed->get_histogram_data($feedid,get('start'),get('end'));
          if ($route->action == 'kwhatpower') $result = $feed->get_kwhd_atpower($feedid,get('min'),get('max'));
          if ($route->action == 'kwhatpowers') $result = $feed->get_kwhd_atpowers($feedid,get('points'));

          if ($route->action == 'data' && $row['timestore']) $result = $feed->get_data_timestore($feedid,get('start'),get('end'),get('dp'));

          if ($route->action == 'data' && !$row['timestore']) $result = $feed->get_data_mysql($feedid,get('start'),get('end'),get('dp'));

        }
     
        // write session required
        if ($session['write'] && $session['userid']>0 && $row['userid']==$session['userid'])
        {
          if ($route->action == 'set') $result = $feed->set_feed_fields($feedid,get('fields'));
          if ($row['timestore'])
          {
            if ($route->action == "insert") $result = $feed->insert_data_timestore($feedid,time(),get("time"),get("value"));
            if ($route->action == "delete") $result = $feed->delete_timestore($feedid);
          }
          else
          {
            if ($route->action == "insert") $result = $feed->insert_data($feedid,time(),get("time"),get("value"));
            if ($route->action == "update") $result = $feed->update_data($feedid,time(),get("time"),get('value'));
            if ($route->action == "deletedatapoint") $result = $feed->delete_data($feedid,get('feedtime'),get('feedtime'));
            if ($route->action == "deletedatarange") $result = $feed->deletedatarange($feedid,get('start'),get('end'));
            if ($route->action == "delete") $result = $feed->delete($feedid);

            if ($route->action == "export") $result = $feed->export($feedid,get('start'));
          }
        }
      }
    }
  }

  return array('content'=>$result);
}
