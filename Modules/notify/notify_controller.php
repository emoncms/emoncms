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

  function notify_controller()
  {
    require "Modules/notify/notify_model.php";
    require "Modules/feed/feed_model.php";
    require "Modules/notify/mail_model.php";
    global $session, $route;

    $format = $route['format'];
    $action = $route['action'];

    $output['content'] = "";
    $output['message'] = "";

    $userid = $session['userid'];

    // notify/set?feedid=1&onvalue=300&oninactive=1&periodic=1
    if ($action == 'set' && $session['write'])
    {
      $feedid = intval(get('feedid'));
      $onvalue = floatval(get('onvalue'));
      $oninactive = intval(get('oninactive'));
      $periodic = intval(get('periodic'));

      $output['message'] = "Notify set: ".set_notify($userid,$feedid,$onvalue,$oninactive,$periodic);
    }

    elseif ($action == 'view' && $session['write'])
    {
      $feedid = intval(get('id'));
      $notify = get_notify($userid, $feedid);
      //if ($format == 'json') $output = json_encode($feeds);

      if ($format == 'html') $output['content'] = view("notify/notify_view.php", array('feedid'=>$feedid,'notify'=>$notify));
    }

	elseif ($action == 'setrecipients' && $session['write'])
    {
      $recipients = preg_replace('/[^\w\s-.,@]/','',$_GET["recipients"]);
      set_notify_recipients($userid,$recipients);

      $recipients = get_notify_recipients($userid);

      if ($format == 'html') $output['content'] = view("notify/notify_settings_view.php", array('recipients'=>$recipients));
    }

	elseif ($action == 'settings' && $session['write'])
    {
      $recipients = get_notify_recipients($userid);

      if ($format == 'html') $output['content'] = view("notify/notify_settings_view.php", array('recipients'=>$recipients));
    }

    return $output;
  }

?>
