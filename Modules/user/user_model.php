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

class User
{

    private $mysqli;
    private $rememberme;
    private $enable_rememberme = false;

    public function __construct($mysqli,$rememberme)
    {
        //copy the settings value, otherwise the enable_rememberme will always be false.
        global $enable_rememberme;
        $this->enable_rememberme = $enable_rememberme;
		
        $this->mysqli = $mysqli;
        $this->rememberme = $rememberme;
    }

    //---------------------------------------------------------------------------------------
    // Core session methods
    //---------------------------------------------------------------------------------------

    public function apikey_session($apikey_in)
    {
        $session = array();

        //----------------------------------------------------
        // Check for apikey login
        //----------------------------------------------------
        $apikey_in = $this->mysqli->real_escape_string($apikey_in);

        $result = $this->mysqli->query("SELECT id FROM users WHERE apikey_read='$apikey_in'");
        if ($result->num_rows == 1) 
        {
            $row = $result->fetch_array();
            if ($row['id'] != 0)
            {
                //session_regenerate_id();
                $session['userid'] = $row['id'];
                $session['read'] = 1;
                $session['write'] = 0;
                $session['admin'] = 0;
                $session['editmode'] = TRUE;
                $session['lang'] = "en";
            }
        }

        $result = $this->mysqli->query("SELECT id FROM users WHERE apikey_write='$apikey_in'");
        if ($result->num_rows == 1) 
        {
            $row = $result->fetch_array();
            if ($row['id'] != 0)
            {
                //session_regenerate_id();
                $session['userid'] = $row['id'];
                $session['read'] = 1;
                $session['write'] = 1;
                $session['admin'] = 0;
                $session['editmode'] = TRUE;
                $session['lang'] = "en";
            }
        }
        //----------------------------------------------------
        return $session;
    }

    public function emon_session_start()
    {
        session_start();

        if ($this->enable_rememberme)
        {
            // if php session exists
            if (!empty($_SESSION['userid'])) {
              // if rememberme emoncms cookie exists but is not valid then  
              // a valid cookie is a cookie who's userid, token and persistant token match a record in the db
              if(!empty($_COOKIE[$this->rememberme->getCookieName()]) && !$this->rememberme->cookieIsValid($_SESSION['userid'])) {
                $this->logout();
              }
            } 
            else 
            {

              $loginresult = $this->rememberme->login();
              if ($loginresult) 
              {
                // Remember me login
                $_SESSION['userid'] = $loginresult;
                $_SESSION['read'] = 1;
                $_SESSION['write'] = 1;
                // There is a chance that an attacker has stolen the login token, so we store
                // the fact that the user was logged in via RememberMe (instead of login form)
                $_SESSION['cookielogin'] = true;
              }
              else
              {
                if($this->rememberme->loginTokenWasInvalid()) {
                  // Stolen
                }
              }
            }
        }

        if (isset($_SESSION['admin'])) $session['admin'] = $_SESSION['admin']; else $session['admin'] = 0;
        if (isset($_SESSION['read'])) $session['read'] = $_SESSION['read']; else $session['read'] = 0;
        if (isset($_SESSION['write'])) $session['write'] = $_SESSION['write']; else $session['write'] = 0;
        if (isset($_SESSION['userid'])) $session['userid'] = $_SESSION['userid']; else $session['userid'] = 0;
        if (isset($_SESSION['lang'])) $session['lang'] = $_SESSION['lang']; else $session['lang'] = '';
        if (isset($_SESSION['username'])) $session['username'] = $_SESSION['username']; else $session['username'] = '';
        if (isset($_SESSION['cookielogin'])) $session['cookielogin'] = $_SESSION['cookielogin']; else $session['cookielogin'] = 0;

        return $session;
    }


    public function register($username, $password, $email)
    {
        // Input validation, sanitisation and error reporting
        if (!$username || !$password || !$email) return array('success'=>false, 'message'=>_("Missing username, password or email paramater"));

        if (!ctype_alnum($username)) return array('success'=>false, 'message'=>_("Username must only contain a-z and 0-9 characters"));
        $username = $this->mysqli->real_escape_string($username);
        $password = $this->mysqli->real_escape_string($password);

        if ($this->get_id($username) != 0) return array('success'=>false, 'message'=>_("Username already exists"));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return array('success'=>false, 'message'=>_("Email address format error"));

        if (strlen($username) < 4 || strlen($username) > 30) return array('success'=>false, 'message'=>_("Username length error"));
        if (strlen($password) < 4 || strlen($password) > 30) return array('success'=>false, 'message'=>_("Password length error"));

        // If we got here the username, password and email should all be valid

        $hash = hash('sha256', $password);
        $string = md5(uniqid(mt_rand(), true));
        $salt = substr($string, 0, 3);
        $hash = hash('sha256', $salt . $hash);

        $apikey_write = md5(uniqid(mt_rand(), true));
        $apikey_read = md5(uniqid(mt_rand(), true));

        if (!$this->mysqli->query("INSERT INTO users ( username, password, email, salt ,apikey_read, apikey_write, admin ) VALUES ( '$username' , '$hash', '$email', '$salt', '$apikey_read', '$apikey_write', 0 );")) {
        	return array('success'=>false, 'message'=>_("Error creating user"));
        }
        
        // Make the first user an admin
        $userid = $this->mysqli->insert_id;
        if ($userid == 1) $this->mysqli->query("UPDATE users SET admin = 1 WHERE id = '1'");

        return array('success'=>true, 'userid'=>$userid, 'apikey_read'=>$apikey_read, 'apikey_write'=>$apikey_write);
    }

    public function login($username, $password, $remembermecheck)
    {
        $remembermecheck = (int) $remembermecheck;

        if (!$username || !$password) return array('success'=>false, 'message'=>_("Username or password empty"));

        // filter out all except for alphanumeric white space and dash
        //if (!ctype_alnum($username)) 
        $username_out = preg_replace('/[^\w\s-]/','',$username); 

        if ($username_out!=$username) return array('success'=>false, 'message'=>_("Username must only contain a-z 0-9 dash and underscore, if you created an account before this rule was in place enter your username without the non a-z 0-9 dash underscore characters to login and feel free to change your username on the profile page."));

        $username = $this->mysqli->real_escape_string($username);
        $password = $this->mysqli->real_escape_string($password);

        $result = $this->mysqli->query("SELECT id,password,admin,salt,language FROM users WHERE username = '$username'");

        if ($result->num_rows < 1) return array('success'=>false, 'message'=>_("Username does not exist"));
     
        $userData = $result->fetch_object();
        $hash = hash('sha256', $userData->salt . hash('sha256', $password));

        if ($hash != $userData->password) 
        {
            return array('success'=>false, 'message'=>_("Incorrect password, if your sure its correct try clearing your browser cache"));
        }
        else
        {
            session_regenerate_id();
            $_SESSION['userid'] = $userData->id;
            $_SESSION['username'] = $username;
            $_SESSION['read'] = 1;
            $_SESSION['write'] = 1;
            $_SESSION['admin'] = $userData->admin;
            $_SESSION['lang'] = $userData->language;
            $_SESSION['editmode'] = TRUE;

            if ($this->enable_rememberme) {
                if ($remembermecheck==true) {
                    $this->rememberme->createCookie($userData->id);
                } else {
                    $this->rememberme->clearCookie();
                }
            }

            return array('success'=>true, 'message'=>_("Login successful"));
        }
    }

    public function logout()
    {
        if ($this->enable_rememberme) $this->rememberme->clearCookie(true);
        $_SESSION['userid'] = 0;
        $_SESSION['read'] = 0;
        $_SESSION['write'] = 0;
        $_SESSION['admin'] = 0;
        session_regenerate_id(true);
        session_destroy();
    }

    public function change_password($userid, $old, $new)
    {
        $userid = intval($userid);
        $old = $this->mysqli->real_escape_string($old);
        $new = $this->mysqli->real_escape_string($new);

        if (strlen($old) < 4 || strlen($old) > 30) return array('success'=>false, 'message'=>_("Password length error"));
        if (strlen($new) < 4 || strlen($new) > 30) return array('success'=>false, 'message'=>_("Password length error"));

        // 1) check that old password is correct
        $result = $this->mysqli->query("SELECT password, salt FROM users WHERE id = '$userid'");
        $row = $result->fetch_object();
        $hash = hash('sha256', $row->salt . hash('sha256', $old));

        if ($hash == $row->password)
        {
            // 2) Save new password
            $hash = hash('sha256', $new);
            $string = md5(uniqid(rand(), true));
            $salt = substr($string, 0, 3);
            $hash = hash('sha256', $salt . $hash);
            $this->mysqli->query("UPDATE users SET password = '$hash', salt = '$salt' WHERE id = '$userid'");
            return array('success'=>true);
        }
        else
        {
            return array('success'=>false, 'message'=>_("Old password incorect"));
        }
    }

    public function change_username($userid, $username)
    {
        if (isset($_SESSION['cookielogin']) && $_SESSION['cookielogin']==true) return array('success'=>false, 'message'=>_("As your using a cookie based remember me login, please logout and log back in to change username"));

        $userid = intval($userid);
        if (strlen($username) < 4 || strlen($username) > 30) return array('success'=>false, 'message'=>_("Username length error"));

        if (!ctype_alnum($username)) return array('success'=>false, 'message'=>_("Username must only contain a-z and 0-9 characters"));

        $result = $this->mysqli->query("SELECT id FROM users WHERE username = '$username'");
        $row = $result->fetch_array();
        if (!$row[0]) 
        {
            $this->mysqli->query("UPDATE users SET username = '$username' WHERE id = '$userid'");
            return array('success'=>true, 'message'=>_("Username updated"));
        }
        else
        {
            return array('success'=>false, 'message'=>_("Username already exists"));
        }
    }

    public function change_email($userid, $email)
    {
        if (isset($_SESSION['cookielogin']) && $_SESSION['cookielogin']==true) return array('success'=>false, 'message'=>_("As your using a cookie based remember me login, please logout and log back in to change email"));

        $userid = intval($userid);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return array('success'=>false, 'message'=>_("Email address format error"));

        $this->mysqli->query("UPDATE users SET email = '$email' WHERE id = '$userid'");
        return array('success'=>true, 'message'=>_("Email updated"));
    }

    //---------------------------------------------------------------------------------------
    // Get by userid methods
    //---------------------------------------------------------------------------------------

    public function get_convert_status($userid)
    {
        $userid = intval($userid);
        $result = $this->mysqli->query("SELECT `convert` FROM users WHERE id = '$userid';");
        $row = $result->fetch_array();
        return array('convert'=>(int)$row['convert']);
    }

    public function get_username($userid)
    {
        $userid = intval($userid);
        $result = $this->mysqli->query("SELECT username FROM users WHERE id = '$userid';");
        $row = $result->fetch_array();
        return $row['username'];
    }

    public function get_apikey_read($userid)
    {
        $userid = intval($userid);
        $result = $this->mysqli->query("SELECT `apikey_read` FROM users WHERE `id`='$userid'");
        $row = $result->fetch_object();
        return $row->apikey_read;
    }

    public function get_apikey_write($userid)
    {
        $userid = intval($userid);
        $result = $this->mysqli->query("SELECT `apikey_write` FROM users WHERE `id`='$userid'");
        $row = $result->fetch_object();
        return $row->apikey_write;
    }

    public function get_lang($userid)
    {
        $userid = intval($userid);
        $result = $this->mysqli->query("SELECT lang FROM users WHERE id = '$userid';"); 
        $row = $result->fetch_array();
        return $row['lang'];
    }

    public function get_timezone($userid)
    {
        $userid = intval($userid);
        $result = $this->mysqli->query("SELECT timezone FROM users WHERE id = '$userid';");
        $row = $result->fetch_object();
        return intval($row->timezone);
    }

    public function get_salt($userid)
    {
        $userid = intval($userid);
        $result = $this->mysqli->query("SELECT salt FROM users WHERE id = '$userid'");
        $row = $result->fetch_object();
        return $row->salt;
    }

    //---------------------------------------------------------------------------------------
    // Get by other paramater methods
    //---------------------------------------------------------------------------------------

    public function get_id($username)
    {
        if (!ctype_alnum($username)) return false;

        $result = $this->mysqli->query("SELECT id FROM users WHERE username = '$username';");
        $row = $result->fetch_array();
        return $row['id'];
    }

    //---------------------------------------------------------------------------------------
    // Set by id methods
    //---------------------------------------------------------------------------------------

    public function set_convert_status($userid)
    {
        $userid = intval($userid);
        $this->mysqli->query("UPDATE users SET `convert` = '1' WHERE id='$userid'");
        return array('convert'=>1);
    }

    public function set_user_lang($userid, $lang)
    {
        $this->mysqli->query("UPDATE users SET lang = '$lang' WHERE id='$userid'");
    }

    public function set_timezone($userid,$timezone)
    {
        $userid = intval($userid);
        $timezone = intval($timezone);
        $this->mysqli->query("UPDATE users SET timezone = '$timezone' WHERE id='$userid'");
    }

    //---------------------------------------------------------------------------------------
    // Special methods
    //---------------------------------------------------------------------------------------

    public function get($userid)
    {
        $userid = intval($userid);
        $result = $this->mysqli->query("SELECT id,username,email,gravatar,name,location,timezone,language,bio FROM users WHERE id=$userid");
        $data = $result->fetch_object();
        return $data;
    }

    public function set($userid,$data)
    {
        // Validation
        $userid = intval($userid);
        $gravatar = preg_replace('/[^\w\s-.@]/','',$data->gravatar);
        $name = preg_replace('/[^\w\s-.]/','',$data->name);
        $location = preg_replace('/[^\w\s-.]/','',$data->location);
        $timezone = intval($data->timezone);
        $language = preg_replace('/[^\w\s-.]/','',$data->language); $_SESSION['lang'] = $language;
        $bio = preg_replace('/[^\w\s-.]/','',$data->bio);

        $result = $this->mysqli->query("UPDATE users SET gravatar = '$gravatar', name = '$name', location = '$location', timezone = '$timezone', language = '$language', bio = '$bio' WHERE id='$userid'");
    }

    // Generates a new random read apikey
    public function new_apikey_read($userid)
    {
        $userid = intval($userid);
        $apikey = md5(uniqid(mt_rand(), true));
        $this->mysqli->query("UPDATE users SET apikey_read = '$apikey' WHERE id='$userid'");
        return $apikey;
    }

    // Generates a new random write apikey
    public function new_apikey_write($userid)
    {
        $userid = intval($userid);
        $apikey = md5(uniqid(mt_rand(), true));
        $this->mysqli->query("UPDATE users SET apikey_write = '$apikey' WHERE id='$userid'");
        return $apikey;
    }
}
