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

    require_once "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis,$feed_settings);

    if ($route->format == 'html')
    {
        if ($route->action == "list" && $session['write']) $result = view("Modules/feed/Views/feedlist_view.php",array());
        else if ($route->action == "api" && $session['write']) $result = view("Modules/feed/Views/feedapi_view.php",array());
    }

    else if ($route->format == 'json')
    {
        // Public actions available on public feeds.
        if ($route->action == "list")
        {
            if ($session['read']) {
                if (!isset($_GET['userid']) || (isset($_GET['userid']) && $_GET['userid'] == $session['userid'])) $result = $feed->get_user_feeds($session['userid']);
                else if (isset($_GET['userid']) && $_GET['userid'] != $session['userid']) $result = $feed->get_user_public_feeds(get('userid'));
            }
            else if (isset($_GET['userid'])) $result = $feed->get_user_public_feeds(get('userid'));

        } elseif ($route->action == "listwithmeta" && $session['read']) {
            $result = $feed->get_user_feeds_with_meta($session['userid']);
        } elseif ($route->action == "getid" && $session['read']) { 
            $route->format = "text";
            $result = $feed->get_id($session['userid'],get("name"));
        } elseif ($route->action == "create" && $session['write']) {
            $result = $feed->create($session['userid'],get('tag'),get('name'),get('datatype'),get('engine'),json_decode(get('options')));
        } elseif ($route->action == "updatesize" && $session['write']) {
            $result = $feed->update_user_feeds_size($session['userid']);
        } elseif ($route->action == "buffersize" && $session['write']) {
            $result = $feed->get_buffer_size();
        // To "fetch" multiple feed values in a single request
        // http://emoncms.org/feed/fetch.json?ids=123,567,890
        } elseif ($route->action == "fetch") {
            $feedids = (array) (explode(",",(get('ids'))));
            for ($i=0; $i<count($feedids); $i++) {
                $feedid = (int) $feedids[$i];
                if ($feed->exist($feedid)) {  // if the feed exists
                   $f = $feed->get($feedid);
                   if ($f['public'] || ($session['userid']>0 && $f['userid']==$session['userid'] && $session['read'])) {
                       $result[$i] = $feed->get_value($feedid); // null is a valid response
                   } else { $result[$i] = false; }
                } else { $result[$i] = false; } // false means feed not found
            }
        } else if ($route->action == "csvexport" && $session['write'] && isset($_GET['ids'])) {
            // Export multiple feeds on the same csv
            // http://emoncms.org/feed/csvexport.json?ids=1,3,4,5,6,7,8,157,156,169&start=1450137600&end=1450224000&interval=10&timeformat=1
            $result = $feed->csv_export_multi(get('ids'),get('start'),get('end'),get('interval'),get('timeformat'),get('name')); 
        
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
                    if ($route->action == "timevalue") $result = $feed->get_timevalue($feedid);
                    else if ($route->action == 'data') {
                        $skipmissing = 1;
                        $limitinterval = 1;
                        if (isset($_GET['skipmissing']) && $_GET['skipmissing']==0) $skipmissing = 0;
                        if (isset($_GET['limitinterval']) && $_GET['limitinterval']==0) $limitinterval = 0;
                        
                        if (isset($_GET['interval'])) {
                            $result = $feed->get_data($feedid,get('start'),get('end'),get('interval'),$skipmissing,$limitinterval);
                        } else if (isset($_GET['mode'])) {
                            if (isset($_GET['split'])) {
                                $result = $feed->get_data_DMY_time_of_day($feedid,get('start'),get('end'),get('mode'),get('split'));
                            } else {
                                $result = $feed->get_data_DMY($feedid,get('start'),get('end'),get('mode'));
                            }
                        }
                    }
                    else if ($route->action == 'average') {
                        if (isset($_GET['interval'])) {
                            $result = $feed->get_average($feedid,get('start'),get('end'),get('interval'));
                        } else if (isset($_GET['mode'])) {
                            $result = $feed->get_average_DMY($feedid,get('start'),get('end'),get('mode'));
                        }
                    }
                    else if ($route->action == "value") $result = $feed->get_value($feedid); // null is a valid response
                    else if ($route->action == "get") $result = $feed->get_field($feedid,get('field')); // '/[^\w\s-]/'
                    else if ($route->action == "aget") $result = $feed->get($feedid);
                    else if ($route->action == "getmeta") $result = $feed->get_meta($feedid);

                    else if ($route->action == 'histogram') $result = $feed->histogram_get_power_vs_kwh($feedid,get('start'),get('end'));
                    else if ($route->action == 'kwhatpower') $result = $feed->histogram_get_kwhd_atpower($feedid,get('min'),get('max'));
                    else if ($route->action == 'kwhatpowers') $result = $feed->histogram_get_kwhd_atpowers($feedid,get('points'));
                    else if ($route->action == "csvexport") $result = $feed->csv_export($feedid,get('start'),get('end'),get('interval'),get('timeformat'),get('name'));
                }

                // write session required
                if (isset($session['write']) && $session['write'] && $session['userid']>0 && $f['userid']==$session['userid'])
                {
                    // Storage engine agnostic
                    
                    // Set feed meta fields
                    if ($route->action == 'set') {
                        $result = $feed->set_feed_fields($feedid,get('fields'));
                        
                    // Insert datapoint
                    } else if ($route->action == "insert") { 
                        $result = $feed->insert_data($feedid,time(),get("time"),get("value"));
                        
                    // Update datapoint
                    } else if ($route->action == "update") {
                        if (isset($_GET['updatetime'])) $updatetime = get("updatetime"); else $updatetime = time();
                        $result = $feed->update_data($feedid,$updatetime,get("time"),get('value'));
                    
                    // Delete feed
                    } else if ($route->action == "delete") {
                        $result = $feed->delete($feedid);
                    
                    // Process
                    } else if ($route->action == "process") {
                        if ($f['engine']!=Engine::VIRTUALFEED) { $result = array('success'=>false, 'message'=>'Feed is not Virtual'); }
                        else if ($route->subaction == "get") $result = $feed->get_processlist($feedid);
                        else if ($route->subaction == "set") $result = $feed->set_processlist($feedid, post('processlist'));
                        else if ($route->subaction == "reset") $result = $feed->reset_processlist($feedid);
                        
                    // Fast bulk uploader
                    } else if ($route->action == "upload") {
                        // Start time and interval
                        if (isset($_GET['start']) && isset($_GET['interval']) && isset($_GET['npoints'])) {
                            $result = $feed->upload_fixed_interval($feedid,get("start"),get("interval"),get("npoints"));
                        } else if (isset($_GET['npoints'])) {
                            $result = $feed->upload_variable_interval($feedid,get("npoints"));
                        }
                    }

                    if ($f['engine']==Engine::MYSQL || $f['engine']==Engine::MYSQLMEMORY) {
                        if ($route->action == "export") $result = $feed->mysqltimeseries_export($feedid,get('start'));
                        else if ($route->action == "deletedatapoint") $result = $feed->mysqltimeseries_delete_data_point($feedid,get('feedtime'));
                        else if ($route->action == "deletedatarange") $result = $feed->mysqltimeseries_delete_data_range($feedid,get('start'),get('end'));
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
