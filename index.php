<?php

/*

All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

*/

$ltime = microtime(true);

define('EMONCMS_EXEC', 1);

// 1) Load settings and core scripts
require 'process_settings.php';
require 'core.php';
require 'route.php';
require 'locale.php';
require CORE . 'Model' . DS . 'Model.php';

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

$mysqli = ConnectionManager::getDataSource(Configure::read('DB_CONFIG.database'));
if (Configure::read('DB_CONFIG.dbtest') == true) 
{
    require 'Lib/dbschemasetup.php';
    if (!db_check($mysqli, Configure::read('DB_CONFIG.database'))) 
    {
        db_schema_setup($mysqli, load_db_schema(), true);
    }
}

// 3) User sessions
require 'Modules/user/rememberme_model.php';
$rememberme = new Rememberme($mysqli);

require 'Modules/user/user_model.php';
$user = new User(compact('redis', 'rememberme'));

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
        $route->controller = Configure::read('Auth.default_controller');
        $route->action = Configure::read('Auth.default_action');
    } 
    else 
    {
        $route->controller = Configure::read('Auth.default_controller_auth');
        $route->action = Configure::read('Auth.default_action_auth');
    }
}

$route->controller = $route->controller == 'api' ? 'input' : $route->controller;

if ($route->controller == 'input' && ($route->action == 'post' || $route->action == 'bulk')) 
{
    $route->format = 'json';
}

// 6) Load the main page controller
$output = controller($route->controller);

// If no controller of this name - then try username
// need to actually test if there isnt a controller rather than if no content
// is returned from the controller.
if (!$output['content'] && Configure::read('Profile.public_profile_enabled') && $route->controller != 'admin')
{
    $userid = $user->get_id($route->controller);
    if ($userid) 
    {
        $route->subaction = $route->action;
        $session['userid'] = $userid;
        $session['username'] = $route->controller;
        $session['read'] = 1;
        $session['profile'] = 1;
        $route->action = Configure::read('Public.public_profile_action');
        $output = controller(Configure::read('Public.public_profile_controller'));
    }
}

$mysqli = null;

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

// view what sql is going on 
if ($route->format != 'json') {
    pr($user->queryLog());
}