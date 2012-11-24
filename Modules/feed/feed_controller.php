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
      $type = intval(get("type"));

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
      $feeds = get_user_feeds($session['userid'],0);
      if ($format == 'json') $output['content'] = json_encode($feeds);
      if ($format == 'html') $output['content'] = view("feed/Views/feedlist_view.php", array('feeds' => $feeds));
    }

    elseif ($action == "deleted" && $session['read'])
    { 
      $feeds = get_user_feeds($session['userid'],1);
      if ($format == 'json') $output['content'] = json_encode($feeds);
      if ($format == 'html') $output['content'] = view("feed/Views/deletedfeedlist_view.php", array('feeds' => $feeds));
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
      $feedid = intval(get("id"));
      if (feed_belongs_user($feedid,$session['userid']))
      {
      	$output['content'] = get_feed_field($feedid,'value');
      }
    }

    elseif ($action == 'get' && $session['read'])
    {
      $feedid = intval(get('id'));
      if (feed_belongs_user($feedid,$session['userid']))
      {
        $field = preg_replace('/[^\w\s-]/','',get('field'));
        if ($field) $output['content'] = get_feed_field($feedid,$field);
        if (!$field) $output['content'] = json_encode(get_feed($feedid));
      }
    }

    elseif ($action == 'set' && $session['write'])
    {
      $feedid = intval(get('id'));
      if (feed_belongs_user($feedid,$session['userid']))
      {
        $field = preg_replace('/[^\w\s-]/','',get('field'));
        $value = preg_replace('/[^\w\s-]/','',get('value'));
        set_feed_field($feedid,$field,$value);
      }
    }

    /*

    Feed data actions

    */

    elseif ($action == "insert" && $session['write'])
    { 
      $feedid = intval(get("id"));
      $feedtime = intval(get("time"));
      $value = floatval(get("value"));

      if (!$feedtime) $feedtime = time();
      insert_feed_data($feedid,time(),$feedtime,$value);
    }

    elseif ($action == "update" && $session['write'])
    { 
      $feedid = intval(get("id"));
      if (feed_belongs_user($feedid, $session['userid'])) {
        $feedtime = intval(get("time"));
        $value = floatval(get("value"));

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
      $feedid = intval(get('id'));
      
      // Check if feed belongs to user
      if (feed_belongs_user($feedid,$session['userid']))
      {
        $start = floatval(get('start'));
        $end = floatval(get('end'));
        $dp = intval(get('dp'));
        $data = get_feed_data($feedid,$start,$end,$dp);
        $output['content'] = json_encode($data);
      } else { $output['message'] = "Permission denied"; }
    }

    elseif ($action == 'histogram' && $session['read'])
    {
      $feedid = intval(get('id'));
      
      // Check if feed belongs to user
      if (feed_belongs_user($feedid,$session['userid']))
      {
        $start = floatval(get('start'));
        $end = floatval(get('end'));
        $data = get_histogram_data($feedid,$start,$end);
        $output['content'] = json_encode($data);
      } else { $output['message'] = "Permission denied"; }
    }

    elseif ($action == 'kwhatpower' && $session['read'])
    {
      $feedid = intval(get('id'));
      // Check if feed belongs to user
      if (feed_belongs_user($feedid,$session['userid']))
      {
        $min = floatval(get('min'));
        $max = floatval(get('max'));
			
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
      $feedid = intval(get("id"));
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
      $feedid = intval(get("id"));
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
