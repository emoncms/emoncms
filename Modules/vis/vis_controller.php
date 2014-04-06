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
  defined('EMONCMS_EXEC') or die(_('Restricted access'));

  function vis_controller()
  {
    global $mysqli, $redis, $session, $route, $user, $timestore_adminkey;

    $result = false;

    require "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $timestore_adminkey);

    require "Modules/vis/multigraph_model.php";
    $multigraph = new Multigraph($mysqli);

    $visdir = "vis/visualisations/";

    /*
      1 - realtime
      2 - daily
      3 - histogram
      4 - boolean (not used uncomment line 122)
      5 - text
      6 - float value
      7 - int value
    */

    $visualisations = array(
        'realtime' => array('options'=>array(array('feedid',1))),
        // Hex colour EDC240 is the default color for flot. since we want existing setups to not change, we set the default value to it manually now,
        'rawdata'=> array('options'=>array(array('feedid',1),array('fill',7,0),array('units',5,'W'),array('colour',5,'EDC240'))),
        'bargraph'=> array('options'=>array(array('feedid',2),array('colour',5,'EDC240'))),
        'timestoredaily'=> array('options'=>array(array('feedid',1),array('units',5,'kWh'))),
        'smoothie'=> array('options'=>array(array('feedid',1),array('ufac',6))),
        'histgraph'=> array('options'=>array(array('feedid',3),array('barwidth',7,50),array('start',7,0),array('end',7,0))),
        //'dailyhistogram'=> array('options'=>array(array('feedid',3))),
        'zoom'=> array('options'=>array(array('power',1),array('kwhd',2),array('currency',5,'&pound;'),array('currency_after_val', 7, 0), array('pricekwh',6,0.14))),
        //'comparison'=> array('options'=>array(array('feedid',3))),
        'stacked'=> array('options'=>array(array('bottom',2),array('top',2))),
        'stackedsolar'=> array('options'=>array(array('solar',2),array('consumption',2))),
        'threshold'=> array('options'=>array(array('feedid',3),array('thresholdA',6,500),array('thresholdB',6,2500))),
        'simplezoom'=> array('options'=>array(array('power',1),array('kwhd',2))),
        'orderbars'=> array('options'=>array(array('feedid',2))),
        'orderthreshold'=> array('options'=>array(array('feedid',3),array('power',1),array('thresholdA',6,500),array('thresholdB',6,2500))),
        'editrealtime'=> array('options'=>array(array('feedid',1))),
        'editdaily'=> array('options'=>array(array('feedid',2))),
        'multigraph' => array ('action'=>'multigraph', 'options'=>array(array('mid',7)) ),
        'compare' => array ('action'=>'compare', 'options'=>array(array('powerx',1),array('powery',1)))
    );

    $write_apikey = ""; $read_apikey = "";
    if ($session['read']) $read_apikey = $user->get_apikey_read($session['userid']);
    if ($session['write']) $write_apikey = $user->get_apikey_write($session['userid']);

    if ($route->format =='html')
    {
        if ($route->action == 'list' && $session['write'])
        {
            $multigraphs = $multigraph->getlist($session['userid']);
            $feedlist = $feed->get_user_feeds($session['userid']);
            $result = view("Modules/vis/vis_main_view.php", array('user' => $user->get($session['userid']), 'feedlist'=>$feedlist, 'apikey'=>$read_apikey, 'visualisations'=>$visualisations, 'multigraphs'=>$multigraphs));
        }

        // Auto - automatically selects visualisation based on datatype
        // and is used primarily for quick checking feeds from the feeds page.
        if ($route->action == "auto")
        {
            $feedid = intval(get('feedid'));
            $datatype = $feed->get_field($feedid,'datatype');
            if ($datatype == 0) $result = "Feed type or authentication not valid";
            if ($datatype == 1) $route->action = 'rawdata';
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
                        $key = $option[0]; $type = $option[1];
                        if (isset($option[2])) $default = $option[2]; else $default = "";

                        if ($type==1 || $type==2 || $type==3)
                        {
                            $feedid = (int) get($key);
                            if ($feedid) {
                              $f = $feed->get($feedid);
                              $array[$key] = $feedid;
                              $array[$key.'name'] = $f['name'];

                              if ($f['userid']!=$session['userid'] || $f['datatype']!=$type) $array['valid'] = false;
                              if ($f['public'] && $f['datatype']==$type) $array['valid'] = true;
                            } else {
                              $array['valid'] = false;
                            }

                        }

                        // Boolean not used at the moment
                            if ($type==4)
                                if (get($key)==true || get($key)==false)
                                    $array[$key] = get($key); else $array[$key] = $default;
                            if ($type==5)
                                $array[$key] = preg_replace('/[^\w\s£$€¥]/','',get($key))?get($key):$default;
                            if ($type==6)
                                $array[$key] = str_replace(',', '.', floatval((get($key)?get($key):$default)));
                            if ($type==7)
                                $array[$key] = intval((get($key)?get($key):$default));

                            # we need to either urlescape the colour, or just scrub out invalid chars. I'm doing the second, since
                            # we can be fairly confident that colours are eiter a hex or a simple word (e.g. "blue" or such)
                            if ($key == "colour")
                                $array[$key] = preg_replace('/[^\dA-Za-z]/','',$array[$key]);
                    }
                }

                $array['apikey'] = $read_apikey;
                $array['write_apikey'] = $write_apikey;

                $result = view("Modules/".$visdir.$viskey.".php", $array);

                if ($array['valid'] == false) $result .= "<div style='position:absolute; top:0px; left:0px; background-color:rgba(240,240,240,0.5); width:100%; height:100%; text-align:center; padding-top:100px;'><h3>Feed type or authentication not valid</h3></div>";

            }
            next($visualisations);
        }
    }

    /*

    MULTIGRAPH ACTIONS

    */

    if ($route->format == 'json' && $route->action == 'multigraph')
    {
        if ($route->subaction == 'new' && $session['write']) $result = $multigraph->create($session['userid']);
        if ($route->subaction == 'delete' && $session['write']) $result = $multigraph->delete(get('id'),$session['userid']);
        if ($route->subaction == 'set' && $session['write']) $result = $multigraph->set(get('id'),$session['userid'],get('feedlist'));
        if ($route->subaction == 'get') $result = $multigraph->get(get('id'),$session['userid']);
        if ($route->subaction == 'getlist') $result = $multigraph->getlist($session['userid']);
    }

    return array('content'=>$result);
  }