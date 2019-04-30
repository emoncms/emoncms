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
    global $mysqli, $redis, $user, $session, $route, $feed_settings;
    $result = false;

    if (!user_has_capability('feed_enabled')) {
        http_response_code(403);
        $route->format = "json";
        return array('content' => 'Permission denied');
    }
    
    $access_write = user_has_capability('feed_write') && $session['write'];
    $access_read = user_has_capability('feed_read') && $session['read'];

    require_once "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis,$feed_settings);

    require_once "Modules/input/input_model.php";
    $input = new Input($mysqli,$redis,$feed);
    
    require_once "Modules/process/process_model.php";
    $process = new Process($mysqli,$input,$feed,$user->get_timezone($session['userid']));

    if ($route->format == 'html')
    {
        if ($route->action=="") $route->action = "list";

        textdomain("messages");
        if ($route->action == "list" && $session['write']) {
            $ui_version_2 = $user->get_preferences($session['userid'], 'deviceView');
            if (!user_has_capability('feed_read')) return "<div class='alert alert-error'>Permission denied</div>";

            if (isset($ui_version_2) && $ui_version_2) {
                return view("Modules/feed/Views/feedlist_view_v2.php",array());
            } else {
                return view("Modules/feed/Views/feedlist_view.php",array());
            }
        }
        else if ($route->action == "api" && $session['write']) return view("Modules/feed/Views/feedapi_view.php",array());
    }

    else if ($route->format == 'json')
    {
        // Public actions available on public feeds.
        if ($route->action == "list")
        {
            if ($access_read) {
                if (!isset($_GET['userid']) || (isset($_GET['userid']) && $_GET['userid'] == $session['userid'])) return $feed->get_user_feeds($session['userid']);
                else if (isset($_GET['userid']) && $_GET['userid'] != $session['userid']) return $feed->get_user_public_feeds(get('userid'));
            }
            else if (isset($_GET['userid'])) {
                return $feed->get_user_public_feeds(get('userid'));
            }
            else {
                return array();
            }
        } elseif ($route->action == "listwithmeta" && $access_read) {
            return $feed->get_user_feeds_with_meta($session['userid']);
        } elseif ($route->action == "getid" && $access_read) { 
            $route->format = "text";
            return $feed->get_id($session['userid'],get("name"));
        } elseif ($route->action == "create" && $access_write) {
            return $feed->create($session['userid'],get('tag'),get('name'),get('datatype'),get('engine'),json_decode(get('options')),get('unit'));
        } elseif ($route->action == "updatesize" && $access_write) {
            return $feed->update_user_feeds_size($session['userid']);
        } elseif ($route->action == "buffersize" && $access_write) {
            return $feed->get_buffer_size();
        // To "fetch" multiple feed values in a single request
        // http://emoncms.org/feed/fetch.json?ids=123,567,890
        } elseif ($route->action == "fetch") {
            $feedids = (array) (explode(",",(get('ids'))));
            for ($i=0; $i<count($feedids); $i++) {
                $feedid = (int) $feedids[$i];
                if ($feed->exist($feedid)) {  // if the feed exists
                   $f = $feed->get($feedid);
                   if ($f['public'] || ($session['userid']>0 && $f['userid']==$session['userid'] && $access_read)) {
                       $result[$i] = $feed->get_value($feedid); // null is a valid response
                   } else { $result[$i] = false; }
                } else { $result[$i] = false; } // false means feed not found
            }
            return $result;
        } else if ($route->action == "csvexport" && $access_read && isset($_GET['ids'])) {
            // Export multiple feeds on the same csv
            // http://emoncms.org/feed/csvexport.json?ids=1,3,4,5,6,7,8,157,156,169&start=1450137600&end=1450224000&interval=10&timeformat=1
            return $feed->csv_export_multi(get('ids'),get('start'),get('end'),get('interval'),get('timeformat'),get('name'));
        
        // ----------------------------------------------------------------------------
        // Multi feed actions
        // ----------------------------------------------------------------------------
        } else if (in_array($route->action,array("data","average"))) {
            if (!user_has_capability('feed_read')) return array('success'=>false, 'message'=>'permission denied');
            // get data for a list of existing feeds
            $result = array('success'=>false, 'message'=>'bad parameters');
            // return $_REQUEST;
            $singular = false;
            $feedids = array();
            if (isset($_GET['id'])) {
                $feedids = explode(",", get('id'));
                $singular = true;
            }
            else if (isset($_GET['ids'])) $feedids = explode(",", get('ids'));
            
            if (!empty($feedids)) {
                $missing = array();
                foreach($feedids as $key => $feedid) {
                    if ($feed->exist($feedid)) { // if the feed exists
                        $f = $feed->get($feedid);
                        // if public or belongs to user
                        if ($f['public'] || ($session['userid']>0 && $f['userid']==$session['userid'] && $session['read']))
                        {
                        
                            $results[$key] = array('feedid'=>$feedid);
                            
                            // feed/data ----------------------------------------------
                            if ($route->action=="data") {
                                $skipmissing = 1;
                                $limitinterval = 1;
                                if (isset($_GET['skipmissing']) && $_GET['skipmissing']==0) $skipmissing = 0;
                                if (isset($_GET['limitinterval']) && $_GET['limitinterval']==0) $limitinterval = 0;
                                
                                
                                if (isset($_GET['interval'])) {
                                    $results[$key]['data'] = $feed->get_data($feedid,get('start'),get('end'),get('interval'),$skipmissing,$limitinterval);
                                } else if (isset($_GET['mode'])) {
                                    if (isset($_GET['split'])) {
                                        $results[$key]['data'] = $feed->get_data_DMY_time_of_day($feedid,get('start'),get('end'),get('mode'),get('split'));
                                    } else {
                                        $results[$key]['data'] = $feed->get_data_DMY($feedid,get('start'),get('end'),get('mode'));
                                    }
                                }
                            // feed/average --------------------------------------------   
                            } else if ($route->action == 'average') {
                                if (isset($_GET['mode'])) {
                                    $results[$key]['data'] = $feed->get_average_DMY($feedid,get('start'),get('end'),get('mode'));
                                } else if (isset($_GET['interval'])) {
                                    $results[$key]['data'] = $feed->get_average($feedid,get('start'),get('end'),get('interval'));
                                } 
                            }
                        }
                    } else {
                        $missing = $feedid;
                    }
                }
                if (!empty($missing)) {
                    // return error if any feed ids not found
                    return array('success'=>false, 'message'=> count($missing) .' feeds do not exist');
                } else {
                    
                    if ($singular && count($results)==1) {
                        return $results[0]['data'];
                    } else {
                        return $results;
                    } 
                    // @todo: return array for each feed's data 
                    // and a single array for each interval timestamp
                }
            } else {
                // no ids passed
                return array('success'=>false, 'message'=>'no ids given');
            }
            return $result;
            // ----------------------------------------------------------------------------
        } else {
            $feedid = (int) get('id');
            // Actions that operate on a single existing feed that all use the feedid to select:
            // First we load the meta data for the feed that we want
            if ($feed->exist($feedid)) // if the feed exists
            {
                $f = $feed->get($feedid);
                // if public or belongs to user
                if ($f['public'] || ($session['userid']>0 && $f['userid']==$session['userid'] && $access_read))
                {
                    if ($route->action == "timevalue") return $feed->get_timevalue($feedid);
                    else if ($route->action == "value") return $feed->get_value($feedid); // null is a valid response
                    else if ($route->action == "get") return $feed->get_field($feedid,get('field')); // '/[^\w\s-]/'
                    else if ($route->action == "aget") return $feed->get($feedid);
                    else if ($route->action == "getmeta") return $feed->get_meta($feedid);
                    else if ($route->action == "setstartdate") return $feed->set_start_date($feedid,get('startdate'));

                    else if ($route->action == 'histogram') return $feed->histogram_get_power_vs_kwh($feedid,get('start'),get('end'));
                    else if ($route->action == 'kwhatpower') return $feed->histogram_get_kwhd_atpower($feedid,get('min'),get('max'));
                    else if ($route->action == 'kwhatpowers') return $feed->histogram_get_kwhd_atpowers($feedid,get('points'));
                    else if ($route->action == "csvexport") return $feed->csv_export($feedid,get('start'),get('end'),get('interval'),get('timeformat'));
                    else if ($route->action == "export") {
                        if ($f['engine']==Engine::MYSQL || $f['engine']==Engine::MYSQLMEMORY) return $feed->mysqltimeseries_export($feedid,get('start'));
                        elseif ($f['engine']==Engine::PHPTIMESERIES) return $feed->phptimeseries_export($feedid,get('start'));
                        elseif ($f['engine']==Engine::PHPFIWA) return $feed->phpfiwa_export($feedid,get('start'),get('layer'));
                        elseif ($f['engine']==Engine::PHPFINA) return $feed->phpfina_export($feedid,get('start'));
                    }
                }

                // write session required
                if (isset($session['write']) && $access_write && $session['userid']>0 && $f['userid']==$session['userid'])
                {
                    // Storage engine agnostic

                    // Set feed meta fields
                    if ($route->action == 'set') {
                        // if tag or name changed check new combination is unique
                        $fields = json_decode(get('fields'), true);
                        if (!empty($fields['tag']) || !empty($fields['name'])) {
                            $original_name = $feed->get_field($feedid, 'name');
                            $original_tag = $feed->get_field($feedid, 'tag');
                            // use original tag/name if no new value given
                            $new_name = !empty($fields['name']) ? $fields['name'] : $original_name;
                            $new_tag = !empty($fields['tag']) ? $fields['tag'] : $original_tag;
                            // exists_tag_name returns false if not found
                            $unique = $feed->exists_tag_name($session['userid'], $new_tag, $new_name) === false;
                            // update if tag:name unique else return error;
                            return $unique ? $feed->set_feed_fields($feedid, get('fields')) : array('success'=>false, 'message'=>'fields tag:name must be unique');
                        }else{
                            // update if no tag/name change
                            return $feed->set_feed_fields($feedid, get('fields'));
                        }

                    // Insert datapoint
                    } else if ($route->action == "insert") { 
                        return $feed->insert_data($feedid,time(),get("time"),get("value"));

                    // Update datapoint
                    } else if ($route->action == "update") {
                        if (isset($_GET['updatetime'])) $updatetime = get("updatetime"); else $updatetime = time();
                        return $feed->update_data($feedid,$updatetime,get("time"),get('value'));

                    // Delete feed
                    } else if ($route->action == "delete") {
                        return $feed->delete($feedid);
                        
                    // scale range for PHPFINA
                    // added by Alexandre CUER - january 2019 
                    } else if ($route->action == "scalerange") {
                        if ($f['engine'] == Engine::PHPFINA) 
                            $result = $feed->EngineClass(Engine::PHPFINA)->scalerange($feedid,get("start"),get("end"),get("value"));
                        
                    // Clear feed
                    } else if ($route->action == "clear") {
                        return $feed->clear($feedid);
                    
                    // Trim feed
                    } else if ($route->action == "trim") {
                        if (!filter_var(get('start_time'), FILTER_VALIDATE_INT)) return false;
                        $start_time = filter_var(get('start_time'), FILTER_SANITIZE_NUMBER_INT);
                        return $feed->trim($feedid, $start_time);
                        
                    // Process
                    } else if ($route->action == "process") {
                        if ($f['engine']!=Engine::VIRTUALFEED) { return array('success'=>false, 'message'=>'Feed is not Virtual'); }
                        else if ($route->subaction == "get") return $feed->get_processlist($feedid);
                        else if ($route->subaction == "set") return $feed->set_processlist($session['userid'], $feedid, post('processlist'),$process->get_process_list());
                        else if ($route->subaction == "reset") return $feed->reset_processlist($feedid);

                    // Fast bulk uploader
                    } else if ($route->action == "upload") {
                        // Start time and interval
                        if (isset($_GET['start']) && isset($_GET['interval']) && isset($_GET['npoints'])) {
                            return $feed->upload_fixed_interval($feedid,get("start"),get("interval"),get("npoints"));
                        } else if (isset($_GET['npoints'])) {
                            return $feed->upload_variable_interval($feedid,get("npoints"));
                        }
                    }

                    if ($f['engine']==Engine::MYSQL || $f['engine']==Engine::MYSQLMEMORY) {
                        if ($route->action == "deletedatapoint") return $feed->mysqltimeseries_delete_data_point($feedid,get('feedtime'));
                        else if ($route->action == "deletedatarange") return $feed->mysqltimeseries_delete_data_range($feedid,get('start'),get('end'));
                    }
                }
            }
            else
            {
                return array('success'=>false, 'message'=>'Feed does not exist');
            }
        }
    }

    return array('content'=>'#UNDEFINED#');
}
