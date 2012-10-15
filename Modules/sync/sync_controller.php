<?php
  /*
    
    The sync controller and view creates a page that:

    1) Allows you to select a remote emoncms account from which you want to sync from.
    2) Select the individual feeds in the remote account that you want to download.
    3) Provide update of the feeds position in the download queue

    Downloading and importing is handled by the import script.

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    SYNC CONTROLLER ACTIONS		ACCESS

    Script is a rough first draft - it needs cleaning up!!
  */

  // no direct access
  defined('EMONCMS_EXEC') or die('Restricted access');

  function sync_controller()
  {
    require "Modules/feed/feed_model.php";
    global $session, $route;

    $format = $route['format'];
    $action = $route['action'];

    $output['content'] = "";
    $output['message'] = "";

    $url = urldecode(get('url'));
    $remotekey = db_real_escape_string(preg_replace('/[^.\/A-Za-z0-9]/', '', get('remotekey')));

    $remote_feeds = array();

    // Register a feed to be downloaded action
    // sync/feed?url=URL &remotekey=REMOTEKEY &id=FEEDID &name=FEEDNAME
    if ($action=="feed" && $session['write'])
    {
      $id = intval($_GET['id']);
      $name = preg_replace('/[^\w\s-.]/','',$_GET["name"]);

      $localfeedid = get_feed_id($session['userid'],$name);

      if (!$localfeedid) $localfeedid = create_feed($session['userid'],$name,1,0);

      // Make sure feed is not already in que
      $result = db_query("SELECT * FROM importqueue WHERE baseurl='$url' AND apikey='$remotekey' AND feedid='$id' AND localfeedid='$localfeedid'");
      if (!db_fetch_array($result))
      {
        db_query("INSERT INTO importqueue (`baseurl`,`apikey`,`feedid`,`localfeedid`) VALUES ('$url','$remotekey','$id','$localfeedid')");
      }
    }

    // SYNC Page display
    if ($session['write'])
    {
      $settingsarray = get_user_settingsarray($session['userid']);

      if ((!$url || !$remotekey) && $settingsarray)
      {
        $url = $settingsarray->remoteurl;
        $remotekey = $settingsarray->remotekey;
      }

      if ($url && $remotekey){

        $settingsarray->remoteurl = $url;
        $settingsarray->remotekey = $remotekey;
        set_user_settingsarray($session['userid'], $settingsarray);

      // Request feed list
      $fh = @fopen($url."/feed/list.json?apikey=".$remotekey, 'r' );
      // Read reply
      $data = ""; while (($buffer = fgets($fh)) !== false) {$data .= $buffer;}
      // Convert into feedlist array
      $remote_feeds = json_decode($data);
      fclose($fh);

      /*
      
      This section gets import queue location of the feeds

      */

      // Get id position of first feed in line
      $result = db_query("SELECT * FROM importqueue ORDER BY `queid` Asc LIMIT 1");
      $row = db_fetch_array($result);
      $first_in_line = $row['queid'];

      // For each feed check that the feed exists in the import que and calculate its position
      for ($i=0; $i<count($remote_feeds); $i++) {
        $id = $remote_feeds[$i][0];
        $localfeedid = get_feed_id($session['userid'],$remote_feeds[$i][1]);
        $result = db_query("SELECT * FROM importqueue WHERE baseurl='$url' AND apikey='$remotekey' AND feedid='$id' AND localfeedid='$localfeedid'");
        if ($row = db_fetch_array($result))
        {
          $remote_feeds[$i]['inque'] = "Queue position: ".($row['queid']-$first_in_line); 
        }

        if ($localfeedid){
          $localfeedname = "feed_".trim($localfeedid)."";
          $localfeedtime_result = db_query("SELECT * FROM $localfeedname ORDER BY time Desc LIMIT 1");
          $localfeedtime_row = db_fetch_array($localfeedtime_result);

          $time_diff = (($remote_feeds[$i][3]/1000) - $localfeedtime_row[0])/3600;

          $remote_feeds[$i]['synctime'] = intval($time_diff)." hours";
          if ($time_diff>48) $remote_feeds[$i]['synctime'] = intval($time_diff / 24)." days";
          if ($time_diff>(24*365)) $remote_feeds[$i]['synctime'] = intval($time_diff / (24*365))." years";
        }
        else 
        {
          $remote_feeds[$i]['synctime'] = "no local feed";
        }
      }

      }
      $output['content'] = view("sync/sync_view.php", array('url'=>$url, 'remotekey'=>$remotekey, 'feeds'=>$remote_feeds));


    }
  
    return $output;
  }

?>
