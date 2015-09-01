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
    require "process_settings.php";
    require "core.php";
    require "route.php";
    require "locale.php";

    $emoncms_version = ($feed_settings['redisbuffer']['enabled'] ? "low-write " : "") . "9 RC | 2015.09.01";

    $path = get_application_path();
    require "Lib/EmonLogger.php";


    // 2) Database
    if ($redis_enabled) {
        $redis = new Redis();
        $connected = $redis->connect($redis_server['host'], $redis_server['port']);
        if (!$connected) { echo "Can't connect to redis at ".$redis_server['host'].":".$redis_server['port']." , it may be that redis-server is not installed or started see readme for redis installation"; die; }
        if (!empty($redis_server['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix']);
        if (!empty($redis_server['auth'])) {
            if (!$redis->auth($redis_server['auth'])) {
                echo "Can't connect to redis at ".$redis_server['host'].", autentication failed"; die;
            }
        }
    } else {
        $redis = false;
    }
    
    $mqtt = false;

    $mysqli = @new mysqli($server,$username,$password,$database);
    if ( $mysqli->connect_error ) {
        echo "Can't connect to database, please verify credentials/configuration in settings.php<br />";
        if ( $display_errors ) {
            echo "Error message: <b>" . $mysqli->connect_error . "</b>";
        }
        die();
    }

    if (!$mysqli->connect_error && $dbtest==true) {
        require "Lib/dbschemasetup.php";
        if (!db_check($mysqli,$database)) db_schema_setup($mysqli,load_db_schema(),true);
    }

    // 3) User sessions
    require("Modules/user/user_model.php");
    $user = new User($mysqli,$redis);

    $apikey = false;
    if (isset($_GET['apikey'])) {
        $apikey = $_GET['apikey'];
    } else if (isset($_POST['apikey'])) {
        $apikey = $_POST['apikey'];
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
              exit();
        }
    } else {
        $session = $user->emon_session_start();
    }

    // 4) Language
    if (!isset($session['lang'])) $session['lang']='';
    set_emoncms_lang($session['lang']);

    // 5) Get route and load controller
    $route = new Route(get('q'));

    if (get('embed')==1) $embed = 1; else $embed = 0;

    // If no route specified use defaults
    if (!$route->controller && !$route->action)
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
        if ($embed == 1) {
            print view("Theme/embed.php", $output);
        } else {
            $menu = load_menu();
            $output['mainmenu'] = view("Theme/menu_view.php", array());
            print view("Theme/theme.php", $output);
        }
    }
    else if ($route->format == 'text')
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
