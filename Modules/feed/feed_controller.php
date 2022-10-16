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
    global $mysqli, $redis, $user, $session, $route, $settings;
    $result = false;

    require_once "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis,$settings["feed"]);

    require_once "Modules/input/input_model.php";
    $input = new Input($mysqli,$redis,$feed);
    
    require_once "Modules/process/process_model.php";
    if (!$user_timezone = $user->get_timezone($session['userid'])) {
        $user_timezone = 'UTC';
    }
    $process = new Process($mysqli,$input,$feed,$user_timezone);

    if ($route->format == 'html')
    {
        if ($route->action=="") $route->action = "view";

        textdomain("messages");
        if (($route->action == "view" || $route->action == "list")) {
            if (!$session['read'] && !$session['public_userid']) return "";
            return view("Modules/feed/Views/feedlist_view.php");
        }
        else if ($route->action == "api") {
            if (!$session['read'] && !$session['public_userid']) return "";       
            require "Modules/feed/feed_api_obj.php";
            return view("Lib/api_tool_view.php",array("title"=>_("Feed API"), "api"=>feed_api_obj(), "selected_api"=>8));
        }
        else if (!$session['read']) return ''; // empty strings force user back to login
        else return EMPTY_ROUTE; // this string displays error
    }

    else if ($route->format == 'json')
    {
        // Public actions available on public feeds.
        if ($route->action == "list")
        {
            if ($session['public_userid']) {
                return $feed->get_user_public_feeds($session['public_userid']);
            } else if (isset($_GET['userid'])) {
                return $feed->get_user_public_feeds((int)$_GET['userid']);
            } else if ($session['read']) {
                return $feed->get_user_feeds($session['userid']);
            } else {
                return false;
            }

        } elseif ($route->action == "listwithmeta" && $session['read']) {
            return $feed->get_user_feeds_with_meta($session['userid']);
        } elseif ($route->action == "getid" && $session['read']) { 
            $route->format = "text";
            if (isset($_GET["tag"]) && isset($_GET["name"])) {
                return $feed->exists_tag_name($session['userid'],get("tag"),get("name"));
            } else if (isset($_GET["name"])) {
                return $feed->get_id($session['userid'],get("name"));
            } else {
                return false;
            }
        } elseif ($route->action == "create" && $session['write']) {
            return $feed->create($session['userid'],get('tag'),get('name'),get('engine'),json_decode(get('options')),get('unit'));
        } elseif ($route->action == "updatesize" && $session['write']) {
            return $feed->update_user_feeds_size($session['userid']);
        } elseif ($route->action == "buffersize" && $session['write']) {
            return $feed->get_buffer_size();
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
            return $result;
        // ----------------------------------------------------------------------------
        // Multi feed actions
        // ----------------------------------------------------------------------------
        } else if (in_array($route->action,array("data","average","csvexport"))) {
            // get data for a list of existing feeds
            $result = array('success'=>false, 'message'=>'bad parameters');
            // return $_REQUEST;
            $singular = false;
            $feedids = array();
            $results = array();
            if (isset($_GET['id'])) {
                $feedids = explode(",", get('id'));
                $singular = true;
            }
            else if (isset($_GET['ids'])) $feedids = explode(",", get('ids'));

            $start = get('start',true);
            $end = get('end',true);
            $interval = get('interval',false,0);
            $timezone = get('timezone',false,$user_timezone);
            $timeformat = get('timeformat',false,'unixms');
            $csv = get('csv',false,0);
            $skipmissing = get('skipmissing',false,0);
            $limitinterval = get('limitinterval',false,0);
            $dp = get('dp',false,-1);
            
            $averages = array();
            if (isset($_GET['average'])) {
                $averages = explode(",",get('average'));
            }
            
            $deltas = array();
            if (isset($_GET['delta'])) {
                $deltas = explode(",",get('delta'));
            }  
            
            // Backwards compatibility
            if ($route->action=="average") $average = 1; else $average = 0;
            if ($route->action=="csvexport") $csv = 1;
            if (isset($_GET['mode'])) $interval = $_GET['mode'];
            
            $multi_csv = false;
            if ($csv && count($feedids)>1) {
                $csv = false;
                $multi_csv = true;
            }
            
            if (!empty($feedids)) {
                $missing = array();
                foreach($feedids as $index => $feedid) {
                    if ($feed->exist($feedid)) { // if the feed exists
                        $f = $feed->get($feedid);
                        // if public or belongs to user
                        if ($f['public'] || ($session['userid']>0 && $f['userid']==$session['userid'] && $session['read']))
                        {
                            $results[$index] = array('feedid'=>$feedid);
                            if (!isset($_GET['split'])) {
                            
                                if (isset($averages[$index]) && $averages[$index]) $average = $averages[$index];
                                if (isset($deltas[$index]) && $deltas[$index]) $delta = $deltas[$index]; else $delta = 0;
                                
                                $results[$index]['data'] = $feed->get_data($feedid,$start,$end,$interval,$average,$timezone,$timeformat,$csv,$skipmissing,$limitinterval,$delta,$dp);
                            } else {
                                $results[$index]['data'] = $feed->get_data_DMY_time_of_day($feedid,$start,$end,$interval,$timezone,$timeformat,get('split'));
                            }
                        }
                    } else {
                        $missing[] = intval($feedid); //add feed id to array of missing ids
                    }
                }
                if (!empty($missing)) {
                    // return error if any feed ids not found
                    if (count($missing) === 1) // if just one feed not found, return its id
                        return array('success'=>false, 'message'=> "feed $missing[0] does not exist", 'feeds' => $missing);
                    else
                        return array('success'=>false, 'message'=> count($missing) .' feeds do not exist', 'feeds' => $missing);
                } else {
                    
                    if ($singular && count($results)==1) {
                        return $results[0]['data'];
                    } else {
                        if ($multi_csv) {
                            return $feed->csv_export_multi($feedids,$results,$timezone,$timeformat);
                        } else {
                            return $results;
                        }
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
                if ($f['public'] || ($session['userid']>0 && $f['userid']==$session['userid'] && $session['read']))
                {
                    if ($route->action == "timevalue") return $feed->get_timevalue($feedid);
                    else if ($route->action == "value") return $feed->get_value($feedid,get('time')); // null is a valid response
                    else if ($route->action == "get") return $feed->get_field($feedid,get('field')); // '/[^\w\s-]/'
                    else if ($route->action == "aget") return $feed->get($feedid);
                    else if ($route->action == "getmeta") return $feed->get_meta($feedid);
                    else if ($route->action == "setstartdate") return $feed->set_start_date($feedid,get('startdate'));
                    else if ($route->action == "export") {
                        if ($f['engine']==Engine::MYSQL || $f['engine']==Engine::MYSQLMEMORY) return $feed->mysqltimeseries_export($feedid,get('start'));
                        elseif ($f['engine']==Engine::PHPTIMESERIES) return $feed->phptimeseries_export($feedid,get('start'));
                        elseif ($f['engine']==Engine::PHPFINA) return $feed->phpfina_export($feedid,get('start'));
                    }
                }

                // write session required
                if (isset($session['write']) && $session['write'] && $session['userid']>0 && $f['userid']==$session['userid'])
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

                    // insert available here for backwards compatibility
                    } else if ($route->action == "insert" || $route->action == "update" || $route->action == "post") {
                        
                        // Single data point
                        if (isset($_GET['time']) || isset($_GET['value'])) {
                             return $feed->post($feedid,time(),get("time"),get("value"));
                        }

                        // Single or multiple datapoints via json format
                        // Format: [[UNIXTIME,VALUE],[UNIXTIME,VALUE],[UNIXTIME,VALUE]]
                        $data = false;
                        if (isset($_GET['data'])) {
                            $data = json_decode($_GET['data']);
                        } else if (isset($_POST['data'])) {
                            $data = json_decode($_POST['data']);
                        } else {
                            return array('success'=>false, 'message'=>'missing data parameter');
                        }
                        if ($data==null) return array('success'=>false, 'message'=>'error decoding json');
                        
                        if (!$data || count($data)==0) return array('success'=>false, 'message'=>'empty data object');
                        
                        return $feed->post_multiple($feedid,$data);

                    // Delete feed
                    } else if ($route->action == "delete") {
                        return $feed->delete($feedid);
                        
                    // scale range for PHPFINA
                    // added by Alexandre CUER - january 2019 
                    } else if ($route->action == "scalerange") {

                        if ($f['engine'] == Engine::PHPFINA) {
                            return $feed->EngineClass(Engine::PHPFINA)->scalerange($feedid,get("start"),get("end"),get("value"));
                        } else {
                            return "scalerange only supported by phpfina engine";
                        }
                        
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
                    } else if ($route->action == "deletedatapoint") {
                        if ($f['engine']==Engine::MYSQL || $f['engine']==Engine::MYSQLMEMORY) {
                            return $feed->mysqltimeseries_delete_data_point($feedid,get('feedtime'));
                        } else {
                            return "deletedatapoint only supported by mysqltimeseries engine";
                        }
                    } else if ($route->action == "deletedatarange") {
                        if ($f['engine']==Engine::MYSQL || $f['engine']==Engine::MYSQLMEMORY) {
                            return $feed->mysqltimeseries_delete_data_range($feedid,get('start'),get('end'));
                        } else {
                            return "deletedatarange only supported by mysqltimeseries engine";
                        }
                    }
                }
            }
            else
            {
                return array('success'=>false, 'message'=>'Feed does not exist');
            }
        }
    }

    return array('content'=>EMPTY_ROUTE);
}
