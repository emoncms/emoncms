<?php
    /*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    */

    use Emoncms\Config\RedisConfig;
    use Emoncms\Redis\Redis;

    $ltime = microtime(true);
    define('EMONCMS_EXEC', 1);

    // 1) Load settings and core scripts
    require "process_settings.php";
    require "core.php";
    require "route.php";
    require "locale.php";

    $emoncms_version = ($feed_settings['redisbuffer']['enabled'] ? "low-write " : "") . "9.7 | 2016.05.25";

    $path = get_application_path();
    require "Lib/EmonLogger.php";


    require "Lib/Config/RedisConfig.php";
    $redisConfig = new RedisConfig(
        $redis_enabled,
        $redis_server['host'],
        $redis_server['port'],
        $redis_server['auth'],
        $redis_server['prefix']
    );

    require "Lib/Redis/Redis.php";
    $emonRedis =  new Redis($redisConfig);
    $redis = false;
    if ($emonRedis->isRedisEnabled()) {
        try {
            $emonRedis->connect();
            $redis = $emonRedis->getRedis();
        } catch (Exception $e) {
            echo $e->getMessage(); die;
        }
    }

    $mqtt = false;

    $mysqli = @new mysqli($server,$username,$password,$database,$port);
    if ( $mysqli->connect_error ) {
        echo "Can't connect to database, please verify credentials/configuration in settings.php<br />";
        if ( $display_errors ) {
            echo "Error message: <b>" . $mysqli->connect_error . "</b>";
        }
        die();
    }
    // Set charset to utf8
    $mysqli->set_charset("utf8");

    if (!$mysqli->connect_error && $dbtest==true) {
        require "Lib/dbschemasetup.php";
        if (!db_check($mysqli,$database)) db_schema_setup($mysqli,load_db_schema(),true);
    }

    // 3) User sessions
    require("Modules/user/user_model.php");
    $user = new User($mysqli, $emonRedis);

    $apikey = false;
    $devicekey = false;
    if (isset($_GET['apikey'])) {
        $apikey = $_GET['apikey'];
    } else if (isset($_POST['apikey'])) {
        $apikey = $_POST['apikey'];
    } else if (isset($_GET['devicekey'])) {
        $devicekey = $_GET['devicekey'];
    } else if (isset($_POST['devicekey'])) {
        $devicekey = $_POST['devicekey'];
    } else if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
        // Support passing apikey on Authorization header per rfc6750, like example:
        //      GET /resource HTTP/1.1
        //      Host: server.example.com
        //      Authorization: Bearer THE_API_KEY_HERE
        $apikey = str_replace('Bearer ', '', $_SERVER["HTTP_AUTHORIZATION"]);
    }

    if ($apikey) {
        $session = $user->apikey_session($apikey);
        if (empty($session)) {
              header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
              header('WWW-Authenticate: Bearer realm="API KEY", error="invalid_apikey", error_description="Invalid API key"');
              print "Invalid API key";
              $log = new EmonLogger(__FILE__);
              $log->error("Invalid API key '" . $apikey. "'");
              exit();
        }
    } else if ($devicekey && (@include "Modules/device/device_model.php")) {
        $device = new Device($mysqli,$redis);
        $session = $device->devicekey_session($devicekey);
        if (empty($session)) {
              header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
              header('WWW-Authenticate: Bearer realm="Device KEY", error="invalid_devicekey", error_description="Invalid device key"');
              print "Invalid device key";
              $log = new EmonLogger(__FILE__);
              $log->error("Invalid device key '" . $devicekey. "'");
              exit();
        }
    } else {
        $session = $user->emon_session_start();
    }

    // Shutdown / Reboot Code Handler
    if (isset($_POST['shutdownPi'])) {
      $shutdownPi = trim($_POST['shutdownPi']);
      $shutdownPi = stripslashes($shutdownPi);
      $shutdownPi = htmlspecialchars($shutdownPi);
    }

    // 4) Language
    if (!isset($session['lang'])) $session['lang']='';
    set_emoncms_lang($session['lang']);

    // 5) Get route and load controller
    $route = new Route(get('q'), server('DOCUMENT_ROOT'), server('REQUEST_METHOD'));

    if (get('embed')==1) $embed = 1; else $embed = 0;

    // If no route specified use defaults
    if ($route->isRouteNotDefined())
    {
        if (!isset($session['read']) || (isset($session['read']) && !$session['read'])) {
            // Non authenticated defaults
            $route->controller = $default_controller;
            $route->action = $default_action;
            $route->subaction = "";
        } else {
            // Authenticated defaults
            $route->controller = $default_controller_auth;
            $route->action = $default_action_auth;
            $route->subaction = "";
        }
    }

    if ($devicekey && !($route->controller == 'input' && ($route->action == 'bulk' || $route->action == 'post'))) {
        header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
        print "Unauthorized. Device key autentication only permits input post or bulk actions";
        $log = new EmonLogger(__FILE__);
        $log->error("Unauthorized. Device key autentication only permits input post or bulk actions");
        exit();
    }

    if ($route->controller == 'input' && $route->action == 'bulk') $route->format = 'json';
    else if ($route->controller == 'input' && $route->action == 'post') $route->format = 'json';

    // 6) Load the main page controller
    $output = controller($route->controller);

    // If no controller of this name - then try username
    // need to actually test if there isnt a controller rather than if no content
    // is returned from the controller.
    if ($output['content'] == "#UNDEFINED#" && $public_profile_enabled && $route->controller!='admin')
    {
        $userid = $user->get_id($route->controller);
        if ($userid) {
            $route->subaction = $route->action;
            $session['userid'] = $userid;
            $session['username'] = $route->controller;
            $session['read'] = 1;
            $session['profile'] = 1;
            $route->controller = $public_profile_controller;
            $route->action = $public_profile_action;
            $output = controller($route->controller);
        }
    }

    // If no controller found or nothing is returned, give friendly error
    if ($output['content'] === "#UNDEFINED#") {
        header($_SERVER["SERVER_PROTOCOL"]." 406 Not Acceptable");
        $output['content'] = "URI not acceptable. No controller '" . $route->controller . "'. (" . $route->action . "/" . $route->subaction .")";
    }

    // If not authenticated and no ouput, asks for login
    if ($output['content'] == "" && (!isset($session['read']) || (isset($session['read']) && !$session['read']))) {
        $route->controller = "user";
        $route->action = "login";
        $route->subaction = "";
        $output = controller($route->controller);
    }

    $output['route'] = $route;
    $output['session'] = $session;

    // 7) Output
    if ($route->format == 'json')
    {
        header('Content-Type: application/json');
        if ($route->controller=='time') {
            print $output['content'];
        } elseif ($route->controller=='input' && $route->action=='post') {
            print $output['content'];
        } elseif ($route->controller=='input' && $route->action=='bulk') {
            print $output['content'];
        } else {
            print json_encode($output['content']);
        }
    }
    else if ($route->format == 'html')
    {
        // Select the theme
        $themeDir = "Theme/" . $theme . "/";
        if ($embed == 1) {
            print view($themeDir . "embed.php", $output);
        } else {
            $menu = load_menu();
            $output['mainmenu'] = view($themeDir . "menu_view.php", array());
            print view($themeDir . "theme.php", $output);
        }
    }
    else if ($route->format == 'text' || $route->format == 'text/plain')
    {
        header('Content-Type: text');
        print $output['content'];
    }
    else {
        header($_SERVER["SERVER_PROTOCOL"]." 406 Not Acceptable");
        print "URI not acceptable. Unknown format '".$route->format."'.";
    }

    $ltime = microtime(true) - $ltime;

    // if ($session['userid']>0) {
    //  $redis->incr("user:postcount:".$session['userid']);
    //  $redis->incrbyfloat("user:reqtime:".$session['userid'],$ltime);
    // }
