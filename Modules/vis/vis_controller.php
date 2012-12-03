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
    require "Modules/feed/feed_model.php";
    require "Modules/vis/multigraph_model.php";
    global $session, $route;

    $format = $route['format'];
    $action = $route['action'];
    $subaction = $route['subaction'];

    $output['content'] = "";
    $output['message'] = "";

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
      'rawdata'=> array('options'=>array(array('feedid',1),array('fill',7,0),array('units',5,'W'))),
      'bargraph'=> array('options'=>array(array('feedid',2))),
      'smoothie'=> array('options'=>array(array('feedid',1),array('ufac',6))),
      'histgraph'=> array('options'=>array(array('feedid',3))),
      //'dailyhistogram'=> array('options'=>array(array('feedid',3))),
      'zoom'=> array('options'=>array(array('power',1),array('kwhd',2),array('currency',5,'&pound;'),array('pricekwh',6,0.14))),
      //'comparison'=> array('options'=>array(array('feedid',3))),
      'stacked'=> array('options'=>array(array('kwhdA',2),array('kwhdB',2))),
      'threshold'=> array('options'=>array(array('feedid',3),array('thresholdA',6,500),array('thresholdB',6,2500))),
      'simplezoom'=> array('options'=>array(array('power',1),array('kwhd',2))),
      'orderbars'=> array('options'=>array(array('feedid',2))),
      'orderthreshold'=> array('options'=>array(array('feedid',3),array('power',1),array('thresholdA',6,500),array('thresholdB',6,2500))),
      'editrealtime'=> array('options'=>array(array('feedid',1))),
      'editdaily'=> array('options'=>array(array('feedid',2))),
      'multigraph' => array ('action'=>'multigraph', 'options'=>array(array('mid',7)) )
    );

    $write_apikey = ""; $read_apikey = "";
    if ($session['read']) $read_apikey = get_apikey_read($session['userid']);
    if ($session['write']) $write_apikey = get_apikey_write($session['userid']);

    if ($action == 'list' && $session['write'])
    {
      $multigraphs = get_user_multigraph($session['userid']);
      $user = get_user($session['userid']);
      $feedlist = get_user_feed_names($session['userid']);
      $output['content'] = view("vis/vis_main_view.php", array('user' => $user, 'feedlist'=>$feedlist, 'apikey'=>$read_apikey, 'visualisations'=>$visualisations, 'multigraphs'=>$multigraphs));
    }

    // Auto - automatically selects visualisation based on datatype
    // and is used primarily for quick checking feeds from the feeds page.
    if ($action == "auto")
    {
      $feedid = intval(get('feedid'));
      $datatype = get_feed_field($feedid,'datatype');
      if ($datatype == 0) $output['message'] = "Feed type or authentication not valid";
      if ($datatype == 1) $action = 'rawdata';
      if ($datatype == 2) $action = 'bargraph';
      if ($datatype == 3) $action = 'histgraph';
    }

    while ($vis = current($visualisations))
    {
      $viskey = key($visualisations);

      // If the visualisation has a set property called action
      // then override the visualisation key and use the set action instead
      if (isset($vis['action'])) $viskey = $vis['action'];
 
      if ($action == $viskey)
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
            $array[$key] = intval(get($key));
            $array[$key.'name'] = get_feed_field(intval(get($key)),'name');
            if (!feedtype_belongs_user_or_public($array[$key], $session['userid'], $type)) $array['valid'] = false;
          }

          // Boolean not used at the moment
          //if ($type==4) if (get($key)==true || get($key)==false) $array[$key] = get($key); else $array[$key] = $default;
          if ($type==5) $array[$key] = preg_replace('/[^\w\s£$]/','',get($key))?get($key):$default;
          if ($type==6) $array[$key] = floatval((get($key)?get($key):$default));
          if ($type==7) $array[$key] = intval((get($key)?get($key):$default));
        }
        }

        $array['apikey'] = $read_apikey;
        $array['write_apikey'] = $write_apikey;

        $output['content'] = view($visdir.$viskey.".php", $array);

        if ($array['valid'] == false) $output['content'] .= "<div style='position:absolute; top:0px; left:0px; background-color:rgba(255,255,255,0.5); width:100%; height:100%; text-align:center; padding-top:100px;'><h3>Feed type or authentication not valid</h3></div>";

      }
      next($visualisations);
    }

    /*

    MULTIGRAPH ACTIONS

    */

    if ($action == 'multigraph' && $subaction == 'new' && $session['write'])
    {
      $id = create_multigraph($session['userid']);
    }

    elseif ($action == 'multigraph' && $subaction == 'delete' && $session['write'])
    {
      $id = intval(get('id'));
      delete_multigraph($id,$session['userid']);
    }

    elseif ($action == 'multigraph' && $subaction == 'set' && $session['write'])
    {
      $id = intval(get('id'));
      $feedlist = preg_replace('/[^\w\s-.",:{}\[\]]/','',get('feedlist'));
      set_multigraph($id,$session['userid'],'',$feedlist);
    }

    elseif ($action == 'multigraph' && $subaction == 'get' && $session['read'])
    {
      $id = intval(get('id'));
      $multigraph_feedlist = get_multigraph($id,$session['userid']);
      $output['content'] = json_encode($multigraph_feedlist);
    }

    return $output;
  }

?>
