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
    global $mysqli, $redis, $user, $path, $session, $route , $settings;

    $result = false;

    $allowusersregister = true;
    // Disables further user creation after first admin user is created
    if ($settings["interface"]["enable_multi_user"]==false && $user->get_number_of_users()>0) {
        $allowusersregister = false;
    }

    // Load html,css,js pages to the client
    if ($route->format == 'html')
    {
        if ($route->action == 'login' && !$session['read']) {
            $route_query = array();
            
            // pass through the referring path
            if(!is_null($route->query)){
                parse_str($route->query, $route_query );
            }
            
            $msg = empty($route_query['msg']) ? get('msg') : $route_query['msg'];
            $ref = empty($route_query['ref']) ? get('ref') : $route_query['ref'];
            
            if(!is_null($msg)){
                $message = htmlspecialchars(urldecode($msg));
            } else {
                $message="";
            }
            
            if(!is_null($ref)){
                $referrer = htmlspecialchars($user->validate_referrer(urldecode(base64_decode($ref))), ENT_QUOTES, 'UTF-8');
            } else {
                $referrer = '';
            }
            // load login template with the above parameters
            return view("Modules/user/login_block.php", array(
                'allowusersregister'=>$allowusersregister,
                'verify'=>array(),
                'message'=>$message,
                'referrer'=>$referrer,
                'v' => 3
            ));
        }
        if ($route->action == 'view' && $session['write']) {
            return view("Modules/user/profile/profile.php", array(
            ));
        }
        
        if ($route->action == 'logout') {
            // decode url parameters
            $next = $path;
            
            $msg = get('msg');
            $message = isset($msg) ? htmlspecialchars(urldecode($msg)) : '';
            $ref = get('ref');
            
            // Validate referrer to prevent open redirect (scheme/host not allowed)
            if(!is_null($ref)){
                $referrer = $user->validate_referrer(urldecode(base64_decode($ref)));
            } else {
                $referrer = '';
            }
            
            // encode url parameters to pass through to login page
            $msg = urlencode($message);
            $ref = base64_encode(urlencode($referrer));
            if(!empty($ref)) {
                $next = sprintf('%s?msg=%s&ref=%s',$path, $msg, $ref);
            }

            $user->logout(); 
            call_hook('on_logout',[]);
            header('Location: '.$next);
            exit();
        }

        // Server-side gravatar proxy, see User::get_gravatar
        if ($route->action == 'gravatar' && $session['read']) {
            // Only ever the visitor's own avatar. Both call sites, the theme
            // and the profile page, render the session user's gravatar address
            // and nothing else, so an arbitrary hash is never legitimate here.
            // Left open, any logged in account could use the proxy to find out
            // whether some other address has a gravatar, and could grow the
            // cache directory without limit: gravatar.com answers 200 for an
            // unknown address, so every distinct hash and size writes a file.
            if (!$user->gravatar_hash_matches(get('hash'), $session['gravatar'])) {
                header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
                exit();
            }

            $avatar = $user->get_gravatar(get('hash'), (int) get('s'));
            if ($avatar === false) {
                header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
                exit();
            }
            header("Content-Type: ".$avatar['mime']);
            header("Content-Length: ".strlen($avatar['content']));
            header("Cache-Control: private, max-age=86400");
            echo $avatar['content'];
            exit();
        }

        // Redeem an emailed password reset link
        if ($route->action == 'passwordreset-confirm') {
            if (empty($settings['interface']['enable_password_reset'])) {
                return view("Modules/user/login_block.php", array(
                    'allowusersregister'=>$allowusersregister,
                    'verify'=>array(),
                    'message'=>tr("Password reset is not enabled on this installation"),
                    'referrer'=>'',
                    'v' => 3
                ));
            }
            // Check the token before rendering the form, so an expired or
            // already used link says so up front rather than after the user has
            // typed a new password twice. passwordreset_confirm() re-checks.
            // Missing key falls through to the same "invalid link" message as a
            // bad one, rather than get()'s bare "missing key parameter" die
            $key = get('key', false, '');
            return view("Modules/user/passwordreset_confirm.php", array(
                'key' => $key,
                'key_valid' => $user->passwordreset_key_is_valid($key)
            ));
        }

        if ($route->action == 'verify' && $settings['interface']['email_verification'] && isset($_GET['key'])) {
            // On first registration the user will not be logged in
            // a message is returned on the login page with the result of the verification process
            if (!$session['read']) {
                $verify = $user->verify_email(get('key', true));
                return view("Modules/user/login_block.php", array('allowusersregister'=>$allowusersregister, 'verify'=>$verify, 'message'=>'', 'referrer'=>''));

            // If the user is logged in already it means they changed their email and are verifying the new email address
            // in this case we show the profile page with a message about the result of the verification process
            } else if ($session['write']) {
                $verify = $user->verify_email(get('key', true));
                
                if ($verify['success']) {
                    if (isset($verify['userid']) && $verify['userid'] == $session['userid']) {
                        $session['emailverified'] = 1;
                        $_SESSION['emailverified'] = 1;
                    }
                }
                
                return view("Modules/user/profile/profile.php",array());
            }
        }
    }

    // JSON API
    if ($route->format == 'json')
    {
        // Core session
        if ($route->action == 'login' && !$session['read']) return $user->login(post('username'),post('password'),post('rememberme'),post('referrer'));
        if ($route->action == 'register' && $allowusersregister) return $user->register(post('username'),post('password'),post('email'),post('timezone'));
        if ($route->action == 'logout' && $session['read']) {$user->logout();call_hook('on_logout',[]);}

        
        if ($route->action == 'resend-verify' && $settings["interface"]["email_verification"]) {
            if (isset($_GET['username'])) $username = $_GET['username']; else $username = $session["username"];
            return  $user->send_verification_email($username);
        }

        // Step 1: email a one time reset link (non authenticated).
        // POST only: as a GET this was triggerable by URL alone, which made it
        // CSRF-able and put the address in access logs and proxy caches.
        if ($route->action == 'passwordreset' && $route->method == 'POST') {
            return $user->passwordreset(post('username'),post('email'));
        }

        // Step 2: redeem the emailed token and set the new password (non authenticated)
        if ($route->action == 'passwordreset-confirm' && $route->method == 'POST') {
            return $user->passwordreset_confirm(post('key'),post('password'));
        }

        // Returns apikey's from login credentials, required username and password.
        if ($route->action == 'auth' && !$session['read']) return  $user->get_apikeys_from_login(post('username'),post('password'));

        // The end points are safe to use with apikeys

        // Describes the current session so that a client holding an apikey can discover which
        // account the key belongs to and whether it grants write access, without exposing the
        // username, email address or the keys themselves. Used by the sync module to validate a
        // remote apikey, in place of user/get which is restricted to interactive logins below.
        if ($route->action == 'session' && $session['read']) return array(
            'userid' => (int) $session['userid'],
            'type' => $session['write'] ? 'write' : 'read'
        );

        if ($route->action == 'getuuid' && $session['read']) return $user->get_uuid($session['userid']);
        if ($route->action == 'timezone' && $session['read']) return $user->get_timezone_offset($session['userid']); // to maintain compatibility but in seconds
        if ($route->action == 'gettimezone' && $session['read']) return $user->get_timezone($session['userid']);
        if ($route->action == 'gettimezones') return $user->get_timezones();

        // ---------------------------------------------------------------------------------------------------------
        // All actions beyond this point require the user to be logged in with a username and password not an apikey
        // ---------------------------------------------------------------------------------------------------------
        $is_apikey_session = !empty($session['apikey']);

        if ($session['read'] && $is_apikey_session) {
            return array('success'=>false, 'message'=>tr("This action requires an interactive login and cannot be performed with an API key"));
        }
        
        // Change username, email, password
        if ($route->action == 'changeusername' && $session['write']) return  $user->change_username($session['userid'],get('username'));
        if ($route->action == 'changeemail' && $session['write']) return  $user->change_email($session['userid'],get('email'));
        if ($route->action == 'changepassword' && $session['write']) return  $user->change_password($session['userid'],post('old'),post('new'));
        
        // Apikey
        if ($route->action == 'newapikeyread' && $session['write']) return  $user->new_apikey_read($session['userid']);
        if ($route->action == 'newapikeywrite' && $session['write']) return  $user->new_apikey_write($session['userid']);

        // Get and set - user by profile client
        if ($route->action == 'get' && $session['write']) return  $user->get($session['userid']);
        if ($route->action == 'set' && $session['write']) return  $user->set($session['userid'],json_decode(post('data')));

        // Delete all
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
                        $userid = (int) $userid;
                        $query_result = $mysqli->query("SELECT password, salt FROM users WHERE id = '$userid'");
                        $row = $query_result->fetch_object();

                        if (verify_password($_POST['password'], $row->password, $row->salt) || $session['admin']==1) {
                            $result = "PERMANENT DELETE:\n";
                            $result .= delete_user($userid,"permanentdelete");
                            $result .= call_hook('on_delete_user',['userid'=>$userid,'mode'=>'permanentdelete']);
                            
                            $user->logout();
                            call_hook('on_logout',[]);
                            return $result;
                        } else {
                            return "invalid password";
                        }
                    } else {
                        return "missing password field";
                    }
                } else {
                    $result = "DRY RUN:\n";
                    $result .= delete_user($userid,"dryrun");
                    $result .= call_hook('on_delete_user',['userid'=>$userid,'mode'=>'dryrun']);
                    return $result;
                }
            } else {
                return "missing mode field";
            }
        }

        // ---------------------- end of restricted section ---------------------------
    }

    return array('content'=>false);
}
