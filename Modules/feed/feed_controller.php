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
    include "Modules/feed/feed_model.php";
    global $session, $route;

    $format = $route['format'];
    $action = $route['action'];

    $output['content'] = "";
    $output['message'] = "";

    if ($action == "api" && $session['write'])
    { 
      if ($format == 'html') $output['content'] = view("feed/Views/feedapi_view.php", array());
    }

    /*

    General actions

    */

    elseif ($action == "create" && $session['write'])
    { 
      $name = preg_replace('/[^\w\s-]/','',get('name'));
      $type = intval($_GET["type"]);

      $feedid = create_feed($session['userid'],$name,$type);
      $output['content'] = "Result: $feedid";
    }

    elseif ($action == "getid" && $session['write'])
    { 
      $name = preg_replace('/[^\w\s-]/','',get('name'));
      $feedid = get_feed_id($session['userid'],$name);
      $output['content'] = "Result: $feedid";
    }

    elseif ($action == "list" && $session['read'])
    { 
      $del = intval(get('del'));
      $feeds = get_user_feeds($session['userid'],$del);
      if ($format == 'json') $output['content'] = json_encode($feeds);
      if ($format == 'html') $output['content'] = view("feed/Views/feedlist_view.php", array('feeds' => $feeds,'del'=>$del));
    }


    /*

    Feed property actions

    */

    //---------------------------------------------------------------------------------------------------------
    // current feed value
    // http://yoursite/emoncms/feed/value?id=1
    //---------------------------------------------------------------------------------------------------------
    elseif ($action == 'value' && $session['read'])
    {
      $feedid = intval($_GET["id"]);
      if (feed_belongs_user($feedid,$session['userid']))
      {
      	$output['content'] = get_feed_field($feedid,'value');
      }
    }

    elseif ($action == 'get' && $session['read'])
    {
      $feedid = intval($_GET['id']);
      if (feed_belongs_user($feedid,$session['userid']))
      {
        $field = $_GET['field'];
        if ($field) $output['content'] = get_feed_field($feedid,$field);
        if (!$field) $output['content'] = json_encode(get_feed($feedid));
      }
    }

    elseif ($action == 'set' && $session['write'])
    {
      $feedid = intval($_GET['id']);
      if (feed_belongs_user($feedid,$session['userid']))
      {
        $field = $_GET['field'];
        $value = $_GET['value'];
        set_feed_field($feedid,$field,$value);
      }
    }

    /*

    Feed data actions

    */

    elseif ($action == "insert" && $session['write'])
    { 
      $feedid = intval($_GET["id"]);
      $feedtime = intval($_GET["time"]);
      $value = floatval($_GET["value"]);

      if (!$feedtime) $feedtime = time();
      insert_feed_data($feedid,time(),$feedtime,$value);
    }

    elseif ($action == "update" && $session['write'])
    { 
      $feedid = intval($_GET["id"]);
      if (feed_belongs_user($feedid, $session['userid'])) {
        $feedtime = intval($_GET["time"]);
        $value = floatval($_GET["value"]);

        if (!$feedtime) $feedtime = time();
        update_feed_data($feedid,time(),$feedtime,$value);
      }
    }

    //---------------------------------------------------------------------------------------------------------
    // get feed data
    // start: start time, end: end time, dp: number of datapoints in time range to fetch
    // http://yoursite/emoncms/feed/data?id=1&start=000&end=000&dp=1
    //---------------------------------------------------------------------------------------------------------
    elseif ($action == 'data' && $session['read'])
    {
      $feedid = intval($_GET['id']);
      
      // Check if feed belongs to user
      if (feed_belongs_user($feedid,$session['userid']))
      {
        $start = floatval($_GET['start']);
        $end = floatval($_GET['end']);
        $dp = intval($_GET['dp']);
        $data = get_feed_data($feedid,$start,$end,$dp);
        $output['content'] = json_encode($data);
      } else { $output['message'] = "Permission denied"; }
    }

    elseif ($action == 'histogram' && $session['read'])
    {
      $feedid = intval($_GET['id']);
      
      // Check if feed belongs to user
      if (feed_belongs_user($feedid,$session['userid']))
      {
        $start = floatval($_GET['start']);
        $end = floatval($_GET['end']);
        $data = get_histogram_data($feedid,$start,$end);
        $output['content'] = json_encode($data);
      } else { $output['message'] = "Permission denied"; }
    }

    elseif ($action == 'kwhatpower' && $session['read'])
    {
      $feedid = intval($_GET['id']);
      // Check if feed belongs to user
      if (feed_belongs_user($feedid,$session['userid']))
      {
        $min = floatval($_GET['min']);
        $max = floatval($_GET['max']);
			
        $data = get_kwhd_atpower($feedid,$min,$max);
        $output['content'] = json_encode($data);

      } else { $output['message'] = "This is not your feed..."; }
    }

    /*

    Delete

    */

    //---------------------------------------------------------------------------------------------------------
    // Delete a feed ( move to recycle bin, so not permanent )
    // http://yoursite/emoncms/feed/delete?id=1
    //--------------------------------------------------------------------------------------------------------- 
    elseif ($action == "delete" && $session['write'])
    { 
      $feedid = intval($_GET["id"]);
      if (feed_belongs_user($feedid, $session['userid']))
      {
        delete_feed($userid,$feedid);
        $output['message'] = _("Feed ").get_feed_field($feedid,'name')._(" deleted");
      } else $output['message'] = _("Feed does not exist");
    }

    //---------------------------------------------------------------------------------------------------------
    // Restore feed ( if in recycle bin )
    // http://yoursite/emoncms/feed/restore?id=1
    //--------------------------------------------------------------------------------------------------------- 
    if ($action == "restore" && $session['write'])
    { 
      $feedid = intval($_GET["id"]);
      if (feed_belongs_user($feedid, $session['userid'])) {
        restore_feed($userid,$feedid);
      } 
      $output['message'] = "feed restored"; 
      if ($format == 'html') header("Location: list");	// Return to feed list page
    }

    //---------------------------------------------------------------------------------------------------------
    // Permanent delete equivalent to empty recycle bin
    // http://yoursite/emoncms/feed/permanentlydelete
    //--------------------------------------------------------------------------------------------------------- 
    if ($action == "emptybin" && $session['write'])
    { 
      permanently_delete_feeds($session['userid']);
      $output['message'] = "Deleted feeds are now permanently deleted";
    }

    return $output;
  }

?>
