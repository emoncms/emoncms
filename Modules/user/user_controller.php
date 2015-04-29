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
    global $user, $path, $session, $route ,$enable_multi_user;

    $result = false;
    
    $allowusersregister = true;
    
    // Disables further user creation after first admin user is created
    if ($enable_multi_user===false && $user->get_number_of_users()>0) {
        $allowusersregister = false;
    }

    // Load html,css,js pages to the client
    if ($route->format == 'html')
    {
        if ($route->action == 'login' && !$session['read']) $result = view("Modules/user/login_block.php", array('allowusersregister'=>$allowusersregister));
        if ($route->action == 'view' && $session['write']) $result = view("Modules/user/profile/profile.php", array());
        if ($route->action == 'logout' && $session['read']) {$user->logout(); header('Location: '.$path);}
    }

    // JSON API
    if ($route->format == 'json')
    {
        // Core session
        if ($route->action == 'login' && !$session['read']) $result = $user->login(post('username'),post('password'),post('rememberme'));
        if ($route->action == 'register' && $allowusersregister) $result = $user->register(post('username'),post('password'),post('email'));
        if ($route->action == 'logout' && $session['read']) $user->logout();

        if ($route->action == 'changeusername' && $session['write']) $result = $user->change_username($session['userid'],get('username'));
        if ($route->action == 'changeemail' && $session['write']) $result = $user->change_email($session['userid'],get('email'));
        if ($route->action == 'changepassword' && $session['write']) $result = $user->change_password($session['userid'],get('old'),get('new'));
        
        if ($route->action == 'passwordreset') $result = $user->passwordreset(get('username'),get('email'));
        // Apikey
        if ($route->action == 'newapikeyread' && $session['write']) $result = $user->new_apikey_read($session['userid']);
        if ($route->action == 'newapikeywrite' && $session['write']) $result = $user->new_apikey_write($session['userid']);

        if ($route->action == 'auth' && !$session['read']) $result = $user->get_apikeys_from_login(post('username'),post('password'));

        // Get and set - user by profile client
        if ($route->action == 'get' && $session['write']) $result = $user->get($session['userid']);

        if ($route->action == 'set' && $session['write']) $result = $user->set($session['userid'],json_decode(get('data')));

        if ($route->action == 'getconvert' && $session['write']) $result = $user->get_convert_status($session['userid']);
        if ($route->action == 'setconvert' && $session['write']) $result = $user->set_convert_status($session['userid']);


        if ($route->action == 'timezone' && $session['read']) $result = $user->get_timezone($session['userid']);
    }

    return array('content'=>$result);
}
