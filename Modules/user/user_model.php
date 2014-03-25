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

class User extends Model
{
    public $useTable = 'users';

    protected $mysqli;

    protected $rememberme;

    protected $enable_rememberme = false;

    protected $redis;

    public function __construct(array $config = array())
    {
        parent::__construct($config);
        $this->enable_rememberme = Configure::read('EmonCMS.Auth.enable_rememberme');

        $this->rememberme = $config['rememberme'];
        $this->redis = $config['redis'];
    }

    //---------------------------------------------------------------------------------------
    // Core session methods
    //---------------------------------------------------------------------------------------

    public function apikey_session($apikey_in)
    {
        $apikey_in = preg_replace('/[^a-f0-9]/i', '', $apikey_in);
        $session = array();

        //----------------------------------------------------
        // Check for apikey login
        //----------------------------------------------------
        if($this->redis && $this->redis->exists("writeapikey:$apikey_in"))
        {
            return array(
                'userid' => $this->redis->get("writeapikey:$apikey_in"),
                'read' => 1,
                'write' => 1,
                'admin' => 0,
                'editmode' => true,
                'lang' => 'en',
            );
        }
        $result = $this->row("SELECT id FROM `users` WHERE apikey_write='$apikey_in'");
        if (!empty($result['id']))
        {
            $session['userid'] = $result['id'];
            $session['read'] = 1;
            $session['write'] = 1;
            $session['admin'] = 0;
            $session['editmode'] = true;
            $session['lang'] = "en";

            if ($this->redis) 
            {
                $this->redis->set("writeapikey:$apikey_in", $result['id']);
            }
            return $session;
        }

        $result = $this->row("SELECT id FROM `users` WHERE apikey_read='$apikey_in'");
        if (!empty($result['id']))
        {
            //session_regenerate_id();
            $session['userid'] = $result['id'];
            $session['read'] = 1;
            $session['write'] = 0;
            $session['admin'] = 0;
            $session['editmode'] = true;
            $session['lang'] = "en";
        }
        
        return $session;

        //----------------------------------------------------
    }

    public function emon_session_start()
    {
        session_start();

        if ($this->enable_rememberme)
        {
            // if php session exists
            if (!empty($_SESSION['userid'])) 
            {
                // if rememberme emoncms cookie exists but is not valid then
                // a valid cookie is a cookie who's userid, token and persistant token match a record in the db
                if(!empty($_COOKIE[$this->rememberme->getCookieName()]) && !$this->rememberme->cookieIsValid($_SESSION['userid'])) 
                {
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
                    if($this->rememberme->loginTokenWasInvalid()) 
                    {
                        // Stolen
                    }
                }
            }
        }

        $keys = array(
            'admin' => 0, 
            'read' => 0, 
            'write' => 0, 
            'userid' => 0, 
            'lang' => '', 
            'username' => '', 
            'cookielogin' => 0,
        );
        foreach ($keys as $k => $v) 
        {
            $session[$k] = isset($_SESSION[$k]) ? $_SESSION[$k] : $v;
        }

        return $session;
    }


    public function register($username, $password, $email)
    {
        if (!$username || !$password || !$email) 
        {
            return array('success' => false, 'message' => _("Missing username, password or email parameter"));
        }

        if (!ctype_alnum($username)) 
        {
            return array('success' => false, 'message' => _("Username must only contain a-z and 0-9 characters"));
        }

        if ($this->get_id($username)) 
        {
            return array('success' => false, 'message' => _("Username already exists"));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
        {
            return array('success' => false, 'message' => _("Email address format error"));
        }

        if (strlen($username) < 4 || strlen($username) > 30) 
        {
            return array('success' => false, 'message' => _("Username length error"));
        }
        if (strlen($password) < 4 || strlen($password) > 30) 
        {
            return array('success' => false, 'message' => _("Password length error"));
        }

        $salt = $this->_salt();
        $user = array(
            'username' => $username,
            'salt' => $salt,
            'password' => hash('sha256', $salt . hash('sha256', $password)), 
            'email' => $email, 
            'apikey_read' => $this->_apiKey(),
            'apikey_write' => $this->_apiKey(),
            'admin' => 0,
        );
        $user = $this->save($user);

        if (!$user) 
        {
            return array('success' => false, 'message' => _("Error creating user"));
        }

        if ($this->id === 1) 
        {
            $user = $this->save(array(
                'id' => $this->id,
                'admin' => 1,
            ));
        }

        return array(
            'success' => true, 
            'userid' => $this->id, 
            'apikey_read' => $user['apikey_read'], 
            'apikey_write' => $user['apikey_write']
        );
    }

/**
 * Generate a salt
 *
 * @return string
 */
    protected function _salt() 
    {
        return substr(md5(uniqid(mt_rand(), true)), 0, 3);
    }

/**
 * Strip evereything from a field that is not a-z0-9
 *
 * @param string $word the thing to clean
 *
 * @return string
 */
    protected function _alphaNumeric($word)
    {
        return preg_replace('/[^\w\s-]/', '', $word);
    }

    public function login($username, $password, $remembermecheck)
    {
        $remembermecheck = (int) $remembermecheck;

        if (!$username || !$password) 
        {
            return array(
                'success' => false, 
                'message' => _("Username or password empty"),
            );
        }

        if ($username != $this->_alphaNumeric($username)) 
        {
            return array(
                'success' => false, 
                'message' => _("Username must only contain a-z 0-9 dash and underscore, if you created an account before this rule was in place enter your username without the non a-z 0-9 dash underscore characters to login and feel free to change your username on the profile page."));
        }

        $userData = $this->row("SELECT id,password,admin,salt,language FROM `users` WHERE username = :username", array(
            'username' => $username,
        ));

        if (empty($userData)) 
        {
            return array(
                'success' => false, 
                'message' => _("Username does not exist"),
            );
        }

        $hash = hash('sha256', $userData['salt'] . hash('sha256', $password));

        if ($hash != $userData['password'])
        {
            return array(
                'success' => false, 
                'message' => _("Incorrect password, if your sure its correct try clearing your browser cache")
            );
        }

        session_regenerate_id();
        $_SESSION['userid'] = $userData['id'];
        $_SESSION['username'] = $username;
        $_SESSION['read'] = 1;
        $_SESSION['write'] = 1;
        $_SESSION['admin'] = $userData['admin'];
        $_SESSION['lang'] = $userData['language'];
        $_SESSION['editmode'] = true;

        if ($this->enable_rememberme) 
        {
            if ($remembermecheck == true) 
            {
                $this->rememberme->createCookie($userData->id);
            } 
            else 
            {
                $this->rememberme->clearCookie();
            }
        }

        return array(
            'success' => true, 
            'message' => _("Login successful")
        );
    }

    public function logout()
    {
        if ($this->enable_rememberme) 
        {
            $this->rememberme->clearCookie(true);
        }

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

        if (strlen($old) < 4 || strlen($old) > 30) 
        {
            return array('success' => false, 'message' => _("Password length error"));
        }
        if (strlen($new) < 4 || strlen($new) > 30) 
        {
            return array('success' => false, 'message' => _("Password length error"));
        }

        // 1) check that old password is correct
        $result = $this->query("SELECT password, salt FROM `users` WHERE id = '$userid'");
        $row = $result->fetch_object();
        $hash = hash('sha256', $row->salt . hash('sha256', $old));

        if ($hash == $row->password)
        {
            // 2) Save new password
            $hash = hash('sha256', $new);
            $string = md5(uniqid(rand(), true));
            $salt = substr($string, 0, 3);
            $hash = hash('sha256', $salt . $hash);
            $this->query("UPDATE `users` SET password = '$hash', salt = '$salt' WHERE id = '$userid'");
            return array('success' => true);
        }
        return array('success' => false, 'message' => _("Old password incorect"));
    }
    
    public function passwordreset($username,$email)
    {
        $username_out = preg_replace('/[^\w\s-]/','',$username);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
        {
            return array('success' => false, 'message' => _("Email address format error"));
        }

        $result = $this->query("SELECT * FROM `users` WHERE `username` = '$username_out' AND `email` = '$email'");

        if ($result->num_rows == 1)
        {
            $row = $result->fetch_array();

            $userid = $row['id'];
            if ($userid > 0)
            {
                // Generate new random password
                $newpass = hash('sha256',md5(uniqid(rand(), true)));
                $newpass = substr($newpass, 0, 10);

                // Hash and salt
                $hash = hash('sha256', $newpass);
                $string = md5(uniqid(rand(), true));
                $salt = substr($string, 0, 3);
                $hash = hash('sha256', $salt . $hash);
                
                // Save hash and salt
                $this->query("UPDATE `users` SET password = '$hash', salt = '$salt' WHERE id = '$userid'");

                if (Configure::read('EmonCMS.enable_password_reset'))
                {                    
                    require_once 'swift_required.php';

                    $transport = Swift_SmtpTransport::newInstance()
                        ->setHost(Configure::read('Smtp.host'))
                        ->setPort(Configure::read('Smtp.port') ?: 26)
                        ->setUsername(Configure::read('Smtp.username'))
                        ->setPassword(Configure::read('Smtp.password'));

                    $mailer = Swift_Mailer::newInstance($transport);
                    $message = Swift_Message::newInstance()
                      ->setSubject('Emoncms password reset')
                      ->setFrom(Configure::read('Smtp.from'))
                      ->setTo(array($email))
                      ->setBody("<p>A password reset was requested for your emoncms account.</p><p>Your can now login with password: $newpass </p>", 'text/html');
                    $result = $mailer->send($message);
                }
                //------------------------------------------------------------------------------

                // Sent email with $newpass to $email
                return array('success' => true, 'message'=>"Password recovery email sent!");
            }
        }

        return array('success' => false, 'message'=>"An error occured");
    }

    protected function _isCookieLogin() 
    {
        return isset($_SESSION['cookielogin']) && $_SESSION['cookielogin'] == true;
    }

    public function change_username($userid, $username)
    {
        if ($this->_isCookieLogin()) 
        {
            return array(
                'success' => false, 
                'message' => _("As your using a cookie based remember me login, please logout and log back in to change username")
            );
        }

        $userid = intval($userid);
        if (strlen($username) < 4 || strlen($username) > 30) 
        {
            return array('success' => false, 'message' => _("Username length error"));
        }

        if (!ctype_alnum($username)) 
        {
            return array('success' => false, 'message' => _("Username must only contain a-z and 0-9 characters"));
        }

        if ($this->field('id', "SELECT id FROM `users` WHERE username = '$username'"))
        {
            $this->query("UPDATE `users` SET username = '$username' WHERE id = '$userid'");
            return array('success' => true, 'message' => _("Username updated"));
        }
        return array('success' => false, 'message' => _("Username already exists"));
    }

    public function change_email($userid, $email)
    {
        if ($this->_isCookieLogin()) 
        {
            return array(
                'success' => false, 
                'message' => _("As your using a cookie based remember me login, please logout and log back in to change email")
            );
        }

        $userid = intval($userid);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
        {
            return array(
                'success' => false,
                'message' => _("Email address format error")
            );
        }

        $this->query("UPDATE `users` SET email = '$email' WHERE id = '$userid'");
        return array(
            'success' => true, 
            'message' => _("Email updated")
        );
    }

    //---------------------------------------------------------------------------------------
    // Get by userid methods
    //---------------------------------------------------------------------------------------

    public function get_convert_status($userid)
    {
        return $this->_getField($userid, 'convert');
    }

    public function get_username($userid)
    {
        return $this->_getField($userid, 'username');
    }

    public function get_apikey_read($userid)
    {
        return $this->_getField($userid, 'apikey_read');
    }

    public function get_apikey_write($userid)
    {
        return $this->_getField($userid, 'apikey_write');
    }

    public function get_lang($userid)
    {
        return $this->_getField($userid, 'lang');
    }

    public function get_timezone($userid)
    {
        return $this->_getField($userid, 'timezone');
    }

    public function get_salt($userid)
    {
        return $this->_getField($userid, 'salt');
    }

    protected function _getField($primaryKey, $field) 
    {
        return $this->field($field, sprintf('SELECT `%s` FROM `users` WHERE id = %d', $field, (int)$primaryKey));
    }

    //---------------------------------------------------------------------------------------
    // Get by other paramater methods
    //---------------------------------------------------------------------------------------

    public function get_id($username)
    {
        if (!ctype_alnum($username)) 
        {
            return false;
        }

        return $this->field('id', 'SELECT `id` FROM `users` WHERE `username` = :username', compact('username'));
    }

    //---------------------------------------------------------------------------------------
    // Set by id methods
    //---------------------------------------------------------------------------------------

    public function set_convert_status($userid)
    {
        $this->_setField($userid, 'convert', 1);
        return array('convert' => 1);
    }

    public function set_user_lang($userid, $lang)
    {
        return $this->_setField($userid, 'lang', $lang);
    }

    public function set_timezone($userid,$timezone)
    {
        return $this->_setField($userid, 'timezone', (int)$timezone);
    }

    protected function _setField($userid, $field, $value) 
    {
        return $this->query(sprintf('UPDATE `users` SET `%s` = "%s" WHERE `id` = %d', $field, $value, (int)$userid));
    }

    //---------------------------------------------------------------------------------------
    // Special methods
    //---------------------------------------------------------------------------------------

    public function get($userid)
    {
        return $this->row("SELECT `id`, `username`, `email`, `gravatar`, `name`, `location`, `timezone`, `language`, `bio` FROM `users` WHERE `id` = :userid", compact('userid'));
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

        $result = $this->query("UPDATE `users` SET gravatar = '$gravatar', name = '$name', location = '$location', timezone = '$timezone', language = '$language', bio = '$bio' WHERE id='$userid'");
    }

/**
 * Generates a new random read apikey for the specified user
 *
 * @param integer $userId the user being updated
 *
 * @return string
 */
    public function new_apikey_read($userid)
    {
        return $this->_setApiKey($userid, 'read');
    }

/**
 * Generates a new random write apikey for the specified user
 *
 * @param integer $userId the user being updated
 *
 * @return string
 */
    public function new_apikey_write($userid)
    {
        return $this->_setApiKey($userid, 'write');
    }

/**
 * Update the specified api key and return the key
 *
 * @param integer $userId the user being updated
 * @param string $type the type of key being updated (read / write)
 * @param string $value the api key (null to have it auto generated)
 *
 * @return string
 */
    protected function _setApiKey($userId, $type, $value = null) 
    {
        if ($value === null) 
        {
            $value = $this->_apiKey($userId);
        }

        $this->query(sprintf('UPDATE `users` SET `apikey_%s` = :apikey WHERE id = :userid'), array(
            'apikey' => $this->_apiKey(),
            'userid' => (int)$userid,
        ));

        return $value;
    }

/**
 * Generate a random api key
 *
 * @return string
 */
    protected function _apiKey() 
    {
        return md5(Configure::read('Security.salt') . uniqid(mt_rand(), true));
    }
}
