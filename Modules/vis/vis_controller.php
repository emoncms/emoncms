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
    global $mysqli, $redis, $session, $route, $user, $feed_settings;

    $result = false;

    require "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $feed_settings);

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

        // Auto - automatically selects visualisation based on datatype
        // and is used primarily for quick checking feeds from the feeds page.
        else if ($route->action == "auto")
        {
            $feedid = intval(get('feedid'));
            $datatype = $feed->get_field($feedid,'datatype');
            if ($datatype == 0) $result = "Feed type or authentication not valid";
            if ($datatype == 1) $route->action = 'graph';
            if ($datatype == 2) $route->action = 'bargraph';
            if ($datatype == 3) $route->action = 'histgraph';
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

                if (isset($vis['options']))
                {
                    foreach ($vis['options'] as $option)
                    {
                        $key = $option[0]; $type = $option[2];
                        if (isset($option[3])) $default = $option[3]; else $default = "";

                        if ($type==0 || $type==1 || $type==2 || $type==3) {
                            $feedid = (int) get($key);
                            if ($feedid) {
                              $f = $feed->get($feedid);
                              $array[$key] = $feedid;
                              $array[$key.'name'] = $f['name'];

                              if ($f['userid']!=$session['userid']) $array['valid'] = false;
                              if ($f['public']) $array['valid'] = true;
                            } else {
                              $array['valid'] = false;
                            }
                        }
                        else if ($type==4) // Boolean
                            if (get($key) == "true" || get($key) == 1)
                                $array[$key] = 1;
                            else if (get($key) || get($key) == "false" || get($key) == 0)
                                $array[$key] = 0;
                            else $array[$key] = $default;
                        else if ($type==5)
                            $array[$key] = preg_replace('/[^\p{L}_\p{N}\s£$€¥₽]/u','',get($key))?get($key):$default;
                        else if ($type==6)
                            $array[$key] = str_replace(',', '.', floatval((get($key)?get($key):$default)));
                        else if ($type==7)
                            $array[$key] = intval((get($key)?get($key):$default));
                        else if ($type==8) {
                            $mid = (int) get($key);
                            if ($mid) {
                              $f = $multigraph->get($mid,$session['userid']);
                              $array[$key] = intval(($mid?$mid:$default));
                              if (!isset($f['feedlist'])) $array['valid'] = false;
                            } else {
                              $array['valid'] = false;
                            }
                        }

                        # we need to either urlescape the colour, or just scrub out invalid chars. I'm doing the second, since
                        # we can be fairly confident that colours are eiter a hex or a simple word (e.g. "blue" or such)
                        else if ($type==9) // Color
                            $array[$key] = preg_replace('/[^\dA-Za-z]/','',get($key))?get($key):$default;
                    }
                }

                $array['apikey'] = $read_apikey;
                $array['write_apikey'] = $write_apikey;

                if ($array['valid'] == false) {
                    $result .= "<div style='position:absolute; top:0px; left:0px; width:100%; height:100%; display: table;'><div class='alert-error' style='text-align:center; display:table-cell; vertical-align:middle;'><h4>"._('Not configured')."<br>"._('or')."<br>"._('Authentication not valid')."</h4></div></div>";
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

    else if ($route->format == 'json' && $route->action == 'multigraph')
    {
        if ($route->subaction == 'get') $result = $multigraph->get(get('id'),$session['userid']);
        else if ($route->subaction == 'getlist') $result = $multigraph->getlist($session['userid']);

        else if ($session['write']) {
            if ($route->subaction == 'new') $result = $multigraph->create($session['userid']);
            else if ($route->subaction == 'delete') $result = $multigraph->delete(get('id'),$session['userid']);
            else if ($route->subaction == 'set') $result = $multigraph->set(get('id'),$session['userid'],get('feedlist'),get('name'));
        }

    }

    return array('content'=>$result);
  }
