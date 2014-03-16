<?php

/*

All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

*/

$emoncms_version = '8.0 pre-release';

$ltime = microtime(true);

define('EMONCMS_EXEC', 1);

// 1) Load settings and core scripts
require 'process_settings.php';
require 'core.php';
require 'route.php';
require 'locale.php';

$path = get_application_path();

// 2) Database
$redis = false;
if (class_exists('Redis') && isset($redis_enabled) && $redis_enabled) 
{
    $redis = new Redis();
    $connected = $redis->connect('127.0.0.1');
    if (!$connected) 
    {
        echo 'Can\'t connect to redis database, it may be that redis-server is not installed or started see readme for redis installation';
        exit;
    }
}

$mysqli = @new mysqli($server, $username, $password, $database);
if ($mysqli->connect_error) 
{
    echo 'Can\'t connect to database, please verify credentials/configuration in settings.php<br />';
    if ($display_errors) 
    {
        echo sprintf('Error message: <b>%s</b>', $mysqli->connect_error);
    }
    exit;
}
elseif ($dbtest == true) 
{
    require 'Lib/dbschemasetup.php';
    if (!db_check($mysqli, $database)) 
    {
        db_schema_setup($mysqli, load_db_schema(), true);
    }
}

// 3) User sessions
require 'Modules/user/rememberme_model.php';
$rememberme = new Rememberme($mysqli);

require 'Modules/user/user_model.php';
$user = new User($mysqli, $redis, $rememberme);

$session = get('apikey') ? $user->apikey_session(get('apikey')) : $user->emon_session_start();

// 4) Language
$session['lang'] = isset($session['lang']) ? $session['lang'] : '';
set_emoncms_lang($session['lang']);

// 5) Get route and load controller
$route = new Route();

$embed = (int)(bool)get('embed');

// If no route specified use defaults
if (!$route->controller && !$route->action) 
{
    if (!$session['read']) 
    {
        $route->controller = $default_controller;
        $route->action = $default_action;
    } 
    else 
    {
        $route->controller = $default_controller_auth;
        $route->action = $default_action_auth;
    }
}

if ($route->controller == 'api') 
{
    $route->controller = 'input';
}
if ($route->controller == 'input' && ($route->action == 'post' || $route->action == 'bulk')) 
{
    $route->format = 'json';
}

// 6) Load the main page controller
$output = controller($route->controller);

// If no controller of this name - then try username
// need to actually test if there isnt a controller rather than if no content
// is returned from the controller.
if (!$output['content'] && $public_profile_enabled && $route->controller!='admin')
{
    $userid = $user->get_id($route->controller);
    if ($userid) 
    {
        $route->subaction = $route->action;
        $session['userid'] = $userid;
        $session['username'] = $route->controller;
        $session['read'] = 1;
        $session['profile'] = 1;
        $route->action = $public_profile_action;
        $output = controller($public_profile_controller);
    }
}

$mysqli->close();

// 7) Output
if ($route->format == 'json') 
{
    if ($route->controller != 'time' && !($route->controller == 'input' && in_array($route->action, array('post', 'bulk')))) 
    {
        $output['content'] = json_encode($output['content']);
    }
    echo $output['content'];
}
if ($route->format == 'html') 
{
    $menu = load_menu();
    $output['mainmenu'] = view('Theme/menu_view.php', array());
    echo $embed ? view('Theme/embed.php', $output) : view('Theme/theme.php', $output);
}

$ltime = microtime(true) - $ltime;
