<?php
  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

   Emoncms - open source energy visualisation
   Part of the OpenEnergyMonitor project: http://openenergymonitor.org
  */

  // no direct access
  defined('EMONCMS_EXEC') or die(_('Restricted access'));

  function vis_controller()
  {
    global $mysqli, $redis, $session, $route, $user, $settings, $vis_version;

    $vis_version = 10;

    $result = false;

    require "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $settings['feed']);

    require "Modules/vis/multigraph_model.php";
    $multigraph = new Multigraph($mysqli);

    $visdir = "vis/visualisations/";

    require "Modules/vis/vis_object.php";

    $write_apikey = ""; $read_apikey = "";
    if ($session['read']) $read_apikey = $user->get_apikey_read($session['userid']);
    if ($session['write']) $write_apikey = $user->get_apikey_write($session['userid']);

    if ($route->format =='html')
    {
        if ($route->action == 'list' && $session['write'])
        {
            $multigraphs = $multigraph->getlist($session['userid']);
            $feedlist = $feed->get_user_feeds($session['userid']);
            $result = view("Modules/vis/Views/vis_main_view.php", array('user' => $user->get($session['userid']), 'feedlist'=>$feedlist, 'apikey'=>$read_apikey, 'visualisations'=>$visualisations, 'multigraphs'=>$multigraphs));
        }

        elseif ($route->action == "auto" || $route->action == "graph")
        {
            $feedid = intval(get('feedid'));
            $route->action = 'rawdata';
        }

        while ($vis = current($visualisations))
        {
            $viskey = key($visualisations);

            // If the visualisation has a set property called action
            // then override the visualisation key and use the set action instead
            if (isset($vis['action'])) $viskey = $vis['action'];

            if ($route->action == $viskey)
            {
                $array = array();
                $array['valid'] = true;
                $array['message'] = '';

                if (isset($vis['options']))
                {
                    foreach ($vis['options'] as $option) {
                        $key = $option[0];
                        $type = $option[2];
                        if (isset($option[3])) $default = $option[3]; else $default = "";

                        if ($type == 0 || $type == 1 || $type == 2 || $type == 3) {
                            $feedid = get($key);

                            // Option to use tag:name feed reference format
                            // only works with feeds belonging to the active session
                            if (!is_numeric($feedid)) {
                                $tagname = explode(":", $feedid);
                                if (count($tagname) == 2) {
                                    $feedid = $feed->exists_tag_name($session['userid'], $tagname[0], $tagname[1]);
                                } else {
                                    $feedid = false;
                                }
                            } else {
                                $feedid = (int)$feedid;
                            }

                            if ($feedid) {
                                $f = $feed->get($feedid);
                                if (isset($f['name'])) {
                                    $array[$key] = $feedid;
                                    $array[$key . 'name'] = $f['name'];

                                    if ($f['userid'] != $session['userid']) {
                                        $array['valid'] = false;
                                        $array['message'] = "authentication not valid";
                                    }
                                    if ($f['public']) {
                                        $array['valid'] = true;
                                        $array['message'] = '';
                                    }
                                } else {
                                    $array['valid'] = false;
                                    $array['message'] = 'feed name not set';
                                }
                            } else {
                                $array['valid'] = false;
                                $array['message'] = 'invalid feedid';
                            }
                        } elseif ($type == 4) {// Boolean
                            if (get($key) == "true" || get($key) == 1) {
                                $array[$key] = 1;
                            } elseif (get($key) || get($key) == "false" || get($key) == 0) {
                                $array[$key] = 0;
                            } else {
                                $array[$key] = $default;
                            }
                        }
                        elseif ($type==5 && !is_null(get($key)))
                            $array[$key] = preg_replace('/[^\p{L}_\p{N}\s£$€¥₽]/u','',get($key))?get($key):$default;
                        elseif ($type==6)
                            $array[$key] = str_replace(',', '.', floatval((get($key) ?: $default)));
                        elseif ($type==7)
                            $array[$key] = intval((get($key) ?: $default));
                        elseif ($type==8) {
                            $mid = (int) get($key);
                            if ($mid) {
                              $f = $multigraph->get($mid,$session['userid']);
                              $array[$key] = intval(($mid ?: $default));
                              if (!isset($f['feedlist'])) {
                                  $array['valid'] = false;
                                  $array['message'] = 'invalid feedlist';
                              }
                            } else {
                              $array['valid'] = false;
                              $array['message'] = 'invalid multigraph id';
                            }
                        }

                        # we need to either urlescape the colour, or just scrub out invalid chars. I'm doing the second, since
                        # we can be fairly confident that colours are either a hex or a simple word (e.g. "blue" or such)
                        elseif ($type==9 && !is_null(get($key))) {// Color
                            $array[$key] = preg_replace('/[^\dA-Za-z]/', '', get($key)) ? get($key) : $default;
                        }
                    }
                }

                $array['apikey'] = $read_apikey;
                $array['write_apikey'] = $write_apikey;

                if ($array['valid'] == false) {
                    $result .= "<div style='position:absolute; top:0px; left:0px; width:100%; height:100%; display: table;'><div class='alert-error' style='text-align:center; display:table-cell; vertical-align:middle;'><h4>".$array['message']."</h4></div></div>";
                } else {
                    $result .= view("Modules/".$visdir.$viskey.".php", $array);
                }
            }
            next($visualisations);
        }
    }

    /*
    MULTIGRAPH ACTIONS
    */

    elseif ($route->format == 'json' && $route->action == 'multigraph')
    {
        if ($route->subaction == 'get') {
            $result = $multigraph->get(get('id'),$session['userid']);
        } elseif ($route->subaction == 'getlist') {
            $result = $multigraph->getlist($session['userid']);
        } elseif ($session['write']) {
            if ($route->subaction == 'new') {
                $result = $multigraph->create($session['userid']);
            } elseif ($route->subaction == 'delete') {
                $result = $multigraph->delete(get('id'),$session['userid']);
            } elseif ($route->subaction == 'set') {
                $result = $multigraph->set(get('id'),$session['userid'],get('feedlist'),get('name'));
            }
        }

    }

    return array('content'=>$result);
  }
