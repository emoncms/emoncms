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

  include "Modules/feed/feed_model.php";
  $feed = new Feed($mysqli,$timestore_adminkey);

  if ($route->format == 'html')
  {
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
    elseif ($route->action == "updatesize" && $session['write']) {
      $result = $feed->update_user_feeds_size($session['userid']);
    }
    else {
      $feedid = (int) get('id');
      // Actions that operate on a single existing feed that all use the feedid to select:
      // First we load the meta data for the feed that we want
      $qresult = $mysqli->query("SELECT userid,public,engine FROM feeds WHERE `id` = '$feedid'");
      $row = $qresult->fetch_array();

      if ($row) // if the feed exists
      {
        // if public or belongs to user
        if ($row['public'] || ($session['userid']>0 && $row['userid']==$session['userid'] && $session['read']))
        {
          if ($route->action == "value") $result = $feed->get_field($feedid,'value');
          if ($route->action == "get") $result = $feed->get_field($feedid,get('field')); // '/[^\w\s-]/'
          
          if ($route->action == 'histogram') $result = $feed->histogram_get_power_vs_kwh($feedid,get('start'),get('end'));
          if ($route->action == 'kwhatpower') $result = $feed->histogram_get_kwhd_atpower($feedid,get('min'),get('max'));
          if ($route->action == 'kwhatpowers') $result = $feed->histogram_get_kwhd_atpowers($feedid,get('points'));

          if ($route->action == 'data') $result = $feed->get_data($feedid,get('start'),get('end'),get('dp'));
          if ($route->action == 'timestoreaverage') $result = $feed->get_timestore_average($feedid,get('start'),get('end'),get('interval'));
        }
     
        // write session required
        if ($session['write'] && $session['userid']>0 && $row['userid']==$session['userid'])
        {
          // Storage engine agnostic
          if ($route->action == 'set') $result = $feed->set_feed_fields($feedid,get('fields'));
          if ($route->action == "insert") $result = $feed->insert_data($feedid,time(),get("time"),get("value"));
          if ($route->action == "update") $result = $feed->update_data($feedid,time(),get("time"),get('value'));
          if ($route->action == "delete") $result = $feed->delete($feedid);
          
          if ($row['engine']==Engine::TIMESTORE)
          {
            if ($route->action == "export") $result = $feed->timestore_export($feedid,get('layer'),get('start'));
            if ($route->action == "exportmeta") $result = $feed->timestore_export_meta($feedid);
            if ($route->action == "getmeta") $result = $feed->timestore_get_meta($feedid);
            if ($route->action == "scalerange") $result = $feed->timestore_scale_range($feedid,get('start'),get('end'),get('value'));
          } elseif ($row['engine']==Engine::MYSQL)  {
            if ($route->action == "export") $result = $feed->mysqltimeseries_export($feedid,get('start'));
            if ($route->action == "deletedatapoint") $result = $feed->mysqltimeseries_delete_data_point($feedid,get('feedtime'));
            if ($route->action == "deletedatarange") $result = $feed->mysqltimeseries_delete_data_range($feedid,get('start'),get('end'));

          } elseif ($row['engine']==Engine::PHPTIMESERIES)  {
            if ($route->action == "export") $result = $feed->phptimeseries_export($feedid,get('start'));
          }
        }
      }
    }
  }

  return array('content'=>$result);
}
