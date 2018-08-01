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

function user_controller()
{
    global $mysqli, $redis, $user, $path, $session, $route , $enable_multi_user, $email_verification;

    $result = false;

    $allowusersregister = true;
    // Disables further user creation after first admin user is created
    if ($enable_multi_user===false && $user->get_number_of_users()>0) {
        $allowusersregister = false;
    }

    // Load html,css,js pages to the client
    if ($route->format == 'html')
    {
        if ($route->action == 'login' && !$session['read']) $result = view("Modules/user/login_block.php", array('allowusersregister'=>$allowusersregister,'verify'=>array()));
        if ($route->action == 'view' && $session['write']) $result = view("Modules/user/profile/profile.php", array());
        
        if ($route->action == 'logout' && $session['read']) {
            $user->logout(); 
            header('Location: '.$path);
        }
        
        if ($route->action == 'verify' && $email_verification && !$session['read'] && isset($_GET['key'])) { 
            $verify = $user->verify_email($_GET['email'], $_GET['key']);
            $result = view("Modules/user/login_block.php", array('allowusersregister'=>$allowusersregister,'verify'=>$verify));
        }
    }

    // JSON API
    if ($route->format == 'json')
    {
        // Core session
        if ($route->action == 'login' && !$session['read']) $result = $user->login(post('username'),post('password'),post('rememberme'));
        if ($route->action == 'register' && $allowusersregister) $result = $user->register(post('username'),post('password'),post('email'));
        if ($route->action == 'logout' && $session['read']) $user->logout();
        
        if ($route->action == 'resend-verify' && $email_verification) {
            if (isset($_GET['username'])) $username = $_GET['username']; else $username = $session["username"];
            $result = $user->send_verification_email($username);
        }

        if ($route->action == 'changeusername' && $session['write']) $result = $user->change_username($session['userid'],get('username'));
        if ($route->action == 'changeemail' && $session['write']) $result = $user->change_email($session['userid'],get('email'));
        if ($route->action == 'changepassword' && $session['write']) $result = $user->change_password($session['userid'],post('old'),post('new'));
        
        if ($route->action == 'passwordreset') $result = $user->passwordreset(get('username'),get('email'));
        // Apikey
        if ($route->action == 'newapikeyread' && $session['write']) $result = $user->new_apikey_read($session['userid']);
        if ($route->action == 'newapikeywrite' && $session['write']) $result = $user->new_apikey_write($session['userid']);

        if ($route->action == 'auth' && !$session['read']) $result = $user->get_apikeys_from_login(post('username'),post('password'));

        // Get and set - user by profile client
        if ($route->action == 'get' && $session['write']) $result = $user->get($session['userid']);
        if ($route->action == 'set' && $session['write']) $result = $user->set($session['userid'],json_decode(post('data')));

        if ($route->action == 'timezone' && $session['read']) $result = $user->get_timezone_offset($session['userid']); // to maintain compatibility but in seconds
        if ($route->action == 'gettimezone' && $session['read']) $result = $user->get_timezone($session['userid']);
        if ($route->action == 'gettimezones' && $session['read']) $result = $user->get_timezones();
        
        if ($route->action == "deleteall" && $session['write']) {
            $route->format = "text";
            $userid = $session['userid'];
            require "Modules/user/deleteuser.php";
            
            if (isset($_POST['mode'])) {
            
                $mode = "dryrun";
                if ($_POST['mode']=="permanentdelete") $mode = "permanentdelete";
            
                if ($mode=="permanentdelete") {
                    if (isset($_POST['password'])) {
                        // Check password
                        $result = $mysqli->query("SELECT password, salt FROM users WHERE id = '$userid'");
                        $row = $result->fetch_object();
                        $hash = hash('sha256', $row->salt . hash('sha256', $_POST['password']));

                        if ($hash == $row->password || $session['admin']==1) {
                            $result = "PERMANENT DELETE:\n";
                            $result .= delete_user($userid,"permanentdelete");
                            $result .= call_hook('on_delete_user',['userid'=>$userid,'mode'=>'permanentdelete']);
                            
                            $user->logout();
                        } else {
                            $result = "invalid password";
                        }
                    } else {
                        $result = "missing password field";
                    }
                } else {
                    $result = "DRY RUN:\n";
                    $result .= delete_user($userid,"dryrun");
                    $result .= call_hook('on_delete_user',['userid'=>$userid,'mode'=>'dryrun']);
                }
            } else {
                $result = "missing mode field";
            }
        }
        // set user peferences for beta opt ins
        if ($route->action == 'beta' && $session['read']) {
            switch($route->method) {
                case 'POST':
                    if(!empty(post('optIn'))){
                        $optIns = post('optIn');
                        $result = $user->setBetaOptIn($optIns);
                    } else {
                        $result = array('success'=>false,'message'=>'Invalid parameters');
                    }
                    break;
                case 'DELETE':
                    $result = $user->removeBetaOptIn();
                    break;
                default:
                    $result = $user->getBetaOptIn();
            }
        }
    }

    return array('content'=>$result);
}
