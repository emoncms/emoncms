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
    // Cache directory for the server side gravatar proxy, alongside the other
    // emoncms data directories. See gravatar_enabled(): where this does not
    // exist or is not writable the gravatar feature is disabled.
    const GRAVATAR_CACHE_DIR = '/var/opt/emoncms/gravatar';

    private $mysqli;
    private $rememberme;
    private $enable_rememberme = false;
    private $email_verification = false;
    private $redis;
    private $log;
    public $appname;

    private $ui_read_only_mode = false;
    private $disable_rate_limiting = false;

    public function __construct($mysqli,$redis)
    {
        //copy the settings value, otherwise the enable_rememberme will always be false.
        global $settings;
        $this->enable_rememberme = $settings["interface"]["enable_rememberme"];
        $this->email_verification = $settings["interface"]["email_verification"];
        $this->appname = $settings["interface"]["appname"];
        $this->ui_read_only_mode = isset($settings["ui_read_only_mode"]) ? $settings["ui_read_only_mode"] : false;
        $this->disable_rate_limiting = !empty($settings["interface"]["disable_rate_limiting"]);

        $this->mysqli = $mysqli;

        require_once "Modules/user/rememberme_model.php";
        $this->rememberme = new Rememberme($mysqli);

        $this->redis = $redis;
        $this->log = new EmonLogger(__FILE__);
    }
    
    public function set_email_verification($email_verification) {
        $this->email_verification = $email_verification;
    }

    //---------------------------------------------------------------------------------------
    // Core session methods
    //---------------------------------------------------------------------------------------

    public function apikey_session($apikey_in)
    {
        $session = array();
        
        // 1. Only allow hexadecimal characters (keys are produced by bin2hex)
        if (!ctype_xdigit($apikey_in)) return $session;
        
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
                $session['gravatar'] = '';
                $session['public_userid'] = 0;
                $session['public_username'] = "";
                $session['apikey'] = 1;       // authenticated with an apikey rather than an interactive login
                return $session;
            }
            
            if ($this->redis->exists("readapikey:$apikey_in")) {
                $session['userid'] = $this->redis->get("readapikey:$apikey_in");
                $session['read'] = 1;
                $session['write'] = 0;
                $session['admin'] = 0;
                $session['lang'] = "en";      // API access is always in english
                $session['username'] = "API"; // TBD
                $session['gravatar'] = '';
                $session['public_userid'] = 0;
                $session['public_username'] = "";
                $session['apikey'] = 1;       // authenticated with an apikey rather than an interactive login
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
            $session['gravatar'] = '';
            $session['public_userid'] = 0;
            $session['public_username'] = "";
            $session['apikey'] = 1;       // authenticated with an apikey rather than an interactive login
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
            $session['gravatar'] = '';
            $session['public_userid'] = 0;
            $session['public_username'] = "";
            $session['apikey'] = 1;       // authenticated with an apikey rather than an interactive login
            if ($this->redis) $this->redis->set("readapikey:$apikey_in",$id);
            return $session;
        }
        
        return array();
    }
    
    public function get_id_from_apikey($apikey_in) 
    {
        if (strlen($apikey_in)!=32) return false;
        if (!ctype_xdigit($apikey_in)) return false;
        
        $stmt = $this->mysqli->prepare("SELECT id FROM users WHERE apikey_read=? OR apikey_write=?");
        $stmt->bind_param("ss",$apikey_in,$apikey_in);
        $stmt->execute();
        $stmt->bind_result($id);
        $stmt->fetch();
        $stmt->close();
        return $id;
    }

    public function get_uuid($userid)
    {       
        $userid = (int) $userid;
        // return unique id associated to user and generate if missing
        $request = "SELECT uuid FROM users WHERE `id` = '$userid'";
        try {
            $result = $this->mysqli->query($request);
        } catch (Exception $e) {
            $message = "PLEASE UPDATE THE DATABASE STRUCTURE IN THE ADMIN MODULE !!";
            $this->log->error("exception $e - $message");
            return array('success'=>false, 'message'=>$message);
        }
        if ($row = $result->fetch_object()) {
            if (!($row->uuid)) {
                $uuid = guidv4();
                $stmt = $this->mysqli->prepare("UPDATE users SET uuid = ? WHERE id = ?");
                $stmt->bind_param("si", $uuid, $userid);
                if (!$stmt->execute()) {
                    $error = $this->mysqli->error;
                    $this->log->error($error);
                    $stmt->close();
                    return array('success'=>false, 'message'=>"ERROR COULD NOT SAVE UUID");
                }
                $stmt->close();
            } else {
                $uuid = $row->uuid;
            }
            return array('success'=>true, 'message'=>$uuid);
        }
    }

    public function emon_session_start()
    {
        // useful for testing session and rememberme
        // ini_set('session.gc_maxlifetime', 20);
        // session_set_cookie_params(20);
        
        $cookie_params = session_get_cookie_params();

        //name of cookie 
        session_name('EMONCMS_SESSID'); 
        //get subdir installation 
        $cookie_params['path'] = dirname($_SERVER['SCRIPT_NAME']);
        // Add a slash if the last character isn't already a slash
        if (substr($cookie_params['path'], -1) !== '/')
            $cookie_params['path'] .= '/';
        //not pass cookie to javascript 
        $cookie_params['httponly'] = true;
        $cookie_params['samesite'] = 'Strict';
        
        if (is_https()) {
            $cookie_params['secure'] = true;
        }
        
        if (PHP_VERSION_ID>=70300) {
            session_set_cookie_params($cookie_params);
        } else {
            session_set_cookie_params(
                $cookie_params['lifetime'],
                $cookie_params['path'],
                $cookie_params['domain'],
                $cookie_params['secure'],
                $cookie_params['httponly']
            );
        }
        
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
                            // Regenerate session ID when logging in via remember-me token
                            session_regenerate_id(true);

                            $_SESSION['userid'] = $userData->id;
                            $_SESSION['username'] = $userData->username;
                            $_SESSION['read'] = 1;
                            $_SESSION['write'] = 1;
                            $_SESSION['admin'] = $userData->admin;
                            $_SESSION['lang'] = $userData->language;
                            $_SESSION['timezone'] = $userData->timezone;
                            if (isset($userData->startingpage)) $_SESSION['startingpage'] = $userData->startingpage;
                            // There is a chance that an attacker has stolen the login token, so we store
                            // the fact that the user was logged in via RememberMe (instead of login form)
                            $_SESSION['cookielogin'] = true;
                        }
                    }
                } else {
                    if($this->rememberme->loginTokenWasInvalid()) {
                        $this->logout(); // Stolen
                    }
                }
            }
        }
        
        if (isset($_SESSION['admin'])) $session['admin'] = $_SESSION['admin']; else $session['admin'] = 0;
        if (isset($_SESSION['read'])) $session['read'] = $_SESSION['read']; else $session['read'] = 0;
        if (isset($_SESSION['write'])) $session['write'] = $_SESSION['write']; else $session['write'] = 0;
        if (isset($_SESSION['userid'])) $session['userid'] = $_SESSION['userid']; else $session['userid'] = 0;
        if (isset($_SESSION['lang'])) $session['lang'] = $_SESSION['lang']; else $session['lang'] = '';
        if (isset($_SESSION['timezone'])) $session['timezone'] = $_SESSION['timezone']; else $session['timezone'] = '';
        if (isset($_SESSION['startingpage'])) $session['startingpage'] = $_SESSION['startingpage']; else $session['startingpage'] = '';
        if (isset($_SESSION['gravatar'])) $session['gravatar'] = $_SESSION['gravatar']; else $session['gravatar'] = '';
        if (isset($_SESSION['username'])) $session['username'] = $_SESSION['username']; else $session['username'] = 'REMEMBER_ME';
        if (isset($_SESSION['cookielogin'])) $session['cookielogin'] = $_SESSION['cookielogin']; else $session['cookielogin'] = 0;
        if (isset($_SESSION['emailverified'])) $session['emailverified'] = $_SESSION['emailverified'];

        $session['public_userid'] = 0;
        $session['public_username'] = "";

        // Read only mode
        if ($this->ui_read_only_mode) {
            if (!$session['admin'] && $session['write']) $session['write'] = 0;
        }

        return $session;
    }


    public function register($username, $password, $email, $timezone)
    {
        if ($this->ui_read_only_mode) {
            return array('success'=>false, 'message'=>tr("System is in read-only mode"));
        }

        if ($this->is_rate_limited('register', 5, 600)) return array('success'=>false, 'message'=>tr("Too many attempts, please try again later"));

        // Input validation, sanitisation and error reporting
        if (!$username || !$password || !$email) return array('success'=>false, 'message'=>tr("Missing username, password or email parameter"));

        $result = $this->is_valid_username($username);
        if (!$result['success']) return $result;

        // Check if username already exists
        if ($this->get_id($username) != 0) return array('success'=>false, 'message'=>tr("Username already exists"));

        $result = $this->is_valid_email($email);
        if (!$result['success']) return $result;

        $result = $this->is_valid_password($password);
        if (!$result['success']) return $result;
        
        if (!$this->timezone_valid($timezone)) {
            // use default UTC timezone if timezone is not valid
            $timezone = "UTC";
        }

        // -------------------------------------------

        // If we got here the username, password and email should all be valid

        // bcrypt and argon2id both carry their own salt inside the hash, so the
        // salt column stays empty for every account created from here on
        // whichever settings['password']['algo'] selects
        $password = hash_password($password);
        $salt = '';

        $apikey_write = generate_secure_key(16);
        $apikey_read = generate_secure_key(16);
        $uuid = guidv4();

        $stmt = $this->mysqli->prepare("INSERT INTO users ( username, password, email, salt ,apikey_read, apikey_write, timezone, uuid, admin) VALUES (?,?,?,?,?,?,?,?,0)");
        $stmt->bind_param("ssssssss", $username, $password, $email, $salt, $apikey_read, $apikey_write, $timezone, $uuid);
        if (!$stmt->execute()) {
            $error = $this->mysqli->error;
            $this->log->error("register: failed to create user username:$username error:$error");
            $stmt->close();
            return array('success'=>false, 'message'=>tr("Error creating user"));
        }

        // Make the first user an admin
        $userid = $this->mysqli->insert_id;
        if ($userid == 1) $this->mysqli->query("UPDATE users SET admin = 1 WHERE id = '1'");
        $stmt->close();
        
        // Email verification
        if ($this->email_verification) {
            $result = $this->send_verification_email($username);
            if ($result['success']) return array('success'=>true, 'userid'=>$userid, 'verifyemail'=>true, 'message'=>"Email verification email sent, please check your inbox");
        } else {
            return array('success'=>true, 'verifyemail'=>false, 'userid'=>$userid, 'apikey_read'=>$apikey_read, 'apikey_write'=>$apikey_write);
        }        
    }
    
    public function send_verification_email($username)
    {
        $result = $this->is_valid_username($username);
        if (!$result['success']) return $result;

        // check that username exists and load email and verification status
        if (!$stmt = $this->mysqli->prepare("SELECT id,email,email_verified FROM users WHERE username=?")) {
            return array('success'=>false, 'message'=>tr("Database error, you may need to run database update"));
        }
        $stmt->bind_param("s",$username);
        $stmt->execute();
        
        $stmt->bind_result($id,$email,$email_verified);
        $result = $stmt->fetch();
        $stmt->close();
        
        // exit if user does not exist, normalize to same message as invalid verification key to prevent username enumeration
        if (!$result || $id<1) return array('success'=>true, 'message'=>tr("Email verification email sent, please check your inbox"));
        // exit if account is already verified
        if ($email_verified) return array('success'=>true, 'message'=>tr("Email verification email sent, please check your inbox"));
        
        // Create new verification key
        $verification_key = generate_secure_key(32);
        // Save new verification key
        $stmt = $this->mysqli->prepare("UPDATE users SET verification_key=? WHERE id=?");
        $stmt->bind_param("si",$verification_key,$id);
        $stmt->execute();
        $stmt->close();
        
        // Send verification email
        global $path;
        $verification_link = $path."user/verify?key=$verification_key";
        
        // $this->redis->rpush("emailqueue",json_encode(array(
        //    "emailto"=>$email,
        //    "type"=>"passwordrecovery",
        //    "subject"=>'Emoncms email verification',
        //    "message"=>"<p>To complete emoncms registration please verify your email by following this link: <a href='$verification_link'>$verification_link</a></p>"
        // )));
        
        require "Lib/email.php";
        $emailer = new Email();
        $emailer->to(array($email));
        $emailer->subject(ucfirst($this->appname).' email verification');
        $emailer->body("<p>To complete ".$this->appname." registration please verify your email by following this link: <a href='$verification_link'>$verification_link</a></p>");
        $result = $emailer->send();
        if (!$result['success']) {
            $this->log->error("Email send returned error. emailto=" . $email . " message='" . $result['message'] . "'");
        } else {
            $this->log->info("Email sent to $email");
        }
        
        return array('success'=>true, 'message'=>tr("Email verification email sent, please check your inbox"));
    }
    
    public function verify_email($verification_key)
    {
        if (strlen($verification_key)!=64) return array('success'=>false, 'message'=>tr("Invalid verification key"));
        if (!ctype_xdigit($verification_key)) return array('success'=>false, 'message'=>tr("Invalid verification key"));
        
        $stmt = $this->mysqli->prepare("SELECT id,email_verified FROM users WHERE verification_key=?");
        $stmt->bind_param("s",$verification_key);
        $stmt->execute();
        $stmt->bind_result($id,$email_verified);
        $result = $stmt->fetch();
        $stmt->close();
        $userid = (int) $id;
        
        // Exit if verification key is invalid or user does not exist, normalize to same message as already verified to prevent enumeration
        if (!$result || $userid<1) return array('success'=>false, 'message'=>tr("Invalid verification key"));
        
        // If email is already verified then exit with error
        if ($email_verified) return array('success'=>false, 'message'=>tr("Email already verified"));

        // Else if we are here: verification key is valid, email is not yet verified, so verify email
        $stmt = $this->mysqli->prepare("UPDATE users SET email_verified='1' WHERE id=?");
        $stmt->bind_param("i",$userid);
        $stmt->execute();
        $stmt->close();

        // Return success message
        return array('success'=>true, 'message'=>"Email verified", "userid"=>$id);

    }

    public function login($username, $password, $remembermecheck, $referrer='')
    {
        // Rate limit login attempts to prevent brute force attacks. Limit to 10 failed attempts in 15 minutes.
        if ($this->is_rate_limit_exceeded('login', 10)) return array('success'=>false, 'message'=>tr("Too many attempts, please try again later"));

        // Basic checks
        $remembermecheck = (int) $remembermecheck;
        if (!$username || !$password) return array('success'=>false, 'message'=>tr("Username or password empty"));

        $result = $this->is_valid_username($username);
        if (!$result['success']) return $result;

        // Dont go further if username does not exist.
        if (!$userid = $this->get_id($username)) {
            $this->record_failed_attempt('login', 900);
            $this->log->error("Login: Username does not exist username:$username ip:".get_client_ip_env());
            return array('success'=>false, 'message'=>tr("Incorrect username or password"));
        }
        
        // Fetch the user
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT * FROM users WHERE id = '$userid'");
        if (!$result) return array('success'=>false, 'message'=>tr("Database error"));
        $userData = $result->fetch_object();
        
        // If email verification is required and email is not verified then dont allow login
        if ($this->email_verification && isset($userData->email_verified) && !$userData->email_verified) return array('success'=>false, 'message'=>tr("Please verify email address"));
        
        // Check password. reads all three formats, legacy sha256, bcrypt and
        // argon2id, please see Lib/password.php
        if (!verify_password($password, $userData->password, $userData->salt))
        {
            $this->record_failed_attempt('login', 900);
            $this->log->error("Login: Incorrect password username:$username ip:".get_client_ip_env());
            return array('success'=>false, 'message'=>tr("Incorrect username or password"));
        }
        else
        {
            // Upgrade password hash e.g from sha256 to bcrypt or argon2id.
            // Please see the [password] section of settings.ini.
            $this->upgrade_password_hash($userData->id, $password, $userData->password);

            // Default write access
            if (!isset($userData->access)) $userData->access = 2;

            // If no access via login
            if ($userData->access==0) {
                return array('success'=>false, 'message'=>tr("Login disabled for this account"));
            }
        
            // Ensure session is active before regenerating
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $this->update_last_active($userData->id);

            // The user still has their password, so any reset link sitting in
            // their inbox is no longer needed and should stop working now
            // rather than at the end of its window
            $this->clear_password_reset_token($userData->id);

            session_regenerate_id(true);
            $_SESSION['userid'] = $userData->id;
            $_SESSION['username'] = $username;
            
            if ($userData->access>0) { 
                $_SESSION['read'] = 1;
            }
            if ($userData->access>1) {
                $_SESSION['write'] = 1;
                $_SESSION['admin'] = $userData->admin;
            }
            $_SESSION['lang'] = $userData->language;
            $_SESSION['timezone'] = $userData->timezone;
            $_SESSION['emailverified'] = $userData->email_verified;
            $_SESSION['startingpage'] = $userData->startingpage;
            $_SESSION['gravatar'] = $userData->gravatar;
                                        
            if ($this->enable_rememberme) {
                if ($remembermecheck==true) {
                    if (!$this->rememberme->createCookie($userData->id)) {
                        $this->logout();
                        return array('success'=>false, 'message'=>tr("Error creating rememberme cookie, try login without rememberme and then a database update"));
                    }
                } else {
                    $this->rememberme->clearCookie();
                }
            }
            
            if ($this->redis) $this->redis->hmset("user:".$userData->id,array('apikey_write'=>$userData->apikey_write));

            $safe_referrer = $this->validate_referrer($referrer);
            if ($safe_referrer !== '') {
                $userData->startingpage = $safe_referrer;
            }
            return array('success'=>true, 'message'=>tr("Login successful"), 'startingpage'=>$userData->startingpage);
        }
    }

    // Authorization API. returns user write and read apikey on correct username + password
    // This is useful for using emoncms with 3rd party applications

    public function get_apikeys_from_login($username, $password)
    {
        if ($this->is_rate_limited('auth', 10, 900)) return array('success'=>false, 'message'=>tr("Too many attempts, please try again later"));

        if (!$username || !$password) return array('success'=>false, 'message'=>tr("Username or password empty"));

        $result = $this->is_valid_username($username);
        if (!$result['success']) return $result;

        $stmt = $this->mysqli->prepare("SELECT id,password,salt,apikey_write,apikey_read FROM users WHERE username=?");
        $stmt->bind_param("s",$username);
        $stmt->execute();
        //$result = $stmt->get_result();
        //$userData = $result->fetch_object();
        //$stmt->close();
        
        $stmt->bind_result($userData_id,$userData_password,$userData_salt,$userData_apikey_write,$userData_apikey_read);
        $result = $stmt->fetch();
        $stmt->close();
        
        if (!$result) {
            $ip_address = get_client_ip_env();
            $this->log->error("get_apikeys_from_login: Incorrect authentication:$username ip:$ip_address");
            return array('success'=>false, 'message'=>tr("Incorrect authentication"));
        }
       
        if (!verify_password($password, $userData_password, $userData_salt)) {
            return array('success'=>false, 'message'=>tr("Incorrect authentication"));
        } else {
            // Upgrade here too: an account driven only through this API would
            // otherwise never pass through login() and never migrate
            $this->upgrade_password_hash($userData_id, $password, $userData_password);

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

    /**
     * Replace a stored hash with a current one, after the password has been
     * verified against it.
     *
     * This is how accounts move off the old sha256 scheme: there is no way to
     * convert a stored hash without the password, so each account is upgraded
     * at the one moment the password is in memory, which is a successful
     * authentication. No mass reset, and nothing the account holder notices.
     *
     * The same path also carries every later change to how passwords are
     * hashed: switching settings['password']['algo'] between bcrypt and
     * argon2id, or raising the bcrypt cost or the argon2id memory or time cost.
     * password_needs_upgrade() compares the stored hash against whatever is
     * configured now, so accounts move to it as their owners log in.
     *
     * Never allowed to fail the authentication that triggered it: the caller
     * has already verified the password, so a write failure here means the
     * account stays on the old format and is retried next time, not that the
     * user is refused.
     *
     * @param int    $userid
     * @param string $password verified plaintext
     * @param string $stored   hash it was verified against
     * @return void
     */
    private function upgrade_password_hash($userid, $password, $stored)
    {
        $userid = (int) $userid;
        if ($userid < 1) return;
        if (!password_needs_upgrade($stored)) return;

        try {
            $new_hash = hash_password($password);
            $salt = '';

            // Guarded on the hash it was verified against, so a concurrent
            // password change is not overwritten by this upgrade
            $stmt = $this->mysqli->prepare("UPDATE users SET password=?, salt=? WHERE id=? AND password=?");
            $stmt->bind_param("ssis", $new_hash, $salt, $userid, $stored);
            $stmt->execute();
            $upgraded = $stmt->affected_rows;
            $stmt->close();

            if ($upgraded > 0) {
                // Name the algorithm actually written, not an assumed one: it
                // follows the setting, and falls back to bcrypt where argon2 is
                // unavailable, so this is the record of what the row now holds
                $algo = password_hash_config();
                $this->log->info("upgrade_password_hash: upgraded to ".$algo['name']." userid:$userid");
            }
        } catch (Exception $e) {
            $this->log->warn("upgrade_password_hash failed userid:$userid ".$e->getMessage());
        }
    }

    public function change_password($userid, $old, $new)
    {
        if ($this->is_rate_limited('changepassword', 5, 900)) return array('success'=>false, 'message'=>tr("Too many attempts, please try again later"));

        $userid = (int) $userid;

        $result = $this->is_valid_password($old);
        if (!$result['success']) return $result;

        $result = $this->is_valid_password($new);
        if (!$result['success']) return $result;

        // 1) check that old password is correct
        $result = $this->mysqli->query("SELECT password, salt FROM users WHERE id = '$userid'");
        $row = $result->fetch_object();

        if (verify_password($old, $row->password, $row->salt))
        {
            // 2) Save new password in the configured algorithm, bcrypt or
            // argon2id, and clear any legacy salt: no separate upgrade needed
            // here, the row is rewritten anyway
            $password = hash_password($new);
            $salt = '';

            $stmt = $this->mysqli->prepare("UPDATE users SET password = ?, salt = ? WHERE id = ?");
            $stmt->bind_param("ssi", $password, $salt, $userid);
            $stmt->execute();
            $stmt->close();

            // Invalidate any outstanding reset link: it would otherwise stay
            // redeemable against the password just set
            $this->clear_password_reset_token($userid);

            return array('success'=>true, 'message'=>tr("Password updated successfully"));
        }
        else
        {
            $ip_address = get_client_ip_env();
            $this->log->error("change_password: old password incorect ip:$ip_address");
            return array('success'=>false, 'message'=>tr("Old password incorect"));
        }
    }

    // How long an emailed password reset link stays usable
    private $password_reset_window = 3600;

    // How many reset emails a single account can be sent, regardless of how
    // many different IPs ask for them. Set to match the link lifetime: there is
    // no legitimate reason to need a fourth link while the first is still live.
    private $password_reset_account_limit = 3;
    private $password_reset_account_window = 3600;

    /**
     * Base URL to put in a password reset email.
     *
     * Built from settings['domain'] wherever it is configured, never from the
     * request: get_application_path() falls back to the Host header, and a
     * reset link built from that can be pointed at an attacker's server by
     * sending a spoofed Host with the reset request. The victim then receives a
     * genuine looking email that hands the token over on click.
     *
     * Most self hosted installs have no domain configured, so rather than
     * refuse to send at all this falls back to the request derived path, which
     * is what every other emoncms generated link already uses. Set
     * settings['domain'] on any install reachable from the internet, and make
     * sure the web server has a default vhost that does not route unknown Host
     * headers here.
     *
     * @return string
     */
    private function password_reset_link_base()
    {
        global $settings, $path;

        if (!empty($settings["domain"])) return get_application_path($settings["domain"]);

        $this->log->warn("passwordreset: settings['domain'] is not set, the reset link is built from the request Host header");
        return $path;
    }

    /**
     * Clear any outstanding password reset token for a user.
     *
     * Called whenever the account holder proves they still have the password:
     * a live emailed link is only meant to cover the case where they have lost
     * it, so leaving one usable for the rest of the window after a successful
     * login or password change is an unnecessary window for whoever can read
     * that mailbox.
     *
     * @param int $userid
     * @return void
     */
    private function clear_password_reset_token($userid)
    {
        $userid = (int) $userid;
        if ($userid < 1) return;

        // This runs on the login path, so it must never be able to break a
        // login: on a database that has the new code but has not had the schema
        // update applied yet the columns are missing and prepare() throws
        try {
            $stmt = $this->mysqli->prepare("UPDATE users SET password_reset_hash='', password_reset_expires=0 WHERE id=? AND password_reset_hash!=''");
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            $this->log->warn("clear_password_reset_token failed userid:$userid ".$e->getMessage());
        }
    }

    /**
     * True if a reset token is currently redeemable.
     *
     * Used to tell the user their link has expired when they open the page,
     * rather than after they have typed a new password twice. This is only a
     * lookup, the redemption in passwordreset_confirm() re-checks everything.
     *
     * @param string $key token from the emailed link
     * @return bool
     */
    public function passwordreset_key_is_valid($key)
    {
        // Generous limit: this is a page load, and the redemption below has its
        // own stricter counter. Fail open, the real gate is on redemption.
        if ($this->is_rate_limited('passwordresetcheck', 30, 900)) return true;

        if (!is_string($key) || strlen($key) != 64 || !ctype_xdigit($key)) return false;

        $token_hash = hash('sha256', $key);
        $now = time();

        // Renders a page, so treat a database that predates the reset columns
        // as "no valid token" rather than letting it 500
        try {
            $userid = 0;
            $stmt = $this->mysqli->prepare("SELECT id FROM users WHERE password_reset_hash=? AND password_reset_expires>?");
            $stmt->bind_param("si", $token_hash, $now);
            $stmt->execute();
            $stmt->bind_result($userid);
            $found = $stmt->fetch();
            $stmt->close();
        } catch (Exception $e) {
            $this->log->warn("passwordreset_key_is_valid failed: ".$e->getMessage());
            return false;
        }

        return $found && $userid > 0;
    }

    /**
     * Step 1 of password reset: email a one time link.
     *
     * This deliberately does NOT change the password. Anyone on the internet
     * can reach this endpoint knowing only a username and an email address, so
     * changing the credential here let a stranger lock any account out at will.
     * The account is only altered once the emailed token comes back in
     * passwordreset_confirm(), which proves control of the mailbox.
     *
     * The response is identical whether or not an account matched, so this
     * cannot be used to test which usernames or addresses are registered.
     *
     * @param string $username
     * @param string $emailto
     * @return array
     */
    public function passwordreset($username,$emailto)
    {
        if ($this->is_rate_limited('passwordreset', 3, 900)) return array('success'=>false, 'message'=>tr("Too many attempts, please try again later"));

        // Sent whatever happens below: never reveal whether an account matched
        $generic = array('success'=>true, 'message'=>tr("If that username and email match an account, a password reset link has been sent."));

        $result = $this->is_valid_username($username);
        if (!$result['success']) return $generic;
        $result = $this->is_valid_email($emailto);
        if (!$result['success']) return $generic;

        global $settings;
        if (empty($settings["interface"]["enable_password_reset"])) return $generic;

        $link_base = $this->password_reset_link_base();

        $stmt = $this->mysqli->prepare("SELECT id,access FROM users WHERE username=? AND email=?");
        $stmt->bind_param("ss",$username,$emailto);
        $stmt->execute();
        $stmt->bind_result($userid,$access);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found || $userid < 1) {
            $this->log->info("passwordreset: no account matched username:$username emailto:$emailto ip:".get_client_ip_env());
            return $generic;
        }

        // Don't send a reset for an account that could not log in with the new
        // password anyway. The caller cannot tell this apart from success.
        if ($access !== null && (int) $access == 0) {
            $this->log->info("passwordreset: refused for account with login disabled userid:$userid");
            return $generic;
        }

        $userid = (int) $userid;

        // Per account limit, on top of the per IP limit at the top of this
        // method. The IP bucket alone does not protect the account holder: an
        // attacker with a pool of addresses stays under it on every address
        // while filling one victim's inbox with reset emails. Checked here, so
        // it only counts requests that would actually send, and applied before
        // the UPDATE below so a flood cannot keep invalidating the link the
        // legitimate user is trying to use. The response is the generic one, so
        // this does not become an oracle for whether an account exists.
        if ($this->is_rate_limited_by('passwordreset:user', $userid, $this->password_reset_account_limit, $this->password_reset_account_window)) {
            $this->log->warn("passwordreset: per account limit reached userid:$userid ip:".get_client_ip_env());
            return $generic;
        }

        // The token goes in the email; only its hash is stored, so read access
        // to the users table does not yield usable reset links
        $token = generate_secure_key(32);
        $token_hash = hash('sha256', $token);
        $expires = time() + $this->password_reset_window;

        $reset_link = $link_base."user/passwordreset-confirm?key=$token";
        $minutes = (int) round($this->password_reset_window / 60);

        require_once "Lib/email.php";
        $emailer = new Email();
        $emailer->to($emailto);
        $emailer->subject(ucfirst($this->appname).' password reset');
        $emailer->body("<p>A password reset was requested for your ".$this->appname." account.</p>"
                      ."<p>To choose a new password follow this link: <a href='$reset_link'>$reset_link</a></p>"
                      ."<p>The link can be used once and expires in $minutes minutes. "
                      ."If you did not request this you can ignore this email, your password has not been changed.</p>");
        $result = $emailer->send();
        if (!$result['success']) {
            // Store nothing if the email did not go out: the outstanding link,
            // if any, stays valid rather than being replaced by one nobody has
            $this->log->error("passwordreset: email send returned error. emailto=$emailto message='".$result['message']."'");
            return $generic;
        }

        $stmt = $this->mysqli->prepare("UPDATE users SET password_reset_hash=?, password_reset_expires=? WHERE id=?");
        $stmt->bind_param("sii", $token_hash, $expires, $userid);
        if (!$stmt->execute()) {
            // The link is already in the user's inbox but nothing will accept
            // it. Nothing to do but log it, telling the caller would break the
            // "identical response either way" property above.
            $this->log->error("passwordreset: failed to store reset token userid:$userid error:".$this->mysqli->error);
        }
        $stmt->close();

        $this->log->info("passwordreset: reset link sent userid:$userid ip:".get_client_ip_env());

        return $generic;
    }

    /**
     * Step 2 of password reset: redeem the emailed token and set a new password.
     *
     * The token is single use and time limited, and redeeming it revokes every
     * remember me cookie on the account.
     *
     * KNOWN LIMITATION: this does not end PHP sessions that are already open.
     * Emoncms keeps no per user record of live sessions, so there is nothing to
     * enumerate and destroy here. Someone who knew the old password and has a
     * session open keeps that session until it expires or they log out. Closing
     * this needs either redis backed sessions keyed by userid, or a session
     * epoch column on users that is bumped here and compared in
     * emon_session_start(). Until then a reset locks out the old password and
     * every remember me cookie, but not an open session.
     *
     * @param string $key   token from the emailed link
     * @param string $new   the new password
     * @return array
     */
    public function passwordreset_confirm($key, $new)
    {
        if ($this->is_rate_limited('passwordresetconfirm', 10, 900)) return array('success'=>false, 'message'=>tr("Too many attempts, please try again later"));

        $invalid = array('success'=>false, 'message'=>tr("This password reset link is invalid or has expired, please request a new one"));

        // Tokens issued before the feature was turned off are not redeemable
        global $settings;
        if (empty($settings["interface"]["enable_password_reset"])) return $invalid;

        // Same shape generate_secure_key(32) produces
        if (!is_string($key) || strlen($key) != 64 || !ctype_xdigit($key)) return $invalid;

        $result = $this->is_valid_password($new);
        if (!$result['success']) return $result;

        $token_hash = hash('sha256', $key);
        $now = time();

        $userid = 0;
        $stmt = $this->mysqli->prepare("SELECT id FROM users WHERE password_reset_hash=? AND password_reset_expires>?");
        $stmt->bind_param("si", $token_hash, $now);
        $stmt->execute();
        $stmt->bind_result($userid);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found || $userid < 1) {
            $this->log->warn("passwordreset_confirm: invalid or expired key ip:".get_client_ip_env());
            return $invalid;
        }

        $userid = (int) $userid;

        // A reset always lands on the configured algorithm, bcrypt or argon2id,
        // and clears any legacy salt with it
        $password = hash_password($new);
        $salt = '';

        // Clearing the token in the same statement makes it single use even if
        // two redemptions arrive at once: the second matches no row
        $stmt = $this->mysqli->prepare("UPDATE users SET password=?, salt=?, password_reset_hash='', password_reset_expires=0 WHERE id=? AND password_reset_hash=?");
        $stmt->bind_param("ssis", $password, $salt, $userid, $token_hash);
        $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();

        if ($changed < 1) return $invalid;

        // A reset is only meaningful if it also ends logins held with the old
        // password. See the limitation noted above: this covers remember me
        // cookies, not sessions that are already open.
        $this->rememberme->clearAllTriplets($userid);

        // The reset completed, so the per account send limit has done its job.
        // Clearing it means someone who used up their allowance and then reset
        // successfully is not locked out of asking again for the rest of the
        // window. Only reachable by redeeming a token, so it cannot be reset by
        // whoever was flooding the account.
        $this->clear_rate_limit_by('passwordreset:user', $userid);

        $this->log->info("passwordreset_confirm: password reset userid:$userid ip:".get_client_ip_env());

        return array('success'=>true, 'message'=>tr("Password updated, you can now log in with your new password"));
    }

    public function change_username($userid, $username)
    {
        if (isset($_SESSION['cookielogin']) && $_SESSION['cookielogin']==true) return array('success'=>false, 'message'=>tr("As you are using a cookie based remember me login, please logout and log back in to change username"));

        $userid = (int) $userid;

        $result = $this->is_valid_username($username);
        if (!$result['success']) return $result;

        $userid_from_username = $this->get_id($username);

        if (!$userid_from_username)
        {
            $stmt = $this->mysqli->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $username, $userid);
            $stmt->execute();
            $stmt->close();
            return array('success'=>true, 'message'=>tr("Username updated"));
        }
        else
        {
            return array('success'=>false, 'message'=>tr("Username already exists"));
        }
    }

    public function change_email($userid, $email)
    {
        if (isset($_SESSION['cookielogin']) && $_SESSION['cookielogin']==true) return array('success'=>false, 'message'=>tr("As you are using a cookie based remember me login, please logout and log back in to change email"));

        $userid = (int) $userid;

        $result = $this->is_valid_email($email);
        if (!$result['success']) return $result;

        $stmt = $this->mysqli->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $email, $userid);
        $stmt->execute();
        $stmt->close();
        
	/*
        global $session;
        
        if (!$session['admin']) {
            $stmt = $this->mysqli->prepare("UPDATE users SET email_verified='0' WHERE id = ?");
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $stmt->close();
        }
        
        $session['emailverified'] = 0;
        $_SESSION['emailverified'] = 0;
	*/
        
        return array('success'=>true, 'message'=>tr("Email updated"));
    }

    //---------------------------------------------------------------------------------------
    // Get by userid methods
    //---------------------------------------------------------------------------------------
    public function get_username($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT username FROM users WHERE id = '$userid';");
        if ($row = $result->fetch_array()) {
            return $row['username'];
        } else {
            return false;
        }
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
        if ($result = $this->mysqli->query("SELECT timezone FROM users WHERE id = '$userid';")) {
            if ($row = $result->fetch_object()) {
                return $row->timezone;
            }
        }
        return false;
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
    
    public function timezone_valid($_timezone) 
    {
        // timezone length check
        if (strlen($_timezone) < 3 || strlen($_timezone) > 50) return false;
        
        // timezone character check
        if (!preg_match('/^[\w\-.\/_]+$/', $_timezone)) return false;

        // whitelist check against supported PHP timezones
        foreach (DateTimeZone::listIdentifiers() as $timezone) {
            if ($timezone===$_timezone) return true;
        }
        return false;
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
        if (!ctype_alnum($username)) return 0;
        
        if (!$stmt = $this->mysqli->prepare("SELECT id FROM users WHERE username = ?")) {
            return 0;
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($id);
        if (!$stmt->fetch()) {
            $stmt->close();
            return 0;
        } else {
            $stmt->close();
            return $id;
        }
    }

    //---------------------------------------------------------------------------------------
    // Set by id methods
    //---------------------------------------------------------------------------------------
    public function set_user_lang($userid, $lang)
    {   
        $userid = (int) $userid;
        $lang = preg_replace('/[^\w\s\-.]/','',$lang);

        $stmt = $this->mysqli->prepare("UPDATE users SET language = ? WHERE id = ?");
        $stmt->bind_param("si", $lang, $userid);
        $stmt->execute();
        $stmt->close();

        return array('success'=>true, 'message'=>"Language updated");
    }

    public function set_timezone($userid,$timezone)
    {
        $userid = (int) $userid;

        if (!$this->timezone_valid($timezone)) {
            return array('success'=>false, 'message'=>"Invalid timezone");
        }

        $stmt = $this->mysqli->prepare("UPDATE users SET timezone = ? WHERE id = ?");
        $stmt->bind_param("si", $timezone, $userid);
        $stmt->execute();
        $stmt->close();

        return array('success'=>true, 'message'=>"Timezone updated");
    }
    
    //---------------------------------------------------------------------------------------
    // Access
    //---------------------------------------------------------------------------------------
    public function set_access($userid, $access) {
        $userid = (int) $userid;
        $access = (int) $access;
        $this->mysqli->query("UPDATE users SET `access`='$access' WHERE `id`='$userid'");

        return array('success'=>true, 'message'=>"Access updated");
    }
    
    public function get_access($userid) {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT `access` FROM users WHERE `id`='$userid'");
        $data = $result->fetch_object();
        return $data->access;
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
        global $settings;
        $default_locale = $settings["interface"]["default_language"];
        $default_timezone = 'Europe/London';
        // Validation
        $userid = (int) $userid;
        if(!$data || $userid < 1) return array('success'=>false, 'message'=>tr("Error updating user info"));

        $gravatar = isset($data->gravatar) && $data->gravatar !== null ? preg_replace('/[^\w\s\-.@]/','',$data->gravatar) : '';
        $name = isset($data->name) && $data->name !== null ? preg_replace('/[^\p{N}\p{L}_\s\-.]/u','',$data->name) : '';
        $location = isset($data->location) && $data->location !== null ? preg_replace('/[^\p{N}\p{L}_\s\-.]/u','',$data->location) : '';
        $timezone = isset($data->timezone) && $data->timezone !== null ? preg_replace('/[^\w\-.\\/_]/','',$data->timezone) : '';
        $bio = isset($data->bio) && $data->bio !== null ? preg_replace('/[^\p{N}\p{L}_\s\-.]/u','',$data->bio) : '';
        $language = isset($data->language) && $data->language !== null ? preg_replace('/[^\w\s\-.]/','',$data->language) : '';
        $tags = isset($data->tags) && $data->tags !== null ? preg_replace('/[^{}",:\w\s\-.]/','', $data->tags) : '';
        $startingpage = isset($data->startingpage) && $data->startingpage !== null ? preg_replace('/[^\p{N}\p{L}_\s\-?#=\/]/u','',$data->startingpage) : '';
        
        $_SESSION['lang'] = !empty($language) ? $language : $default_locale;
        $_SESSION['timezone'] = !empty($timezone) ? $timezone : $default_timezone;
        $_SESSION['gravatar'] = !empty($gravatar) ? $gravatar : '';

        $stmt = $this->mysqli->prepare("UPDATE users SET gravatar = ?, name = ?, location = ?, timezone = ?, language = ?, bio = ?, startingpage = ?, tags = ? WHERE id = ?");
        $stmt->bind_param("ssssssssi", $gravatar, $name, $location, $timezone, $language, $bio, $startingpage, $tags, $userid);
        if (!$stmt->execute()) {
            $stmt->close();
            return array('success'=>false, 'message'=>tr("Error updating user info"));
        }
        $stmt->close();

        return array('success'=>true, 'message'=>_("User info updated"));
    }

    // Generates a new random read apikey
    public function new_apikey_read($userid)
    {
        $userid = (int) $userid;
        $apikey = generate_secure_key(16);
        
        $stmt = $this->mysqli->prepare("UPDATE users SET apikey_read = ? WHERE id = ?");
        $stmt->bind_param("si", $apikey, $userid);
        $stmt->execute();
        $stmt->close();
        
        return array('success'=>true, 'read_apikey'=>$apikey);
    }

    // Generates a new random write apikey
    public function new_apikey_write($userid)
    {
        $userid = (int) $userid;
        $apikey = generate_secure_key(16);
        
        $stmt = $this->mysqli->prepare("UPDATE users SET apikey_write = ? WHERE id = ?");
        $stmt->bind_param("si", $apikey, $userid);
        $stmt->execute();
        $stmt->close();

        return array('success'=>true, 'write_apikey'=>$apikey);
    }

    public function get_number_of_users()
    {
        $result = $this->mysqli->query("SELECT COUNT(*) FROM users");
        $row = $result->fetch_row();
        return $row[0];
    }
    
    public function get_usernames_by_email($email) {
        $result = $this->is_valid_email($email);
        if (!$result['success']) return false;
        
        $stmt = $this->mysqli->prepare("SELECT id,username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();        
        $stmt->bind_result($id,$username);
        
        $users = array();
        while ($stmt->fetch()) {
            $users[] = array("id"=>$id,"username"=>$username);
        }
        $stmt->close();
        
        return $users;
    }


    /**
     * Is the server side gravatar proxy available on this install?
     *
     * The cache directory is deliberately not created here: it is provisioned by
     * packaging alongside the other /var/opt/emoncms data directories, so that
     * ownership and permissions are set once by the installer rather than by
     * whichever request happens to arrive first. Emoncms also runs on hosts
     * where that path does not exist and is not writable (shared hosting,
     * containers), and creating a cache under a world writable temp directory
     * would mean following whatever another local user had planted there.
     * Where the directory is missing or read only the feature is simply off.
     *
     * @return bool
     */
    public function gravatar_enabled()
    {
        static $enabled = null;
        if ($enabled === null) {
            $enabled = is_dir(self::GRAVATAR_CACHE_DIR) && is_writable(self::GRAVATAR_CACHE_DIR);
        }
        return $enabled;
    }

    /**
     * Does $hash identify $address on gravatar.com?
     *
     * Used to confine the proxy to the visitor's own avatar, see the gravatar
     * route in user_controller. Neither side is a secret from the session
     * holder; hash_equals is used because it is the right default for
     * comparing a submitted digest, not because there is anything to leak.
     *
     * @param string $hash     hash supplied by the request
     * @param string $address  gravatar address to check it against
     * @return bool
     */
    public function gravatar_hash_matches($hash, $address)
    {
        if (!is_string($hash) || !is_string($address)) return false;

        $normalised = strtolower(trim($address));
        if ($normalised === '') return false;

        return hash_equals(hash('sha256', $normalised), $hash);
    }

    /**
     * Server-side gravatar proxy: avatars are fetched and cached by the server
     * so that the visitor's browser never connects to gravatar.com directly
     * (gravatar.com would otherwise receive the visitor's IP and email hash on every page view)
     *
     * @param string $hash  sha256 (64 hex chars) gravatar email hash
     * @param int    $size  image size in pixels
     * @return array|false  array('content'=>bytes, 'mime'=>type) or false if unavailable
     */
    public function get_gravatar($hash, $size)
    {
        if (!$this->gravatar_enabled()) return false;
        // \z, not $: PHP's $ also matches before a trailing newline, so a hash
        // submitted as "<64 hex>%0A" would otherwise pass and be spliced into
        // the request URL and the cache filename. libcurl rejects such a URL
        // anyway; this does not rely on it doing so.
        if (!is_string($hash) || !preg_match('/^[0-9a-f]{64}\z/', $hash)) return false;
        $size = (int) $size;
        if ($size<1 || $size>512) $size = 52;

        $cache_file = self::GRAVATAR_CACHE_DIR."/$hash-$size";
        $cache_max_age = 7*24*3600;

        $content = false;
        if (is_file($cache_file) && (time()-filemtime($cache_file)) < $cache_max_age) {
            $content = @file_get_contents($cache_file);
        }
        if ($content === false) {
            $ch = curl_init("https://www.gravatar.com/avatar/$hash?s=$size&d=mp&r=g");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
            $fetched = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // no curl_close(): deprecated since PHP 8.0 and the handle is freed
            // when it goes out of scope. The deprecation notice it raises would be
            // written into the response ahead of the image bytes, corrupting every
            // cache miss on installs that have display_errors on.
            unset($ch);
            if ($fetched !== false && $http_code == 200 && strlen($fetched)) {
                $content = $fetched;
                // atomic write so concurrent requests never read a partial file
                $tmp_file = $cache_file.".".getmypid().".tmp";
                if (@file_put_contents($tmp_file, $content) !== false) @rename($tmp_file, $cache_file);
            } else if (is_file($cache_file)) {
                // gravatar.com unreachable: fall back to the stale cached copy
                $content = @file_get_contents($cache_file);
            }
        }
        if ($content === false) return false;

        $mime = "image/png";
        if (substr($content,0,3)=="\xFF\xD8\xFF") $mime = "image/jpeg";
        else if (substr($content,0,6)=="GIF87a" || substr($content,0,6)=="GIF89a") $mime = "image/gif";
        else if (substr($content,0,4)=="RIFF" && substr($content,8,4)=="WEBP") $mime = "image/webp";

        return array('content'=>$content, 'mime'=>$mime);
    }

    public function update_last_active($userid) {
        $userid = (int) $userid;
        $lastactive = time();
        $result = $this->mysqli->query("SHOW COLUMNS FROM users LIKE 'lastactive'");
        if ($result->num_rows > 0) {
            $this->mysqli->query("UPDATE users SET `lastactive` = '$lastactive' WHERE `id`= '$userid'");
            return true;
	}
        return false;
    }

    /**
     * Check rate limit for a given action and IP using Redis.
     * Returns false if under the limit (allowed), true if over the limit (blocked).
     *
     * @param string $action  e.g. 'login', 'register', 'passwordreset'
     * @param int    $limit   max attempts allowed within the window
     * @param int    $window  time window in seconds
     * @return bool
     */
    private function is_rate_limited($action, $limit, $window)
    {
        if ($this->disable_rate_limiting) return false;
        if (!$this->redis) return false;

        $ip = get_client_ip_env();
        if (empty($ip)) {
            // REMOTE_ADDR is missing or invalid (e.g. CLI, misconfigured proxy).
            // Skip rate limiting rather than writing to a shared key-less bucket.
            $this->log->warn("Rate limit skipped: empty IP for action:{$action}");
            return false;
        }

        return $this->is_rate_limited_by($action, $ip, $limit, $window);
    }

    /**
     * Check rate limit for a given action against an explicit bucket.
     *
     * Same counter as is_rate_limited(), but the caller chooses what is being
     * limited instead of it always being the client IP. Used to limit an action
     * per target account, which an IP bucket cannot do: an attacker with a pool
     * of addresses stays under the per IP limit on every one of them while
     * hitting a single victim over and over.
     *
     * @param string $action  e.g. 'passwordreset:user'
     * @param string $bucket  what is being limited, e.g. a userid
     * @param int    $limit   max attempts allowed within the window
     * @param int    $window  time window in seconds
     * @return bool
     */
    private function is_rate_limited_by($action, $bucket, $limit, $window)
    {
        if ($this->disable_rate_limiting) return false;
        if (!$this->redis) return false;
        if ($bucket === '' || $bucket === null) return false;

        $key = "ratelimit:{$action}:" . $bucket;
        $attempts = $this->redis->incr($key);
        if ($attempts === 1) {
            $this->redis->expire($key, $window);
        }
        if ($attempts > $limit) {
            $this->log->warn("Rate limit hit action:{$action} bucket:{$bucket}");
            return true;
        }
        return false;
    }

    /**
     * Reset the counter for a bucket, once whatever it was protecting against
     * has been settled legitimately.
     *
     * @param string $action
     * @param string $bucket
     * @return void
     */
    private function clear_rate_limit_by($action, $bucket)
    {
        if (!$this->redis) return;
        if ($bucket === '' || $bucket === null) return;

        $this->redis->del("ratelimit:{$action}:" . $bucket);
    }

    // Check rate limit without incrementing the counter.
    private function is_rate_limit_exceeded($action, $limit)
    {
        if ($this->disable_rate_limiting) return false;
        if (!$this->redis) return false;

        $ip = get_client_ip_env();
        if (empty($ip)) return false;

        $key = "ratelimit:{$action}:" . $ip;
        $attempts = (int) $this->redis->get($key);
        if ($attempts > $limit) {
            $this->log->warn("Rate limit hit action:{$action} ip:{$ip}");
            return true;
        }
        return false;
    }

    // Increment the failure counter for an action without checking the limit.
    private function record_failed_attempt($action, $window)
    {
        if ($this->disable_rate_limiting) return;
        if (!$this->redis) return;

        $ip = get_client_ip_env();
        if (empty($ip)) return;

        $key = "ratelimit:{$action}:" . $ip;
        $attempts = $this->redis->incr($key);
        if ($attempts === 1) {
            $this->redis->expire($key, $window);
        }
    }

    /**
     * return true if input is not null
     *
     * @param mixed $var
     * @return boolean
     */
    private function is_not_null ($var) {
        return !is_null($var);
    }

    // Consistent validation functions for email, username and password. 
    // These are used in multiple places, centralise them here.
    private function is_valid_email($email) {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return array('success'=>false, 'message'=>tr("Email address format error"));
        } else {
            return array('success'=>true);
        }
    }

    private function is_valid_username($username) {
        if (!ctype_alnum($username)) {
            return array('success'=>false, 'message'=>tr("Username must only contain a-z and 0-9 characters"));
        } else if (strlen($username) < 3) {
            return array('success'=>false, 'message'=>tr("Username must be at least 3 characters"));
        } else if (strlen($username) > 30) {
            return array('success'=>false, 'message'=>tr("Username must be less than 30 characters"));
        } else {
            return array('success'=>true);
        }
    }

    private function is_valid_password($password) {
        if (strlen($password) < 4) {
            return array('success'=>false, 'message'=>tr("Password must be at least 4 characters"));
        } else if (strlen($password) > 250) {
            return array('success'=>false, 'message'=>tr("Password must be less than 250 characters"));
        } else {
            return array('success'=>true);
        }
    }

    /**
     * Validate a referrer string, returning it if it is a safe relative path,
     * or an empty string if it contains a scheme or host (open-redirect prevention).
     *
     * @param  string $referrer  Raw referrer value (not yet URL-decoded)
     * @return string            Safe relative path, or '' if invalid
     */
    public function validate_referrer($referrer)
    {
        if (empty($referrer)) return '';

        // Prevent oversized payloads
        if (strlen($referrer) > 2000) return '';

        $decoded = urldecode($referrer);

        // Reject backslashes: some browsers normalise \\ to / which can turn
        // /\example.com into //example.com (a protocol-relative redirect)
        if (strpos($decoded, '\\') !== false) return '';

        // Only allow relative paths — no scheme (http://) and no host
        $parsed = parse_url($decoded);
        if (!isset($parsed['scheme']) && !isset($parsed['host']) && isset($parsed['path'])) {
            return $decoded;
        }
        return '';
    }
}

