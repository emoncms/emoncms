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
                $decoded_ref = urldecode(base64_decode($ref));
                $referrer = filter_var($decoded_ref, FILTER_VALIDATE_URL) ? htmlentities($decoded_ref) : '';
            } else {
                $referrer="";
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
        if ($route->action == 'view' && $session['write']) return view("Modules/user/profile/profile.php", array());
          
        if ($route->action == 'logout') {
            // decode url parameters
            $next = $path;
            
            $msg = get('msg');
            $message = isset($msg) ? htmlspecialchars(urldecode($msg)) : '';
            $ref = get('ref');
            $referrer = isset($ref) ? htmlspecialchars(urldecode(base64_decode(get('ref')))) : '';
            
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
        
        if ($route->action == 'verify' && $settings["interface"]["email_verification"] && !$session['read'] && isset($_GET['key'])) { 
            $verify = $user->verify_email($_GET['email'], $_GET['key']);
            return view("Modules/user/login_block.php", array('allowusersregister'=>$allowusersregister,'verify'=>$verify));
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
        if ($route->action == 'getuuid' && $session['read']) return $user->get_uuid($session['userid']);
        if ($route->action == 'changeusername' && $session['write']) return  $user->change_username($session['userid'],get('username'));
        if ($route->action == 'changeemail' && $session['write']) return  $user->change_email($session['userid'],get('email'));
        if ($route->action == 'changepassword' && $session['write']) return  $user->change_password($session['userid'],post('old'),post('new'));
        
        if ($route->action == 'passwordreset') return  $user->passwordreset(get('username'),get('email'));
        // Apikey
        if ($route->action == 'newapikeyread' && $session['write']) return  $user->new_apikey_read($session['userid']);
        if ($route->action == 'newapikeywrite' && $session['write']) return  $user->new_apikey_write($session['userid']);

        if ($route->action == 'auth' && !$session['read']) return  $user->get_apikeys_from_login(post('username'),post('password'));

        // Get and set - user by profile client
        if ($route->action == 'get' && $session['write']) return  $user->get($session['userid']);
        if ($route->action == 'set' && $session['write']) return  $user->set($session['userid'],json_decode(post('data')));

        if ($route->action == 'timezone' && $session['read']) return $user->get_timezone_offset($session['userid']); // to maintain compatibility but in seconds
        if ($route->action == 'gettimezone' && $session['read']) return $user->get_timezone($session['userid']);
        if ($route->action == 'gettimezones') return $user->get_timezones();
           
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
                        $query_result = $mysqli->query("SELECT password, salt FROM users WHERE id = '$userid'");
                        $row = $query_result->fetch_object();
                        $hash = hash('sha256', $row->salt . hash('sha256', $_POST['password']));

                        if ($hash == $row->password || $session['admin']==1) {
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
    }

    return array('content'=>false);
}
