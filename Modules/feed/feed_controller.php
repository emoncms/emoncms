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
    global $mysqli, $redis, $session, $route, $feed_settings;
    $result = false;

    include "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis,$feed_settings);

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

        } elseif ($route->action == "getid" && $session['read']) {
            $result = $feed->get_id($session['userid'],get('name'));
        } elseif ($route->action == "create" && $session['write']) {
            $result = $feed->create($session['userid'],get('name'),get('datatype'),get('engine'),json_decode(get('options')));
        } elseif ($route->action == "updatesize" && $session['write']) {
            $result = $feed->update_user_feeds_size($session['userid']);
        // To "fetch" multiple feed values in a single request
        // http://emoncms.org/feed/fetch.json?ids=123,567,890
        } elseif ($route->action == "fetch" && $session['read']) {
            $feedids = (array) (explode(",",(get('ids'))));
            for ($i=0; $i<count($feedids); $i++) {
                $feedid = (int) $feedids[$i];
                if ($feed->exist($feedid)) // if the feed exists
                { $result[$i] = $feed->get_value($feedid);
                } else { $result[$i] = ""; }
            }
        } else {
            $feedid = (int) get('id');
            // Actions that operate on a single existing feed that all use the feedid to select:
            // First we load the meta data for the feed that we want

            if ($feed->exist($feedid)) // if the feed exists
            {
                $f = $feed->get($feedid);
                // if public or belongs to user
                if ($f['public'] || ($session['userid']>0 && $f['userid']==$session['userid'] && $session['read']))
                {
                    if ($route->action == "value") $result = $feed->get_value($feedid);
                    if ($route->action == "timevalue") $result = $feed->get_timevalue_seconds($feedid);
                    if ($route->action == "get") $result = $feed->get_field($feedid,get('field')); // '/[^\w\s-]/'
                    if ($route->action == "aget") $result = $feed->get($feedid);

                    if ($route->action == 'histogram') $result = $feed->histogram_get_power_vs_kwh($feedid,get('start'),get('end'));
                    if ($route->action == 'kwhatpower') $result = $feed->histogram_get_kwhd_atpower($feedid,get('min'),get('max'));
                    if ($route->action == 'kwhatpowers') $result = $feed->histogram_get_kwhd_atpowers($feedid,get('points'));
                    if ($route->action == 'data') $result = $feed->get_data($feedid,get('start'),get('end'),get('dp'));
                    if ($route->action == 'average') $result = $feed->get_average($feedid,get('start'),get('end'),get('interval'));
                }

                // write session required
                if (isset($session['write']) && $session['write'] && $session['userid']>0 && $f['userid']==$session['userid'])
                {
                    // Storage engine agnostic
                    if ($route->action == 'set') $result = $feed->set_feed_fields($feedid,get('fields'));
                    if ($route->action == "insert") $result = $feed->insert_data($feedid,time(),get("time"),get("value"));
                    if ($route->action == "update") $result = $feed->update_data($feedid,time(),get("time"),get('value'));
                    if ($route->action == "delete") $result = $feed->delete($feedid);
                    if ($route->action == "getmeta") $result = $feed->get_meta($feedid);
                    
                    if ($route->action == "csvexport") $feed->csv_export($feedid,get('start'),get('end'),get('interval'));
                    
                    if ($f['engine']==Engine::TIMESTORE) {
                        if ($route->action == "export") $result = $feed->timestore_export($feedid,get('start'),get('layer'));
                        if ($route->action == "exportmeta") $result = $feed->timestore_export_meta($feedid);
                        if ($route->action == "scalerange") $result = $feed->timestore_scale_range($feedid,get('start'),get('end'),get('value'));
                    } elseif ($f['engine']==Engine::MYSQL) {
                        if ($route->action == "export") $result = $feed->mysqltimeseries_export($feedid,get('start'));
                        if ($route->action == "deletedatapoint") $result = $feed->mysqltimeseries_delete_data_point($feedid,get('feedtime'));
                        if ($route->action == "deletedatarange") $result = $feed->mysqltimeseries_delete_data_range($feedid,get('start'),get('end'));
                    } elseif ($f['engine']==Engine::PHPTIMESERIES) {
                        if ($route->action == "export") $result = $feed->phptimeseries_export($feedid,get('start'));
                    } elseif ($f['engine']==Engine::PHPFIWA) {
                        if ($route->action == "export") $result = $feed->phpfiwa_export($feedid,get('start'),get('layer'));
                    } elseif ($f['engine']==Engine::PHPFINA) {
                        if ($route->action == "export") $result = $feed->phpfina_export($feedid,get('start'));
                    }
                }
            }
            else
            {
                $result = array('success'=>false, 'message'=>'Feed does not exist');
            }
        }
    }

    return array('content'=>$result);
}
