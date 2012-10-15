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

function run_notify($userid)
{
  $notification_number = 0;
  // Will be used to display number of notifications in an email
  $notification_message = "";
  // Variable holds body of email

  $result = db_query("SELECT * FROM notify WHERE `userid` = '$userid'");
  // Get user notification list
  while ($row = db_fetch_array($result))// For each feed in the notification list
  {
    $feedid = $row['feedid'];
    $feed = get_feed($feedid);
    // Get all feed details from feed model function

    if ((time() - strtotime($feed[3])) < (60 * 1))
      $active = 1;
    else
      $active = 0;
    // Check if feed is active

    // if feed becomes active again then the notification status needs to be reset to unsent
    if ($active && $row['oninactive_sent'])
      db_query("UPDATE notify SET oninactive_sent = 0 WHERE feedid='$feedid'");

    // if feed is not the onvalue value then notification status needs to be reset to unsent
    if ($feed[4] != $row['onvalue'] && $row['onvalue_sent'])
      db_query("UPDATE notify SET onvalue_sent = 0 WHERE feedid='$feedid'");

    if (!$row['onvalue_sent'] && $row['onvalue'] && $feed[4] == $row['onvalue'])// NOTIFY On value event
    {
      $notification_message .= "<p>" . _("Feed ") . $feed[1] . _(" is") . " == " . $row['onvalue'] . "</p>";
      $notification_number++;
      db_query("UPDATE notify SET onvalue_sent = 1 WHERE feedid='$feedid'");
    }

    if (!$row['oninactive_sent'] && $row['oninactive'] && !$active)// NOTIFY On inactive
    {
      $notification_message .= "<p>" . _("Feed ") . $feed[1] . _(" is inactive") . "</p>";
      $notification_number++;
      db_query("UPDATE notify SET oninactive_sent = 1 WHERE feedid='$feedid'");
    }
  }

  $to = get_notify_recipients($userid);
  $subject = $notification_number . "x emoncms events";
  $body = $notification_message;

  if ($notification_number > 0)
    send_mail($to, $subject, $body);
  return $notification_message;
}

function set_notify($userid, $feedid, $onvalue, $oninactive, $periodic)
{
  $result = db_query("SELECT * FROM notify WHERE `userid` = '$userid' AND `feedid` = '$feedid' ");
  if ($row = db_fetch_array($result))
  {
    db_query("UPDATE notify SET onvalue = '$onvalue', oninactive = '$oninactive', periodic = '$periodic' WHERE `feedid` = '$feedid'");
  }
  else
  {
    db_query("INSERT INTO notify (`userid`,`feedid`,`onvalue`,`onvalue_sent`,`oninactive`,`oninactive_sent`,`periodic`) VALUES ('$userid','$feedid','$onvalue','0','$oninactive','0','$periodic')");
  }
}

function get_notify($userid, $feedid)
{
  $result = db_query("SELECT * FROM notify WHERE `userid` = '$userid' AND `feedid` = '$feedid' ");
  return db_fetch_array($result);
}

function set_notify_recipients($userid, $recipients)
{
  $result = db_query("SELECT * FROM notify_mail WHERE `userid` = '$userid'");
  if ($row = db_fetch_array($result))
  {
    db_query("UPDATE notify_mail SET recipients = '$recipients' WHERE `userid` = '$userid'");
  }
  else
  {
    db_query("INSERT INTO notify_mail (`userid`,`recipients`) VALUES ('$userid','$recipients')");
  }
}

function get_notify_recipients($userid)
{
  $result = db_query("SELECT * FROM notify_mail WHERE `userid` = '$userid'");
  $row = db_fetch_array($result);
  return $row['recipients'];
}

function get_notify_users()
{
  $result = db_query("SELECT * FROM notify_mail");

  $users = array();
  while ($row = db_fetch_array($result))
  {
    $users[] = array(
      'userid' => $row['userid'],
      'recipients' => $row['recipients']
    );
  }
  return $users;
}
