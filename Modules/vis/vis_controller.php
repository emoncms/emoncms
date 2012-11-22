<?php
  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    USER CONTROLLER ACTIONS		ACCESS

    realtime?feedid=1			read
    rawdata?feedid=1			read
    bargraph?feedid=1			read

  */

  //---------------------------------------------------------------------
  // The html content on this page could be seperated out into a view
  //---------------------------------------------------------------------

  // no direct access
  defined('EMONCMS_EXEC') or die(_('Restricted access'));

  function vis_controller()
  {
    require "Modules/feed/feed_model.php";
    require "Modules/vis/multigraph_model.php";
    global $session, $route;

    $format = $route['format'];
    $action = $route['action'];

    $output['content'] = "";
    $output['message'] = "";

    $visdir = "vis/visualisations/";

    if ($session['read'])
    {
    	$apikey = get_apikey_read($session['userid']);
    }

    if ($action == 'list' && $session['write'])
    {
      $user = get_user($session['userid']);
      $feedlist = get_user_feed_names($session['userid']);
      $output['content'] = view("vis/vis_main_view.php", array('user' => $user, 'feedlist'=>$feedlist, 'apikey'=>$apikey));
    }

    // vis/realtime?feedid=1
    if ($action == "realtime" && $session['read'])
    {
      $feedid = intval(get('feedid'));
      $output['content'] = view($visdir."realtime.php", array('feedid'=>$feedid,'feedname'=>get_feed_field($feedid,'name')));
    }

    // vis/rawdata?feedid=1
    if ($action == "rawdata" && $session['read'])
    {
      $feedid = intval(get('feedid'));
      $output['content'] = view($visdir."rawdata.php", array('feedid'=>$feedid,'feedname'=>get_feed_field($feedid,'name')));
    }

    // vis/bargraph?feedid=2
    if ($action == "bargraph" && $session['read'])
    {
      $feedid = intval(get('feedid'));
      $output['content'] = view($visdir."bargraph.php", array('feedid'=>$feedid,'feedname'=>get_feed_field($feedid,'name')));
    }

    if ($action == 'smoothie' && $session['read'])
    {
      $output['content'] = view($visdir."smoothie/smoothie.php", array());
    }

    // vis/histgraph?feedid=3
    if ($action == "histgraph" && $session['read'])
    {
      $feedid = intval(get('feedid'));
      $output['content'] = view($visdir."histgraph.php", array('feedid'=>$feedid,'feedname'=>get_feed_field($feedid,'name')));
    }

    // vis/dailyhistogram?power=  &kwhd=  &whw= 
    if ($action == 'dailyhistogram' && $session['read'])
    {
      $output['content'] = view($visdir."dailyhistogram/dailyhistogram.php", array());
    }

    if ($action == 'zoom' && $session['read'])
    {
      $output['content'] = view($visdir."zoom/zoom.php", array());
    }
    
    if ($action == 'comparison' && $session['read'])
    {
      $output['content'] = view($visdir."comparison/comparison.php", array());
    }

    if ($action == 'stacked' && $session['read'])
    {
      $output['content'] = view($visdir."stacked.php", array());
    }

    if ($action == 'threshold' && $session['read'])
    {
      $output['content'] = view($visdir."threshold.php", array());
    }

    if ($action == 'simplezoom' && $session['read'])
    {
      $output['content'] = view($visdir."simplezoom.php", array());
    }

    if ($action == "orderbars" && $session['read'])
    {
      $feedid = intval(get('feedid'));
      $output['content'] = view($visdir."orderbars.php", array('feedid'=>$feedid,'feedname'=>get_feed_field($feedid,'name')));
    }

    if ($action == 'orderthreshold' && $session['read'])
    {
      $output['content'] = view($visdir."orderthreshold.php", array());
    }

    elseif ($action == 'multigraph' && $session['read'])
    {
      $write_apikey = "";
      if ($session['write'])
      {
      	$write_apikey = get_apikey_write($session['userid']);
      }
      $output['content'] = view($visdir."multigraph.php", array('write_apikey'=>$write_apikey));
    }

    if ($action == "multigraphsave" && $session['write'])
    {
      $output['message'] = "saving";
      $json = preg_replace('/[^\w\s-.?@%&:[]{},]/','',post('data'));

      set_multigraph($session['userid'], $json);
    }

    if ($action == "multigraphget" && $session['read'])
    {
      $output['content'] =  get_multigraph($session['userid']);
    }

    // vis/rawdata?feedid=1
    if ($action == "edit" && $session['write'])
    {
      $feedid = intval(get('feedid'));
      $output['content'] = view($visdir."edit.php", array('feedid'=>$feedid,'feedname'=>get_feed_field($feedid,'name'), 'type'=>get_feed_field($feedid,'datatype')));
    }
 
    return $output;
  }

?>
