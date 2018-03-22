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
    private $redis;
    private $log;

    public function __construct($mysqli,$redis)
    {
        //copy the settings value, otherwise the enable_rememberme will always be false.
        global $enable_rememberme;
        $this->enable_rememberme = $enable_rememberme;

        $this->mysqli = $mysqli;

        require "Modules/user/rememberme_model.php";
        $this->rememberme = new Rememberme($mysqli);

        $this->redis = $redis;
        $this->log = new EmonLogger(__FILE__);
    }

    //---------------------------------------------------------------------------------------
    // Core session methods
    //---------------------------------------------------------------------------------------

    public function apikey_session($apikey_in)
    {
        $session = array();
        
        // 1. Only allow alphanumeric characters
        // if (!ctype_alnum($apikey_in)) return $session;
        
        // 2. Only allow 32 character length
        if (strlen($apikey_in)!=32) return array();

        //----------------------------------------------------
        // Check redis cache first
        //----------------------------------------------------
        if($this->redis)
        {
            if ($this->redis->exists("writeapikey:$apikey_in")) {
                $session['userid'] = $this->redis->get("writeapikey:$apikey_in");
                $session['read'] = 1;
                $session['write'] = 1;
                $session['admin'] = 0;
                $session['lang'] = "en";      // API access is always in english
                $session['username'] = "API"; // TBD
                return $session;
            }
            
            if ($this->redis->exists("readapikey:$apikey_in")) {
                $session['userid'] = $this->redis->get("readapikey:$apikey_in");
                $session['read'] = 1;
                $session['write'] = 0;
                $session['admin'] = 0;
                $session['lang'] = "en";      // API access is always in english
                $session['username'] = "API"; // TBD
                return $session;
            }
        }
        
        //----------------------------------------------------
        // If not in redis check mysql
        //----------------------------------------------------
        $stmt = $this->mysqli->prepare("SELECT id,username FROM users WHERE apikey_write=?");
        $stmt->bind_param("s",$apikey_in);
        $stmt->execute();
        $stmt->bind_result($id,$username);
        $result = $stmt->fetch();
        $stmt->close();

        if ($result && $id>0) {
            $session['userid'] = $id;
            $session['read'] = 1;
            $session['write'] = 1;
            $session['admin'] = 0;
            $session['lang'] = "en"; // API access is always in english
            $session['username'] = $username;
            if ($this->redis) $this->redis->set("writeapikey:$apikey_in",$id);
            return $session;
        }

        $stmt = $this->mysqli->prepare("SELECT id,username FROM users WHERE apikey_read=?");
        $stmt->bind_param("s",$apikey_in);
        $stmt->execute();
        $stmt->bind_result($id,$username);
        $result = $stmt->fetch();
        $stmt->close();
        
        if ($result && $id>0) {
            $session['userid'] = $id;
            $session['read'] = 1;
            $session['write'] = 0;
            $session['admin'] = 0;
            $session['lang'] = "en"; // API access is always in english
            $session['username'] = $username;
            if ($this->redis) $this->redis->set("readapikey:$apikey_in",$id);
            return $session;
        }
        
        return array();
    }
    
    public function get_id_from_apikey($apikey_in) 
    {
        if (strlen($apikey_in)!=32) return false;
        // if (!ctype_alnum($apikey_in)) return false;
        
        $stmt = $this->mysqli->prepare("SELECT id FROM users WHERE apikey_read=? OR apikey_write=?");
        $stmt->bind_param("ss",$apikey_in,$apikey_in);
        $stmt->execute();
        $stmt->bind_result($id);
        $stmt->fetch();
        $stmt->close();
        return $id;
    }

    public function emon_session_start()
    {
        // useful for testing session and rememberme
        // ini_set('session.gc_maxlifetime', 20);
        // session_set_cookie_params(20);
        
        session_start();

        if ($this->enable_rememberme)
        {
            if (!empty($_SESSION['userid'])) {
                // if rememberme emoncms cookie exists but is not valid then
                // a valid cookie is a cookie who's userid, token and persistant token match a record in the db
                
                if ((isset($_SESSION['cookielogin']) && $_SESSION['cookielogin']==true) && !$this->rememberme->cookieIsValid($_SESSION['userid'])) {
                    $this->logout();
                }
            } else {
                // No session exists, try remember me login
                $loginresult = $this->rememberme->login();
                if ($loginresult)
                {
                    // 28/04/17: Changed explicitly stated fields to load all with * in order to access startingpage
                    // without cuasing an error if it has not yet been created in the database.
                    // SELECT id,username,admin,language,startingpage FROM users WHERE id = '$loginresult'
                    $loginresult = (int) $loginresult;
                    $result = $this->mysqli->query("SELECT * FROM users WHERE id = '$loginresult'");
                    if ($result->num_rows < 1) {
                        $this->logout(); // user id does not exist
                    } else {
                        $userData = $result->fetch_object();
                        if ($userData->id != 0)
                        {
                            $_SESSION['userid'] = $userData->id;
                            $_SESSION['username'] = $userData->username;
                            $_SESSION['read'] = 1;
                            $_SESSION['write'] = 1;
                            //$_SESSION['admin'] = $userData->admin; // Admin mode requires user to login manualy
                            $_SESSION['lang'] = $userData->language;
                            if (isset($userData->startingpage)) $_SESSION['startingpage'] = $userData->startingpage;
                            // There is a chance that an attacker has stolen the login token, so we store
                            // the fact that the user was logged in via RememberMe (instead of login form)
                            $_SESSION['cookielogin'] = true;
                        }
                    }
                } else {
                    // if($this->rememberme->loginTokenWasInvalid()) {
                    //    $this->logout(); // Stolen
                    // }
                }
            }
        }
        
        if (isset($_SESSION['admin'])) $session['admin'] = $_SESSION['admin']; else $session['admin'] = 0;
        if (isset($_SESSION['read'])) $session['read'] = $_SESSION['read']; else $session['read'] = 0;
        if (isset($_SESSION['write'])) $session['write'] = $_SESSION['write']; else $session['write'] = 0;
        if (isset($_SESSION['userid'])) $session['userid'] = $_SESSION['userid']; else $session['userid'] = 0;
        if (isset($_SESSION['lang'])) $session['lang'] = $_SESSION['lang']; else $session['lang'] = '';
        if (isset($_SESSION['startingpage'])) $session['startingpage'] = $_SESSION['startingpage']; else $session['startingpage'] = '';
        if (isset($_SESSION['username'])) $session['username'] = $_SESSION['username']; else $session['username'] = 'REMEMBER_ME';
        if (isset($_SESSION['cookielogin'])) $session['cookielogin'] = $_SESSION['cookielogin']; else $session['cookielogin'] = 0;

        return $session;
    }


    public function register($username, $password, $email)
    {
        // Input validation, sanitisation and error reporting
        if (!$username || !$password || !$email) return array('success'=>false, 'message'=>_("Missing username, password or email parameter"));
        if (!ctype_alnum($username)) return array('success'=>false, 'message'=>_("Username must only contain a-z and 0-9 characters"));
        if ($this->get_id($username) != 0) return array('success'=>false, 'message'=>_("Username already exists"));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return array('success'=>false, 'message'=>_("Email address format error"));

        if (strlen($username) < 3 || strlen($username) > 30) return array('success'=>false, 'message'=>_("Username length error"));
        if (strlen($password) < 4 || strlen($password) > 250) return array('success'=>false, 'message'=>_("Password length error"));

        // If we got here the username, password and email should all be valid

        $hash = hash('sha256', $password);
        $salt = md5(uniqid(mt_rand(), true));
        $password = hash('sha256', $salt . $hash);

        $apikey_write = md5(uniqid(mt_rand(), true));
        $apikey_read = md5(uniqid(mt_rand(), true));

        $stmt = $this->mysqli->prepare("INSERT INTO users ( username, password, email, salt ,apikey_read, apikey_write, admin ) VALUES (?,?,?,?,?,?,0)");
        $stmt->bind_param("ssssss", $username, $password, $email, $salt, $apikey_read, $apikey_write);
        if (!$stmt->execute()) {
            $stmt->close();
            return array('success'=>false, 'message'=>_("Error creating user"));
        }

        // Make the first user an admin
        $userid = $this->mysqli->insert_id;
        if ($userid == 1) $this->mysqli->query("UPDATE users SET admin = 1 WHERE id = '1'");

        $stmt->close();
        return array('success'=>true, 'userid'=>$userid, 'apikey_read'=>$apikey_read, 'apikey_write'=>$apikey_write);
    }

    public function login($username, $password, $remembermecheck)
    {
        $remembermecheck = (int) $remembermecheck;

        if (!$username || !$password) return array('success'=>false, 'message'=>_("Username or password empty"));

        // filter out all except for alphanumeric white space and dash
        // if (!ctype_alnum($username))
        $username_out = preg_replace('/[^\p{N}\p{L}_\s-]/u','',$username);

        if ($username_out!=$username) return array('success'=>false, 'message'=>_("Username must only contain a-z 0-9 dash and underscore, if you created an account before this rule was in place enter your username without the non a-z 0-9 dash underscore characters to login and feel free to change your username on the profile page."));

        // 28/04/17: Changed explicitly stated fields to load all with * in order to access startingpage
        // without cuasing an error if it has not yet been created in the database.
        if (!$stmt = $this->mysqli->prepare("SELECT id,password,salt,apikey_write,admin,language,startingpage FROM users WHERE username=?")) {
            return array('success'=>false, 'message'=>_("Database error, you may need to run database update"));
        }
        $stmt->bind_param("s",$username);
        $stmt->execute();
        
        $stmt->bind_result($userData_id,$userData_password,$userData_salt,$userData_apikey_write,$userData_admin,$userData_language,$userData_startingpage);
        $result = $stmt->fetch();
        $stmt->close();
        
        //$result = $stmt->get_result();
        //$userData = $result->fetch_object();
        //$stmt->close();

        if (!$result) return array('success'=>false, 'message'=>_("Username does not exist"));
        
        $hash = hash('sha256', $userData_salt . hash('sha256', $password));

        if ($hash != $userData_password)
        {
            return array('success'=>false, 'message'=>_("Incorrect password, if your sure its correct try clearing your browser cache"));
        }
        else
        {
            session_regenerate_id();
            $_SESSION['userid'] = $userData_id;
            $_SESSION['username'] = $username;
            $_SESSION['read'] = 1;
            $_SESSION['write'] = 1;
            $_SESSION['admin'] = $userData_admin;
            $_SESSION['lang'] = $userData_language;
            if (isset($userData_startingpage)) $_SESSION['startingpage'] = $userData_startingpage;
                            
            if ($this->enable_rememberme) {
                if ($remembermecheck==true) {
                    if (!$this->rememberme->createCookie($userData_id)) {
                        $this->logout();
                        return array('success'=>false, 'message'=>_("Error creating rememberme cookie, try login without rememberme"));
                    }
                } else {
                    $this->rememberme->clearCookie();
                }
            }

            return array('success'=>true, 'message'=>_("Login successful"));
        }
    }

    // Authorization API. returns user write and read apikey on correct username + password
    // This is useful for using emoncms with 3rd party applications

    public function get_apikeys_from_login($username, $password)
    {
        if (!$username || !$password) return array('success'=>false, 'message'=>_("Username or password empty"));
        $username_out = preg_replace('/[^\p{N}\p{L}_\s-]/u','',$username);
        if ($username_out!=$username) return array('success'=>false, 'message'=>_("Username must only contain a-z 0-9 dash and underscore"));

        $stmt = $this->mysqli->prepare("SELECT id,password,salt,apikey_write,apikey_read FROM users WHERE username=?");
        $stmt->bind_param("s",$username);
        $stmt->execute();
        //$result = $stmt->get_result();
        //$userData = $result->fetch_object();
        //$stmt->close();
        
        $stmt->bind_result($userData_id,$userData_password,$userData_salt,$userData_apikey_write,$userData_apikey_read);
        $result = $stmt->fetch();
        $stmt->close();
        
        if (!$result) return array('success'=>false, 'message'=>_("Incorrect authentication"));
       
        $hash = hash('sha256', $userData_salt . hash('sha256', $password));

        if ($hash != $userData_password) {
            return array('success'=>false, 'message'=>_("Incorrect authentication"));
        } else {
            return array('success'=>true, 'userid'=>$userData_id, 'apikey_write'=>$userData_apikey_write, 'apikey_read'=>$userData_apikey_read);
        }
    }

    public function logout()
    {
        $this->log->info("logout");
        if ($this->enable_rememberme) $this->rememberme->clearCookie();
        session_unset();
        //session_regenerate_id(true);
        session_destroy();
    }

    public function change_password($userid, $old, $new)
    {
        $userid = (int) $userid;

        if (strlen($old) < 4 || strlen($old) > 250) return array('success'=>false, 'message'=>_("Password length error"));
        if (strlen($new) < 4 || strlen($new) > 250) return array('success'=>false, 'message'=>_("Password length error"));

        // 1) check that old password is correct
        $result = $this->mysqli->query("SELECT password, salt FROM users WHERE id = '$userid'");
        $row = $result->fetch_object();
        $hash = hash('sha256', $row->salt . hash('sha256', $old));

        if ($hash == $row->password)
        {
            // 2) Save new password
            $hash = hash('sha256', $new);
            $salt = md5(uniqid(rand(), true));
            $password = hash('sha256', $salt . $hash);

            $stmt = $this->mysqli->prepare("UPDATE users SET password = ?, salt = ? WHERE id = ?");
            $stmt->bind_param("ssi", $password, $salt, $userid);
            $stmt->execute();
            $stmt->close();
            
            return array('success'=>true);
        }
        else
        {
            return array('success'=>false, 'message'=>_("Old password incorect"));
        }
    }

    public function passwordreset($username,$emailto)
    {
        $username_out = preg_replace('/[^\p{N}\p{L}_\s-]/u','',$username);
        if (!filter_var($emailto, FILTER_VALIDATE_EMAIL)) return array('success'=>false, 'message'=>_("Email address format error"));

        $stmt = $this->mysqli->prepare("SELECT id FROM users WHERE username=? AND email=?");
        $stmt->bind_param("ss",$username_out,$emailto);
        $stmt->execute();
        $stmt->bind_result($userid);
        $stmt->fetch();
        $stmt->close();
        
        if ($userid!==false && $userid>0)
        {
            // Generate new random password
            $newpass = hash('sha256',md5(uniqid(rand(), true)));
            $newpass = substr($newpass, 0, 10);

            // Hash and salt
            $hash = hash('sha256', $newpass);
            $salt = md5(uniqid(rand(), true));
            $password = hash('sha256', $salt . $hash);

            // Save password and salt
            $stmt = $this->mysqli->prepare("UPDATE users SET password = ?, salt = ? WHERE id = ?");
            $stmt->bind_param("ssi", $password, $salt, $userid);
            $stmt->execute();
            $stmt->close();
            //------------------------------------------------------------------------------
            global $enable_password_reset;
            if ($enable_password_reset==true)
            {
                require "Lib/email.php";
                $email = new Email();
                //$email->from(from);
                $email->to($emailto);
                $email->subject('Emoncms password reset');
                $email->body("<p>A password reset was requested for your emoncms account.</p><p>You can now login with password: $newpass </p>");
                $result = $email->send();
                if (!$result['success']) {
                    $this->log->error("Email send returned error. emailto=" + $emailto . " message='" . $result['message'] . "'");
                } else {
                    $this->log->info("Email sent to $emailto");
                }
            }
            //------------------------------------------------------------------------------

            // Sent email with $newpass to $email
            return array('success'=>true, 'message'=>"Password recovery email sent!");
        }

        return array('success'=>false, 'message'=>"An error occured");
    }

    public function change_username($userid, $username)
    {
        if (isset($_SESSION['cookielogin']) && $_SESSION['cookielogin']==true) return array('success'=>false, 'message'=>_("As you are using a cookie based remember me login, please logout and log back in to change username"));

        $userid = (int) $userid;
        if (strlen($username) < 3 || strlen($username) > 30) return array('success'=>false, 'message'=>_("Username length error"));

        if (!ctype_alnum($username)) return array('success'=>false, 'message'=>_("Username must only contain a-z and 0-9 characters"));

        $userid_from_username = $this->get_id($username);

        if (!$userid_from_username)
        {
            $stmt = $this->mysqli->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $username, $userid);
            $stmt->execute();
            $stmt->close();
            return array('success'=>true, 'message'=>_("Username updated"));
        }
        else
        {
            return array('success'=>false, 'message'=>_("Username already exists"));
        }
    }

    public function change_email($userid, $email)
    {
        if (isset($_SESSION['cookielogin']) && $_SESSION['cookielogin']==true) return array('success'=>false, 'message'=>_("As you are using a cookie based remember me login, please logout and log back in to change email"));

        $userid = (int) $userid;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return array('success'=>false, 'message'=>_("Email address format error"));

        $stmt = $this->mysqli->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $email, $userid);
        $stmt->execute();
        $stmt->close();
        return array('success'=>true, 'message'=>_("Email updated"));
    }

    //---------------------------------------------------------------------------------------
    // Get by userid methods
    //---------------------------------------------------------------------------------------
    public function get_username($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT username FROM users WHERE id = '$userid';");
        $row = $result->fetch_array();
        return $row['username'];
    }

    public function get_name($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT name FROM users WHERE id = '$userid';");
        $row = $result->fetch_array();
        return $row['name'];
    }

    public function get_email($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT email FROM users WHERE id = '$userid';");
        $row = $result->fetch_array();
        return $row['email'];
    }

    public function get_apikey_read($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT `apikey_read` FROM users WHERE `id`='$userid'");
        if (!$row = $result->fetch_object()) return false;
        return $row->apikey_read;
    }

    public function get_apikey_write($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT `apikey_write` FROM users WHERE `id`='$userid'");
        if (!$row = $result->fetch_object()) return false;
        return $row->apikey_write;
    }

    public function get_lang($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT language FROM users WHERE id = '$userid';");
        $row = $result->fetch_array();
        return $row['language'];
    }

    public function get_timezone_offset($userid)
    {
        $userid = (int) $userid;
        if (!$userid) return false;
        $result = $this->mysqli->query("SELECT timezone FROM users WHERE id = '$userid';");
        $row = $result->fetch_object();
        $now = new DateTime();
        $now->setTimezone(new DateTimeZone($row->timezone));
        return intval($now->getOffset()); // Will return seconds offset from GMT
    }

    public function get_timezone($userid)
    {
        $userid = (int) $userid;
        if (!$userid) return false;
        $result = $this->mysqli->query("SELECT timezone FROM users WHERE id = '$userid';");
        $row = $result->fetch_object();
        return $row->timezone;
    }

    // List supported PHP timezones
    public function get_timezones()
    {
        static $timezones = null;

        if ($timezones === null) {
            $timezones = array();
            $now = new DateTime();

            foreach (DateTimeZone::listIdentifiers() as $timezone) {
                $now->setTimezone(new DateTimeZone($timezone));
                $offset = $now->getOffset();
                $hours = intval($offset / 3600);
                $minutes = abs(intval($offset % 3600 / 60));
                $gmt_offset_text = 'GMT ' . ($offset ? sprintf('%+03d:%02d', $hours, $minutes) : '+00:00');
                $timezones[] =  array('id'=>$timezone, 'gmt_offset_secs'=>$offset, 'gmt_offset_text'=>$gmt_offset_text);
            }
        }
        return $timezones;
    }

    public function get_salt($userid)
    {
        $userid = (int) $userid;
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
        
        $stmt = $this->mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($id);
        $stmt->fetch();
        $stmt->close();
        
        return $id;
    }

    //---------------------------------------------------------------------------------------
    // Set by id methods
    //---------------------------------------------------------------------------------------
    public function set_user_lang($userid, $lang)
    {   
        $stmt = $this->mysqli->prepare("UPDATE users SET language = ? WHERE id = ?");
        $stmt->bind_param("si", $lang, $userid);
        $stmt->execute();
        $stmt->close();
    }

    public function set_timezone($userid,$timezone)
    {
        $userid = (int) $userid;
        $timezone = preg_replace('/[^\w-.\\/_]/','',$timezone);
        
        $stmt = $this->mysqli->prepare("UPDATE users SET timezone = ? WHERE id = ?");
        $stmt->bind_param("si", $timezone, $userid);
        $stmt->execute();
        $stmt->close();
    }

    //---------------------------------------------------------------------------------------
    // Special methods
    //---------------------------------------------------------------------------------------

    public function get($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT id,username,email,gravatar,name,location,timezone,language,bio,startingpage,apikey_write,apikey_read,tags FROM users WHERE id=$userid");
        if (!$result) return array("success" => false, "message" => "Error fetching user data, you may need to run database update");
        $data = $result->fetch_object();
        return $data;
    }

    public function set($userid,$data)
    {
        // Validation
        $userid = (int) $userid;

        $gravatar = preg_replace('/[^\w\s-.@]/','',$data->gravatar);
        $name = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$data->name);
        $location = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$data->location);
        $timezone = preg_replace('/[^\w-.\\/_]/','',$data->timezone);
        $bio = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$data->bio);
        $language = preg_replace('/[^\w\s-.]/','',$data->language);
        $tags = isset($data->tags) == false ? '' : preg_replace('/[^{}",:\w\s-.]/','', $data->tags);
        
        $startingpage = preg_replace('/[^\p{N}\p{L}_\s-?#=\/]/u','',$data->startingpage);
        
        $_SESSION['lang'] = $language;

        $stmt = $this->mysqli->prepare("UPDATE users SET gravatar = ?, name = ?, location = ?, timezone = ?, language = ?, bio = ?, startingpage = ?, tags = ? WHERE id = ?");
        $stmt->bind_param("ssssssssi", $gravatar, $name, $location, $timezone, $language, $bio, $startingpage, $tags, $userid);
        if (!$stmt->execute()) {
            $stmt->close();
            return array('success'=>false, 'message'=>_("Error updating user info"));
        }
        $stmt->close();
    }

    // Generates a new random read apikey
    public function new_apikey_read($userid)
    {
        $userid = (int) $userid;
        $apikey = md5(uniqid(mt_rand(), true));
        
        $stmt = $this->mysqli->prepare("UPDATE users SET apikey_read = ? WHERE id = ?");
        $stmt->bind_param("si", $apikey, $userid);
        $stmt->execute();
        $stmt->close();
        
        return $apikey;
    }

    // Generates a new random write apikey
    public function new_apikey_write($userid)
    {
        $userid = (int) $userid;
        $apikey = md5(uniqid(mt_rand(), true));
        
        $stmt = $this->mysqli->prepare("UPDATE users SET apikey_write = ? WHERE id = ?");
        $stmt->bind_param("si", $apikey, $userid);
        $stmt->execute();
        $stmt->close();
        
        return $apikey;
    }

    public function get_number_of_users()
    {
        $result = $this->mysqli->query("SELECT COUNT(*) FROM users");
        $row = $result->fetch_row();
        return $row[0];
    }
}

