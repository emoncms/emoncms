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

function user_apikey_session($apikey_in)
{
  $session = array();

  //----------------------------------------------------
  // Check for apikey login
  //----------------------------------------------------
  $apikey_in = db_real_escape_string($apikey_in);

  $userid = get_apikey_read_user($apikey_in);
  if ($userid != 0)
  {
    session_regenerate_id();
    $session['userid'] = $userid;
    $session['read'] = 1;
    $session['write'] = 0;
    $session['admin'] = 0;
    $session['editmode'] = TRUE;
 //   $session['lang'] = "en";
  }

  $userid = get_apikey_write_user($apikey_in);
  if ($userid != 0)
  {
    session_regenerate_id();
    $session['userid'] = $userid;
    $session['read'] = 1;
    $session['write'] = 1;
    $session['admin'] = 0;
    $session['editmode'] = TRUE;
   // $session['lang'] = "en";

  }
  //----------------------------------------------------
  return $session;
}

function get_user($userid)
{
  $result = db_query("SELECT * FROM users WHERE id=$userid");
  if ($result)
  {
    $row = db_fetch_array($result);
    if (!isset($row['email'])) $row['email']="";
    $user = array(
      'username' => $row['username'],
      'email' => $row['email'],
      'apikey_read' => $row['apikey_read'],
      'apikey_write' => $row['apikey_write'],
      'lang' => $row['lang'],
      'timeoffset' => $row['timeoffset']
    );
  }
  return $user;
}

function get_apikey_read($userid)
{
  $result = db_query("SELECT apikey_read FROM users WHERE id=$userid");
  if ($result)
  {
    $row = db_fetch_array($result);
    $apikey = $row['apikey_read'];
  }
  return $apikey;
}

function get_apikey_write($userid)
{
  $result = db_query("SELECT apikey_write FROM users WHERE id=$userid");
  if ($result)
  {
    $row = db_fetch_array($result);
    $apikey = $row['apikey_write'];
  }
  return $apikey;
}

function set_user_username($userid, $username)
{
  db_query("UPDATE users SET username = '$username' WHERE id='$userid'");
}

function set_user_email($userid, $email)
{
  db_query("UPDATE users SET email = '$email' WHERE id='$userid'");
}

function set_apikey_read($userid, $apikey)
{
  db_query("UPDATE users SET apikey_read = '$apikey' WHERE id='$userid'");
}

function set_apikey_write($userid, $apikey)
{
  db_query("UPDATE users SET apikey_write = '$apikey' WHERE id='$userid'");
}

function get_apikey_read_user($apikey)
{
  $result = db_query("SELECT id FROM users WHERE apikey_read='$apikey'");
  $row = db_fetch_array($result);
  return $row['id'];
}

function get_apikey_write_user($apikey)
{
  $result = db_query("SELECT id FROM users WHERE apikey_write='$apikey'");
  $row = db_fetch_array($result);
  return $row['id'];
}

function create_user($username, $password)
{
  $hash = hash('sha256', $password);
  $string = md5(uniqid(mt_rand(), true));
  $salt = substr($string, 0, 3);
  $hash = hash('sha256', $salt . $hash);

  $apikey_write = md5(uniqid(mt_rand(), true));
  $apikey_read = md5(uniqid(mt_rand(), true));

  db_query("INSERT INTO users ( username, password, salt ,apikey_read, apikey_write ) VALUES ( '$username' , '$hash' , '$salt', '$apikey_read', '$apikey_write' );");

  // Make the first user an admin
  $userid = db_insert_id();
  if ($userid == 1)
  {
    db_query("UPDATE users SET admin = 1 WHERE id = '$userid'");
  }
  return $userid;
}

function ckeck_for_user_directory($username)
{
	// Get the user id
	$id = get_user_id($username);
	
	// Check if the user directory exists and create it
	//if (!is_dir("./users/$id"))
	//	mkdir("./users/$id", 0700);	
}

function user_logon($username, $password)
{
  $result = db_query("SELECT id,password,admin,salt,lang FROM users WHERE username = '$username'");
  $userData = db_fetch_array($result);
  $hash = hash('sha256', $userData['salt'] . hash('sha256', $password));

  if ((db_num_rows($result) < 1) || ($hash != $userData['password']))
  {
    $_SESSION['read'] = 0;
    $_SESSION['write'] = 0;
    $_SESSION['admin'] = 0;
    $success = 0;
  }
  else
  {
    //this is a security measure
    session_regenerate_id();
    $_SESSION['userid'] = $userData['id'];
    $_SESSION['username'] = $username;
    $_SESSION['read'] = 1;
    $_SESSION['write'] = 1;
    $_SESSION['admin'] = $userData['admin'];
    $_SESSION['lang'] = $userData['lang'];
    $_SESSION['editmode'] = TRUE;
	
	// If user is created or login we check here if the user directory was created on server
	ckeck_for_user_directory($username);
		
    $success = 1;
  }
  return $success;
}

function user_logout()
{
  $_SESSION['read'] = 0;
  $_SESSION['write'] = 0;
  $_SESSION['admin'] = 0;
  session_destroy();
}

function get_user_id($username)
{
  $result = db_query("SELECT id FROM users WHERE username = '$username';");
  $row = db_fetch_array($result);
  return $row['id'];
}

function get_user_name($id)
{
  $result = db_query("SELECT username FROM users WHERE id = '$id';");
  $row = db_fetch_array($result);
  return $row['username'];
}

function change_password($userid, $oldpass, $newpass)
{
  $result = db_query("SELECT password, salt FROM users WHERE id = '$userid'");
  $userData = db_fetch_array($result);
  $hash = hash('sha256', $userData['salt'] . hash('sha256', $oldpass));
  // hash of oldpass

  if ($hash == $userData['password'])
  {
    $hash = hash('sha256', $newpass);
    $string = md5(uniqid(rand(), true));
    $salt = substr($string, 0, 3);
    $hash = hash('sha256', $salt . $hash);
    db_query("UPDATE users SET password = '$hash', salt = '$salt' WHERE id = '$userid'");
    return 1;
    // success
  }
  else
  {
    return 0;
    // failed
  }
}

function get_user_list()
{
  $result = db_query("SELECT id, username, admin, uphits, dnhits FROM users");
  $userlist = array();
  while ($row = db_fetch_array($result))
  {
    $userlist[] = array(
      'userid' => $row['id'],
      'name' => $row['username'],
      'admin' => $row['admin'],
      'uphits' => $row['uphits'],
      'dnhits' => $row['dnhits']
    );
  }

  return $userlist;
}

function set_user_lang($userid, $lang)
{
  db_query("UPDATE users SET lang = '$lang' WHERE id='$userid'");
}

function get_user_lang($userid)
{
	$result = db_query("SELECT lang FROM users WHERE id = '$userid';");
	$row = db_fetch_array($result);
	return $row['lang'];
}

function set_user_timeoffset($userid,$timeoffset)
{
  db_query("UPDATE users SET timeoffset = '$timeoffset' WHERE id='$userid'");
}

function get_user_timeoffset($userid)
{
  $result = db_query("SELECT timeoffset FROM users WHERE id = '$userid';");
  $row = db_fetch_array($result);
  return $row['timeoffset'];
}

function get_user_settingsarray($userid)
{
  $result = db_query("SELECT settingsarray FROM users WHERE id = '$userid';");
  $row = db_fetch_array($result);
  return json_decode($row['settingsarray']);
}

function set_user_settingsarray($userid, $settingsarray)
{
  $settingsarray = json_encode($settingsarray);
  db_query("UPDATE users SET settingsarray = '$settingsarray' WHERE id='$userid'");
}

function user_inc_uphits($userid)
{
  db_query("update users SET uphits = uphits + 1 WHERE id='$userid'");
}

function user_inc_dnhits($userid)
{
  db_query("update users SET dnhits = dnhits + 1 WHERE id='$userid'");
}

  function user_sortby_uphits($x,$y)
  {
    return $y['uphits'] - $x['uphits'];
  }

  function user_sortby_dnhits($x,$y)
  {
    return $y['dnhits'] - $x['dnhits'];
  }

function get_user_salt($userid)
{
  $result = db_query("SELECT salt FROM users WHERE id = '$userid'");
  $row = db_fetch_array($result);
  return $row['salt'];
}


?>
